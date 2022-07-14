<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Contracts\FetchItemInterface;
use App\Entity\Rate;

class RateDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{

    /**
     * @var FetchItemInterface[]
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
     * @return Rate|null
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Rate
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
     * @return bool
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $resourceClass === Rate::class;
    }

}