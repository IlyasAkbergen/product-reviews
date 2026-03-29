<?php

declare(strict_types=1);

namespace App\Tests\Unit\Review\Application\MessageHandler;

use App\Catalog\Domain\ProductId;
use App\Review\Application\MessageHandler\ReviewAddedHandler;
use App\Review\Application\Port\RatingCacheInterface;
use App\Review\Infrastructure\Message\ReviewAddedMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(ReviewAddedHandler::class)]
final class ReviewAddedHandlerTest extends TestCase
{
    #[Test]
    public function invokes_rating_cache_increment(): void
    {
        $pid = Uuid::uuid4()->toString();

        $cache = $this->createMock(RatingCacheInterface::class);
        $cache->expects(self::once())
            ->method('increment')
            ->with(
                self::callback(static fn (ProductId $id) => $id->value() === $pid),
                self::equalTo(5),
            );

        $handler = new ReviewAddedHandler($cache);
        $handler(new ReviewAddedMessage($pid, 5));
    }
}
