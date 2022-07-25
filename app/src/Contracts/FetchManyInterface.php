<?php

declare(strict_types=1);


namespace App\Contracts;


interface FetchManyInterface
{
    public function fetchMany(): array;
}
