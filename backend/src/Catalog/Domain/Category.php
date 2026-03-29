<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

final class Category
{
    public function __construct(
        public readonly CategoryId $id,
        public readonly string $name,
    ) {}
}
