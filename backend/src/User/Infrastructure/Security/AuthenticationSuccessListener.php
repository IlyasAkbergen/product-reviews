<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Infrastructure\Persistence\ORM\UserOrmEntity;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_success')]
final class AuthenticationSuccessListener
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
    ) {}

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof UserOrmEntity) {
            return;
        }

        $refreshToken = $this->refreshTokenRepository->create($user->id);

        $event->setData(array_merge($event->getData(), [
            'refresh_token' => $refreshToken,
        ]));
    }
}
