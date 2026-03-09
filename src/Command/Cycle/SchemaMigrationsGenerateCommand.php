<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Cycle\Database\DatabaseManager;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Enum\SchemaCompileMode;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class SchemaMigrationsGenerateCommand extends Command
{
    /**
     * @param array<string>        $entities
     * @param array<string, mixed> $manualMappingSchemaDefinitions
     * @param array<int, mixed>    $additionalGenerators
     */
    public function __construct(
        private readonly SchemaCompilerInterface $schemaCompiler,
        private readonly CompiledSchemaStorage $compiledSchemaStorage,
        private readonly DatabaseManager $dbal,
        private readonly array $entities,
        private readonly array $manualMappingSchemaDefinitions,
        private readonly array $additionalGenerators,
        private readonly string $compiledSchemaPath,
        private readonly bool $isCacheEnabled,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setName(CommandName::SchemaMigrationGenerate->value)
            ->setDescription('Generates schema migrations and optionally refreshes compiled schema cache')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $schema = $this->schemaCompiler->compile(
                $this->dbal,
                $this->entities,
                $this->manualMappingSchemaDefinitions,
                $this->additionalGenerators,
                SchemaCompileMode::GenerateMigrations,
            );

            if ($this->isCacheEnabled) {
                $this->compiledSchemaStorage->save($this->compiledSchemaPath, $schema);
                $io->success('Schema migrations generated and compiled cache refreshed.');
            } else {
                $io->success('Schema migrations generated. Compiled cache update skipped (cache disabled).');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error('Failed to generate schema migrations: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
