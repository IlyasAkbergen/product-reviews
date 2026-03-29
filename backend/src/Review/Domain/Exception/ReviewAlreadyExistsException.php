<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

class ReviewAlreadyExistsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('You have already reviewed this product.');
    }
}
