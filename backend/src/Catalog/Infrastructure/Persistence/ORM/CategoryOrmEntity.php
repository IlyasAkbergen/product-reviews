<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
class CategoryOrmEntity
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 36)]
        public readonly string $id,

        #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
        public readonly string $name,
    ) {}
}
