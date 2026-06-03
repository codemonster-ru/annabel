<?php

use Codemonster\Annabel\Application;

$baseDir = __DIR__ . '/..';

$app = new Application($baseDir);

require "$baseDir/routes/web.php";

return $app;
