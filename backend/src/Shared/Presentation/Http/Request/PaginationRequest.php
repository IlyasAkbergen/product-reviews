<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

class PaginationRequest
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,

        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public readonly int $limit = 20,
    ) {}
}
