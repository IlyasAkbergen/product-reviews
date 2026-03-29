<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use App\Catalog\Application\Port\ProductApiClientInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client for the public API https://dummyjson.com/docs/products
 *
 * @phpstan-type ProductPayload array{id: int, title: string, description: string, price: float|int, category: string, thumbnail?: string, stock: int, brand?: string}
 */
#[AsAlias(ProductApiClientInterface::class)]
final class DummyJsonApiClient implements ProductApiClientInterface
{
    private const BASE_URI = 'https://dummyjson.com';

    public function __construct(
        private readonly HttpClientInterface $http,
    ) {}

    /**
     * Paginated request to /products.
     *
     * @return array{products: list<array<string, mixed>>, total: int, skip: int, limit: int}
     */
    public function fetchProductsPage(int $limit = 100, int $skip = 0): array
    {
        $response = $this->http->request('GET', self::BASE_URI.'/products', [
            'query' => [
                'limit' => $limit,
                'skip' => $skip,
            ],
        ]);

        return $response->toArray();
    }

    /**
     * All products from the API (respecting the dummyjson limit, typically up to 100 per request).
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function iterateAllProducts(int $pageSize = 100): \Generator
    {
        $skip = 0;
        do {
            $payload = $this->fetchProductsPage($pageSize, $skip);
            foreach ($payload['products'] as $product) {
                yield $product;
            }
            /** @var int $total */
            $total = $payload['total'];
            $skip += $pageSize;
        } while ($skip < $total);
    }
}
