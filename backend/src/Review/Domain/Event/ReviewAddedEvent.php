<?php

declare(strict_types=1);

namespace App\Review\Domain\Event;

use App\Catalog\Domain\ProductId;
use App\Review\Domain\Rating;
use App\Review\Domain\ReviewId;
use App\Shared\Domain\Event\DomainEventInterface;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class ReviewAddedEvent implements DomainEventInterface
{
    public function __construct(
        public readonly ReviewId $reviewId,
        public readonly ProductId $productId,
        public readonly UserId $userId,
        public readonly Rating $rating,
        public readonly DateTimeImmutable $occurredOn,
    ) {}

    public static function fromReview(
        ReviewId $reviewId,
        ProductId $productId,
        UserId $userId,
        Rating $rating,
    ): self {
        return new self($reviewId, $productId, $userId, $rating, new DateTimeImmutable());
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
