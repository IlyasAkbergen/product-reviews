<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\ORM;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class ProductOrmEntity
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 36)]
        public readonly string $id,

        #[ORM\Column(name: 'external_id', type: Types::INTEGER, unique: true)]
        public readonly int $externalId,

        #[ORM\Column(type: Types::STRING, length: 255)]
        public readonly string $title,

        #[ORM\Column(type: Types::TEXT)]
        public readonly string $description,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        public readonly string $price,

        #[ORM\Column(type: Types::STRING, length: 100)]
        public readonly string $category,

        #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
        public readonly ?string $thumbnail,

        #[ORM\Column(type: Types::INTEGER)]
        public readonly int $stock,

        #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        public readonly ?string $brand,

        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
