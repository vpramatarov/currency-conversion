<?php

declare(strict_types=1);


namespace App\Test;


use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;


class CustomApiTestCase extends ApiTestCase
{

    protected function getCacheService(): CacheItemPoolInterface
    {
        return self::getContainer()->get('cache.app');
    }

    protected function getMock(string $className): MockObject
    {
        return $this->getMockBuilder($className)
             ->disableOriginalConstructor()    // you may need the constructor on integration tests only
             ->getMock();
    }
}
