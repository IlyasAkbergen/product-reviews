<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Mapper;

use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\Aggregate\User;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\ORM\UserOrmEntity;

final class UserMapper
{
    public function toDomain(UserOrmEntity $entity): User
    {
        return new User(
            UserId::fromString($entity->id),
            new EmailAddress($entity->email),
            $entity->passwordHash,
            $entity->name,
            $entity->createdAt,
        );
    }

    public function toOrm(User $user): UserOrmEntity
    {
        return new UserOrmEntity(
            $user->id->value(),
            $user->email->value,
            $user->passwordHash,
            $user->name,
            $user->createdAt,
        );
    }
}
