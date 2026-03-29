<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\Aggregate\User;
use App\User\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(EmailAddress $email): ?User;

    public function emailExists(EmailAddress $email): bool;

    /** @return list<UserId> */
    public function findAllIds(): array;

    /**
     * @param  string[] $ids
     * @return array<string, string> userId => name
     */
    public function findNamesByIds(array $ids): array;
}
