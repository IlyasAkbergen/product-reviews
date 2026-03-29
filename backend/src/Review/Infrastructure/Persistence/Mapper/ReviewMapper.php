<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Mapper;

use App\Catalog\Domain\ProductId;
use App\Review\Domain\Rating;
use App\Review\Domain\Review;
use App\Review\Domain\ReviewId;
use App\Review\Infrastructure\Persistence\ORM\ReviewOrmEntity;
use App\User\Domain\ValueObject\UserId;

final class ReviewMapper
{
    public function toDomain(ReviewOrmEntity $entity): Review
    {
        return new Review(
            ReviewId::fromString($entity->id),
            ProductId::fromString($entity->productId),
            UserId::fromString($entity->userId),
            new Rating($entity->rating),
            $entity->body,
            $entity->createdAt,
        );
    }

    public function toOrm(Review $review): ReviewOrmEntity
    {
        return new ReviewOrmEntity(
            $review->id->value(),
            $review->productId->value(),
            $review->userId->value(),
            $review->rating->value,
            $review->body,
            $review->createdAt,
        );
    }
}
