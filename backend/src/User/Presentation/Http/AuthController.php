<?php

declare(strict_types=1);

namespace App\User\Presentation\Http;

use App\Shared\Application\Bus\CommandBusInterface;
use App\User\Application\Command\Register\RegisterCommand;
use App\User\Presentation\Http\Request\RegisterRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Route('/api/auth/register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $dto): JsonResponse
    {
        $this->commandBus->dispatch(new RegisterCommand(
            $dto->email,
            $dto->password,
            $dto->name,
        ));

        return new JsonResponse(['message' => 'User registered successfully.'], Response::HTTP_CREATED);
    }

    /**
     * This route is intercepted by the JWT security firewall (json_login).
     * The controller method is never reached in production.
     */
    #[Route('/api/auth/login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('This route is handled by the security firewall.');
    }
}
