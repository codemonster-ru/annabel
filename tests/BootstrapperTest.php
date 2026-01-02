<?php

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Bootstrap\Bootstrapper;
use PHPUnit\Framework\TestCase;

class BootstrapperTest extends TestCase
{
    public function test_resolve_class_from_file_throws_on_multiple_classes()
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/..', null, false);
        $bootstrapper = new TestBootstrapper($app);

        $file = tempnam(sys_get_temp_dir(), 'annabel-provider-');
        $php = "<?php\nnamespace TestProvider;\nclass First {}\nclass Second {}\n";

        file_put_contents($file, $php);

        $this->expectException(RuntimeException::class);

        try {
            $bootstrapper->exposeResolve($file);
        } finally {
            @unlink($file);

            Application::resetInstance();
        }
    }
}

class TestBootstrapper extends Bootstrapper
{
    public function exposeResolve(string $file): ?string
    {
        return $this->resolveClassFromFile($file);
    }
}
