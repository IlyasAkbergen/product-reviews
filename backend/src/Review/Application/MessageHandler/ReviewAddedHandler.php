<?php

declare(strict_types=1);

namespace App\Review\Application\MessageHandler;

use App\Catalog\Domain\ProductId;
use App\Review\Application\Port\RatingCacheInterface;
use App\Review\Infrastructure\Message\ReviewAddedMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ReviewAddedHandler
{
    public function __construct(
        private readonly RatingCacheInterface $ratingCache,
    ) {}

    public function __invoke(ReviewAddedMessage $message): void
    {
        $this->ratingCache->increment(
            ProductId::fromString($message->productId),
            $message->rating,
        );
    }
}
