<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Ramsey\Uuid\Uuid;

abstract class UuidValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID: "%s"', $value));
        }
        $this->value = $value;
    }

    public static function generate(): static
    {
        return new static(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
