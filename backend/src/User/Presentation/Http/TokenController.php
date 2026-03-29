<?php

declare(strict_types=1);

namespace App\User\Presentation\Http;

use App\User\Infrastructure\Persistence\ORM\UserOrmEntity;
use App\User\Infrastructure\Security\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TokenController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/api/auth/refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $token = $body['refresh_token'] ?? null;

        if (!is_string($token) || $token === '') {
            return new JsonResponse(['message' => 'refresh_token is required.'], Response::HTTP_BAD_REQUEST);
        }

        $entity = $this->refreshTokenRepository->findValid($token);
        if ($entity === null) {
            return new JsonResponse(['message' => 'Invalid or expired refresh token.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->em->find(UserOrmEntity::class, $entity->userId);
        if ($user === null) {
            return new JsonResponse(['message' => 'User not found.'], Response::HTTP_UNAUTHORIZED);
        }

        // Rotate: revoke old token, issue a new one
        $this->refreshTokenRepository->revoke($token);
        $newRefreshToken = $this->refreshTokenRepository->create($user->id);
        $accessToken = $this->jwtManager->create($user);

        return new JsonResponse([
            'token'         => $accessToken,
            'refresh_token' => $newRefreshToken,
        ]);
    }

    #[Route('/api/auth/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $token = $body['refresh_token'] ?? null;

        if (is_string($token) && $token !== '') {
            $this->refreshTokenRepository->revoke($token);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
