<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

/**
 * EmailAddress is a demonstration of the value-object pattern: format validation
 * lives here once, so no caller ever needs to re-validate or re-normalise.
 *
 * In a real project you might also normalise to lowercase, strip whitespace, or
 * apply stricter RFC-5321 rules. Keeping all of that in one place is the point.
 */
final readonly class EmailAddress
{
    public readonly string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('Invalid email address: "%s"', $value));
        }

        $this->value = strtolower($value);
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
