<?php

declare(strict_types=1);

namespace App\User\Application\Command\Register;

use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\Exception\EmailAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Aggregate\User;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

#[AsMessageHandler]
final class RegisterCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(RegisterCommand $command): void
    {
        $email = new EmailAddress($command->email);

        if ($this->userRepository->emailExists($email)) {
            throw new EmailAlreadyExistsException($email);
        }

        // InMemoryUser is used only to satisfy the hasher's UserInterface requirement.
        // The hashing algorithm is determined by the password_hashers config, not the user type.
        $passwordHash = $this->passwordHasher->hashPassword(
            new InMemoryUser($command->email, null),
            $command->password,
        );

        $user = new User(
            UserId::generate(),
            $email,
            $passwordHash,
            $command->name,
            new DateTimeImmutable(),
        );

        $this->userRepository->save($user);
    }
}
