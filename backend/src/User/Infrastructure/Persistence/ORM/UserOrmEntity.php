<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\ORM;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserOrmEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 36)]
        public readonly string $id,

        #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
        public readonly string $email,

        #[ORM\Column(name: 'password_hash', type: Types::STRING)]
        public readonly string $passwordHash,

        #[ORM\Column(type: Types::STRING, length: 255)]
        public readonly string $name,

        #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
        public readonly DateTimeImmutable $createdAt,
    ) {}

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }
}
