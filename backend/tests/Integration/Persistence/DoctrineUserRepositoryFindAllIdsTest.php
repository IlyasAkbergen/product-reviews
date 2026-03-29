<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\User\Domain\ValueObject\EmailAddress;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Aggregate\User;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineUserRepositoryFindAllIdsTest extends KernelTestCase
{
    #[Test]
    public function find_all_ids_returns_saved_users_ordered_by_email(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $tool->createSchema($meta);

        $repo = self::getContainer()->get(UserRepositoryInterface::class);
        $repo->save(new User(
            UserId::generate(),
            new EmailAddress('z@example.com'),
            'hash',
            'Z',
            new DateTimeImmutable(),
        ));
        $repo->save(new User(
            UserId::generate(),
            new EmailAddress('a@example.com'),
            'hash',
            'A',
            new DateTimeImmutable(),
        ));

        $ids = $repo->findAllIds();
        self::assertCount(2, $ids);
        self::assertSame('a@example.com', $repo->findById($ids[0])?->email->value);
        self::assertSame('z@example.com', $repo->findById($ids[1])?->email->value);
    }
}
