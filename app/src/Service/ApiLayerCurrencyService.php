<?php

namespace App\Service;

use App\Contracts\FetchInterface;
use App\Entity\Currency;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use \Redis;


class ApiLayerCurrencyService implements FetchInterface
{
    private const API_URL = 'https://api.apilayer.com/currency_data/list';

    private const TTL = 86400; // seconds in day

    private string $apiKey;

    private HttpClientInterface $httpClient;

    private Redis $redis;

    /**
     * @param HttpClientInterface $httpClient
     * @param string $apiKey
     * @param Redis $redis
     */
    public function __construct(HttpClientInterface $httpClient, string $apiKey, Redis $redis)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->redis = $redis;
    }

    /**
     * @param string $id
     * @return Currency|null
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
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
     */
    public function fetchData(): array
    {
        $key = 'apilayer.currencies';

        $data = $this->redis->get($key);

        if (!$data) {
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
                return [];
            }

            $this->redis->setex($key, self::TTL, $data); // cache the results
        }

        $data = json_decode($data, true);

        return $data['currencies'] ?? [];
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

}