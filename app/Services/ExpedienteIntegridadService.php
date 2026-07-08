<?php

namespace App\Services;

use App\Models\DocumentoAlumno;
use App\Models\DocumentoPersonal;
use App\Models\Inscripcion;
use App\Models\Persona;
use Illuminate\Support\Collection;

class ExpedienteIntegridadService
{
    public function incidencias(bool $incluirPersonal = true, bool $incluirAlumnos = true, bool $incluirFotos = true): Collection
    {
        $incidencias = collect();

        if ($incluirPersonal) {
            DocumentoPersonal::query()
                ->with(['persona:id,titulo,nombre,apellido_paterno,apellido_materno', 'tipoDocumento:id,nombre'])
                ->orderBy('id')
                ->chunkById(200, function (Collection $documentos) use ($incidencias): void {
                    foreach ($documentos as $documento) {
                        if ($documento->archivo_existe) {
                            continue;
                        }

                        $incidencias->push([
                            'origen' => 'Personal',
                            'categoria' => 'Documento',
                            'modelo' => DocumentoPersonal::class,
                            'registro_id' => $documento->id,
                            'responsable' => $this->nombrePersona($documento->persona),
                            'detalle' => $documento->tipoDocumento?->nombre ?? 'Documento del personal',
                            'estado' => $documento->estado,
                            'disco' => $documento->disco ?: '—',
                            'ruta' => $documento->ruta ?: '—',
                            'puede_marcar_pendiente' => in_array($documento->estado, ['recibido', 'validado'], true),
                        ]);
                    }
                });

            if ($incluirFotos) {
                Persona::query()->select(['id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno', 'foto'])
                    ->whereNotNull('foto')
                    ->where('foto', '!=', '')
                    ->orderBy('id')
                    ->chunkById(200, function (Collection $personas) use ($incidencias): void {
                        foreach ($personas as $persona) {
                            if ($persona->foto_existe) {
                                continue;
                            }

                            $incidencias->push([
                                'origen' => 'Personal',
                                'categoria' => 'Fotografía',
                                'modelo' => Persona::class,
                                'registro_id' => $persona->id,
                                'responsable' => $this->nombrePersona($persona),
                                'detalle' => 'Fotografía del personal',
                                'estado' => 'requiere volver a cargar',
                                'disco' => (string) config('filesystems.fotos_disk', 'public'),
                                'ruta' => $persona->foto_ruta ?: '—',
                                'puede_marcar_pendiente' => false,
                            ]);
                        }
                    });
            }
        }

        if ($incluirAlumnos) {
            DocumentoAlumno::query()
                ->with(['inscripcion:id,nombre,apellido_paterno,apellido_materno,matricula', 'tipoDocumento:id,nombre'])
                ->orderBy('id')
                ->chunkById(200, function (Collection $documentos) use ($incidencias): void {
                    foreach ($documentos as $documento) {
                        if ($documento->archivo_existe) {
                            continue;
                        }

                        $incidencias->push([
                            'origen' => 'Alumno',
                            'categoria' => 'Documento',
                            'modelo' => DocumentoAlumno::class,
                            'registro_id' => $documento->id,
                            'responsable' => $this->nombreAlumno($documento->inscripcion),
                            'detalle' => $documento->tipoDocumento?->nombre ?? 'Documento del alumno',
                            'estado' => $documento->estado,
                            'disco' => $documento->disco ?: '—',
                            'ruta' => $documento->ruta ?: '—',
                            'puede_marcar_pendiente' => in_array($documento->estado, ['recibido', 'validado', 'emitida'], true),
                        ]);
                    }
                });

            if ($incluirFotos) {
                Inscripcion::withTrashed()
                    ->select(['id', 'nombre', 'apellido_paterno', 'apellido_materno', 'matricula', 'foto_path'])
                    ->whereNotNull('foto_path')
                    ->where('foto_path', '!=', '')
                    ->orderBy('id')
                    ->chunkById(200, function (Collection $alumnos) use ($incidencias): void {
                        foreach ($alumnos as $alumno) {
                            if ($alumno->foto_existe) {
                                continue;
                            }

                            $incidencias->push([
                                'origen' => 'Alumno',
                                'categoria' => 'Fotografía',
                                'modelo' => Inscripcion::class,
                                'registro_id' => $alumno->id,
                                'responsable' => $this->nombreAlumno($alumno),
                                'detalle' => 'Fotografía del alumno',
                                'estado' => 'requiere volver a cargar',
                                'disco' => (string) config('filesystems.fotos_disk', 'public'),
                                'ruta' => $alumno->foto_ruta ?: '—',
                                'puede_marcar_pendiente' => false,
                            ]);
                        }
                    });
            }
        }

        return $incidencias->sortBy([
            ['origen', 'asc'],
            ['responsable', 'asc'],
            ['categoria', 'asc'],
        ])->values();
    }

    public function marcarDocumentosFaltantesPendientes(): int
    {
        $actualizados = 0;

        DocumentoPersonal::query()
            ->whereIn('estado', ['recibido', 'validado'])
            ->chunkById(200, function (Collection $documentos) use (&$actualizados): void {
                foreach ($documentos as $documento) {
                    if ($documento->archivo_existe) {
                        continue;
                    }

                    $documento->forceFill([
                        'estado' => 'pendiente',
                        'validado_por' => null,
                        'validado_at' => null,
                    ])->save();
                    $actualizados++;
                }
            });

        DocumentoAlumno::query()
            ->whereIn('estado', ['recibido', 'validado', 'emitida'])
            ->chunkById(200, function (Collection $documentos) use (&$actualizados): void {
                foreach ($documentos as $documento) {
                    if ($documento->archivo_existe) {
                        continue;
                    }

                    $documento->forceFill([
                        'estado' => 'pendiente',
                        'validado_por' => null,
                        'validado_at' => null,
                    ])->save();
                    $actualizados++;
                }
            });

        return $actualizados;
    }

    private function nombrePersona(?Persona $persona): string
    {
        if (! $persona) {
            return 'Personal no disponible';
        }

        return trim(implode(' ', array_filter([
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ]))) ?: 'Personal no disponible';
    }

    private function nombreAlumno(?Inscripcion $alumno): string
    {
        if (! $alumno) {
            return 'Alumno no disponible';
        }

        $nombre = trim(implode(' ', array_filter([
            $alumno->nombre,
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
        ])));

        return trim(($alumno->matricula ? $alumno->matricula . ' · ' : '') . ($nombre ?: 'Alumno no disponible'));
    }
}
