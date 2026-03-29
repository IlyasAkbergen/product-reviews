<?php

declare(strict_types=1);

namespace App\User\Presentation\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public readonly string $password = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $name = '',
    ) {}
}
