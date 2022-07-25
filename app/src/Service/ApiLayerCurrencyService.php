<?php

declare(strict_types=1);


namespace App\Service;


use App\Contracts\FetchInterface;
use App\Entity\Currency;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Cache\CacheItemPoolInterface;


class ApiLayerCurrencyService implements FetchInterface
{
    private const ENDPOINT = 'symbols';

    private const TTL = 86400; // seconds in day

    private string $apiKey;

    private string $apiUrl;

    private HttpClientInterface $httpClient;

    private CacheItemPoolInterface $cache;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $apiKey
     * @param string $apiUrl
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(HttpClientInterface $httpClient, string $apiKey, string $apiUrl, CacheItemPoolInterface $cache)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
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
    public function fetchCurrencies(): array
    {
        $value = $this->cache->getItem('apilayer.currencies');

        if ($value->isHit()) {
            return $value->get();
        }

        if ($data = $this->fetchData()) {
            $value->expiresAfter(self::TTL);
            $this->cache->save($value->set($data));
            return $value->get();
        }

        return [];
    }

    /**
     * @param string $symbol
     * @param string $name
     * @return Currency
     */
    public function createCurrencyObject(string $symbol, string $name): Currency
    {
        return new Currency($symbol, $name);
    }

    /**
     * Fetch data from API
     *
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function fetchData(): array
    {
        $apiEndpoint = sprintf('%s%s', $this->apiUrl, self::ENDPOINT);

        $response = $this->httpClient->request(
            'GET',
            $apiEndpoint,
            [
                'headers' => [
                    'Content-Type' => 'text/plain',
                    'Accept' => 'application/json',
                    'apikey' => $this->apiKey
                ]
            ]
        );

        $data = json_decode($response->getContent(), true);

        return $data['symbols'] ?? [];
    }
}
