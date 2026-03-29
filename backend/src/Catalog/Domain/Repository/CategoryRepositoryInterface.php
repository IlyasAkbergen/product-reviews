<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Category;

interface CategoryRepositoryInterface
{
    public function save(Category $category): void;

    public function findByName(string $name): ?Category;

    /** @return string[] */
    public function findAllNames(): array;
}
