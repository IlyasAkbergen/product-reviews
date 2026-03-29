<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\GetProducts;

final readonly class ProductSummary
{
    public function __construct(
        public string $id,
        public int $externalId,
        public string $title,
        public string $description,
        public float $price,
        public string $category,
        public ?string $thumbnail,
        public int $stock,
        public ?string $brand,
        public ?float $averageRating,
        public int $reviewCount,
    ) {}
}
