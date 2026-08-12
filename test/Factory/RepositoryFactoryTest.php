<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Factory;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\SchemaInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sirix\Cycle\Factory\RepositoryFactory;

use function sprintf;

final class RepositoryFactoryTest extends TestCase
{
    public function testCreatesRepositoryConfiguredForRole(): void
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $schema     = $this->createMock(SchemaInterface::class);
        $orm        = $this->createMock(ORMInterface::class);

        $schema->method('getRoles')->willReturn(['user']);
        $schema
            ->expects($this->once())
            ->method('define')
            ->with('user', SchemaInterface::REPOSITORY)
            ->willReturn(TestUserRepository::class)
        ;
        $orm->method('getSchema')->willReturn($schema);
        $orm
            ->expects($this->once())
            ->method('getRepository')
            ->with('user')
            ->willReturn($repository)
        ;

        $factory = new RepositoryFactory();

        $this->assertSame($repository, $factory($this->createContainer($orm), TestUserRepository::class));
    }

    public function testThrowsWhenRepositoryIsNotConfigured(): void
    {
        $schema = $this->createMock(SchemaInterface::class);
        $orm    = $this->createMock(ORMInterface::class);

        $schema->method('getRoles')->willReturn(['user']);
        $schema->method('define')->willReturn(OtherRepository::class);
        $orm->method('getSchema')->willReturn($schema);

        $factory = new RepositoryFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not configured for any entity role');
        $factory($this->createContainer($orm), TestUserRepository::class);
    }

    public function testThrowsWhenRepositoryIsConfiguredForMultipleRoles(): void
    {
        $schema = $this->createMock(SchemaInterface::class);
        $orm    = $this->createMock(ORMInterface::class);

        $schema->method('getRoles')->willReturn(['user', 'admin']);
        $schema->method('define')->willReturn(TestUserRepository::class);
        $orm->method('getSchema')->willReturn($schema);

        $factory = new RepositoryFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is configured for multiple entity roles: user, admin');
        $factory($this->createContainer($orm), TestUserRepository::class);
    }

    private function createContainer(MockObject|ORMInterface $orm): ContainerInterface
    {
        return new class($orm) implements ContainerInterface {
            public function __construct(private readonly ORMInterface $orm) {}

            public function get(string $id): mixed
            {
                if (ORMInterface::class === $id) {
                    return $this->orm;
                }

                throw new InvalidArgumentException(sprintf('Unknown service "%s".', $id));
            }

            public function has(string $id): bool
            {
                return ORMInterface::class === $id;
            }
        };
    }
}

final class TestUserRepository {}

final class OtherRepository {}
