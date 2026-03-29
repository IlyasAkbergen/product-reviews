<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Mapper;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Infrastructure\Persistence\ORM\ProductOrmEntity;

final class ProductMapper
{
    public function toDomain(ProductOrmEntity $entity): Product
    {
        return new Product(
            ProductId::fromString($entity->id),
            $entity->externalId,
            $entity->title,
            $entity->description,
            (float) $entity->price,
            $entity->category,
            $entity->thumbnail,
            $entity->stock,
            $entity->brand,
            $entity->createdAt,
        );
    }

    public function toOrm(Product $product): ProductOrmEntity
    {
        return new ProductOrmEntity(
            $product->id->value(),
            $product->externalId,
            $product->title,
            $product->description,
            number_format($product->price, 2, '.', ''),
            $product->category,
            $product->thumbnail,
            $product->stock,
            $product->brand,
            $product->createdAt,
        );
    }
}
