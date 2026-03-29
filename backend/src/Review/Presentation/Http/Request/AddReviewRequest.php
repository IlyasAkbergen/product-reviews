<?php

declare(strict_types=1);

namespace App\Review\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AddReviewRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Range(min: 1, max: 5)]
        public readonly int $rating = 0,

        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public readonly string $body = '',
    ) {}
}
