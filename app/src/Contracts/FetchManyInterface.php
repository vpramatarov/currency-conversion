<?php

namespace App\Contracts;

interface FetchManyInterface extends ProviderInterface
{
    public function fetchMany(): array;
}