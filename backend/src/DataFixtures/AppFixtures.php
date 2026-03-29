<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Aggregate\User;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $email = new EmailAddress('demo@example.com');

        if ($this->userRepository->emailExists($email)) {
            return;
        }

        $passwordHash = $this->passwordHasher->hashPassword(
            new InMemoryUser($email->value, null),
            'demo1234',
        );

        $this->userRepository->save(new User(
            UserId::generate(),
            $email,
            $passwordHash,
            'Demo User',
            new DateTimeImmutable(),
        ));
    }
}
