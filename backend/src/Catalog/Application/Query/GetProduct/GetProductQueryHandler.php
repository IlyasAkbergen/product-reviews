<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\GetProduct;

use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Review\Application\Port\RatingCacheInterface;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetProductQueryHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly RatingCacheInterface $ratingCache,
    ) {}

    public function __invoke(GetProductQuery $query): GetProductResult
    {
        $productId = ProductId::fromString($query->productId);
        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw new ProductNotFoundException($query->productId);
        }

        $averageRating = $this->ratingCache->getAverageFromCache($productId);

        if ($averageRating === null) {
            // Cache miss: calculate from DB and warm
            $sum = $this->reviewRepository->sumRatingByProduct($productId);
            $count = $this->reviewRepository->countByProduct($productId);
            $this->ratingCache->warm($productId, $sum, $count);
            $averageRating = $count > 0 ? $sum / $count : null;
        }

        $reviewCount = $this->reviewRepository->countByProduct($productId);

        return new GetProductResult(
            $product->id->value(),
            $product->externalId,
            $product->title,
            $product->description,
            $product->price,
            $product->category,
            $product->thumbnail,
            $product->stock,
            $product->brand,
            $product->createdAt->format(\DateTimeInterface::ATOM),
            $averageRating !== null ? round($averageRating, 2) : null,
            $reviewCount,
        );
    }
}
