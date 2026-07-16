<?php

namespace App\Providers;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\Constancia;
use App\Models\DocumentoAlumno;
use App\Models\DocumentoPersonal;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Oficio;
use App\Models\Persona;
use App\Models\Tutor;
use App\Models\User;
use App\Observers\SystemAuditObserver;
use App\Services\DocumentConfigurationService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        Gate::before(fn (User $user): ?bool => $user->is_admin ? true : null);

        Gate::define('configurar-firmas-documentales',
            fn (User $user): bool => $user->canAccess('configuracion.gestionar')
        );

        foreach ([
            Inscripcion::class, Tutor::class, Persona::class, Grupo::class, Materia::class,
            AsignacionMateria::class, Horario::class, Calificacion::class,
            DocumentoAlumno::class, DocumentoPersonal::class, Constancia::class, Oficio::class,
            User::class,
        ] as $model) {
            $model::observe(SystemAuditObserver::class);
        }

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['ultimo_acceso_at' => now()])->saveQuietly();
            }
        });


        View::composer(['PDF.*', 'pdf.*'], function ($view): void {
            $view->with('documentConfiguration', app(DocumentConfigurationService::class)->get());
        });
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
