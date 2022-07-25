<?php

declare(strict_types=1);


namespace App\Contracts;


interface FetchItemInterface
{
    public function fetchOne($id);
}
