<?php

namespace App\Jobs;

use App\Models\OrganizacionDocumentoAlumno;
use App\Services\Expedientes\OrganizadorExpedienteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ConfirmarOrganizacionExpedienteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 1800;

    public function __construct(
        public int $organizacionId,
        public ?int $usuarioId = null
    ) {
        $this->onQueue('documentos');
    }

    public function handle(OrganizadorExpedienteService $service): void
    {
        $organizacion = OrganizacionDocumentoAlumno::query()->findOrFail($this->organizacionId);
        $service->procesarConfirmacion($organizacion, $this->usuarioId);
    }

    public function failed(Throwable $exception): void
    {
        OrganizacionDocumentoAlumno::query()
            ->whereKey($this->organizacionId)
            ->update([
                'estado' => 'error',
                'error' => mb_substr($exception->getMessage(), 0, 4000),
                'updated_at' => now(),
            ]);
    }
}
