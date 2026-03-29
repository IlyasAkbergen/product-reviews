<?php

declare(strict_types=1);

namespace App\Review\Application\Command\AddReview;

final readonly class AddReviewCommand
{
    public function __construct(
        public string $productId,
        public string $userId,
        public int $rating,
        public string $body,
    ) {}
}
