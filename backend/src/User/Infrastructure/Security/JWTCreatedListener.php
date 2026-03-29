<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Infrastructure\Persistence\ORM\UserOrmEntity;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
final class JWTCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof UserOrmEntity) {
            return;
        }

        $event->setData(array_merge($event->getData(), [
            'id'    => $user->id,
            'email' => $user->email,
            'name'  => $user->name,
        ]));
    }
}
