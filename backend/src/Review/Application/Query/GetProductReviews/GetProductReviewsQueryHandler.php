<?php

declare(strict_types=1);

namespace App\Review\Application\Query\GetProductReviews;

use App\Catalog\Domain\ProductId;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use App\Review\Domain\Review;
use App\Shared\Application\Query\PaginatedResult;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetProductReviewsQueryHandler
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /** @return PaginatedResult<ReviewItem> */
    public function __invoke(GetProductReviewsQuery $query): PaginatedResult
    {
        $productId = ProductId::fromString($query->productId);
        $result = $this->reviewRepository->findByProduct($productId, $query->page, $query->limit);

        $userIds = array_map(static fn (Review $r) => $r->userId->value(), $result['items']);
        $namesByUserId = $this->userRepository->findNamesByIds(array_unique($userIds));

        $items = array_map(
            static fn (Review $r) => new ReviewItem(
                $r->id->value(),
                $r->userId->value(),
                $namesByUserId[$r->userId->value()] ?? null,
                $r->rating->value,
                $r->body,
                $r->createdAt->format(\DateTimeInterface::ATOM),
            ),
            $result['items'],
        );

        return new PaginatedResult($items, $result['total'], $query->page, $query->limit);
    }
}
