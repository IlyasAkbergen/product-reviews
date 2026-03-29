<?php

declare(strict_types=1);

namespace App\Review\Domain;

use App\Catalog\Domain\ProductId;
use App\Review\Domain\Event\ReviewAddedEvent;
use App\Shared\Domain\AggregateRoot;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class Review extends AggregateRoot
{
    public function __construct(
        public readonly ReviewId $id,
        public readonly ProductId $productId,
        public readonly UserId $userId,
        public readonly Rating $rating,
        public readonly string $body,
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        ProductId $productId,
        UserId $userId,
        Rating $rating,
        string $body,
    ): self {
        $review = new self(
            ReviewId::generate(),
            $productId,
            $userId,
            $rating,
            $body,
            new DateTimeImmutable(),
        );

        $review->recordEvent(ReviewAddedEvent::fromReview(
            $review->id,
            $review->productId,
            $review->userId,
            $review->rating,
        ));

        return $review;
    }
}
