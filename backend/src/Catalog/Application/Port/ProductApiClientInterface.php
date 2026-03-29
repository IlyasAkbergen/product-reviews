<?php

declare(strict_types=1);

namespace App\Catalog\Application\Port;

interface ProductApiClientInterface
{
    /**
     * Yields raw product payloads from the remote API.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function iterateAllProducts(): \Generator;
}
