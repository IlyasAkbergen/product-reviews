<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Application\MessageHandler;

use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Review\Application\MessageHandler\GenerateFakeReviewsHandler;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use App\Review\Infrastructure\Message\GenerateFakeReviewsMessage;
use App\Review\Infrastructure\Message\ReviewAddedMessage;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Faker\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(GenerateFakeReviewsHandler::class)]
final class GenerateFakeReviewsHandlerTest extends TestCase
{
    #[Test]
    public function saves_review_and_dispatches_rating_message(): void
    {
        $productId = ProductId::generate();
        $userId = UserId::generate();

        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $productRepository->method('findAllIds')->willReturn([$productId]);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->method('findAllIds')->willReturn([$userId]);

        $reviewRepository = $this->createMock(ReviewRepositoryInterface::class);
        $reviewRepository->method('existsByProductAndUser')->willReturn(false);
        $reviewRepository->expects(self::once())->method('save');

        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message) use (&$dispatched): Envelope {
            $dispatched[] = $message instanceof Envelope ? $message->getMessage() : $message;

            return $message instanceof Envelope ? $message : new Envelope($message);
        });

        $handler = new GenerateFakeReviewsHandler(
            $productRepository,
            $userRepository,
            $reviewRepository,
            $bus,
            Factory::create(),
        );

        $handler(new GenerateFakeReviewsMessage(1, 3, 3));

        self::assertCount(1, $dispatched);
        self::assertInstanceOf(ReviewAddedMessage::class, $dispatched[0]);
        /** @var ReviewAddedMessage $msg */
        $msg = $dispatched[0];
        self::assertSame($productId->value(), $msg->productId);
        self::assertSame(3, $msg->rating);
    }

    #[Test]
    public function does_nothing_when_count_is_zero(): void
    {
        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $productRepository->expects(self::never())->method('findAllIds');

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $reviewRepository = $this->createMock(ReviewRepositoryInterface::class);
        $reviewRepository->expects(self::never())->method('save');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $handler = new GenerateFakeReviewsHandler(
            $productRepository,
            $userRepository,
            $reviewRepository,
            $bus,
            Factory::create(),
        );

        $handler(new GenerateFakeReviewsMessage(0));
    }
}
