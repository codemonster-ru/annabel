<?php

namespace Codemonster\Database\CLI\Commands;

use Codemonster\Database\CLI\CommandInterface;
use Codemonster\Database\Seeders\SeedPathResolver;

class MakeSeedCommand implements CommandInterface
{
    protected SeedPathResolver $paths;

    public function __construct(SeedPathResolver $paths)
    {
        $this->paths = $paths;
    }

    public function signature(): string
    {
        return 'make:seed';
    }

    public function description(): string
    {
        return 'Create a new seed file';
    }

    public function handle(array $arguments): int
    {
        $name = $arguments[0] ?? null;

        if (!$name) {
            echo "Seed name is required.\n";
            echo "Usage: make:seed UsersSeeder\n";

            return 1;
        }

        if (!$this->isValidName($name)) {
            echo "Seed name must be CamelCase, Latin letters only. Example: UsersSeeder\n";

            return 1;
        }

        $path = $this->detectPath();

        if (!$path) {
            echo "No seeds path configured.\n";

            return 1;
        }

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            echo "Cannot create seeds directory: {$path}\n";

            return 1;
        }

        $filename = $this->buildFileName($name);
        $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($fullPath)) {
            echo "Seed file already exists: {$fullPath}\n";

            return 1;
        }

        file_put_contents($fullPath, $this->stub());

        echo "Created seed: {$fullPath}\n";

        return 0;
    }

    protected function detectPath(): ?string
    {
        $paths = $this->paths->getPaths();

        if (!empty($paths)) {
            return $paths[0];
        }

        return null;
    }

    protected function isValidName(string $name): bool
    {
        return (bool) preg_match('/^[A-Z][a-z]*(?:[A-Z][a-z]*)*$/', $name);
    }

    protected function buildFileName(string $name): string
    {
        $now = new \DateTimeImmutable('now');
        $timestamp = $now->format('Y_m_d_His');
        $slug = preg_replace('/(?<!^)([A-Z])/', '_$1', $name) ?? $name;
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', $slug) ?? $slug;
        $slug = trim($slug, '_');
        $slug = strtolower($slug);

        return $timestamp . '_' . $slug . '.php';
    }

    protected function stub(): string
    {
        return <<<PHP
        <?php

        use Codemonster\\Database\\Seeders\\Seeder;

        return new class extends Seeder {
            public function run(): void
            {
                // Example:
                // db()->table('users')->insert([
                //     'name' => 'Admin',
                //     'email' => 'admin@example.com',
                // ]);
            }
        };

        PHP;
    }
}
