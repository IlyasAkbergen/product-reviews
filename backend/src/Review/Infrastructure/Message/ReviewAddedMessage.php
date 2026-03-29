<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Message;

/**
 * Async update of rating aggregates in Redis after a review is saved.
 */
final readonly class ReviewAddedMessage
{
    public function __construct(
        public string $productId,
        public int $rating,
    ) {}
}
