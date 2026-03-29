<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\GetProducts;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Shared\Application\Query\PaginatedResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetProductsQueryHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    /** @return PaginatedResult<ProductSummary> */
    public function __invoke(GetProductsQuery $query): PaginatedResult
    {
        $result = $this->productRepository->findPaginated(
            $query->page,
            $query->limit,
            $query->search,
            $query->category,
            $query->minPrice,
            $query->maxPrice,
        );

        $items = array_map(
            static fn (Product $p) => new ProductSummary(
                $p->id->value(),
                $p->externalId,
                $p->title,
                $p->price,
                $p->category,
                $p->thumbnail,
                $p->stock,
                $p->brand,
            ),
            $result['items'],
        );

        return new PaginatedResult($items, $result['total'], $query->page, $query->limit);
    }
}
