<?php

namespace App\Service;

class HelperService
{
    /**
     * Calculate average in array
     *
     * @param array $data
     * @return float
     */
    private function arrayAverage(array $data): float
    {
        $data = array_filter($data, 'is_numeric'); // filter out non-numeric values
        $avg = array_sum($data) / count($data);

        return (float) sprintf('%.3f', $avg);
    }

    /**
     * @param array $data
     * @param float $todayExchangeRate
     * @return string
     */
    public function calculateTrend(array $data, float $todayExchangeRate): string
    {
        $avg = $this->arrayAverage($data);

        $trendSign = '-';

        if ($avg > $todayExchangeRate) {
            $trendSign = '↑';
        } else if ($avg < $todayExchangeRate) {
            $trendSign = '↓';
        }

        return $trendSign;
    }
}