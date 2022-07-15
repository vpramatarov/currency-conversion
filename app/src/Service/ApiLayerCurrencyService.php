<?php

namespace App\Service;

use App\Contracts\FetchInterface;
use App\Entity\Currency;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;


class ApiLayerCurrencyService implements FetchInterface
{
    private const API_URL = 'https://api.apilayer.com/exchangerates_data/symbols';

    private const PROVIDER = 'CURRENCY.APILAYER';

    private const TTL = 86400; // seconds in day

    private string $apiKey;

    private HttpClientInterface $httpClient;

    private CacheInterface $cache;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $apiKey
     * @param CacheInterface $cache
     */
    public function __construct(HttpClientInterface $httpClient, string $apiKey, CacheInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->cache = $cache;
    }

    /**
     * @param string $id
     * @return Currency|null
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function fetchOne($id): ?Currency
    {
        $data = $this->fetchData();

        $currency = $data[$id] ?? null;

        if ($currency) {
            return $this->createCurrencyObject($id, $currency);
        }

        return null;
    }

    /**
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function fetchMany(): array
    {
        $currencies = [];
        $data = $this->fetchData();

        foreach ($data as $symbol => $name) {
            $currencies[] = $this->createCurrencyObject($symbol, $name);
        }

        return $currencies;
    }

    /**
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function fetchData(): array
    {
        $key = 'apilayer.currencies';

        // The callable will only be executed on a cache miss.
        $value = $this->cache->get($key, function (ItemInterface $item) {

            $item->expiresAfter(self::TTL);

            // ... do some HTTP request or heavy computations
            $response = $this->httpClient->request(
                'GET',
                self::API_URL,
                [
                    'headers' => [
                        'Content-Type' => 'text/plain',
                        'Accept' => 'application/json',
                        'apikey' => $this->apiKey
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

        return $data['symbols'] ?? [];
    }

    /**
     * @param string $symbol
     * @param string $name
     * @return Currency
     */
    public function createCurrencyObject(string $symbol, string $name): Currency
    {
        return new Currency($symbol, self::PROVIDER, $name);
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