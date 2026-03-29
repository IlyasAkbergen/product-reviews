<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Http\Request;

use App\Shared\Presentation\Http\Request\PaginationRequest;
use Symfony\Component\Validator\Constraints as Assert;

final class GetProductsRequest extends PaginationRequest
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $category = null,

        #[Assert\PositiveOrZero]
        public readonly ?float $minPrice = null,

        #[Assert\PositiveOrZero]
        public readonly ?float $maxPrice = null,

        int $page = 1,
        int $limit = 20,
    ) {
        parent::__construct($page, $limit);
    }
}
