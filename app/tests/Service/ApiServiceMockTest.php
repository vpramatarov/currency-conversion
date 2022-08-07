<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ApiServiceMockTest extends KernelTestCase
{
    /**
     * Test that the "http.client" service is decorated by the mock.
     *
     * @return void
     */
    public function testAbstractApiMockDecoration(): void
    {
        // this is the service ID of the HTTP client in the DI container
        $abstractApiClientId = 'http.client';

        // standart service (as it is in the prod env)
        self::assertTrue(self::getContainer()->has($abstractApiClientId));

        // decorated service
        self::assertTrue(self::getContainer()->has(ApiServiceMock::class.'.'.$abstractApiClientId));
    }
}
