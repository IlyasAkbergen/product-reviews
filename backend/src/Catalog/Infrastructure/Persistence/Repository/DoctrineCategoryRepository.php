<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Repository;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Catalog\Infrastructure\Persistence\Mapper\CategoryMapper;
use App\Catalog\Infrastructure\Persistence\ORM\CategoryOrmEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CategoryRepositoryInterface::class, public: true)]
final class DoctrineCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryMapper $mapper,
    ) {}

    public function save(Category $category): void
    {
        if ($this->em->find(CategoryOrmEntity::class, $category->id->value()) === null) {
            $this->em->persist($this->mapper->toOrm($category));
            $this->em->flush();
        }
    }

    public function findByName(string $name): ?Category
    {
        $entity = $this->em->getRepository(CategoryOrmEntity::class)->findOneBy(['name' => $name]);

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    /** @return string[] */
    public function findAllNames(): array
    {
        /** @var list<string> */
        return $this->em->createQueryBuilder()
            ->select('c.name')
            ->from(CategoryOrmEntity::class, 'c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
