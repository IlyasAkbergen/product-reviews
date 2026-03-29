<?php

declare(strict_types=1);

namespace App\Catalog\Presentation\Http;

use App\Catalog\Application\Query\GetProduct\GetProductQuery;
use App\Catalog\Application\Query\GetProducts\GetProductsQuery;
use App\Catalog\Presentation\Http\Request\GetProductsRequest;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('/api/products', methods: ['GET'])]
    public function list(#[MapQueryString] GetProductsRequest $request = new GetProductsRequest()): JsonResponse
    {
        $result = $this->queryBus->ask(new GetProductsQuery(
            page: $request->page,
            limit: $request->limit,
            search: $request->search,
            category: $request->category,
            minPrice: $request->minPrice,
            maxPrice: $request->maxPrice,
        ));

        return $this->json($result);
    }

    #[Route('/api/products/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $result = $this->queryBus->ask(new GetProductQuery($id));

        return $this->json($result);
    }
}
