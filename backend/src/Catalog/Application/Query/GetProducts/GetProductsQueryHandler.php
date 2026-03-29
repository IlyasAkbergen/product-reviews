<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\GetProducts;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Review\Application\Port\RatingCacheInterface;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use App\Shared\Application\Query\PaginatedResult;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetProductsQueryHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly RatingCacheInterface $ratingCache,
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
            fn (Product $p) => $this->toProductSummary($p),
            $result['items'],
        );

        return new PaginatedResult($items, $result['total'], $query->page, $query->limit);
    }

    private function toProductSummary(Product $product): ProductSummary
    {
        $averageRating = $this->ratingCache->getAverageFromCache($product->id);

        if ($averageRating === null) {
            $sum = $this->reviewRepository->sumRatingByProduct($product->id);
            $count = $this->reviewRepository->countByProduct($product->id);
            $this->ratingCache->warm($product->id, $sum, $count);
            $averageRating = $count > 0 ? $sum / $count : null;
        }

        $reviewCount = $this->reviewRepository->countByProduct($product->id);

        return new ProductSummary(
            $product->id->value(),
            $product->externalId,
            $product->title,
            $product->description,
            $product->price,
            $product->category,
            $product->thumbnail,
            $product->stock,
            $product->brand,
            $averageRating !== null ? round($averageRating, 2) : null,
            $reviewCount,
        );
    }
}
