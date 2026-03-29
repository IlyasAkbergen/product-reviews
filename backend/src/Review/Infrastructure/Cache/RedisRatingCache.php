<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Cache;

use App\Catalog\Domain\ProductId;
use App\Review\Application\Port\RatingCacheInterface;
use Predis\ClientInterface;

final class RedisRatingCache implements RatingCacheInterface
{
    private const HASH_SUM = 'sum';

    private const HASH_COUNT = 'count';

    public function __construct(
        private readonly ClientInterface $redis,
    ) {}

    /**
     * Average rating from cache, or null if the key does not exist (cold).
     *
     * @return float|null null — miss; 0.0 — cache exists, no reviews yet (unused with HINCRBY-only writes)
     */
    public function getAverageFromCache(ProductId $productId): ?float
    {
        $key = $this->key($productId);
        $sum = $this->redis->hget($key, self::HASH_SUM);
        $count = $this->redis->hget($key, self::HASH_COUNT);

        if ($sum === null || $count === null) {
            return null;
        }

        $countInt = (int) $count;
        if ($countInt === 0) {
            return null;
        }

        return (float) $sum / $countInt;
    }

    /**
     * Warm the hash from DB aggregates (sum, count).
     */
    public function warm(ProductId $productId, int $sum, int $count): void
    {
        $key = $this->key($productId);
        $this->redis->hmset($key, [
            self::HASH_SUM => (string) $sum,
            self::HASH_COUNT => (string) $count,
        ]);
    }

    public function increment(ProductId $productId, int $rating): void
    {
        $key = $this->key($productId);
        $this->redis->hincrby($key, self::HASH_SUM, $rating);
        $this->redis->hincrby($key, self::HASH_COUNT, 1);
    }

    private function key(ProductId $productId): string
    {
        return 'product:rating:'.$productId->value();
    }
}
