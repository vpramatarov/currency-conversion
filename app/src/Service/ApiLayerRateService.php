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

    private const API_URL = 'https://api.apilayer.com/exchangerates_data/timeseries';

    private const PROVIDER = 'RATE.APILAYER';

    private const TTL = 3600; // seconds in hour

    private string $apiKey;

    private HttpClientInterface $httpClient;

    private CacheInterface $cache;

    private ApiLayerCurrencyService $apiLayerCurrencyService;

    private HelperService $helperService;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $apiKey
     * @param CacheInterface $cache
     * @param ApiLayerCurrencyService $apiLayerCurrencyService
     * @param HelperService $helperService
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $apiKey,
        CacheInterface $cache,
        ApiLayerCurrencyService $apiLayerCurrencyService,
        HelperService $helperService
    )
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->cache = $cache;
        $this->apiLayerCurrencyService = $apiLayerCurrencyService;
        $this->helperService = $helperService;
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
        $ratesData = $this->validateData($id);

        if (!$ratesData) {
            return null;
        }

        return $this->createRateObject($ratesData);
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

        // callback is called on cache miss only.
        return $this->cache->get($key, function (ItemInterface $item) use ($currencies) {
            $item->expiresAfter(self::TTL);

            $id = implode('_', $currencies);
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
                        'symbols' => $currencies[1],
                        'base' => $currencies[0]
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            $data = $response->getContent();

            if ($statusCode !== 200 || !$data) {
                return [];
            }

            $data = json_decode($data, true);
            $ratesData = $data['rates'] ?? [];

            $pairKey = $currencies[1];
            $endDate = (new \DateTime('now'))->format('Y-m-d');
            $todayRate = $ratesData[$endDate] ?? [];
            $todayExchangeRate = (float) sprintf('%.3f', $todayRate[$pairKey] ?? 0);

            if ($todayRate && $todayExchangeRate) {
                $todayRate['pair'] = str_replace('_', '', $id);
                $todayRate['base'] = $currencies[0];
                $todayRate['target'] = $currencies[1];
                $calculateTrendData = array_column($ratesData, $pairKey);
                $todayRate['suffix'] = $this->helperService->calculateTrend($calculateTrendData, $todayExchangeRate);
                $todayRate['exchangeRate'] = $todayExchangeRate;

                return $todayRate;
            }

            return [];
        });
    }

    /**
     * @param array $ratesData
     * @return Rate
     */
    private function createRateObject(array $ratesData): Rate
    {
        $rate = new Rate();
        $rate->pair = $ratesData['pair'];
        $rate->provider = self::PROVIDER;
        $rate->base = $ratesData['base'];
        $rate->target = $ratesData['target'];
        $rate->exchangeRate = $ratesData['exchangeRate'];
        $rate->suffix = $ratesData['suffix'];

        return $rate;
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

    /**
     * @param string $provider
     * @return bool
     */
    public function supports(string $provider): bool
    {
        return strtoupper($provider) === self::PROVIDER;
    }
}