<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsAlias(CommandBusInterface::class)]
final class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function dispatch(object $command): void
    {
        $this->bus->dispatch($command);
    }
}
