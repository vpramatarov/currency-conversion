<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Contracts\FetchInterface;
use App\Entity\Currency;

class CurrencyDataProvider implements ItemDataProviderInterface, ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var FetchInterface[]
     */
    private iterable $fetchServices;


    public function __construct(iterable $fetchServices)
    {
        foreach ($fetchServices as $services) {
            foreach ($services as $service) {
                $this->fetchServices[] = $service;
            }
        }
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
        $provider = $context['_provider'] ?? '';

        if (!empty($provider)) {
            foreach($this->fetchServices as $fetchService) {
                if ($fetchService->supports($provider)) {
                    return $fetchService->fetchOne($id);
                }
            }
        }

        return null;
    }

    /**
     * @param string $resourceClass
     * @param string|null $operationName
     * @param array $context
     * @return iterable
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $provider = $context['_provider'] ?? '';

        if (!empty($provider)) {
            foreach($this->fetchServices as $fetchService) {
                if ($fetchService->supports($provider)) {
                    return $fetchService->fetchMany();
                }
            }
        }

        return [];
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