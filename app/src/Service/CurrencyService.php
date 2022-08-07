<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\FetchInterface;
use App\Entity\Currency;
use Psr\Cache\CacheItemPoolInterface;

class CurrencyService implements FetchInterface
{
    private const ENDPOINT = 'symbols';

    private const TTL = 86400; // seconds in day

    private CacheItemPoolInterface $cache;

    private ApiService $apiService;

    /**
     * @param CacheItemPoolInterface $cache
     * @param ApiService             $apiService
     */
    public function __construct(CacheItemPoolInterface $cache, ApiService $apiService)
    {
        $this->cache = $cache;
        $this->apiService = $apiService;
    }

    /**
     * @param string $id
     *
     * @return Currency|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchOne($id): ?Currency
    {
        $data = $this->fetchCurrencies();

        $currency = $data[$id] ?? null;

        if ($currency) {
            return $this->createCurrencyObject($id, $currency);
        }

        return null;
    }

    /**
     * @return array<int, Currency>
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchMany(): array
    {
        $currencies = [];
        $data = $this->fetchCurrencies();

        foreach ($data as $symbol => $name) {
            $currencies[] = $this->createCurrencyObject($symbol, $name);
        }

        return $currencies;
    }

    /**
     * @return mixed[]
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchCurrencies(): array
    {
        $value = $this->cache->getItem('apilayer.currencies');

        if ($value->isHit()) {
            return $value->get();
        }

        if ($data = $this->apiService->fetchCurrencies(self::ENDPOINT)) {
            $value->expiresAfter(self::TTL);
            $this->cache->save($value->set($data));

            return $value->get();
        }

        return [];
    }

    /**
     * @param string $symbol
     * @param string $name
     *
     * @return Currency
     */
    public function createCurrencyObject(string $symbol, string $name): Currency
    {
        return new Currency($symbol, $name);
    }
}
