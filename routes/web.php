<?php

use App\Http\Controllers\CierreGeneracionReporteController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/register', function () {
    return redirect()->route('login');
})->name('register.disabled');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'active', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

Route::middleware(['auth', 'active', 'route.permission'])->group(base_path('routes/misrutas.php'));

/*
|--------------------------------------------------------------------------
| Rutas de respaldo para reportes del cierre de generación
|--------------------------------------------------------------------------
|
| Estas rutas normalmente se registran desde routes/misrutas.php. El bloque
| de respaldo evita un RouteNotFoundException cuando una instalación conserva
| una copia anterior de ese archivo o cuando la caché de rutas quedó desfasada.
|
*/
Route::middleware(['auth', 'active', 'route.permission'])->group(function (): void {
    if (! Route::has('generales.cierre-generacion.reporte')) {
        Route::get(
            '/generales/cierre-generacion/{proceso}/reporte/{formato}',
            [CierreGeneracionReporteController::class, 'reporte']
        )
            ->whereIn('formato', ['pdf', 'excel'])
            ->name('generales.cierre-generacion.reporte');
    }

    if (! Route::has('generales.cierre-generacion.comprobante')) {
        Route::get(
            '/generales/cierre-generacion/{proceso}/detalle/{detalle}/comprobante',
            [CierreGeneracionReporteController::class, 'comprobante']
        )
            ->name('generales.cierre-generacion.comprobante');
    }
});
