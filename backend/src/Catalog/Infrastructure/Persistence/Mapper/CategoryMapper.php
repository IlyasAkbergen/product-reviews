<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Mapper;

use App\Catalog\Domain\Category;
use App\Catalog\Domain\CategoryId;
use App\Catalog\Infrastructure\Persistence\ORM\CategoryOrmEntity;

final class CategoryMapper
{
    public function toDomain(CategoryOrmEntity $entity): Category
    {
        return new Category(
            CategoryId::fromString($entity->id),
            $entity->name,
        );
    }

    public function toOrm(Category $category): CategoryOrmEntity
    {
        return new CategoryOrmEntity(
            $category->id->value(),
            $category->name,
        );
    }
}
