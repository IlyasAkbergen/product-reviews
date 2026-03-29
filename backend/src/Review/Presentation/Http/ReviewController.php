<?php

declare(strict_types=1);

namespace App\Review\Presentation\Http;

use App\Review\Application\Command\AddReview\AddReviewCommand;
use App\Review\Application\Query\GetProductReviews\GetProductReviewsQuery;
use App\Review\Presentation\Http\Request\AddReviewRequest;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Presentation\Http\Request\PaginationRequest;
use App\User\Infrastructure\Persistence\ORM\UserOrmEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ReviewController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route('/api/products/{productId}/reviews', methods: ['GET'])]
    public function list(
        string $productId,
        #[MapQueryString] PaginationRequest $pagination = new PaginationRequest(),
    ): JsonResponse {
        $result = $this->queryBus->ask(
            new GetProductReviewsQuery($productId, $pagination->page, $pagination->limit),
        );

        return $this->json($result);
    }

    #[Route('/api/products/{productId}/reviews', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(string $productId, #[MapRequestPayload] AddReviewRequest $dto): JsonResponse
    {
        /** @var UserOrmEntity $user */
        $user = $this->getUser();

        $this->commandBus->dispatch(new AddReviewCommand(
            productId: $productId,
            userId: $user->id,
            rating: $dto->rating,
            body: $dto->body,
        ));

        return new JsonResponse(['message' => 'Review added.'], Response::HTTP_CREATED);
    }
}
