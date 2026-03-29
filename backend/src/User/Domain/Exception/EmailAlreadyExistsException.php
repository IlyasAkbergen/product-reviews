<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\User\Domain\ValueObject\EmailAddress;

final class EmailAlreadyExistsException extends \RuntimeException
{
    public function __construct(EmailAddress $email)
    {
        parent::__construct(sprintf('Email "%s" is already registered.', $email));
    }
}
