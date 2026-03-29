<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query\GetCategories;

use App\Catalog\Domain\Repository\CategoryRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetCategoriesQueryHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    /** @return string[] */
    public function __invoke(GetCategoriesQuery $query): array
    {
        return $this->categoryRepository->findAllNames();
    }
}
