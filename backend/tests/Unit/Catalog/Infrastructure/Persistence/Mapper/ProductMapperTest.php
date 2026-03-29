<?php

declare(strict_types=1);

namespace App\Tests\Unit\Catalog\Infrastructure\Persistence\Mapper;

use App\Catalog\Domain\Product;
use App\Catalog\Domain\ProductId;
use App\Catalog\Infrastructure\Persistence\Mapper\ProductMapper;
use App\Catalog\Infrastructure\Persistence\ORM\ProductOrmEntity;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProductMapper::class)]
final class ProductMapperTest extends TestCase
{
    #[Test]
    public function round_trip_preserves_data(): void
    {
        $id = ProductId::generate();
        $createdAt = new DateTimeImmutable('2026-01-15 12:00:00');
        $product = new Product(
            $id,
            42,
            'Title',
            'Description',
            19.99,
            'books',
            'https://example.com/t.jpg',
            7,
            'Brand',
            $createdAt,
        );

        $mapper = new ProductMapper();
        $orm = $mapper->toOrm($product);
        self::assertInstanceOf(ProductOrmEntity::class, $orm);
        self::assertSame('19.99', $orm->price);

        $back = $mapper->toDomain($orm);
        self::assertSame($id->value(), $back->id->value());
        self::assertSame(42, $back->externalId);
        self::assertSame('Title', $back->title);
        self::assertSame(19.99, $back->price);
        self::assertSame($createdAt->format('Y-m-d H:i:s'), $back->createdAt->format('Y-m-d H:i:s'));
    }
}
