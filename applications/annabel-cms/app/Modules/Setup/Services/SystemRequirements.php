<?php

namespace Codemonster\Cms\Modules\Setup\Services;

class SystemRequirements
{
    private const GROUP_SERVER = 'Server';
    private const GROUP_PHP_MODULES = 'PHP modules';
    private const GROUP_PHP_CONFIGURATION = 'PHP configuration';
    private const GROUP_FILES = 'Files and folders';
    private const SEVERITY_REQUIRED = 'required';
    private const SEVERITY_RECOMMENDED = 'recommended';

    public function __construct(
        private string $basePath,
    ) {
    }

    /**
     * @return array{passed: bool, checks: list<array{id: string, group: string, label: string, expected: string, actual: string, passed: bool, severity: string, documentationUrl?: string, path?: string}>}
     */
    public function report(): array
    {
        $checks = array_merge(
            $this->phpChecks(),
            $this->extensionChecks(),
            $this->phpConfigurationChecks(),
            $this->filesystemChecks(),
        );

        return [
            'passed' => $this->passed($checks),
            'checks' => $checks,
        ];
    }

    /**
     * @return list<array{id: string, group: string, label: string, expected: string, actual: string, passed: bool, severity: string}>
     */
    private function phpChecks(): array
    {
        return [
            [
                'id' => 'php_version',
                'group' => self::GROUP_SERVER,
                'label' => 'PHP version',
                'expected' => 'PHP 8.2 or newer',
                'actual' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'severity' => self::SEVERITY_REQUIRED,
            ],
        ];
    }

    /**
     * @return list<array{id: string, group: string, label: string, expected: string, actual: string, passed: bool, severity: string, documentationUrl: string}>
     */
    private function extensionChecks(): array
    {
        $extensions = [
            'ctype' => [
                'label' => 'Character type checks',
                'documentationUrl' => 'https://www.php.net/manual/en/book.ctype.php',
            ],
            'json' => [
                'label' => 'JSON support',
                'documentationUrl' => 'https://www.php.net/manual/en/book.json.php',
            ],
            'mbstring' => [
                'label' => 'Multibyte string support',
                'documentationUrl' => 'https://www.php.net/manual/en/book.mbstring.php',
            ],
            'openssl' => [
                'label' => 'Encryption support',
                'documentationUrl' => 'https://www.php.net/manual/en/book.openssl.php',
            ],
            'pdo' => [
                'label' => 'PDO database support',
                'documentationUrl' => 'https://www.php.net/manual/en/book.pdo.php',
            ],
            'pdo_mysql' => [
                'label' => 'MySQL PDO driver',
                'documentationUrl' => 'https://www.php.net/manual/en/ref.pdo-mysql.php',
            ],
        ];

        $checks = [];

        foreach ($extensions as $extension => $metadata) {
            $loaded = extension_loaded($extension);

            $checks[] = [
                'id' => 'extension_' . $extension,
                'group' => self::GROUP_PHP_MODULES,
                'label' => $metadata['label'],
                'documentationUrl' => $metadata['documentationUrl'],
                'expected' => 'Installed',
                'actual' => $loaded ? 'Installed' : 'Not installed',
                'passed' => $loaded,
                'severity' => self::SEVERITY_REQUIRED,
            ];
        }

        return $checks;
    }

    /**
     * @return list<array{id: string, group: string, label: string, expected: string, actual: string, passed: bool, severity: string}>
     */
    private function phpConfigurationChecks(): array
    {
        return [
            [
                'id' => 'php_memory_limit',
                'group' => self::GROUP_PHP_CONFIGURATION,
                'label' => 'Memory limit',
                'expected' => '128M or more',
                'actual' => $this->iniBytesActual('memory_limit'),
                'passed' => $this->iniBytesAtLeast('memory_limit', 128 * 1024 * 1024),
                'severity' => self::SEVERITY_RECOMMENDED,
            ],
            [
                'id' => 'php_file_uploads',
                'group' => self::GROUP_PHP_CONFIGURATION,
                'label' => 'File uploads',
                'expected' => 'Enabled',
                'actual' => $this->iniBooleanActual('file_uploads'),
                'passed' => $this->iniBoolean('file_uploads'),
                'severity' => self::SEVERITY_RECOMMENDED,
            ],
            [
                'id' => 'php_upload_max_filesize',
                'group' => self::GROUP_PHP_CONFIGURATION,
                'label' => 'Upload max filesize',
                'expected' => '8M or more',
                'actual' => $this->iniBytesActual('upload_max_filesize'),
                'passed' => $this->iniBytesAtLeast('upload_max_filesize', 8 * 1024 * 1024),
                'severity' => self::SEVERITY_RECOMMENDED,
            ],
            [
                'id' => 'php_post_max_size',
                'group' => self::GROUP_PHP_CONFIGURATION,
                'label' => 'Post max size',
                'expected' => '8M or more',
                'actual' => $this->iniBytesActual('post_max_size'),
                'passed' => $this->iniBytesAtLeast('post_max_size', 8 * 1024 * 1024),
                'severity' => self::SEVERITY_RECOMMENDED,
            ],
            [
                'id' => 'php_display_errors',
                'group' => self::GROUP_PHP_CONFIGURATION,
                'label' => 'Display errors',
                'expected' => 'Disabled',
                'actual' => $this->iniBooleanActual('display_errors'),
                'passed' => !$this->iniBoolean('display_errors'),
                'severity' => self::SEVERITY_RECOMMENDED,
            ],
            [
                'id' => 'php_log_errors',
                'group' => self::GROUP_PHP_CONFIGURATION,
                'label' => 'Log errors',
                'expected' => 'Enabled',
                'actual' => $this->iniBooleanActual('log_errors'),
                'passed' => $this->iniBoolean('log_errors'),
                'severity' => self::SEVERITY_RECOMMENDED,
            ],
        ];
    }

    /**
     * @return list<array{id: string, group: string, label: string, expected: string, actual: string, passed: bool, severity: string, path: string}>
     */
    private function filesystemChecks(): array
    {
        return [
            $this->accessiblePathCheck('env_file', 'Environment file', $this->basePath . '/.env'),
            $this->accessiblePathCheck('storage', 'Storage directory', $this->basePath . '/storage'),
            $this->accessiblePathCheck('storage_sessions', 'Session storage', $this->basePath . '/storage/sessions'),
            $this->accessiblePathCheck('setup_state', 'Installation state storage', $this->basePath . '/storage/app/setup'),
            $this->accessiblePathCheck('bootstrap_cache', 'Bootstrap cache directory', $this->basePath . '/bootstrap/cache'),
        ];
    }

    /**
     * @return array{id: string, group: string, label: string, expected: string, actual: string, passed: bool, severity: string, path: string}
     */
    private function accessiblePathCheck(string $id, string $label, string $path): array
    {
        $target = $this->accessibleTarget($path);
        $passed = is_readable($target) && is_writable($target);

        return [
            'id' => 'accessible_' . $id,
            'group' => self::GROUP_FILES,
            'label' => $label,
            'path' => $this->relativePath($path),
            'expected' => 'Readable and writable',
            'actual' => $passed ? 'Readable and writable' : 'Not readable and writable',
            'passed' => $passed,
            'severity' => self::SEVERITY_REQUIRED,
        ];
    }

    private function iniBoolean(string $option): bool
    {
        $value = ini_get($option);

        if ($value === false) {
            return false;
        }

        return !in_array(strtolower(trim($value)), ['', '0', 'off', 'false', 'no'], true);
    }

    private function iniBooleanActual(string $option): string
    {
        return $this->iniBoolean($option) ? 'Enabled' : 'Disabled';
    }

    private function iniBytesActual(string $option): string
    {
        $value = ini_get($option);

        if ($value === false || trim($value) === '') {
            return 'Unknown';
        }

        return trim($value) === '-1' ? 'Unlimited' : trim($value);
    }

    private function iniBytesAtLeast(string $option, int $minimum): bool
    {
        $bytes = $this->iniBytes(ini_get($option));

        return $bytes === -1 || ($bytes !== null && $bytes >= $minimum);
    }

    private function iniBytes(string|false $value): ?int
    {
        if ($value === false) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($value === '-1') {
            return -1;
        }

        if (!preg_match('/^(\d+)([kmg])?$/i', $value, $matches)) {
            return null;
        }

        $bytes = (int) $matches[1];
        $unit = strtolower($matches[2] ?? '');

        return match ($unit) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };
    }

    private function accessibleTarget(string $path): string
    {
        $target = $path;

        while (!file_exists($target) && dirname($target) !== $target) {
            $target = dirname($target);
        }

        return $target;
    }

    private function relativePath(string $path): string
    {
        if (str_starts_with($path, $this->basePath . '/')) {
            return substr($path, strlen($this->basePath) + 1);
        }

        return $path;
    }

    /**
     * @param list<array{passed: bool, severity: string}> $checks
     */
    private function passed(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['severity'] === self::SEVERITY_REQUIRED && !$check['passed']) {
                return false;
            }
        }

        return true;
    }
}
