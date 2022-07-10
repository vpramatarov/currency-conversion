<?php

namespace App\Contracts;

interface FetchItemInterface
{
    public function fetchOne($id);
}