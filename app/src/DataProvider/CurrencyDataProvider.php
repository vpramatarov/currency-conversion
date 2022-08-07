<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Contracts\FetchInterface;
use App\Entity\Currency;

class CurrencyDataProvider implements ItemDataProviderInterface, ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private FetchInterface $fetchService;

    public function __construct(FetchInterface $fetchService)
    {
        $this->fetchService = $fetchService;
    }

    /**
     * @param string      $resourceClass
     * @param int|string  $id
     * @param string|null $operationName
     * @param mixed[]     $context
     *
     * @return Currency|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Currency
    {
        return $this->fetchService->fetchOne($id);
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     * @param mixed[]     $context
     *
     * @return mixed[]
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        return $this->fetchService->fetchMany();
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     * @param mixed[]     $context
     *
     * @return bool
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $resourceClass === Currency::class;
    }
}
