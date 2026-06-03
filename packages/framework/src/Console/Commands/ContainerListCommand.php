<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;

class ContainerListCommand extends Command
{
    public function getName(): string
    {
        return 'container:list';
    }

    public function getDescription(): string
    {
        return 'Show container bindings and instantiated singletons.';
    }

    public function getUsage(): string
    {
        return 'container:list';
    }

    public function handle(array $arguments = []): int
    {
        $console = $this->console();
        $app = $console->getApplication();
        $container = $app->getContainer();

        $bindings = $container->getBindings();
        $instances = $container->getInstances();

        $console->writeln($console->color('Bindings:', 'label'));

        if (empty($bindings)) {
            $console->writeln('  ' . $console->color('none', 'muted'));
        } else {
            foreach ($bindings as $abstract => $binding) {
                $concrete = is_string($binding['concrete']) ? $binding['concrete'] : 'Closure';
                $console->writeln(sprintf(
                    '  %s => %s%s',
                    $console->color($abstract, 'command'),
                    $concrete,
                    $binding['singleton'] ? ' (singleton)' : ''
                ));
            }
        }

        $console->writeln('');
        $console->writeln($console->color('Instances:', 'label'));

        if (empty($instances)) {
            $console->writeln('  ' . $console->color('none', 'muted'));
        } else {
            foreach ($instances as $abstract => $instance) {
                $console->writeln(sprintf(
                    '  %s => %s',
                    $console->color($abstract, 'command'),
                    get_class($instance)
                ));
            }
        }

        return 0;
    }
}
