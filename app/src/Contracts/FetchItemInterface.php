<?php

namespace App\Contracts;

interface FetchItemInterface extends ProviderInterface
{
    public function fetchOne($id);
}