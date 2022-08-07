<?php

declare(strict_types=1);

namespace App\Contracts;

interface FetchManyInterface
{
    /**
     * @return array<int,object>
     */
    public function fetchMany(): array;
}
