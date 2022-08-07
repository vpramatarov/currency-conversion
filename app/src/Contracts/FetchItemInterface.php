<?php

declare(strict_types=1);

namespace App\Contracts;

interface FetchItemInterface
{
    /**
     * @param int|string $id
     *
     * @return mixed
     */
    public function fetchOne($id);
}
