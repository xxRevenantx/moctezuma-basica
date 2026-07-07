<?php

namespace App\Console\Commands;

use App\Models\DocumentoAlumno;
use App\Models\DocumentoPersonal;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VerificarArchivosExpedientes extends Command
{
    protected $signature = 'expedientes:verificar-archivos
                            {--personal : Revisar únicamente expedientes del personal}
                            {--alumnos : Revisar únicamente expedientes de alumnos}
                            {--actuales : Revisar solo documentos marcados como versión actual}
                            {--marcar-pendiente : Si falta el archivo, cambia recibido/validado/emitida a pendiente}';

    protected $description = 'Detecta registros documentales cuya ruta ya no existe físicamente en el almacenamiento configurado.';

    public function handle(): int
    {
        $revisarPersonal = (bool) $this->option('personal');
        $revisarAlumnos = (bool) $this->option('alumnos');

        if (! $revisarPersonal && ! $revisarAlumnos) {
            $revisarPersonal = true;
            $revisarAlumnos = true;
        }

        $totalFaltantes = 0;

        if ($revisarPersonal) {
            $totalFaltantes += $this->revisarPersonal();
        }

        if ($revisarAlumnos) {
            $totalFaltantes += $this->revisarAlumnos();
        }

        $this->newLine();

        $totalFaltantes > 0
            ? $this->warn("Se encontraron {$totalFaltantes} registro(s) con archivo faltante.")
            : $this->components->info('Todos los archivos revisados existen correctamente.');

        return self::SUCCESS;
    }

    private function revisarPersonal(): int
    {
        $this->newLine();
        $this->line('<fg=cyan>Revisando expedientes del personal...</>');

        $query = DocumentoPersonal::query()
            ->with([
                'persona:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tipoDocumento:id,nombre,slug',
            ])
            ->orderBy('persona_id')
            ->orderBy('tipo_documento_personal_id')
            ->orderBy('id');

        return $this->revisarConsulta($query, 'personal');
    }

    private function revisarAlumnos(): int
    {
        $this->newLine();
        $this->line('<fg=cyan>Revisando expedientes de alumnos...</>');

        $query = DocumentoAlumno::query()
            ->with([
                'inscripcion:id,nombre,apellido_paterno,apellido_materno,matricula',
                'tipoDocumento:id,nombre,slug',
            ])
            ->orderBy('inscripcion_id')
            ->orderBy('tipo_documento_id')
            ->orderBy('id');

        return $this->revisarConsulta($query, 'alumno');
    }

    private function revisarConsulta(Builder $query, string $origen): int
    {
        if ($this->option('actuales')) {
            $query->where('es_actual', true);
        }

        $faltantes = collect();
        $revisados = 0;

        $query->chunkById(200, function (Collection $documentos) use (&$faltantes, &$revisados, $origen): void {
            foreach ($documentos as $documento) {
                $revisados++;

                if ($documento->archivo_existe) {
                    continue;
                }

                $faltantes->push([
                    'id' => $documento->id,
                    'origen' => $origen,
                    'persona' => $this->nombreResponsable($documento, $origen),
                    'tipo' => $documento->tipoDocumento?->nombre ?? 'Documento',
                    'estado' => $documento->estado,
                    'disco' => $documento->disco ?: '—',
                    'ruta' => $documento->ruta ?: '—',
                ]);

                if ($this->option('marcar-pendiente') && in_array($documento->estado, ['recibido', 'validado', 'emitida'], true)) {
                    $documento->forceFill([
                        'estado' => 'pendiente',
                        'validado_por' => null,
                        'validado_at' => null,
                    ])->save();
                }
            }
        });

        $this->line("Registros revisados: {$revisados}");

        if ($faltantes->isEmpty()) {
            $this->components->info('Sin archivos faltantes.');

            return 0;
        }

        $this->table(
            ['ID', 'Origen', 'Persona/Alumno', 'Tipo', 'Estado BD', 'Disco', 'Ruta'],
            $faltantes->map(fn(array $fila) => [
                $fila['id'],
                $fila['origen'],
                $fila['persona'],
                $fila['tipo'],
                $fila['estado'],
                $fila['disco'],
                $fila['ruta'],
            ])->all()
        );

        if ($this->option('marcar-pendiente')) {
            $this->warn('Los documentos faltantes con estado recibido/validado/emitida fueron marcados como pendiente.');
        }

        return $faltantes->count();
    }

    private function nombreResponsable($documento, string $origen): string
    {
        if ($origen === 'personal') {
            $persona = $documento->persona;

            return trim(implode(' ', array_filter([
                $persona?->titulo,
                $persona?->nombre,
                $persona?->apellido_paterno,
                $persona?->apellido_materno,
            ]))) ?: 'Personal no disponible';
        }

        $alumno = $documento->inscripcion;
        $nombre = trim(implode(' ', array_filter([
            $alumno?->nombre,
            $alumno?->apellido_paterno,
            $alumno?->apellido_materno,
        ])));

        return trim(($alumno?->matricula ? $alumno->matricula . ' · ' : '') . ($nombre ?: 'Alumno no disponible'));
    }
}
