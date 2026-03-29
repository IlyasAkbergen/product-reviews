<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

use DateTimeImmutable;

final class Product
{
    public function __construct(
        public readonly ProductId $id,
        public readonly int $externalId,
        public readonly string $title,
        public readonly string $description,
        public readonly float $price,
        public readonly string $category,
        public readonly ?string $thumbnail,
        public readonly int $stock,
        public readonly ?string $brand,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
