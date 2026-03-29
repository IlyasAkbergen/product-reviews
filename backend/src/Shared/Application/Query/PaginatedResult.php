<?php

declare(strict_types=1);

namespace App\Shared\Application\Query;

/**
 * Generic paginated query result.
 *
 * @template T
 */
final readonly class PaginatedResult
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {}
}
