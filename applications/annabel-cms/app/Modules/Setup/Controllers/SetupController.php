<?php

namespace Codemonster\Cms\Modules\Setup\Controllers;

use Codemonster\Cms\Modules\Setup\Services\CmsInstaller;
use Codemonster\Cms\Modules\Setup\Services\DatabaseProbe;
use Codemonster\Cms\Modules\Setup\Services\SetupAssetManager;
use Codemonster\Cms\Modules\Setup\Services\SystemRequirements;
use Codemonster\Cms\Support\Installation\InstallationState;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Validation\Validator;
use Codemonster\View\View;
use Throwable;

class SetupController
{
    public function __construct(
        private InstallationState $state,
        private SetupAssetManager $assets,
        private SystemRequirements $requirements,
        private DatabaseProbe $database,
        private CmsInstaller $installer,
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

        if (!$this->validAdminUsername((string) $validated['admin_username'])) {
            return $this->fieldErrorResponse(
                $request,
                'admin_username',
                'Username may contain only letters, numbers, underscores, or hyphens and must start with a letter or number.',
            );
        }

        $dbConfig = $this->databaseConfig($validated);

        try {
            $this->database->assertReady($dbConfig);
        } catch (Throwable $e) {
            return $this->errorResponse(
                $request,
                $this->database->errorMessage($e, $dbConfig),
            );
        }

        try {
            $this->installer->install($dbConfig, [
                'username' => (string) $validated['admin_username'],
                'email' => (string) $validated['admin_email'],
                'password' => (string) $validated['admin_password'],
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($request, env('APP_DEBUG', false, true)
                ? $e->getMessage()
                : 'Installation failed. Please verify the entered data and try again.');
        }

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

        $dbConfig = $this->databaseConfig($validated);

        try {
            $this->database->assertReady($dbConfig);
        } catch (Throwable $e) {
            return $this->errorResponse(
                $request,
                $this->database->errorMessage($e, $dbConfig),
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

    private function validAdminUsername(string $username): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{2,59}$/', $username) === 1;
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
