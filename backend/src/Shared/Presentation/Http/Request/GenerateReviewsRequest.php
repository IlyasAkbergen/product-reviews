<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class GenerateReviewsRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(500)]
        public readonly int $count = 0,

        #[Assert\Range(min: 1, max: 5)]
        public readonly int $ratingMin = 1,

        #[Assert\Range(min: 1, max: 5)]
        public readonly int $ratingMax = 5,

        #[Assert\Uuid]
        public readonly ?string $productId = null,
    ) {}
}
