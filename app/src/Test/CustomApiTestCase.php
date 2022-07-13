<?php

namespace App\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Contracts\Cache\CacheInterface;

class CustomApiTestCase extends ApiTestCase
{

    protected function getCacheService()
    {
        return self::getContainer()->get(CacheInterface::class);
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invoke(string $className, string $methodName, array $args = [])
    {
        $mockedInstance = $this->getMockBuilder($className)
                               ->disableOriginalConstructor()    // you may need the constructor on integration tests only
                               ->getMock();

        $privateMethod = $this->getMethod($className, $methodName);

        return $privateMethod->invokeArgs($mockedInstance, $args);
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    private function getMethod(string $className, string $methodName): \ReflectionMethod
    {
        $class = new \ReflectionClass($className);

        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

}