<?php

declare(strict_types=1);


namespace App\Service;


use Symfony\Contracts\HttpClient\HttpClientInterface;


class ApiService
{

    private HttpClientInterface $httpClient;

    private HelperService $helperService;

    public function __construct(HttpClientInterface $httpClient, HelperService $helperService)
    {
        $this->httpClient = $httpClient;
        $this->helperService = $helperService;
    }

    /**
     * Fetch data for given currency pair
     *
     * @param array $currencies
     * @param string $endpoint
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchCurrencyPair(array $currencies, string $endpoint): array
    {
        $endDate = (new \DateTime('now'))->format('Y-m-d');
        $startDate = (new \DateTime('now'))->modify('-9 days')->format('Y-m-d');

        $response = $this->httpClient->request(
            'GET',
            $endpoint,
            [
                'query' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'symbols' => $currencies[1],
                    'base' => $currencies[0]
                ]
            ]
        );

        $data = json_decode($response->getContent(), true);
        $ratesData = $data['rates'] ?? [];

        $pairKey = $currencies[1];
        $endDate = (new \DateTime('now'))->format('Y-m-d');
        $todayRate = $ratesData[$endDate] ?? [];
        $todayExchangeRate = (float) sprintf('%.3f', $todayRate[$pairKey] ?? 0);

        if ($todayRate && ($todayExchangeRate > 0)) {
            $todayRate['pair'] = implode('', $currencies);
            $todayRate['base'] = $currencies[0];
            $todayRate['target'] = $currencies[1];
            $calculateTrendData = array_column($ratesData, $pairKey);
            $todayRate['suffix'] = $this->helperService->calculateTrend($calculateTrendData, $todayExchangeRate);
            $todayRate['exchangeRate'] = $todayExchangeRate;

            return $todayRate;
        }

        return [];
    }

    /**
     * Fetch currencies data from API
     *
     * @param string $endpoint
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function fetchCurrencies(string $endpoint): array
    {
        $response = $this->httpClient->request('GET', $endpoint);
        $data = json_decode($response->getContent(), true);

        return $data['symbols'] ?? [];
    }
}
