<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Commands\Concerns\GeneratesFiles;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class MakeModelCommand extends Command
{
    use GeneratesFiles;

    public function getName(): string
    {
        return 'make:model';
    }

    public function getDescription(): string
    {
        return 'Create a database model class.';
    }

    public function getUsage(): string
    {
        return 'make:model Name [--force]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->arguments()[0] ?? null;

        if ($name === null) {
            $output->writeln('Model name is required.');
            $output->writeln('Usage: ' . $this->getUsage());

            return ExitCode::INVALID;
        }

        $basePath = $this->console()->getApplication()->getBasePath();

        return $this->generateClass(
            $name,
            'App\\Models',
            $basePath . '/app/Models',
            '',
            fn (string $namespace, string $class): string => $this->stub($namespace, $class),
            $output,
            $input->hasOption('force'),
        );
    }

    private function stub(string $namespace, string $class): string
    {
        return <<<PHP
        <?php

        namespace {$namespace};

        use Codemonster\Database\ORM\Model;

        class {$class} extends Model
        {
            protected array \$guarded = [];
        }

        PHP;
    }
}
