<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Presentation\Http\Request;

use App\Shared\Presentation\Http\Request\PaginationRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(PaginationRequest::class)]
final class PaginationRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    #[Test]
    public function defaults_are_valid(): void
    {
        $violations = $this->validator->validate(new PaginationRequest());

        self::assertCount(0, $violations);
    }

    #[Test]
    #[DataProvider('valid_combinations')]
    public function valid_combinations_pass_validation(int $page, int $limit): void
    {
        $violations = $this->validator->validate(new PaginationRequest($page, $limit));

        self::assertCount(0, $violations, sprintf('page=%d limit=%d should be valid', $page, $limit));
    }

    /** @return iterable<string, array{int, int}> */
    public static function valid_combinations(): iterable
    {
        yield 'first page, default limit' => [1, 20];
        yield 'high page, min limit'      => [999, 1];
        yield 'first page, max limit'     => [1, 100];
        yield 'arbitrary valid values'    => [5, 50];
    }

    /** @param array<string> $violatedFields */
    #[Test]
    #[DataProvider('invalid_combinations')]
    public function invalid_combinations_fail_validation(int $page, int $limit, array $violatedFields): void
    {
        $violations = $this->validator->validate(new PaginationRequest($page, $limit));

        self::assertCount(count($violatedFields), $violations);

        $paths = array_map(static fn ($v) => $v->getPropertyPath(), iterator_to_array($violations));
        foreach ($violatedFields as $field) {
            self::assertContains($field, $paths);
        }
    }

    /** @return iterable<string, array{int, int, array<string>}> */
    public static function invalid_combinations(): iterable
    {
        yield 'page zero'         => [0,   20,  ['page']];
        yield 'page negative'     => [-1,  20,  ['page']];
        yield 'limit zero'        => [1,   0,   ['limit']];
        yield 'limit negative'    => [1,   -5,  ['limit']];
        yield 'limit exceeds max' => [1,   101, ['limit']];
        yield 'both invalid'      => [0,   0,   ['page', 'limit']];
    }
}
