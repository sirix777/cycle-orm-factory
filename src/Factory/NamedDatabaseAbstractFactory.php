<?php

declare(strict_types=1);

namespace Sirix\Cycle\Factory;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseProviderInterface;
use InvalidArgumentException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\ResolverException;

use function array_key_exists;
use function is_string;
use function sprintf;

/**
 * Creates named Cycle databases for Laminas ServiceManager applications.
 *
 * Service IDs must be explicitly listed in the `cycle.database_services` map.
 */
final class NamedDatabaseAbstractFactory implements AbstractFactoryInterface
{
    public const CONFIG_PATH = 'cycle.database_services';

    /**
     * @param null|array<mixed> $options
     *
     * @throws ContainerExceptionInterface
     * @throws ResolverException
     */
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): DatabaseInterface
    {
        $databaseServices = $this->databaseServices($container);

        if (! array_key_exists($requestedName, $databaseServices)) {
            throw new InvalidArgumentException(sprintf(
                'Cycle database service "%s" is not listed in "%s".',
                $requestedName,
                self::CONFIG_PATH,
            ));
        }

        $databaseName = $databaseServices[$requestedName];

        if (! is_string($databaseName) || '' === $databaseName) {
            throw new InvalidArgumentException(sprintf(
                'Cycle database service "%s" must map to a non-empty database name in "%s".',
                $requestedName,
                self::CONFIG_PATH,
            ));
        }

        $databaseProvider = ContainerResolver::forFactory($container, self::class)
            ->getAs('dbal', DatabaseProviderInterface::class)
        ;

        return $databaseProvider->database($databaseName);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ResolverException
     */
    public function canCreate(ContainerInterface $container, string $requestedName): bool
    {
        return array_key_exists($requestedName, $this->databaseServices($container));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ContainerExceptionInterface
     * @throws ResolverException
     */
    private function databaseServices(ContainerInterface $container): array
    {
        $configReader = ConfigReader::fromContainer(
            ContainerResolver::forFactory($container, self::class),
        );

        return $configReader->map(self::CONFIG_PATH, []);
    }
}
