<?php

declare(strict_types=1);

namespace App\Review\Application\Port;

use App\Catalog\Domain\ProductId;

interface RatingCacheInterface
{
    public function getAverageFromCache(ProductId $productId): ?float;

    public function warm(ProductId $productId, int $sum, int $count): void;

    public function increment(ProductId $productId, int $rating): void;
}
