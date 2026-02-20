<?php
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$path = $root . '/resources/views/ne-kazanirim/index.blade.php';
$compiler = app('blade.compiler');
$compiled = $compiler->compileString(file_get_contents($path));
file_put_contents($root . '/tmp/ne_kazanirim_compiled_check.php', $compiled);
echo "compiled\n";
