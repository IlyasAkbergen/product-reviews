<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\ORM;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshTokenOrmEntity
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 128)]
        public readonly string $token,

        #[ORM\Column(name: 'user_id', type: Types::STRING, length: 36)]
        public readonly string $userId,

        #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
        public readonly DateTimeImmutable $expiresAt,
    ) {}
}
