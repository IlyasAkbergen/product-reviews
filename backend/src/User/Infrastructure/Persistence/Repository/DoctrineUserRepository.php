<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Repository;

use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Aggregate\User;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\Mapper\UserMapper;
use App\User\Infrastructure\Persistence\ORM\UserOrmEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserRepositoryInterface::class, public: true)]
final class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserMapper $mapper,
    ) {}

    public function save(User $user): void
    {
        if ($this->em->find(UserOrmEntity::class, $user->id->value()) === null) {
            $this->em->persist($this->mapper->toOrm($user));
            $this->em->flush();

            return;
        }

        $this->em->createQueryBuilder()
            ->update(UserOrmEntity::class, 'u')
            ->set('u.email', ':email')
            ->set('u.passwordHash', ':passwordHash')
            ->set('u.name', ':name')
            ->set('u.createdAt', ':createdAt')
            ->where('u.id = :id')
            ->setParameter('id', $user->id->value())
            ->setParameter('email', $user->email->value)
            ->setParameter('passwordHash', $user->passwordHash)
            ->setParameter('name', $user->name)
            ->setParameter('createdAt', $user->createdAt)
            ->getQuery()
            ->execute();
    }

    public function findById(UserId $id): ?User
    {
        $entity = $this->em->find(UserOrmEntity::class, $id->value());

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        $entity = $this->em->getRepository(UserOrmEntity::class)->findOneBy(['email' => $email->value]);

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    public function emailExists(EmailAddress $email): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(UserOrmEntity::class, 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email->value)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findAllIds(): array
    {
        $ids = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(UserOrmEntity::class, 'u')
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(static fn (string $id) => UserId::fromString($id), $ids);
    }

    public function findNamesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->em->createQueryBuilder()
            ->select('u.id, u.name')
            ->from(UserOrmEntity::class, 'u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['id']] = $row['name'];
        }

        return $map;
    }
}
