<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Repository;

use App\Catalog\Domain\ProductId;
use App\Review\Domain\Repository\ReviewRepositoryInterface;
use App\Review\Domain\Review;
use App\Review\Infrastructure\Persistence\Mapper\ReviewMapper;
use App\Review\Infrastructure\Persistence\ORM\ReviewOrmEntity;
use App\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ReviewRepositoryInterface::class, public: true)]
final class DoctrineReviewRepository implements ReviewRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReviewMapper $mapper,
    ) {}

    public function save(Review $review): void
    {
        $this->em->persist($this->mapper->toOrm($review));
        $this->em->flush();
    }

    public function existsByProductAndUser(ProductId $productId, UserId $userId): bool
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(ReviewOrmEntity::class, 'r')
            ->where('r.productId = :productId')
            ->andWhere('r.userId = :userId')
            ->setParameter('productId', $productId->value())
            ->setParameter('userId', $userId->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * @return array{items: Review[], total: int}
     */
    public function findByProduct(ProductId $productId, int $page, int $limit): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from(ReviewOrmEntity::class, 'r')
            ->where('r.productId = :productId')
            ->setParameter('productId', $productId->value())
            ->orderBy('r.createdAt', 'DESC');

        $count = (int) (clone $qb)->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $offset = max(0, ($page - 1) * $limit);
        $entities = $qb->select('r')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $items = array_map(fn (ReviewOrmEntity $e) => $this->mapper->toDomain($e), $entities);

        return ['items' => $items, 'total' => $count];
    }

    public function calculateAverageRating(ProductId $productId): ?float
    {
        $avg = $this->em->createQueryBuilder()
            ->select('AVG(r.rating)')
            ->from(ReviewOrmEntity::class, 'r')
            ->where('r.productId = :productId')
            ->setParameter('productId', $productId->value())
            ->getQuery()
            ->getSingleScalarResult();

        return $avg !== null ? (float) $avg : null;
    }

    public function countByProduct(ProductId $productId): int
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(ReviewOrmEntity::class, 'r')
            ->where('r.productId = :productId')
            ->setParameter('productId', $productId->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    public function sumRatingByProduct(ProductId $productId): int
    {
        $sum = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(r.rating), 0)')
            ->from(ReviewOrmEntity::class, 'r')
            ->where('r.productId = :productId')
            ->setParameter('productId', $productId->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $sum;
    }
}
