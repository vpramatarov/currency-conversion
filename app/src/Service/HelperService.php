<?php

declare(strict_types=1);

namespace App\Service;

class HelperService
{
    /**
     * @param array<int, float> $data
     * @param float             $todayExchangeRate
     *
     * @return string
     */
    public function calculateTrend(array $data, float $todayExchangeRate): string
    {
        $avg = $this->arrayAverage($data);

        $trendSign = '-';

        if ($avg < $todayExchangeRate) {
            $trendSign = '↑';
        } elseif ($avg > $todayExchangeRate) {
            $trendSign = '↓';
        }

        return $trendSign;
    }

    /**
     * Calculate average in array.
     *
     * @param array<int, float> $data
     *
     * @return float
     */
    private function arrayAverage(array $data): float
    {
        $data = array_filter($data, 'is_numeric'); // filter out non-numeric values
        $avg = array_sum($data) / \count($data);

        return (float) sprintf('%.3f', $avg);
    }
}
