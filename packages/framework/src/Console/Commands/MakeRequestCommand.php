<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Commands\Concerns\GeneratesFiles;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class MakeRequestCommand extends Command
{
    use GeneratesFiles;

    public function getName(): string
    {
        return 'make:request';
    }

    public function getDescription(): string
    {
        return 'Create a request validation class.';
    }

    public function getUsage(): string
    {
        return 'make:request Name [--force]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->arguments()[0] ?? null;

        if ($name === null) {
            $output->writeln('Request name is required.');
            $output->writeln('Usage: ' . $this->getUsage());

            return ExitCode::INVALID;
        }

        $basePath = $this->console()->getApplication()->getBasePath();

        return $this->generateClass(
            $name,
            'App\\Http\\Requests',
            $basePath . '/app/Http/Requests',
            'Request',
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

        class {$class}
        {
            /** @return array<string, string> */
            public function rules(): array
            {
                return [];
            }
        }

        PHP;
    }
}
