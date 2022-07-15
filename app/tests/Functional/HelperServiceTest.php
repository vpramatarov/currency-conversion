<?php

namespace App\Tests\Functional;

use App\Service\HelperService;
use App\Test\CustomApiTestCase;

class HelperServiceTest extends CustomApiTestCase
{
    public function testArrayAverage()
    {
        $data = $this->getData();

        $average = $this->invoke(
            HelperService::class,
            'arrayAverage',
            [
                $data
            ]
        );

        $this->assertEquals(0.751, $average);
    }

    public function testCalculateTrend()
    {
        $data = $this->getData();

        $trendEqual = $this->invoke(
            HelperService::class,
            'calculateTrend',
            [
                $data,
                0.751
            ]
        );

        $this->assertEquals('-', $trendEqual);

        $trendDown = $this->invoke(
            HelperService::class,
            'calculateTrend',
            [
                $data,
                0.748
            ]
        );

        $this->assertEquals('↓', $trendDown);

        $trendUp = $this->invoke(
            HelperService::class,
            'calculateTrend',
            [
                $data,
                0.753
            ]
        );

        $this->assertEquals('↑', $trendUp);
    }

    /**
     * Get fake rates data
     *
     * @return array
     */
    private function getData(): array
    {
        $ratesData = json_decode(file_get_contents(__DIR__.'/timeframe.json'), true);
        $pairKey = 'CHF';
        $data = $ratesData['rates'] ?? [];
        return array_filter(array_column($data, $pairKey));
    }
}