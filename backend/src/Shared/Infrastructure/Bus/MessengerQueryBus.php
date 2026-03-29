<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsAlias(QueryBusInterface::class)]
final class MessengerQueryBus implements QueryBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function ask(object $query): mixed
    {
        $envelope = $this->bus->dispatch($query);

        return $envelope->last(HandledStamp::class)?->getResult();
    }
}
