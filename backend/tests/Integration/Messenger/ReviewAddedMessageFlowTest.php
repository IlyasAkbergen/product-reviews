<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messenger;

use App\Review\Infrastructure\Message\ReviewAddedMessage;
use App\Tests\Mock\InMemoryRatingCache;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class ReviewAddedMessageFlowTest extends KernelTestCase
{
    #[Test]
    public function dispatched_message_runs_review_added_handler(): void
    {
        self::bootKernel();
        $cache = self::getContainer()->get(InMemoryRatingCache::class);
        $cache->reset();

        $bus = self::getContainer()->get(MessageBusInterface::class);
        $pid = Uuid::uuid4()->toString();
        $bus->dispatch(new ReviewAddedMessage($pid, 4));

        self::assertCount(1, $cache->increments);
        self::assertSame($pid, $cache->increments[0]['productId']);
        self::assertSame(4, $cache->increments[0]['rating']);
    }
}
