<?php

declare(strict_types=1);

namespace Sirix\Cycle\Command\Cycle;

use Cycle\Database\DatabaseManager;
use Sirix\Cycle\Enum\CommandName;
use Sirix\Cycle\Service\CompiledSchemaStorage;
use Sirix\Cycle\Service\SchemaCompilerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class SchemaCompileCommand extends Command
{
    /**
     * @param array<string>        $entities
     * @param array<string, mixed> $manualMappingSchemaDefinitions
     * @param array<int, mixed>    $additionalGenerators
     */
    public function __construct(
        private readonly SchemaCompilerInterface $schemaCompiler,
        private readonly CompiledSchemaStorage $compiledSchemaStorage,
        private readonly DatabaseManager $databaseManager,
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
            ->setName(CommandName::SchemaCompile->value)
            ->setDescription('Compiles Cycle ORM schema and stores it in a PHP file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        try {
            $schema = $this->schemaCompiler->compile(
                $this->databaseManager,
                $this->entities,
                $this->manualMappingSchemaDefinitions,
                $this->additionalGenerators,
            );

            $this->compiledSchemaStorage->save($this->compiledSchemaPath, $schema);
            $symfonyStyle->success('Cycle ORM schema compiled and saved to: ' . $this->compiledSchemaPath);

            if (! $this->isCacheEnabled) {
                $symfonyStyle->note('cycle.schema.cache.enabled=false, runtime will not use compiled schema cache.');
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $symfonyStyle->error('Failed to compile schema: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
