<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Infrastructure\Persistence\ORM\RefreshTokenOrmEntity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class RefreshTokenRepository
{
    private const TTL_DAYS = 30;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function create(string $userId): string
    {
        $token = bin2hex(random_bytes(64));
        $expiresAt = new DateTimeImmutable(sprintf('+%d days', self::TTL_DAYS));

        $this->em->persist(new RefreshTokenOrmEntity($token, $userId, $expiresAt));
        $this->em->flush();

        return $token;
    }

    public function findValid(string $token): ?RefreshTokenOrmEntity
    {
        $entity = $this->em->find(RefreshTokenOrmEntity::class, $token);

        if ($entity === null || $entity->expiresAt < new DateTimeImmutable()) {
            return null;
        }

        return $entity;
    }

    public function revoke(string $token): void
    {
        $entity = $this->em->find(RefreshTokenOrmEntity::class, $token);
        if ($entity !== null) {
            $this->em->remove($entity);
            $this->em->flush();
        }
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->em->createQueryBuilder()
            ->delete(RefreshTokenOrmEntity::class, 'rt')
            ->where('rt.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
