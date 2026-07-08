<?php

declare(strict_types=1);

namespace Sirix\Cycle\Internal;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM;
use Cycle\ORM\Collection\CollectionFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Sirix\ContainerResolver\ConfigReader;
use Sirix\ContainerResolver\ContainerResolver;
use Sirix\ContainerResolver\Exception\InvalidConfigValueException;
use Sirix\ContainerResolver\Exception\ResolverException;

use function class_exists;
use function interface_exists;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;

final readonly class CollectionFactoryConfigurator
{
    /**
     * @throws ResolverException
     * @throws ContainerExceptionInterface
     */
    public function createFactory(DatabaseProviderInterface $databaseProvider, ConfigReader $configReader, ContainerResolver $containerResolver): ORM\Factory
    {
        $factory = new ORM\Factory(
            dbal: $databaseProvider,
            defaultCollectionFactory: $this->resolveOptionalCollectionFactory(
                'cycle.collections.default',
                $configReader->get('cycle.collections.default'),
                $containerResolver,
            ),
        );

        foreach ($configReader->map('cycle.collections.factories', []) as $alias => $definition) {
            [$collectionFactory, $interface] = $this->resolveCollectionFactoryDefinition(
                'cycle.collections.factories.' . $alias,
                $definition,
                $containerResolver,
            );

            $factory = $factory->withCollectionFactory($alias, $collectionFactory, $interface);
        }

        return $factory;
    }

    /**
     * @return array{0: CollectionFactoryInterface<mixed>, 1: null|class-string}
     *
     * @throws ResolverException
     * @throws ContainerExceptionInterface
     */
    private function resolveCollectionFactoryDefinition(string $path, mixed $definition, ContainerResolver $containerResolver): array
    {
        if (! is_array($definition)) {
            return [
                $this->resolveCollectionFactory($path, $definition, $containerResolver),
                null,
            ];
        }

        $configReader = ConfigReader::fromArray($definition, self::class);

        $interface = $configReader->optionalNonEmptyString('interface');
        if (
            null !== $interface
            && ! class_exists($interface)
            && ! interface_exists($interface)
        ) {
            throw InvalidConfigValueException::forType(
                $path . '.interface',
                'class-string',
                $interface,
                self::class,
            );
        }

        return [
            $this->resolveCollectionFactory(
                $path . '.factory',
                $configReader->required('factory'),
                $containerResolver,
            ),
            $interface,
        ];
    }

    /**
     * @return null|CollectionFactoryInterface<mixed>
     *
     * @throws ResolverException
     * @throws ContainerExceptionInterface
     */
    private function resolveOptionalCollectionFactory(string $path, mixed $definition, ContainerResolver $containerResolver): ?CollectionFactoryInterface
    {
        if (null === $definition) {
            return null;
        }

        return $this->resolveCollectionFactory($path, $definition, $containerResolver);
    }

    /**
     * @return CollectionFactoryInterface<mixed>
     *
     * @throws ResolverException
     * @throws ContainerExceptionInterface
     */
    private function resolveCollectionFactory(string $path, mixed $definition, ContainerResolver $containerResolver): CollectionFactoryInterface
    {
        if ($definition instanceof CollectionFactoryInterface) {
            return $definition;
        }

        if (is_string($definition)) {
            if ($containerResolver->has($definition)) {
                return $containerResolver->getAs($definition, CollectionFactoryInterface::class);
            }

            if (
                ! class_exists($definition)
                || ! is_subclass_of($definition, CollectionFactoryInterface::class)
            ) {
                throw InvalidConfigValueException::forType(
                    $path,
                    CollectionFactoryInterface::class . ' service id or class-string',
                    $definition,
                    self::class,
                );
            }

            return new $definition();
        }

        throw InvalidConfigValueException::forType(
            $path,
            CollectionFactoryInterface::class . '|class-string|' . CollectionFactoryInterface::class . ' service id',
            is_object($definition) ? $definition::class : $definition,
            self::class,
        );
    }
}
