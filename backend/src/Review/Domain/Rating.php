<?php

declare(strict_types=1);

namespace App\Review\Domain;

final class Rating
{
    public readonly int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 5) {
            throw new \InvalidArgumentException(sprintf(
                'Rating must be between 1 and 5, got %d',
                $value
            ));
        }
        $this->value = $value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
