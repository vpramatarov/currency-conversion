<?php

declare(strict_types=1);


namespace App\Service;


use ApiPlatform\Core\Validator\Exception\ValidationException;
use App\Contracts\FetchItemInterface;
use App\Entity\Rate;
use Psr\Cache\CacheItemPoolInterface;


class RateService implements FetchItemInterface
{

    private const ENDPOINT = 'timeseries';
    /** @todo: return old value (3600) */
    private const TTL = 86400; //3600; // seconds in hour

    private CacheItemPoolInterface $cache;

    private CurrencyService $currencyService;

    private ApiService $apiService;

    /**
     * @param CacheItemPoolInterface $cache
     * @param ApiService $apiService
     * @param CurrencyService $apiLayerCurrencyService
     */
    public function __construct(
        CacheItemPoolInterface $cache,
        ApiService $apiService,
        CurrencyService $apiLayerCurrencyService
    ) {
        $this->cache = $cache;
        $this->apiService = $apiService;
        $this->currencyService = $apiLayerCurrencyService;
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

        if ($data = $this->apiService->fetchCurrencyPair($currencies, self::ENDPOINT)) {
            $value->expiresAfter(self::TTL);
            $this->cache->save($value->set($data));
            return $value->get();
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

        $availableCurrencies = $this->currencyService->fetchCurrencies();

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
