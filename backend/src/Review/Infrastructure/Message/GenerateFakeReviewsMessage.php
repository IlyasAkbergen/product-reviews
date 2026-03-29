<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Message;

/**
 * Generate N fake reviews (async). When productId is null — random products from the catalog.
 */
final readonly class GenerateFakeReviewsMessage
{
    public function __construct(
        public int $count,
        public int $ratingMin = 1,
        public int $ratingMax = 5,
        public ?string $productId = null,
    ) {}
}
