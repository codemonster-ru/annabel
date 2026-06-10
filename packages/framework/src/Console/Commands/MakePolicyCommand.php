<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Commands\Concerns\GeneratesFiles;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

class MakePolicyCommand extends Command
{
    use GeneratesFiles;

    public function getName(): string
    {
        return 'make:policy';
    }

    public function getDescription(): string
    {
        return 'Create an authorization policy class.';
    }

    public function getUsage(): string
    {
        return 'make:policy Name [--force]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->arguments()[0] ?? null;

        if ($name === null) {
            $output->writeln('Policy name is required.');
            $output->writeln('Usage: ' . $this->getUsage());

            return ExitCode::INVALID;
        }

        $basePath = $this->console()->getApplication()->getBasePath();

        return $this->generateClass(
            $name,
            'App\\Policies',
            $basePath . '/app/Policies',
            'Policy',
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

        use Codemonster\Auth\Contracts\AuthenticatableInterface;

        class {$class}
        {
            public function view(?AuthenticatableInterface \$user, mixed \$subject): bool
            {
                return false;
            }

            public function update(?AuthenticatableInterface \$user, mixed \$subject): bool
            {
                return false;
            }

            public function delete(?AuthenticatableInterface \$user, mixed \$subject): bool
            {
                return false;
            }
        }

        PHP;
    }
}
