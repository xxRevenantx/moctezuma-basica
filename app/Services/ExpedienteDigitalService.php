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
        return $this->tipos ??= TipoDocumento::query()->where('activo', true)->orderBy('orden')->orderBy('nombre')->get();
    }

    public function niveles(): Collection
    {
        return $this->niveles ??= Nivel::query()->select('id', 'nombre', 'slug', 'color')->orderBy('id')->get();
    }

    public function resumen(Inscripcion $alumno): array
    {
        $alumno->loadMissing([
            'nivel:id,nombre,slug,color',
            'documentos.tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden',
            'documentos.nivel:id,nombre,slug,color',
            'documentos.grado:id,nombre,orden',
            'documentos.cicloEscolar:id,inicio_anio,fin_anio',
        ]);

        $actuales = $alumno->documentos->where('es_actual', true);
        $disponibles = $actuales
            ->reject(fn (DocumentoAlumno $d) => in_array($d->estado, ['pendiente', 'rechazado', 'reemplazado', 'cancelada'], true))
            ->filter(fn (DocumentoAlumno $d) => $d->archivo_existe);
        $items = collect();

        foreach ($this->tiposActivos()->where('es_general', true) as $tipo) {
            $actual = $actuales->where('tipo_documento_id', $tipo->id)->sortByDesc('version')->first();
            $disponible = $disponibles->where('tipo_documento_id', $tipo->id)->sortByDesc('version')->first();
            $items->push([
                'tipo_id' => $tipo->id,
                'nivel_id' => null,
                'clave' => $tipo->slug,
                'nombre' => $tipo->nombre,
                'etiqueta' => $tipo->nombre,
                'presente' => (bool) $disponible,
                'estado' => $actual?->estado ?? 'pendiente',
                'documento_id' => $actual?->id,
                'archivo_faltante' => (bool) $actual && ! $actual->archivo_existe,
            ]);
        }

        $nivelCertificado = $this->nivelCertificadoRequerido($alumno->nivel);
        $tipoCertificado = $this->tiposActivos()->firstWhere('slug', 'certificado-estudios');
        if ($nivelCertificado && $tipoCertificado) {
            $actual = $actuales->where('tipo_documento_id', $tipoCertificado->id)->where('nivel_id', $nivelCertificado->id)->sortByDesc('version')->first();
            $disponible = $disponibles->where('tipo_documento_id', $tipoCertificado->id)->where('nivel_id', $nivelCertificado->id)->sortByDesc('version')->first();
            $items->push([
                'tipo_id' => $tipoCertificado->id,
                'nivel_id' => $nivelCertificado->id,
                'clave' => 'certificado-estudios',
                'nombre' => $tipoCertificado->nombre,
                'etiqueta' => 'Certificado de ' . Str::lower($nivelCertificado->nombre),
                'presente' => (bool) $disponible,
                'estado' => $actual?->estado ?? 'pendiente',
                'documento_id' => $actual?->id,
                'archivo_faltante' => (bool) $actual && ! $actual->archivo_existe,
            ]);
        }

        // Las boletas históricas se determinan por los documentos realmente archivados,
        // no por una estructura paralela de ubicación escolar.
        $tipoBoleta = $this->tiposActivos()->firstWhere('slug', 'boleta-final-grado');
        if ($tipoBoleta) {
            $actuales->where('tipo_documento_id', $tipoBoleta->id)
                ->groupBy(fn (DocumentoAlumno $d) => ($d->nivel_id ?: 0) . '|' . ($d->grado_id ?: 0) . '|' . ($d->ciclo_escolar_id ?: 0))
                ->each(function (Collection $docs) use ($items, $disponibles, $tipoBoleta): void {
                    $doc = $docs->sortByDesc('version')->first();
                    $disponible = $disponibles->where('tipo_documento_id', $tipoBoleta->id)
                        ->where('nivel_id', $doc->nivel_id)->where('grado_id', $doc->grado_id)
                        ->where('ciclo_escolar_id', $doc->ciclo_escolar_id)->sortByDesc('version')->first();
                    $ciclo = $doc->cicloEscolar ? $doc->cicloEscolar->inicio_anio . '-' . $doc->cicloEscolar->fin_anio : 'Sin ciclo';
                    $items->push([
                        'tipo_id' => $tipoBoleta->id,
                        'nivel_id' => $doc->nivel_id,
                        'grado_id' => $doc->grado_id,
                        'ciclo_escolar_id' => $doc->ciclo_escolar_id,
                        'clave' => 'boleta-final-grado',
                        'nombre' => $tipoBoleta->nombre,
                        'etiqueta' => 'Boleta final · ' . ($doc->nivel?->nombre ?? 'Nivel') . ' · ' . ($doc->grado?->nombre ?? 'Grado') . ' · ' . $ciclo,
                        'presente' => (bool) $disponible,
                        'estado' => $doc->estado ?? 'pendiente',
                        'documento_id' => $doc->id,
                        'archivo_faltante' => ! $doc->archivo_existe,
                    ]);
                });
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
            'archivos_faltantes' => $actuales->reject(fn(DocumentoAlumno $documento) => $documento->archivo_existe)->count(),
            'foto_faltante' => filled($alumno->foto_path) && ! $alumno->foto_existe,
            'sin_foto' => blank($alumno->foto_path),
            'nivel_certificado_requerido' => $nivelCertificado ? ['id' => $nivelCertificado->id, 'nombre' => $nivelCertificado->nombre] : null,
        ];
    }

    public function nivelCertificadoRequerido(?Nivel $nivelActual): ?Nivel
    {
        if (! $nivelActual) return null;
        $texto = Str::lower(trim($nivelActual->slug . ' ' . $nivelActual->nombre));
        $anterior = match (true) {
            Str::contains($texto, 'bachillerato') => 'secundaria',
            Str::contains($texto, 'secundaria') => 'primaria',
            Str::contains($texto, 'primaria') => 'preescolar',
            default => null,
        };
        if (! $anterior) return null;
        return $this->niveles()->first(fn (Nivel $n) => Str::contains(Str::lower(trim($n->slug . ' ' . $n->nombre)), $anterior));
    }

    public function documentosOrdenados(Inscripcion $alumno): Collection
    {
        $alumno->loadMissing([
            'documentos.tipoDocumento:id,nombre,slug,orden', 'documentos.nivel:id,nombre,slug,color',
            'documentos.grado:id,nombre,orden', 'documentos.grupo:id,asignacion_grupo_id',
            'documentos.grupo.asignacionGrupo:id,nombre', 'documentos.cicloEscolar:id,inicio_anio,fin_anio',
            'documentos.usuarioQueSubio:id,name', 'documentos.usuarioQueValido:id,name',
        ]);
        return $alumno->documentos->sort(function (DocumentoAlumno $a, DocumentoAlumno $b) {
            return (($a->tipoDocumento?->orden ?? 999) <=> ($b->tipoDocumento?->orden ?? 999))
                ?: (($a->nivel_id ?? 0) <=> ($b->nivel_id ?? 0))
                ?: (($a->grado_id ?? 0) <=> ($b->grado_id ?? 0))
                ?: (($a->ciclo_escolar_id ?? 0) <=> ($b->ciclo_escolar_id ?? 0))
                ?: ($b->version <=> $a->version) ?: ($b->id <=> $a->id);
        })->values();
    }
}
