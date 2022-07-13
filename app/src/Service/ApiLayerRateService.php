<?php

namespace App\Service;


use ApiPlatform\Core\Validator\Exception\ValidationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Contracts\FetchItemInterface;
use App\Entity\Rate;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;


class ApiLayerRateService implements FetchItemInterface
{
    private const API_URL = 'https://api.apilayer.com/currency_data/timeframe';

    private const TTL = 3600; // seconds in hour

    private string $apiKey;

    private HttpClientInterface $httpClient;

    private CacheInterface $cache;

    private ApiLayerCurrencyService $apiLayerCurrencyService;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $apiKey
     * @param CacheInterface $cache
     * @param ApiLayerCurrencyService $apiLayerCurrencyService
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $apiKey,
        CacheInterface $cache,
        ApiLayerCurrencyService $apiLayerCurrencyService
    )
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->cache = $cache;
        $this->apiLayerCurrencyService = $apiLayerCurrencyService;
    }

    /**
     * @param string $id
     * @return Rate|null
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws ValidationException
     */
    public function fetchOne($id): ?Rate
    {
        $currencies = explode('_', $id);
        $pairKey = implode('', $currencies);
        $ratesData = $this->validateData($id);
        $endDate = (new \DateTime('now'))->format('Y-m-d');
        $todayRate = $ratesData[$endDate] ?? [];
        $todayExchangeRate = $todayRate[$pairKey];

        if ($todayRate && $todayExchangeRate) {
            $todayRate['currencies'] = $currencies;
            $calculateTrendData = array_column($ratesData, $pairKey);
            $todayRate['suffix'] = $this->calculateTrend($calculateTrendData, $todayExchangeRate);

            return $this->createRateObject($id, $todayRate);
        }

        return null;
    }

    /**
     * @param array $currencies
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function fetchData(array $currencies): array
    {
        $key = sprintf('apilayer.rate.%s', implode('', $currencies));

        $value = $this->cache->get($key, function (ItemInterface $item) use ($currencies) {
            $item->expiresAfter(self::TTL);
            $endDate = (new \DateTime('now'))->format('Y-m-d');
            $startDate = (new \DateTime('now'))->modify('-9 days')->format('Y-m-d');

            $response = $this->httpClient->request(
                'GET',
                self::API_URL,
                [
                    'headers' => [
                        'Content-Type' => 'text/plain',
                        'Accept' => 'application/json',
                        'apikey' => $this->apiKey
                    ],
                    'query' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'currencies' => implode(',', $currencies),
                        'source' => $currencies[0]
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            $data = $response->getContent();

            if ($statusCode !== 200 || !$data) {
                return '';
            }

            return $data;
        });

        $data = json_decode($value, true);

        return $data['quotes'] ?? [];
    }

    /**
     * @param string $pair
     * @param array $ratesData
     * @return Rate
     */
    private function createRateObject(string $pair, array $ratesData): Rate
    {
        $pairKey = str_replace('_', '', $pair);

        $rate = new Rate();
        $rate->pair = $pair;
        $rate->base = $ratesData['currencies'][0];
        $rate->target = $ratesData['currencies'][1];
        $rate->exchangeRate = (float) sprintf('%.3f', $ratesData[$pairKey]);
        $rate->suffix = $ratesData['suffix'];

        return $rate;
    }

    /**
     * Calculate average in array
     *
     * @param array $data
     * @return float
     */
    private function arrayAverage(array $data): float
    {
        $data = array_filter($data, 'is_numeric'); // filter out non-numeric values
        $avg = array_sum($data) / count($data);

        return (float) sprintf('%.3f', $avg);
    }

    /**
     * @param array $data
     * @param float $todayExchangeRate
     * @return string
     */
    private function calculateTrend(array $data, float $todayExchangeRate): string
    {
        $avg = $this->arrayAverage($data);

        $trendSign = '-';

        if ($avg > $todayExchangeRate) {
            $trendSign = '↑';
        } else if ($avg < $todayExchangeRate) {
            $trendSign = '↓';
        }

        return $trendSign;
    }

    /**
     * @param string $id
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws ValidationException
     */
    private function validateData(string $id): array
    {
        $currencies = explode('_', $id);

        if (!isset($currencies[1])) {
            throw new ValidationException('Please provide 2 Currency codes separated by underscore. Ex.: CAD_CHF');
        }

        $availableCurrencies = $this->apiLayerCurrencyService->fetchData();

        $diff = array_diff_key(array_flip($currencies), $availableCurrencies);
        $validateCurrencies = count($diff) === 0;

        if (!$validateCurrencies) {
            $msg = sprintf(
                "Please provide valid Currencies. %s %s not a valid %s.",
                implode(' AND ', array_flip($diff)),
                count($diff) === 1 ? 'is' : 'are',
                count($diff) === 1 ? 'Currency' : 'Currencies'
            );

            throw new ValidationException($msg);
        }

        return $this->fetchData($currencies);
    }

}