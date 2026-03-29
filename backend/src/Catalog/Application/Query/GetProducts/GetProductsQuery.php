<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\GetProducts;

final readonly class GetProductsQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        public ?string $search = null,
        public ?string $category = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
    ) {}
}
