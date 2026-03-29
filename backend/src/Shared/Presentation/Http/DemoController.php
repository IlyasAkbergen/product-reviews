<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Review\Infrastructure\Message\GenerateFakeReviewsMessage;
use App\Shared\Presentation\Http\Request\GenerateReviewsRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    #[Route('/api/demo/generate-reviews', methods: ['POST'])]
    public function generateReviews(#[MapRequestPayload] GenerateReviewsRequest $dto): JsonResponse
    {
        $this->bus->dispatch(new GenerateFakeReviewsMessage(
            count: $dto->count,
            ratingMin: $dto->ratingMin,
            ratingMax: $dto->ratingMax,
            productId: $dto->productId,
        ));

        return new JsonResponse(
            ['message' => sprintf('Dispatched generation of %d fake reviews.', $dto->count)],
            Response::HTTP_ACCEPTED,
        );
    }
}
