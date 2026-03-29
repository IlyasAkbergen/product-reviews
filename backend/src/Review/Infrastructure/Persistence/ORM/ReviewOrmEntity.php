<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\ORM;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reviews')]
#[ORM\UniqueConstraint(name: 'uq_product_user', columns: ['product_id', 'user_id'])]
class ReviewOrmEntity
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 36)]
        public readonly string $id,

        #[ORM\Column(name: 'product_id', type: Types::STRING, length: 36)]
        public readonly string $productId,

        #[ORM\Column(name: 'user_id', type: Types::STRING, length: 36)]
        public readonly string $userId,

        #[ORM\Column(type: Types::SMALLINT)]
        public readonly int $rating,

        #[ORM\Column(type: Types::TEXT)]
        public readonly string $body,

        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
