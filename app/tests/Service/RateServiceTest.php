<?php

declare(strict_types=1);


namespace App\Tests\Service;


use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use App\Test\CustomApiTestCase;


class RateServiceTest extends CustomApiTestCase
{

    private Client $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->regenerateTimeframeFile();
    }

    /**
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testGetRateRequest()
    {
        $this->client->request('GET', '/api/rates/CAD_CHF');
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['@context' => '/api/contexts/Rate', 'base' => 'CAD', 'target' => 'CHF']);
    }

    /**
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testGetRateRequestCache()
    {
        $cache = $this->getCacheService();
        $key = 'apilayer.rate.CADCHF';
        $cache->deleteItem($key);

        $cacheItem = $cache->getItem($key);
        $this->assertFalse($cacheItem->isHit());

        $this->client->request('GET', '/api/rates/CAD_CHF');
        $this->assertResponseStatusCodeSame(200);
        $cacheItem2 = $cache->getItem($key);
        $this->assertTrue($cacheItem2->isHit());

        $cacheItem3 = $cache->getItem($key);
        $this->assertTrue($cacheItem3->isHit());
        $cache->deleteItem($key);
    }

    /**
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testGetRateMalformedRequest()
    {
        $this->client->request('GET', '/api/rates/CA_CHF');
        $this->assertResponseStatusCodeSame(500);
        $this->assertJsonContains(['hydra:description' => 'Please provide valid Currencies. CA is not a valid Currency.']);
    }

    /**
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testRateNotFound()
    {
        $this->client->request('GET', '/api/rates/ASD_CHF');
        $this->assertResponseStatusCodeSame(500);
        $this->assertJsonContains(['hydra:description' => 'Please provide valid Currencies. ASD is not a valid Currency.']);
    }

    /**
     * Generate an array of string dates between 2 dates
     *
     * @param string $start Start date
     * @param string $end End date
     * @param string $format Output format (Default: Y-m-d)
     * @return array<int, string>
     * @throws \Exception
     */
    private function generateDatesFromRange(string $start, string $end, string $format = 'Y-m-d'): array
    {
        $array = [];
        $interval = new \DateInterval('P1D');

        $realEnd = new \DateTime($end);
        $realEnd->add($interval);

        $period = new \DatePeriod(new \DateTime($start), $interval, $realEnd);

        foreach($period as $date) {
            $array[] = $date->format($format);
        }

        return $array;
    }

    /**
     * Need to regenerate file with test data, because the dates do not match.
     *
     * @return void
     * @throws \Exception
     */
    private function regenerateTimeframeFile()
    {
        $file = file_get_contents(__DIR__ . '/timeframe.json');
        $data = json_decode($file, true);
        $oldDates = array_keys($data['rates']);
        $endDate = (new \DateTime('now'))->format('Y-m-d');
        $startDate = (new \DateTime('now'))->modify('-9 days')->format('Y-m-d');
        $rangeDates = $this->generateDatesFromRange($startDate, $endDate);
        $dates = array_combine($oldDates, $rangeDates);
        $lastDate = $rangeDates[array_key_last($rangeDates)];
        $firstDate = $rangeDates[array_key_first($rangeDates)];

        $newData = [];
        $newData['base'] = $data['base'];
        $newData['success'] = $data['success'];
        $newData['timeseries'] = $data['timeseries'];
        $newData['end_date'] = $lastDate;
        $newData['start_date'] = $firstDate;
        $newData['rates'] = [];

        foreach ($data['rates'] as $key => $rate) {
            $newData['rates'][$dates[$key]] = $rate;
        }

        // override data
        file_put_contents(__DIR__ . '/timeframe.json', json_encode($newData));
    }
}
