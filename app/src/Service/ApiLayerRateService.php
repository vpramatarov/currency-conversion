<?php

declare(strict_types=1);


namespace App\Service;


use ApiPlatform\Core\Validator\Exception\ValidationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Contracts\FetchItemInterface;
use App\Entity\Rate;
use Psr\Cache\CacheItemPoolInterface;


class ApiLayerRateService implements FetchItemInterface
{

    private const ENDPOINT = 'timeseries';

    private const TTL = 3600; // seconds in hour

    private string $apiKey;

    private string $apiUrl;

    private HttpClientInterface $httpClient;

    private CacheItemPoolInterface $cache;

    private ApiLayerCurrencyService $apiLayerCurrencyService;

    private HelperService $helperService;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $apiKey
     * @param string $apiUrl
     * @param CacheItemPoolInterface $cache
     * @param ApiLayerCurrencyService $apiLayerCurrencyService
     * @param HelperService $helperService
     */
    public function __construct(
        HttpClientInterface $httpClient,
        string $apiKey,
        string $apiUrl,
        CacheItemPoolInterface $cache,
        ApiLayerCurrencyService $apiLayerCurrencyService,
        HelperService $helperService
    )
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
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
        $this->validateData($id);
        $currencies = explode('_', $id);
        $ratesData = $this->fetchData($currencies);

        if (!$ratesData) {
            return null;
        }

        return $this->createRateObject($ratesData);
    }

    /**
     * Get data from cache if exists.
     * If data does not exist in cache, it's saved and returned.
     * Empty array is returned if data could not be retrieved.
     *
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

        $value = $this->cache->getItem($key);

        if ($value->isHit()) {
            return $value->get();
        }

        if ($data = $this->fetchCurrencyPair($currencies)) {
            $value->expiresAfter(self::TTL);
            $this->cache->save($value->set($data));
            return $value->get();
        }

        return [];
    }

    /**
     * @param array $currencies
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function fetchCurrencyPair(array $currencies): array
    {
        $apiEndpoint = sprintf('%s%s', $this->apiUrl, self::ENDPOINT);
        $endDate = (new \DateTime('now'))->format('Y-m-d');
        $startDate = (new \DateTime('now'))->modify('-9 days')->format('Y-m-d');

        $response = $this->httpClient->request(
            'GET',
            $apiEndpoint,
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

        $data = json_decode($response->getContent(), true);
        $ratesData = $data['rates'] ?? [];

        $pairKey = $currencies[1];
        $endDate = (new \DateTime('now'))->format('Y-m-d');
        $todayRate = $ratesData[$endDate] ?? [];
        $todayExchangeRate = (float) sprintf('%.3f', $todayRate[$pairKey] ?? 0);

        if ($todayRate && ($todayExchangeRate > 0)) {
            $todayRate['pair'] = implode('', $currencies);
            $todayRate['base'] = $currencies[0];
            $todayRate['target'] = $currencies[1];
            $calculateTrendData = array_column($ratesData, $pairKey);
            $todayRate['suffix'] = $this->helperService->calculateTrend($calculateTrendData, $todayExchangeRate);
            $todayRate['exchangeRate'] = $todayExchangeRate;

            return $todayRate;
        }

        return [];
    }

    /**
     * @param array $ratesData
     * @return Rate
     */
    private function createRateObject(array $ratesData): Rate
    {
        $rate = new Rate();
        $rate->pair = $ratesData['pair'];
        $rate->base = $ratesData['base'];
        $rate->target = $ratesData['target'];
        $rate->exchangeRate = $ratesData['exchangeRate'];
        $rate->suffix = $ratesData['suffix'];

        return $rate;
    }

    /**
     * @param string $id
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws ValidationException
     */
    private function validateData(string $id): void
    {
        $currencies = explode('_', $id);

        if (!isset($currencies[1])) {
            throw new ValidationException('Please provide 2 Currency codes separated by underscore. Ex.: CAD_CHF');
        }

        $availableCurrencies = $this->apiLayerCurrencyService->fetchCurrencies();

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
    }
}
