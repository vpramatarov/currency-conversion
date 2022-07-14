<?php

namespace App\Contracts;

interface ProviderInterface
{
    /**
     * Function to check weather this implementation supports the $provider
     */
    public function supports(string $provider): bool;
}