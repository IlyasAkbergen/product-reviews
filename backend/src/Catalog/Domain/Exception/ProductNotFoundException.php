<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

final class ProductNotFoundException extends \RuntimeException
{
    public function __construct(string $productId)
    {
        parent::__construct(sprintf('Product "%s" not found.', $productId));
    }
}
