<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command\ImportProducts;

use App\Catalog\Application\Port\ProductApiClientInterface;
use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Review\Application\Port\RatingCacheInterface;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportProductsCommandHandler
{
    public function __construct(
        private readonly ProductApiClientInterface $apiClient,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly RatingCacheInterface $ratingCache,
    ) {}

    public function __invoke(ImportProductsCommand $command): void
    {
        foreach ($this->apiClient->iterateAllProducts() as $raw) {
            $existing = $this->productRepository->findByExternalId((int) $raw['id']);

            if ($existing !== null) {
                $product = new Product(
                    $existing->id,
                    (int) $raw['id'],
                    (string) $raw['title'],
                    (string) $raw['description'],
                    (float) $raw['price'],
                    (string) $raw['category'],
                    isset($raw['thumbnail']) ? (string) $raw['thumbnail'] : null,
                    (int) $raw['stock'],
                    isset($raw['brand']) ? (string) $raw['brand'] : null,
                    $existing->createdAt,
                );
            } else {
                $product = new Product(
                    ProductId::generate(),
                    (int) $raw['id'],
                    (string) $raw['title'],
                    (string) $raw['description'],
                    (float) $raw['price'],
                    (string) $raw['category'],
                    isset($raw['thumbnail']) ? (string) $raw['thumbnail'] : null,
                    (int) $raw['stock'],
                    isset($raw['brand']) ? (string) $raw['brand'] : null,
                    new DateTimeImmutable(),
                );
            }

            $this->productRepository->save($product);

            // Warm rating cache from DB aggregates after upsert
            $sum = $this->reviewRepository->sumRatingByProduct($product->id);
            $count = $this->reviewRepository->countByProduct($product->id);
            $this->ratingCache->warm($product->id, $sum, $count);
        }
    }
}
