<?php

declare(strict_types=1);


namespace App\Tests\Service;


use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;


final class ApiServiceMock extends MockHttpClient
{

    private string $baseUri = 'https://api.example.com';


    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);

        parent::__construct($callback, $this->baseUri);
    }

    /**
     * @param string $method
     * @param string $url
     * @return MockResponse
     * @throws \JsonException
     */
    private function handleRequests(string $method, string $url): MockResponse
    {
        if ($method === 'GET' && str_starts_with($url, $this->baseUri.'/symbols')) {
            return $this->getCurrenciesMock();
        }

        if ($method === 'GET' && str_starts_with($url, $this->baseUri.'/timeseries')) {
            return $this->getRatesMock();
        }

        throw new \UnexpectedValueException("Mock not implemented: $method/$url");
    }

    /**
     * "/symbols" endpoint.
     *
     * @return MockResponse
     * @throws \JsonException
     */
    private function getCurrenciesMock(): MockResponse
    {
        $mockData = json_decode(file_get_contents(__DIR__.'/symbols.json'), true);

        return new MockResponse(
            json_encode($mockData, JSON_THROW_ON_ERROR),
            ['http_code' => Response::HTTP_OK]
        );
    }

    /**
     * "/timeseries" endpoint.
     *
     * @return MockResponse
     * @throws \JsonException
     */
    private function getRatesMock(): MockResponse
    {
        $mockData = json_decode(file_get_contents(__DIR__.'/timeframe.json'), true);

        return new MockResponse(
            json_encode($mockData, JSON_THROW_ON_ERROR),
            ['http_code' => Response::HTTP_OK]
        );
    }
}
