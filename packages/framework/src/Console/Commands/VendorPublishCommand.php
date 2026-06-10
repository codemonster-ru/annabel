<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Annabel\Publishing\ResourcePublisher;

class VendorPublishCommand extends Command
{
    public function getName(): string
    {
        return 'vendor:publish';
    }

    public function getDescription(): string
    {
        return 'Publish package config, migrations, views, or assets.';
    }

    public function getUsage(): string
    {
        return 'vendor:publish (--provider=Class|--tag=name|--all) [--force]';
    }

    public function handle(array $arguments = []): int
    {
        return parent::handle($arguments);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = $this->console();
        $provider = $input->option('provider');
        $tag = $input->option('tag');
        $all = $input->hasOption('all');
        $force = $input->hasOption('force');

        if (($provider !== null && !is_string($provider)) || ($tag !== null && !is_string($tag))) {
            $output->writeln($console->color(
                'Options --provider and --tag require string values.',
                'error',
            ));

            return ExitCode::INVALID;
        }

        if (!$all && $provider === null && $tag === null) {
            $output->writeln($console->color(
                'Select resources with --provider, --tag, or --all.',
                'error',
            ));

            return ExitCode::INVALID;
        }

        $app = $console->getApplication();
        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $all
            ? $registry->all()
            : $registry->matching($provider, $tag);

        if ($resources === []) {
            $output->writeln($console->color('No publishable resources matched.', 'muted'));

            return ExitCode::SUCCESS;
        }

        try {
            /** @var ResourcePublisher $publisher */
            $publisher = $app->make(ResourcePublisher::class);
            $result = $publisher->publish($resources, $force);
        } catch (\Throwable $e) {
            $output->writeln($console->color('Publish failed: ' . $e->getMessage(), 'error'));

            return ExitCode::FAILURE;
        }

        foreach ($result->published as $path) {
            $output->writeln($console->color('Published:', 'label') . ' ' . $path);
        }

        foreach ($result->skipped as $path) {
            $output->writeln($console->color('Skipped:', 'muted') . ' ' . $path);
        }

        $output->writeln(sprintf(
            'Published %d file(s); skipped %d existing file(s).',
            count($result->published),
            count($result->skipped),
        ));

        return ExitCode::SUCCESS;
    }
}
