<?php

declare(strict_types=1);

namespace App\Review\Application\Query\GetProductReviews;

final readonly class GetProductReviewsQuery
{
    public function __construct(
        public string $productId,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
