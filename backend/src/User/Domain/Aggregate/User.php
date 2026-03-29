<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;

final class User
{
    public function __construct(
        public readonly UserId $id,
        public readonly EmailAddress $email,
        public readonly string $passwordHash,
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
