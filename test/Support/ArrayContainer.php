<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Support;

use Psr\Container\ContainerInterface;
use RuntimeException;

final readonly class ArrayContainer implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services
     */
    public function __construct(private array $services) {}

    public function get(string $id): mixed
    {
        return $this->services[$id] ?? throw new RuntimeException('Unknown service: ' . $id);
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
