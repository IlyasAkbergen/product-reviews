<?php

declare(strict_types=1);

namespace App\Review\Application\Command\AddReview;

use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Review\Domain\Exception\ReviewAlreadyExistsException;
use App\Review\Domain\Rating;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use App\Review\Domain\Review;
use App\Review\Infrastructure\Message\ReviewAddedMessage;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class AddReviewCommandHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly MessageBusInterface $bus,
    ) {}

    public function __invoke(AddReviewCommand $command): void
    {
        $productId = ProductId::fromString($command->productId);
        $userId = UserId::fromString($command->userId);

        if ($this->productRepository->findById($productId) === null) {
            throw new ProductNotFoundException($command->productId);
        }

        if ($this->reviewRepository->existsByProductAndUser($productId, $userId)) {
            throw new ReviewAlreadyExistsException();
        }

        $review = Review::create(
            $productId,
            $userId,
            new Rating($command->rating),
            $command->body,
        );

        $this->reviewRepository->save($review);

        $this->bus->dispatch(new ReviewAddedMessage(
            $review->productId->value(),
            $review->rating->value,
        ));
    }
}
