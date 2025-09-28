<?php

declare(strict_types=1);

namespace Sirix\Cycle\Test\Internal;

use PHPUnit\Framework\TestCase;
use Sirix\Cycle\Internal\MigrationsToggle;

use function class_exists;
use function putenv;

final class MigrationsToggleTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED');
        parent::tearDown();
    }

    public function testIsDisabledByEnvVariants(): void
    {
        putenv('CYCLE_MIGRATIONS_DISABLED');
        $this->assertFalse(MigrationsToggle::isDisabledByEnv(), 'Unset env should be treated as enabled');

        putenv('CYCLE_MIGRATIONS_DISABLED=');
        $this->assertFalse(MigrationsToggle::isDisabledByEnv(), 'Empty string should be treated as enabled');

        putenv('CYCLE_MIGRATIONS_DISABLED=0');
        $this->assertFalse(MigrationsToggle::isDisabledByEnv(), '"0" should be treated as enabled');

        putenv('CYCLE_MIGRATIONS_DISABLED=1');
        $this->assertTrue(MigrationsToggle::isDisabledByEnv(), '"1" should be treated as disabled');

        putenv('CYCLE_MIGRATIONS_DISABLED=true');
        $this->assertTrue(MigrationsToggle::isDisabledByEnv(), '"true" value should disable');

        putenv('CYCLE_MIGRATIONS_DISABLED=yes');
        $this->assertTrue(MigrationsToggle::isDisabledByEnv(), '"yes" value should disable');

        putenv('CYCLE_MIGRATIONS_DISABLED=on');
        $this->assertTrue(MigrationsToggle::isDisabledByEnv(), '"on" value should disable');
    }

    public function testAreMigrationsEnabledDependsOnClassAndEnv(): void
    {
        if (! class_exists('Cycle\Migrations\Migrator')) {
            $this->markTestSkipped('Cycle migrations package is not installed in this environment.');
        }

        putenv('CYCLE_MIGRATIONS_DISABLED=1');
        $this->assertFalse(MigrationsToggle::areMigrationsEnabled());

        putenv('CYCLE_MIGRATIONS_DISABLED=0');
        $this->assertTrue(MigrationsToggle::areMigrationsEnabled());
    }
}
