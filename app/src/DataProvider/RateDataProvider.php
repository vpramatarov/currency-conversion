<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Contracts\FetchItemInterface;
use App\Entity\Rate;

class RateDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private FetchItemInterface $fetchService;

    public function __construct(FetchItemInterface $fetchService)
    {
        $this->fetchService = $fetchService;
    }

    /**
     * @param string      $resourceClass
     * @param string|int  $id
     * @param string|null $operationName
     * @param mixed[]     $context
     *
     * @return Rate|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Rate
    {
        return $this->fetchService->fetchOne($id);
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
        return $resourceClass === Rate::class;
    }
}
