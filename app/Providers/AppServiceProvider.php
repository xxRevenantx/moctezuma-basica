<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use PhpOffice\PhpWord\Settings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarDirectorioTemporalPhpWord();

        Gate::define('configurar-firmas-documentales', fn ($user): bool => (bool) $user->is_admin);
    }

    /**
     * Fuerza a PHPWord a trabajar dentro de storage y evita que utilice
     * C:\\Windows\\Temp, una ruta que puede quedar bloqueada en Laragon/Windows.
     *
     * Esta configuración es global y protege todos los exportadores Word
     * del proyecto, no solamente Documentos oficiales.
     */
    private function configurarDirectorioTemporalPhpWord(): void
    {
        if (! class_exists(Settings::class)) {
            return;
        }

        $directorio = storage_path('app/temp/phpword');
        File::ensureDirectoryExists($directorio, 0775, true);

        if (! is_dir($directorio) || ! is_writable($directorio)) {
            @chmod($directorio, 0775);
        }

        if (! is_dir($directorio) || ! is_writable($directorio)) {
            return;
        }

        // Algunas partes internas de PHPWord consultan las variables del sistema
        // y otras consultan Settings::getTempDir(). Se configuran ambas.
        putenv('TMP=' . $directorio);
        putenv('TEMP=' . $directorio);
        putenv('TMPDIR=' . $directorio);

        $_ENV['TMP'] = $directorio;
        $_ENV['TEMP'] = $directorio;
        $_ENV['TMPDIR'] = $directorio;
        $_SERVER['TMP'] = $directorio;
        $_SERVER['TEMP'] = $directorio;
        $_SERVER['TMPDIR'] = $directorio;

        Settings::setTempDir($directorio);
    }
}
