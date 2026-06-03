<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Database\CLI\DatabaseCLIKernel;

class DatabaseCommand extends Command
{
    public function __construct(
        protected string $signature,
        protected string $description
    ) {}

    public function getName(): string
    {
        return $this->signature;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUsage(): string
    {
        return $this->signature;
    }

    public function handle(array $arguments = []): int
    {
        $console = $this->console();

        try {
            /** @var DatabaseCLIKernel $kernel */
            $kernel = $console->getApplication()->make(DatabaseCLIKernel::class);
            $command = $kernel->getRegistry()->get($this->signature);

            if (!$command) {
                $console->writeln($console->color("Database command [{$this->signature}] not found.", 'error'));

                return 1;
            }

            return $command->handle($arguments);
        } catch (\Throwable $e) {
            $console->writeln($console->color(
                "Cannot run database command [{$this->signature}]. Check database configuration and connection. Error: {$e->getMessage()}",
                'error'
            ));

            return 1;
        }
    }
}
