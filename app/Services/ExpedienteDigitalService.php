<?php

namespace App\Services;

use App\Models\DocumentoAlumno;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\TipoDocumento;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExpedienteDigitalService
{
    private ?Collection $tipos = null;

    private ?Collection $niveles = null;

    public function tiposActivos(): Collection
    {
        return $this->tipos ??= TipoDocumento::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    public function niveles(): Collection
    {
        return $this->niveles ??= Nivel::query()
            ->select('id', 'nombre', 'slug', 'color')
            ->orderBy('id')
            ->get();
    }

    public function resumen(Inscripcion $alumno): array
    {
        $alumno->loadMissing([
            'nivel:id,nombre,slug,color',
            'documentos.tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden',
            'documentos.nivel:id,nombre,slug,color',
            'documentos.grado:id,nombre,orden',
            'documentos.cicloEscolar:id,inicio_anio,fin_anio',
            'trayectoriasAcademicas.nivel:id,nombre,slug,color',
            'trayectoriasAcademicas.grado:id,nombre,orden',
            'trayectoriasAcademicas.cicloEscolar:id,inicio_anio,fin_anio',
        ]);

        $documentosActuales = $alumno->documentos
            ->filter(fn(DocumentoAlumno $documento) => $documento->es_actual);

        $documentosDisponibles = $documentosActuales
            ->reject(fn(DocumentoAlumno $documento) => in_array($documento->estado, ['pendiente', 'rechazado', 'reemplazado', 'cancelada'], true));

        $items = collect();

        foreach ($this->tiposActivos()->where('es_general', true) as $tipo) {
            $documentoActual = $documentosActuales
                ->where('tipo_documento_id', $tipo->id)
                ->sortByDesc('version')
                ->first();

            $documentoDisponible = $documentosDisponibles
                ->where('tipo_documento_id', $tipo->id)
                ->sortByDesc('version')
                ->first();

            $items->push([
                'tipo_id' => $tipo->id,
                'nivel_id' => null,
                'clave' => $tipo->slug,
                'nombre' => $tipo->nombre,
                'etiqueta' => $tipo->nombre,
                'presente' => (bool) $documentoDisponible,
                'estado' => $documentoActual?->estado ?? 'pendiente',
                'documento_id' => $documentoActual?->id,
            ]);
        }

        $nivelCertificado = $this->nivelCertificadoRequerido($alumno->nivel);
        $tipoCertificado = $this->tiposActivos()->firstWhere('slug', 'certificado-estudios');

        if ($nivelCertificado && $tipoCertificado) {
            $documentoActual = $documentosActuales
                ->where('tipo_documento_id', $tipoCertificado->id)
                ->where('nivel_id', $nivelCertificado->id)
                ->sortByDesc('version')
                ->first();

            $documentoDisponible = $documentosDisponibles
                ->where('tipo_documento_id', $tipoCertificado->id)
                ->where('nivel_id', $nivelCertificado->id)
                ->sortByDesc('version')
                ->first();

            $items->push([
                'tipo_id' => $tipoCertificado->id,
                'nivel_id' => $nivelCertificado->id,
                'clave' => 'certificado-estudios',
                'nombre' => $tipoCertificado->nombre,
                'etiqueta' => 'Certificado de ' . Str::lower($nivelCertificado->nombre),
                'presente' => (bool) $documentoDisponible,
                'estado' => $documentoActual?->estado ?? 'pendiente',
                'documento_id' => $documentoActual?->id,
            ]);
        }

        $tipoBoleta = $this->tiposActivos()->firstWhere('slug', 'boleta-final-grado');

        if ($tipoBoleta) {
            $ultimaTrayectoriaId = $alumno->trayectoriasAcademicas->max('id');

            $trayectoriasConBoleta = $alumno->trayectoriasAcademicas
                ->filter(function ($trayectoria) use ($ultimaTrayectoriaId) {
                    $gradoConcluido = (bool) $trayectoria->promovido
                        || ((int) $trayectoria->id !== (int) $ultimaTrayectoriaId && !$trayectoria->activo);

                    if (!$gradoConcluido || !$trayectoria->nivel || !$trayectoria->grado) {
                        return false;
                    }

                    $textoNivel = Str::lower(trim(
                        ($trayectoria->nivel->slug ?? '') . ' ' .
                        ($trayectoria->nivel->nombre ?? '')
                    ));

                    return Str::contains($textoNivel, ['primaria', 'secundaria']);
                })
                ->sortBy(fn($trayectoria) => sprintf('%06d-%06d', $trayectoria->ciclo_escolar_id ?? 0, $trayectoria->grado?->orden ?? 0));

            foreach ($trayectoriasConBoleta as $trayectoria) {
                $documentoActual = $documentosActuales
                    ->where('tipo_documento_id', $tipoBoleta->id)
                    ->where('nivel_id', $trayectoria->nivel_id)
                    ->where('grado_id', $trayectoria->grado_id)
                    ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
                    ->sortByDesc('version')
                    ->first();

                $documentoDisponible = $documentosDisponibles
                    ->where('tipo_documento_id', $tipoBoleta->id)
                    ->where('nivel_id', $trayectoria->nivel_id)
                    ->where('grado_id', $trayectoria->grado_id)
                    ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
                    ->sortByDesc('version')
                    ->first();

                $ciclo = $trayectoria->cicloEscolar
                    ? $trayectoria->cicloEscolar->inicio_anio . '-' . $trayectoria->cicloEscolar->fin_anio
                    : 'Sin ciclo';

                $items->push([
                    'tipo_id' => $tipoBoleta->id,
                    'nivel_id' => $trayectoria->nivel_id,
                    'grado_id' => $trayectoria->grado_id,
                    'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
                    'clave' => 'boleta-final-grado',
                    'nombre' => $tipoBoleta->nombre,
                    'etiqueta' => 'Boleta final · ' . $trayectoria->nivel->nombre . ' · ' . $trayectoria->grado->nombre . ' · ' . $ciclo,
                    'presente' => (bool) $documentoDisponible,
                    'estado' => $documentoActual?->estado ?? 'pendiente',
                    'documento_id' => $documentoActual?->id,
                ]);
            }
        }

        $total = $items->count();
        $completados = $items->where('presente', true)->count();

        return [
            'total' => $total,
            'completados' => $completados,
            'pendientes' => max($total - $completados, 0),
            'porcentaje' => $total > 0 ? (int) floor(($completados / $total) * 100) : 100,
            'completo' => $total === 0 || $completados === $total,
            'items' => $items->values()->all(),
            'nivel_certificado_requerido' => $nivelCertificado ? [
                'id' => $nivelCertificado->id,
                'nombre' => $nivelCertificado->nombre,
            ] : null,
        ];
    }

    public function nivelCertificadoRequerido(?Nivel $nivelActual): ?Nivel
    {
        if (!$nivelActual) {
            return null;
        }

        $texto = Str::lower(trim($nivelActual->slug . ' ' . $nivelActual->nombre));

        $nivelAnterior = match (true) {
            Str::contains($texto, 'bachillerato') => 'secundaria',
            Str::contains($texto, 'secundaria') => 'primaria',
            Str::contains($texto, 'primaria') => 'preescolar',
            default => null,
        };

        if (!$nivelAnterior) {
            return null;
        }

        return $this->niveles()->first(function (Nivel $nivel) use ($nivelAnterior) {
            $textoNivel = Str::lower(trim($nivel->slug . ' ' . $nivel->nombre));

            return Str::contains($textoNivel, $nivelAnterior);
        });
    }

    public function documentosOrdenados(Inscripcion $alumno): Collection
    {
        $alumno->loadMissing([
            'documentos.tipoDocumento:id,nombre,slug,orden',
            'documentos.nivel:id,nombre,slug,color',
            'documentos.grado:id,nombre,orden',
            'documentos.grupo:id,asignacion_grupo_id',
            'documentos.grupo.asignacionGrupo:id,nombre',
            'documentos.cicloEscolar:id,inicio_anio,fin_anio',
            'documentos.usuarioQueSubio:id,name',
            'documentos.usuarioQueValido:id,name',
        ]);

        return $alumno->documentos
            ->sort(function (DocumentoAlumno $a, DocumentoAlumno $b) {
                return (($a->tipoDocumento?->orden ?? 999) <=> ($b->tipoDocumento?->orden ?? 999))
                    ?: (($a->nivel_id ?? 0) <=> ($b->nivel_id ?? 0))
                    ?: (($a->grado_id ?? 0) <=> ($b->grado_id ?? 0))
                    ?: (($a->ciclo_escolar_id ?? 0) <=> ($b->ciclo_escolar_id ?? 0))
                    ?: ($b->version <=> $a->version)
                    ?: ($b->id <=> $a->id);
            })
            ->values();
    }
}
