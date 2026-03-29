<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Repository;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Infrastructure\Persistence\Mapper\ProductMapper;
use App\Catalog\Infrastructure\Persistence\ORM\ProductOrmEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProductRepositoryInterface::class, public: true)]
final class DoctrineProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductMapper $mapper,
    ) {}

    public function save(Product $product): void
    {
        $id = $product->id->value();
        if ($this->em->find(ProductOrmEntity::class, $id) === null) {
            $this->em->persist($this->mapper->toOrm($product));
            $this->em->flush();

            return;
        }

        $this->em->createQueryBuilder()
            ->update(ProductOrmEntity::class, 'p')
            ->set('p.externalId', ':externalId')
            ->set('p.title', ':title')
            ->set('p.description', ':description')
            ->set('p.price', ':price')
            ->set('p.category', ':category')
            ->set('p.thumbnail', ':thumbnail')
            ->set('p.stock', ':stock')
            ->set('p.brand', ':brand')
            ->set('p.createdAt', ':createdAt')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->setParameter('externalId', $product->externalId)
            ->setParameter('title', $product->title)
            ->setParameter('description', $product->description)
            ->setParameter('price', number_format($product->price, 2, '.', ''))
            ->setParameter('category', $product->category)
            ->setParameter('thumbnail', $product->thumbnail)
            ->setParameter('stock', $product->stock)
            ->setParameter('brand', $product->brand)
            ->setParameter('createdAt', $product->createdAt)
            ->getQuery()
            ->execute();
    }

    public function findById(ProductId $id): ?Product
    {
        $entity = $this->em->find(ProductOrmEntity::class, $id->value());

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    public function findByExternalId(int $externalId): ?Product
    {
        $entity = $this->em->getRepository(ProductOrmEntity::class)->findOneBy(['externalId' => $externalId]);

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    /**
     * @return array{items: Product[], total: int}
     */
    public function findPaginated(
        int $page,
        int $limit,
        ?string $search = null,
        ?string $category = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): array {
        $qb = $this->em->createQueryBuilder()
            ->select('p')
            ->from(ProductOrmEntity::class, 'p');

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('p.title', ':q'),
                $qb->expr()->like('p.description', ':q'),
            ));
            $qb->setParameter('q', '%'.$search.'%');
        }

        if ($category !== null && $category !== '') {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', number_format($minPrice, 2, '.', ''));
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', number_format($maxPrice, 2, '.', ''));
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $offset = max(0, ($page - 1) * $limit);
        $entities = $qb->select('p')
            ->orderBy('p.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $items = array_map(fn (ProductOrmEntity $e) => $this->mapper->toDomain($e), $entities);

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return string[]
     */
    public function findAllCategories(): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('DISTINCT p.category')
            ->from(ProductOrmEntity::class, 'p')
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        /** @var list<string> $rows */
        return $rows;
    }

    /** @return ProductId[] */
    public function findAllIds(): array
    {
        $ids = $this->em->createQueryBuilder()
            ->select('p.id')
            ->from(ProductOrmEntity::class, 'p')
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(static fn (string $id) => ProductId::fromString($id), $ids);
    }
}
