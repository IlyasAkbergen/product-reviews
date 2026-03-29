<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function findById(ProductId $id): ?Product;

    public function findByExternalId(int $externalId): ?Product;

    /**
     * @return array{items: Product[], total: int}
     */
    public function findPaginated(
        int $page,
        int $limit,
        ?string $search = null,
        ?string $category = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): array;

    /**
     * @return string[]
     */
    public function findAllCategories(): array;

    /** @return ProductId[] */
    public function findAllIds(): array;
}
