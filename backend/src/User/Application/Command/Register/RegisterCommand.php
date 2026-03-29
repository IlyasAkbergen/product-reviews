<?php

declare(strict_types=1);

namespace App\User\Application\Command\Register;

final readonly class RegisterCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $name,
    ) {}
}
