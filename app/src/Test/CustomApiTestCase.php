<?php

declare(strict_types=1);

namespace App\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Psr\Cache\CacheItemPoolInterface;

class CustomApiTestCase extends ApiTestCase
{
    protected function getCacheService(): CacheItemPoolInterface
    {
        return self::getContainer()->get('cache.app');
    }
}
