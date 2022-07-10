<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Contracts\FetchInterface;
use App\Entity\Currency;

class CurrencyDataProvider implements ItemDataProviderInterface, CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private FetchInterface $fetchService;

    /**
     * @param FetchInterface $fetchService
     */
    public function __construct(FetchInterface $fetchService)
    {
        $this->fetchService = $fetchService;
    }

    /**
     * @param string $resourceClass
     * @param $id
     * @param string|null $operationName
     * @param array $context
     * @return Currency|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Currency
    {
        return $this->fetchService->fetchOne($id);
    }

    /**
     * @param string $resourceClass
     * @param string|null $operationName
     * @return iterable
     */
    public function getCollection(string $resourceClass, string $operationName = null): iterable
    {
        return $this->fetchService->fetchMany();
    }

    /**
     * @param string $resourceClass
     * @param string|null $operationName
     * @param array $context
     * @return bool
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $resourceClass === Currency::class;
    }

}