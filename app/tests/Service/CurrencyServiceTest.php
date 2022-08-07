<?php

declare(strict_types=1);

namespace App\Tests\Service;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use App\Test\CustomApiTestCase;

class CurrencyServiceTest extends CustomApiTestCase
{
    private Client $client;

    public function setUp(): void
    {
        $this->client = self::createClient();
    }

    /**
     * @return void
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testGetCurrencies()
    {
        $this->client->request('GET', '/api/currencies');
        $this->assertResponseIsSuccessful();
        $data = $this->client->getResponse()->toArray();
        $this->assertGreaterThan(0, $data['hydra:totalItems']);
    }
}
