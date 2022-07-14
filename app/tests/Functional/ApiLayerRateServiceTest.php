<?php

namespace App\Tests\Functional;

use App\Test\CustomApiTestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ApiLayerRateServiceTest extends CustomApiTestCase
{

    public function testCacheIsWorking()
    {
        self::bootKernel(); // bootstrap the container

        $keys = [
            'test.apilayer.currencies' => [
                'json' => file_get_contents(__DIR__.'/symbols.json'),
                'ttl' => 3600 // seconds in hour
            ],
            'test.apilayer.rate.CADCHF' => [
                'json' => file_get_contents(__DIR__.'/timeframe.json'),
                'ttl' => 86400 // seconds in day
            ]
        ];

        /**
         * @var $cache CacheInterface
         */
        $cache = $this->getCacheService();

        foreach ($keys as $key => $data) {
            // delete cache data if exist
            $cache->delete($key);

            $json = $data['json'] ?? '';
            $ttl = $data['ttl'] ?? 3600;

            // set cache
            $cacheItem = $cache->get($key, function (ItemInterface $item) use ($json, $ttl)  {
                $item->expiresAfter($ttl);
                return $json; // example response
            });

            sleep(10);

            /**
             * @note:
             * $cacheItem2 should return already stored json in $cacheItem if $key exist in cache.
             * Callback function is executed only when cache misses.
             */
            $cacheItem2 = $cache->get($key, function () {
                return '';
            });

            $this->assertJsonStringEqualsJsonString($cacheItem, $cacheItem2);
        }
    }

}