<?php

use Codemonster\View\View;
use Codemonster\Annabel\Http\Response;
use PHPUnit\Framework\TestCase;

class ViewHelperTest extends TestCase
{
    public function test_view_returns_instance_or_response(): void
    {
        $this->assertInstanceOf(View::class, view());

        $tmpDir = sys_get_temp_dir() . '/annabel_test_views';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $file = $tmpDir . '/home.php';
        file_put_contents($file, "<p><?= htmlspecialchars(\$msg) ?></p>");

        $view = view();
        $view->getLocator()->addPath($tmpDir);

        $response = view('home', ['msg' => 'hi']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('hi', $response->getContent());
    }
}
