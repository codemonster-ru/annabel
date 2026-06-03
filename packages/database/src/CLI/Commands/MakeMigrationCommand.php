<?php

namespace Codemonster\Database\CLI\Commands;

use Codemonster\Database\CLI\CommandInterface;
use Codemonster\Database\Migrations\MigrationPathResolver;

class MakeMigrationCommand implements CommandInterface
{
    protected MigrationPathResolver $paths;

    public function __construct(MigrationPathResolver $paths)
    {
        $this->paths = $paths;
    }

    public function signature(): string
    {
        return 'make:migration';
    }

    public function description(): string
    {
        return 'Create a new migration file';
    }

    public function handle(array $arguments): int
    {
        $name = $arguments[0] ?? null;

        if (!$name) {
            fwrite(STDERR, "Migration name is required.\n");
            echo "Usage: make:migration CreateUsersTable\n";

            return 1;
        }

        if (!$this->isValidName($name)) {
            fwrite(STDERR, "Migration name must be CamelCase, Latin letters only. Example: CreateUsersTable\n");

            return 1;
        }

        $path = $this->detectPath();

        if (!$path) {
            fwrite(STDERR, "No migrations path configured.\n");

            return 1;
        }

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            fwrite(STDERR, "Cannot create migrations directory: {$path}\n");

            return 1;
        }

        $filename = $this->buildFileName($name);
        $fullPath = $path . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($fullPath)) {
            fwrite(STDERR, "Migration file already exists: {$fullPath}\n");

            return 1;
        }

        file_put_contents($fullPath, $this->stub($name));

        echo "Created migration: {$fullPath}\n";

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
        $slug = preg_replace('/(?<!^)([A-Z])/', '_$1', $name);
        $slug = preg_replace('/[^A-Za-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        $slug = strtolower($slug);

        return $timestamp . '_' . $slug . '.php';
    }

    protected function stub(string $name): string
    {
        return <<<PHP
        <?php

        use Codemonster\Database\Schema\Blueprint;
        use Codemonster\Database\Schema\Schema;
        use Codemonster\Database\Migrations\Migration;

        return new class extends Migration {
            public function up(): void
            {
                // Example:
                // schema()->create('table_name', function (Blueprint \$table) {
                //     \$table->id();
                //     \$table->string('name');
                // });
            }

            public function down(): void
            {
                // Example:
                // schema()->drop('table_name');
            }
        };

        PHP;
    }
}
