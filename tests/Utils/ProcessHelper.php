<?php

namespace Annabel\Tests\Utils;

class ProcessHelper
{
    public static function runPhpCode(string $code): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'phpunit_');
        $autoload = getcwd() . '/vendor/autoload.php';

        file_put_contents(
            $tmpFile,
            "<?php\nrequire '" . addslashes($autoload) . "';\n" . $code
        );

        $descriptorSpec = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open("php $tmpFile", $descriptorSpec, $pipes);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        unlink($tmpFile);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
        ];
    }
}
