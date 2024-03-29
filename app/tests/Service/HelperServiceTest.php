<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\HelperService;
use App\Test\CustomApiTestCase;

class HelperServiceTest extends CustomApiTestCase
{
    /**
     * @dataProvider getTrendTests
     *
     * @param array<int, float> $data
     * @param float             $todayExchangeRate
     * @param string            $expectedValue
     *
     * @return void
     */
    public function testCalculateTrend(array $data, float $todayExchangeRate, string $expectedValue)
    {
        $helperService = new HelperService();
        $trend = $helperService->calculateTrend($data, $todayExchangeRate);

        $this->assertEquals($expectedValue, $trend);
    }

    /**
     * @return mixed[]
     */
    public function getTrendTests(): array
    {
        $data = $this->getRatesTestData();

        return [
            [$data, 0.752, '-'],
            [$data, 0.748, '↓'],
            [$data, 0.753, '↑'],
        ];
    }

    /**
     * Get fake rates data.
     *
     * @return mixed[]
     */
    private function getRatesTestData(): array
    {
        $ratesData = json_decode(file_get_contents(__DIR__.'/timeframe.json'), true);
        $currency = 'CHF';
        $data = $ratesData['rates'] ?? [];

        return array_filter(array_column($data, $currency));
    }
}
