<?php

declare(strict_types=1);


namespace App\Tests\Functional;


use App\Entity\Rate;
use App\Service\CurrencyService;
use App\Service\RateService;
use App\Service\HelperService;
use App\Test\CustomApiTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


class RateServiceTest extends CustomApiTestCase
{

    private \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @group time-sensitive
     */
    public function testCacheIsWorking()
    {
        $keys = [
            'test.apilayer.currencies' => [
                'json' => file_get_contents(__DIR__ . '/symbols.json'),
                'ttl' => 3600 // seconds in hour
            ],
            'test.apilayer.rate.CADCHF' => [
                'json' => file_get_contents(__DIR__ . '/timeframe.json'),
                'ttl' => 86400 // seconds in day
            ]
        ];

        $cache = $this->getCacheService();

        foreach ($keys as $key => $data)
        {
            // delete cache data if exist
            $cache->deleteItem($key);

            $json = $data['json'] ?? '';
            $ttl = $data['ttl'] ?? 3600;

            $cacheItem = $cache->getItem($key);

            if (!$cacheItem->isHit()) {
                $cacheItem->expiresAfter($ttl);
                $cache->save($cacheItem->set($json));
            }

            $cacheItem2 = $cache->getItem($key);

            $this->assertNotEquals($cacheItem->isHit(), $cacheItem2->isHit());
        }
    }


    public function testGetRateRequest()
    {
        $this->client->request('GET', '/api/rates/CAD_CHF');
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['base' => 'CAD', 'target' => 'CHF']);
    }


    public function testGetRateMalformedRequest()
    {
        $this->client->request('GET', '/api/rates/CA_CHF');
        $this->assertResponseStatusCodeSame(500);
        $this->assertJsonContains(['hydra:description' => 'Please provide valid Currencies. CA is not a valid Currency.']);
    }


    public function testRateNotFound()
    {
        $this->client->request('GET', '/api/rates/ASD_CHF');
        $this->assertResponseStatusCodeSame(500);
        $this->assertJsonContains(['hydra:description' => 'Please provide valid Currencies. ASD is not a valid Currency.']);
    }

    /**
     * Get fake rates data
     *
     * @return array
     */
    private function getCurrencyData(): array
    {
        $currencyData = json_decode(file_get_contents(__DIR__.'/symbols.json'), true);
        return $currencyData['symbols'] ?? [];
    }
}
