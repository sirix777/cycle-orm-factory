<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Service;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Service\SchemaCompilerService;
use Sirix\Cycle\Service\SchemaCompilerServiceFactory;
use Sirix\Cycle\Test\Support\ArrayContainer;

final class SchemaCompilerServiceFactoryTest extends TestCase
{
    public function testCreatesService(): void
    {
        $this->assertInstanceOf(
            SchemaCompilerService::class,
            (new SchemaCompilerServiceFactory())(new ArrayContainer([])),
        );
    }
}
