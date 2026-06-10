<?php

namespace Codemonster\Ssr;

class ProcessHelper
{
    /**
     * @param list<string> $args
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    public static function run(string $command, array $args = [], ?string $input = null, ?string $cwd = null): array
    {
        $cmd = escapeshellcmd($command) . ' ' . implode(' ', array_map('escapeshellarg', $args));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        if ($cwd) {
            $cwd = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cwd);
            $cwd = rtrim($cwd, DIRECTORY_SEPARATOR);
            $cwd = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '+#', DIRECTORY_SEPARATOR, $cwd);
        }

        $process = proc_open($cmd, $descriptors, $pipes, $cwd ?: null);

        if (!is_resource($process)) {
            return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Failed to start process'];
        }

        if ($input !== null) {
            fwrite($pipes[0], $input);
        }
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return compact('exitCode', 'stdout', 'stderr');
    }
}
