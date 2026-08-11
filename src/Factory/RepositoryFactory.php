<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\SchemaInterface;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;

use function count;
use function implode;
use function is_string;
use function sprintf;

final class RepositoryFactory
{
    /**
     * @return RepositoryInterface<object>
     *
     * @throws ContainerExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container, string $requestedName): RepositoryInterface
    {
        $containerResolver = ContainerResolver::forFactory($container, self::class);
        $orm               = $containerResolver->getAs(ORMInterface::class, ORMInterface::class);
        $roles             = $this->findRoles($orm->getSchema(), $requestedName);

        if ([] === $roles) {
            throw new InvalidArgumentException(sprintf(
                'Cycle repository "%s" is not configured for any entity role.',
                $requestedName,
            ));
        }

        if (count($roles) > 1) {
            throw new InvalidArgumentException(sprintf(
                'Cycle repository "%s" is configured for multiple entity roles: %s.',
                $requestedName,
                implode(', ', $roles),
            ));
        }

        return $orm->getRepository($roles[0]);
    }

    /**
     * @return list<non-empty-string>
     */
    private function findRoles(SchemaInterface $schema, string $repository): array
    {
        $roles = [];

        foreach ($schema->getRoles() as $role) {
            if (! is_string($role)) {
                continue;
            }

            if ('' === $role) {
                continue;
            }

            if ($schema->define($role, SchemaInterface::REPOSITORY) === $repository) {
                $roles[] = $role;
            }
        }

        return $roles;
    }
}
