<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Commands\Concerns\GeneratesFiles;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class MakeMiddlewareCommand extends Command
{
    use GeneratesFiles;

    public function getName(): string
    {
        return 'make:middleware';
    }

    public function getDescription(): string
    {
        return 'Create a middleware class.';
    }

    public function getUsage(): string
    {
        return 'make:middleware Name [--force]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->arguments()[0] ?? null;

        if ($name === null) {
            $output->writeln('Middleware name is required.');
            $output->writeln('Usage: ' . $this->getUsage());

            return ExitCode::INVALID;
        }

        $basePath = $this->console()->getApplication()->getBasePath();

        return $this->generateClass(
            $name,
            'App\\Middleware',
            $basePath . '/app/Middleware',
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

        use Codemonster\Http\Request;

        class {$class}
        {
            public function handle(Request \$request, callable \$next): mixed
            {
                return \$next(\$request);
            }
        }

        PHP;
    }
}
