<?php

declare(strict_types=1);

namespace App\Review\Application\MessageHandler;

use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Review\Domain\Rating;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use App\Review\Domain\Review;
use App\Review\Infrastructure\Message\GenerateFakeReviewsMessage;
use App\Review\Infrastructure\Message\ReviewAddedMessage;
use App\User\Domain\Aggregate\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\ValueObject\UserId;
use Faker\Generator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class GenerateFakeReviewsHandler
{
    private const MAX_ATTEMPTS_FACTOR = 50;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly MessageBusInterface $bus,
        private readonly Generator $faker,
    ) {}

    public function __invoke(GenerateFakeReviewsMessage $message): void
    {
        if ($message->count <= 0) {
            return;
        }

        $ratingMin = max(1, min(5, $message->ratingMin));
        $ratingMax = max(1, min(5, $message->ratingMax));
        if ($ratingMin > $ratingMax) {
            [$ratingMin, $ratingMax] = [$ratingMax, $ratingMin];
        }

        $productIds = $this->resolveProductIds($message->productId);
        $userIds = $this->userRepository->findAllIds();

        if ($productIds === [] || $userIds === []) {
            return;
        }

        $created = 0;
        $maxAttempts = max($message->count * self::MAX_ATTEMPTS_FACTOR, self::MAX_ATTEMPTS_FACTOR);
        $attempts = 0;

        while ($created < $message->count && $attempts < $maxAttempts) {
            ++$attempts;
            $productId = $productIds[array_rand($productIds)];
            $userId = $userIds[array_rand($userIds)];

            if ($this->reviewRepository->existsByProductAndUser($productId, $userId)) {
                $userId = $this->createSyntheticUserId();
                $userIds[] = $userId;
            }

            $ratingValue = $this->faker->numberBetween($ratingMin, $ratingMax);
            $review = Review::create(
                $productId,
                $userId,
                new Rating($ratingValue),
                $this->faker->paragraph(),
            );

            $this->reviewRepository->save($review);
            $this->bus->dispatch(new ReviewAddedMessage(
                $review->productId->value(),
                $review->rating->value,
            ));

            ++$created;
        }
    }

    private function createSyntheticUserId(): UserId
    {
        $userId = UserId::generate();
        $this->userRepository->save(new User(
            $userId,
            new EmailAddress(sprintf('fake-%s@example.test', str_replace('-', '', $userId->value()))),
            hash('sha256', $userId->value()),
            $this->faker->name(),
            new \DateTimeImmutable(),
        ));

        return $userId;
    }

    /** @return list<ProductId> */
    private function resolveProductIds(?string $singleProductId): array
    {
        if ($singleProductId === null || $singleProductId === '') {
            return $this->productRepository->findAllIds();
        }

        if (!Uuid::isValid($singleProductId)) {
            return [];
        }

        $productId = ProductId::fromString($singleProductId);
        $product = $this->productRepository->findById($productId);

        return $product !== null ? [$product->id] : [];
    }
}
