<?php

declare(strict_types=1);

namespace App\Review\Application\Query\GetProductReviews;

final readonly class ReviewItem
{
    public function __construct(
        public string $id,
        public string $userId,
        public int $rating,
        public string $body,
        public string $createdAt,
    ) {}
}
