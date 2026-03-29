<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Catalog\Domain\ProductId;
use App\Review\Application\Port\RatingCacheInterface;

/**
 * @phpstan-type IncrementRecord array{productId: string, rating: int}
 */
final class InMemoryRatingCache implements RatingCacheInterface
{
    /** @var list<IncrementRecord> */
    public array $increments = [];

    public function reset(): void
    {
        $this->increments = [];
    }

    public function getAverageFromCache(ProductId $productId): ?float
    {
        return null;
    }

    public function warm(ProductId $productId, int $sum, int $count): void {}

    public function increment(ProductId $productId, int $rating): void
    {
        $this->increments[] = [
            'productId' => $productId->value(),
            'rating' => $rating,
        ];
    }
}
