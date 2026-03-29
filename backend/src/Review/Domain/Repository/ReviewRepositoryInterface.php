<?php

declare(strict_types=1);

namespace App\Review\Domain\Repository;

use App\Catalog\Domain\ProductId;
use App\Review\Domain\Review;
use App\User\Domain\ValueObject\UserId;

interface ReviewRepositoryInterface
{
    public function save(Review $review): void;

    public function existsByProductAndUser(ProductId $productId, UserId $userId): bool;

    /**
     * @return array{items: Review[], total: int}
     */
    public function findByProduct(ProductId $productId, int $page, int $limit): array;

    public function calculateAverageRating(ProductId $productId): ?float;

    public function countByProduct(ProductId $productId): int;

    public function sumRatingByProduct(ProductId $productId): int;
}
