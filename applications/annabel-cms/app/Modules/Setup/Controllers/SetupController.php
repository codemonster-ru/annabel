<?php

namespace Codemonster\Cms\Modules\Setup\Controllers;

use Codemonster\Annabel\Application;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Cms\Modules\Setup\Services\SetupAssetManager;
use Codemonster\Cms\Modules\Setup\Services\EnvironmentFile;
use Codemonster\Cms\Modules\Setup\Services\InstallationState;
use Codemonster\Cms\Modules\Setup\Services\SystemRequirements;
use Codemonster\Database\Connection;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\DatabaseManager;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;
use Codemonster\View\View;
use Throwable;

class SetupController
{
    public function __construct(
        private Application $app,
        private InstallationState $state,
        private SetupAssetManager $assets,
        private EnvironmentFile $env,
        private SystemRequirements $requirements,
        private Validator $validator,
        private View $view,
    ) {
    }

    public function index(): Response
    {
        return $this->renderAdminSetup();
    }

    public function install(Request $request): Response
    {
        if (!$this->requirements->report()['passed']) {
            return $this->errorResponse(
                $request,
                'System requirements are not satisfied. Resolve failed checks before installing Annabel CMS.',
            );
        }

        $validated = $this->validator->validateOrFail($this->normalizeInput($request), [
            'license_accepted' => 'required|boolean|in:1',
            ...$this->databaseRules(),
            'admin_username' => 'required|string|min:3|max:60',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:8|confirmed',
            'admin_password_confirmation' => 'required|string',
        ], $this->validationAttributes());

        if (!User::validUsername((string) $validated['admin_username'])) {
            return $this->fieldErrorResponse(
                $request,
                'admin_username',
                'Username may contain only letters, numbers, underscores, or hyphens and must start with a letter or number.',
            );
        }

        $dbConfig = $this->databaseConfig($validated);

        try {
            $this->assertDatabaseReady($dbConfig);
        } catch (Throwable $e) {
            return $this->errorResponse(
                $request,
                $this->databaseAccessErrorMessage($e, $dbConfig),
            );
        }

        try {
            $this->env->write([
                'DB_HOST' => $dbConfig['host'],
                'DB_PORT' => $dbConfig['port'],
                'DB_DATABASE' => $dbConfig['database'],
                'DB_USERNAME' => $dbConfig['username'],
                'DB_PASSWORD' => $dbConfig['password'],
            ]);

            $this->installCms($dbConfig, $validated);
        } catch (Throwable $e) {
            return $this->errorResponse($request, env('APP_DEBUG', false, true)
                ? $e->getMessage()
                : 'Installation failed. Please verify the entered data and try again.');
        }

        $this->state->markInstalled([
            'admin_email' => $validated['admin_email'],
        ]);

        if ($request->wantsJson()) {
            return Response::json([
                'message' => 'Annabel CMS installed successfully.',
                'redirect' => '/admin/login',
            ]);
        }

        return Response::redirect('/admin/login');
    }

    public function database(Request $request): Response
    {
        $validated = $this->validator->validateOrFail(
            $this->normalizeInput($request),
            $this->databaseRules(),
            $this->validationAttributes(),
        );

        try {
            $dbConfig = $this->databaseConfig($validated);
            $this->assertDatabaseReady($dbConfig);
        } catch (Throwable $e) {
            return $this->errorResponse(
                $request,
                $this->databaseAccessErrorMessage($e, $dbConfig),
            );
        }

        return Response::json([
            'message' => 'Database connection is ready.',
        ]);
    }

    public function requirements(): Response
    {
        return Response::json($this->requirements->report());
    }

    private function renderAdminSetup(): Response
    {
        return new Response($this->view->render('setup::app', [
            'boot' => [
                'csrfToken' => csrf_token(),
                'setup' => [
                    'installationPath' => $this->state->path(),
                ],
            ],
            'assets' => $this->assets->entrypoints(),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeInput(Request $request): array
    {
        return [
            'license_accepted' => $request->input('license_accepted'),
            'db_host' => trim((string) $request->input('db_host')),
            'db_port' => trim((string) $request->input('db_port', '3306')),
            'db_database' => trim((string) $request->input('db_database')),
            'db_username' => trim((string) $request->input('db_username')),
            'db_password' => (string) $request->input('db_password'),
            'admin_username' => trim((string) $request->input('admin_username')),
            'admin_email' => trim((string) $request->input('admin_email')),
            'admin_password' => (string) $request->input('admin_password'),
            'admin_password_confirmation' => (string) $request->input('admin_password_confirmation'),
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function databaseConfig(array $validated): array
    {
        return [
            'driver' => 'mysql',
            'host' => (string) $validated['db_host'],
            'port' => (int) $validated['db_port'],
            'database' => (string) $validated['db_database'],
            'username' => (string) $validated['db_username'],
            'password' => (string) ($validated['db_password'] ?? ''),
            'charset' => 'utf8mb4',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function databaseRules(): array
    {
        return [
            'db_host' => 'required|string',
            'db_port' => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'license_accepted' => 'license agreement',
            'db_host' => 'host',
            'db_port' => 'port',
            'db_database' => 'database',
            'db_username' => 'username',
            'db_password' => 'password',
            'admin_username' => 'username',
            'admin_email' => 'email',
            'admin_password' => 'password',
            'admin_password_confirmation' => 'password confirmation',
        ];
    }

    /**
     * @param array<string, mixed> $dbConfig
     */
    private function assertDatabaseReady(array $dbConfig): void
    {
        $connection = new Connection($dbConfig);
        $table = 'annabel_setup_permission_check_' . bin2hex(random_bytes(6));
        $identifier = $this->quoteIdentifier($table);
        $created = false;

        $connection->select('SELECT 1');

        try {
            $connection->statement("CREATE TABLE {$identifier} (id INT NOT NULL PRIMARY KEY)");
            $created = true;
            $connection->statement("ALTER TABLE {$identifier} ADD COLUMN value VARCHAR(16) NULL");
        } finally {
            if ($created) {
                $connection->statement("DROP TABLE {$identifier}");
            }
        }

        $this->assertDatabaseEmpty($connection);
    }

    private function assertDatabaseEmpty(Connection $connection): void
    {
        if ($connection->select('SHOW TABLES') !== []) {
            throw new \RuntimeException(
                'The selected database is not empty. Use an empty database before continuing.',
            );
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * @param array<string, mixed> $dbConfig
     */
    private function databaseAccessErrorMessage(Throwable $e, array $dbConfig): string
    {
        $message = $e->getMessage();
        $debug = env('APP_DEBUG', false, true);
        $host = (string) ($dbConfig['host'] ?? '');

        if (str_contains($message, '[2002] No such file or directory')) {
            $friendly = $host === 'localhost'
                ? 'Unable to connect to MySQL through the local socket. Use 127.0.0.1 instead of localhost, or check the database host.'
                : 'Unable to connect to MySQL through the local socket. Check the database host or socket configuration.';

            return $friendly;
        }

        return $debug
            ? 'Unable to verify database access: ' . $message
            : 'Unable to verify database access. Check the host, port, database name, username, and password.';
    }

    /**
     * @param array<string, mixed> $dbConfig
     * @param array<string, mixed> $validated
     */
    private function installCms(array $dbConfig, array $validated): void
    {
        $this->withRuntimeDatabase($dbConfig, function () use ($validated): void {
            $connection = app(ConnectionInterface::class);

            $this->runMigrations($connection);
            $this->createAdmin($validated);
        });
    }

    /**
     * @param array<string, mixed> $dbConfig
     */
    private function withRuntimeDatabase(array $dbConfig, callable $callback): void
    {
        $container = $this->app->getContainer();
        $originalManager = $container->make(DatabaseManager::class);
        $originalConnection = $container->make(ConnectionInterface::class);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => $dbConfig,
        ]);

        $manager = new DatabaseManager((array) config('database'));
        $connection = $manager->connection('mysql');

        $container->instance(DatabaseManager::class, $manager);
        $container->instance(ConnectionInterface::class, $connection);

        try {
            $callback();
        } finally {
            $container->instance(DatabaseManager::class, $originalManager);
            $container->instance(ConnectionInterface::class, $originalConnection);
        }
    }

    private function runMigrations(ConnectionInterface $connection): void
    {
        $paths = new MigrationPathResolver();

        foreach ((array) config('database.migrations.paths', []) as $path) {
            if (is_string($path) && $path !== '') {
                $paths->addPath($path);
            }
        }

        $repository = new MigrationRepository(
            $connection,
            (string) config('database.migrations.table', 'migrations'),
        );

        (new Migrator($repository, $connection, $paths))->migrate();
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function createAdmin(array $validated): void
    {
        $email = (string) $validated['admin_email'];
        $username = (string) $validated['admin_username'];

        if (User::findByUsername($username) instanceof User) {
            throw new \RuntimeException('Admin username is already in use.');
        }

        if (User::findByEmail($email) instanceof User) {
            throw new \RuntimeException('Admin email is already in use.');
        }

        transaction(function () use ($validated, $email, $username): void {
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'password' => password_hash((string) $validated['admin_password'], PASSWORD_BCRYPT),
            ]);

            $user->assignRole('admin');
        });
    }

    private function errorResponse(Request $request, string $message): Response
    {
        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
            ], 422);
        }

        session()->flash('setup_error', $message);

        return Response::redirect('/setup');
    }

    private function fieldErrorResponse(Request $request, string $field, string $message): Response
    {
        if ($request->wantsJson()) {
            return Response::json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    $field => [$message],
                ],
            ], 422);
        }

        session()->flash('setup_error', $message);

        return Response::redirect('/setup');
    }
}
