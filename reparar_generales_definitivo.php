<?php

declare(strict_types=1);

/**
 * REPARACIÓN DEFINITIVA de:
 * resources/views/livewire/accion/generales.blade.php
 *
 * Sustituye completamente la vista por una versión conocida y balanceada.
 * Después Laravel la compila y PHP valida el archivo compilado.
 *
 * Ejecutar desde la raíz:
 *   php reparar_generales_definitivo.php
 */

$root = getcwd();
$target = $root . '/resources/views/livewire/accion/generales.blade.php';
$replacement = __DIR__ . '/generales.blade.php';

if (!is_file($target)) {
    fwrite(STDERR, "ERROR: No existe {$target}\n");
    exit(1);
}

if (!is_file($replacement)) {
    fwrite(STDERR, "ERROR: Coloca generales.blade.php junto a este reparador.\n");
    exit(1);
}

$stamp = date('Ymd_His');
$backup = $target . '.bak-antes-reparacion-definitiva-' . $stamp;

if (!copy($target, $backup)) {
    fwrite(STDERR, "ERROR: No se pudo crear respaldo: {$backup}\n");
    exit(1);
}

$newContent = file_get_contents($replacement);

if ($newContent === false || trim($newContent) === '') {
    fwrite(STDERR, "ERROR: El archivo de reemplazo está vacío.\n");
    exit(1);
}

if (file_put_contents($target, $newContent) === false) {
    fwrite(STDERR, "ERROR: No se pudo escribir {$target}\n");
    exit(1);
}

echo "REEMPLAZADO: resources/views/livewire/accion/generales.blade.php\n";
echo "RESPALDO: {$backup}\n\n";

function run(string $command, string $cwd): array
{
    $output = [];
    $code = 0;

    exec(
        'cd ' . escapeshellarg($cwd) . ' && ' . $command . ' 2>&1',
        $output,
        $code
    );

    return [$code, implode(PHP_EOL, $output)];
}

/*
|--------------------------------------------------------------------------
| 1. Limpiar vistas
|--------------------------------------------------------------------------
*/

[$clearCode, $clearOutput] = run('php artisan view:clear', $root);

if ($clearCode !== 0) {
    copy($backup, $target);
    fwrite(STDERR, "ERROR al limpiar vistas:\n{$clearOutput}\n");
    fwrite(STDERR, "Se restauró el respaldo.\n");
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 2. Compilar TODAS las vistas Laravel
|--------------------------------------------------------------------------
|
| El objetivo es que Blade genere PHP real. Si hay un ParseError de directivas,
| view:cache normalmente lo deja visible durante el proceso.
|
*/

[$cacheCode, $cacheOutput] = run('php artisan view:cache', $root);

if ($cacheCode !== 0) {
    copy($backup, $target);
    run('php artisan view:clear', $root);

    fwrite(STDERR, "ERROR: Laravel no pudo compilar las vistas:\n");
    fwrite(STDERR, $cacheOutput . "\n\n");
    fwrite(STDERR, "Se restauró automáticamente el respaldo.\n");
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 3. Validar específicamente el PHP compilado de esta vista
|--------------------------------------------------------------------------
*/

$validateScript = <<<'PHP'
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$view = resource_path('views/livewire/accion/generales.blade.php');
$compiler = app('blade.compiler');

$compiled = $compiler->getCompiledPath($view);

if (!is_file($compiled)) {
    $compiler->compile($view);
    $compiled = $compiler->getCompiledPath($view);
}

echo $compiled;
PHP;

$tempValidator = $root . '/storage/app/validar_generales_compilado.php';

if (!is_dir(dirname($tempValidator))) {
    mkdir(dirname($tempValidator), 0775, true);
}

file_put_contents(
    $tempValidator,
    "<?php\nchdir(" . var_export($root, true) . ");\n" . $validateScript
);

[$compiledPathCode, $compiledPathOutput] = run(
    'php ' . escapeshellarg($tempValidator),
    $root
);

@unlink($tempValidator);

if ($compiledPathCode !== 0) {
    copy($backup, $target);
    run('php artisan view:clear', $root);

    fwrite(STDERR, "ERROR obteniendo la vista compilada:\n{$compiledPathOutput}\n");
    fwrite(STDERR, "Se restauró automáticamente el respaldo.\n");
    exit(1);
}

$compiledPath = trim($compiledPathOutput);

if ($compiledPath === '' || !is_file($compiledPath)) {
    copy($backup, $target);
    run('php artisan view:clear', $root);

    fwrite(STDERR, "ERROR: No se localizó el PHP compilado de generales.blade.php.\n");
    fwrite(STDERR, "Se restauró automáticamente el respaldo.\n");
    exit(1);
}

[$lintCode, $lintOutput] = run(
    'php -l ' . escapeshellarg($compiledPath),
    $root
);

if ($lintCode !== 0) {
    copy($backup, $target);
    run('php artisan view:clear', $root);

    fwrite(STDERR, "ERROR: El PHP compilado por Blade no es válido:\n");
    fwrite(STDERR, $lintOutput . "\n\n");
    fwrite(STDERR, "Se restauró automáticamente el respaldo.\n");
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 4. Dejar limpio el entorno de desarrollo
|--------------------------------------------------------------------------
*/

run('php artisan view:clear', $root);

echo "VALIDACIÓN BLADE: OK\n";
echo "VALIDACIÓN PHP COMPILADO: OK\n\n";
echo "============================================================\n";
echo "REPARACIÓN COMPLETADA\n";
echo "============================================================\n";
echo "Ahora ejecuta:\n";
echo "  php artisan optimize:clear\n\n";
echo "Después recarga:\n";
echo "  /nivel/primaria/generales\n";

exit(0);
