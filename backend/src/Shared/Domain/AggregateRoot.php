<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Event\DomainEventInterface;

abstract class AggregateRoot
{
    /** @var DomainEventInterface[] */
    private array $domainEvents = [];

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Returns all recorded events and clears the internal list.
     *
     * @return DomainEventInterface[]
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
