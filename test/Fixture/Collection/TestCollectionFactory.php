<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Fixture\Collection;

use Cycle\ORM\Collection\CollectionFactoryInterface;
use Traversable;

use function iterator_to_array;

/**
 * @implements CollectionFactoryInterface<array<array-key, mixed>>
 */
final class TestCollectionFactory implements CollectionFactoryInterface
{
    /**
     * @param null|class-string $collectionClass
     */
    public function __construct(public ?string $collectionClass = null) {}

    public function getInterface(): ?string
    {
        return null;
    }

    public function withCollectionClass(string $class): static
    {
        return new self($class);
    }

    public function collect(iterable $data): iterable
    {
        return $data instanceof Traversable ? iterator_to_array($data) : $data;
    }
}
