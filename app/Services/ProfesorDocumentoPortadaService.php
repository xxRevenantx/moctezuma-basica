<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\LiberacionSueldo;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\ProfesorDocumentoPlantilla;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProfesorDocumentoPortadaService
{
    public function defaultConfiguracion(?Nivel $nivel = null): array
    {
        return [
            'usar_cargo_real' => true,
            'escuela_automatica' => true,
            'escuela_nombre' => null,
            'lugar' => 'Cd. Altamirano, Gro.',
            'campos' => [
                'nombre_cargo' => ['top' => 28.5, 'left' => 9.5, 'width' => 76.0, 'font' => 13.5, 'visible' => true],
                'rfc' => ['top' => 33.7, 'left' => 18.8, 'width' => 62.0, 'font' => 13.0, 'visible' => true],
                'curp' => ['top' => 38.8, 'left' => 15.0, 'width' => 69.0, 'font' => 13.0, 'visible' => true],
                'clave_presupuestal' => ['top' => 44.0, 'left' => 12.2, 'width' => 72.0, 'font' => 13.0, 'visible' => true],
                'telefono' => ['top' => 49.1, 'left' => 17.7, 'width' => 60.0, 'font' => 13.0, 'visible' => true],
                'escuela' => ['top' => 54.1, 'left' => 11.5, 'width' => 77.0, 'font' => 13.0, 'visible' => true],
                'cct' => ['top' => 60.0, 'left' => 17.7, 'width' => 55.0, 'font' => 13.0, 'visible' => true],
                'lugar' => ['top' => 65.5, 'left' => 15.8, 'width' => 60.0, 'font' => 13.0, 'visible' => true],
            ],
        ];
    }

    public function fondoUrl(?ProfesorDocumentoPlantilla $plantilla): ?string
    {
        $ruta = trim((string) ($plantilla?->archivo_path ?? ''));

        if ($ruta === '') {
            return null;
        }

        try {
            return Storage::disk('public')->url($ruta);
        } catch (Throwable) {
            return null;
        }
    }

    public function fondoDataUri(?ProfesorDocumentoPlantilla $plantilla): ?string
    {
        $ruta = trim((string) ($plantilla?->archivo_path ?? ''));

        if ($ruta === '') {
            return null;
        }

        try {
            $contenido = Storage::disk('public')->get($ruta);
            $mime = (string) ($plantilla?->mime_type ?: 'image/png');

            return 'data:' . $mime . ';base64,' . base64_encode($contenido);
        } catch (Throwable) {
            return null;
        }
    }

    public function datosPortada(Persona $persona, Nivel $nivel, ?CicloEscolar $ciclo, mixed $plantilla = null): array
    {
        $config = $this->configuracionNormalizada($plantilla, $nivel);
        $personaNivel = $this->personaNivel($persona, $nivel, $ciclo);
        $liberacion = $this->ultimaLiberacion($persona, $nivel, $ciclo, $personaNivel);
        $cargoReal = $this->cargoReal($personaNivel);
        $cargoLabel = $config['usar_cargo_real']
            ? $this->normalizarEtiquetaCargo($cargoReal)
            : 'PROFESOR(A)';

        return [
            'persona_id' => (int) $persona->id,
            'nombre' => $this->nombrePersona($persona),
            'nombre_corto' => trim(($persona->nombre ?? '') . ' ' . ($persona->apellido_paterno ?? '')),
            'rfc' => $this->valorTexto($persona->rfc, 'S/C.'),
            'curp' => $this->valorTexto($persona->curp, 'S/C.'),
            'telefono' => $this->telefonoPersona($persona),
            'clave_presupuestal' => $this->valorTexto($liberacion?->clave_presupuestal, 'S/C.'),
            'escuela' => $config['escuela_automatica']
                ? $this->nombreEscuela($nivel)
                : $this->valorTexto($config['escuela_nombre'] ?? null, $this->nombreEscuela($nivel)),
            'cct' => $this->valorTexto($nivel->cct, 'S/C.'),
            'lugar' => $this->valorTexto($config['lugar'] ?? null, 'Cd. Altamirano, Gro.'),
            'cargo_real' => $cargoReal,
            'cargo_label' => $cargoLabel,
            'campos' => $config['campos'],
            'configuracion' => $config,
            'alertas' => $this->alertas($persona, $nivel, $ciclo, $liberacion),
        ];
    }

    public function configuracionNormalizada(mixed $plantilla = null, ?Nivel $nivel = null): array
    {
        $base = $this->defaultConfiguracion($nivel);
        $config = null;

        if ($plantilla instanceof ProfesorDocumentoPlantilla) {
            $config = $plantilla->configuracion;
        } elseif (is_array($plantilla)) {
            $config = $plantilla;
        } elseif (is_object($plantilla) && isset($plantilla->configuracion)) {
            $config = $plantilla->configuracion;
        }

        if (!is_array($config)) {
            return $base;
        }

        $camposBase = $base['campos'];
        $camposActuales = is_array($config['campos'] ?? null) ? $config['campos'] : [];

        foreach ($camposBase as $clave => $defaults) {
            $camposBase[$clave] = array_merge($defaults, is_array($camposActuales[$clave] ?? null) ? $camposActuales[$clave] : []);
        }

        return array_merge($base, Arr::except($config, ['campos']), ['campos' => $camposBase]);
    }

    public function nombrePersona(?Persona $persona): string
    {
        return trim(
            ($persona?->titulo ? $persona->titulo . ' ' : '') .
            ($persona->nombre ?? '') . ' ' .
            ($persona->apellido_paterno ?? '') . ' ' .
            ($persona->apellido_materno ?? '')
        );
    }

    public function cargoReal(?PersonaNivel $personaNivel): string
    {
        $cargoDetalle = $personaNivel?->detalles()
            ->with('personaRole.rolePersona:id,nombre')
            ->get()
            ->map(fn($detalle) => $detalle->personaRole?->rolePersona?->nombre)
            ->filter()
            ->first();

        return trim((string) ($cargoDetalle ?: 'Profesor(a)')) ?: 'Profesor(a)';
    }

    public function normalizarEtiquetaCargo(string $cargo): string
    {
        $cargo = trim($cargo);

        if ($cargo === '') {
            return 'PROFESOR(A)';
        }

        return Str::upper($cargo);
    }

    public function nombreEscuela(Nivel $nivel): string
    {
        $nombre = trim((string) $nivel->nombre);
        $upper = Str::upper($nombre);

        return match (true) {
            str_contains($upper, 'PREESCOLAR') => 'Preescolar Centro Universitario Moctezuma',
            str_contains($upper, 'PRIMARIA') => 'Primaria Centro Universitario Moctezuma',
            str_contains($upper, 'SECUNDARIA') => 'Secundaria Centro Universitario Moctezuma',
            str_contains($upper, 'BACHILLERATO') => 'Bachillerato Centro Universitario Moctezuma',
            default => 'Centro Universitario Moctezuma',
        };
    }

    private function telefonoPersona(Persona $persona): string
    {
        $telefono = trim((string) ($persona->telefono_movil ?: $persona->telefono_fijo ?: ''));

        return $telefono !== '' ? $telefono : 'S/C.';
    }

    private function valorTexto(mixed $valor, string $respaldo): string
    {
        $texto = trim((string) $valor);

        return $texto !== '' ? $texto : $respaldo;
    }

    private function personaNivel(Persona $persona, Nivel $nivel, ?CicloEscolar $ciclo): ?PersonaNivel
    {
        return $persona->personaNiveles()
            ->where('nivel_id', $nivel->id)
            ->where('estado', PersonaNivel::ESTADO_ACTIVO)
            ->with([
                'detalles' => function ($detalles) use ($ciclo) {
                    if ($ciclo?->id) {
                        $detalles->vigenteEnCiclo((int) $ciclo->id, false);
                    }

                    $detalles->with('personaRole.rolePersona:id,nombre');
                },
            ])
            ->first();
    }

    private function ultimaLiberacion(Persona $persona, Nivel $nivel, ?CicloEscolar $ciclo, ?PersonaNivel $personaNivel): ?LiberacionSueldo
    {
        return LiberacionSueldo::query()
            ->where('persona_id', $persona->id)
            ->where('nivel_id', $nivel->id)
            ->when($ciclo?->id, fn($q) => $q->where('ciclo_escolar_id', $ciclo->id))
            ->when($personaNivel?->id, fn($q) => $q->where('persona_nivel_id', $personaNivel->id))
            ->latest('fecha_documento')
            ->latest('id')
            ->first();
    }

    private function alertas(Persona $persona, Nivel $nivel, ?CicloEscolar $ciclo, ?LiberacionSueldo $liberacion): array
    {
        $alertas = [];

        if (blank($persona->rfc)) {
            $alertas[] = 'El profesor no tiene RFC registrado.';
        }

        if (blank($persona->curp)) {
            $alertas[] = 'El profesor no tiene CURP registrado.';
        }

        if (blank($persona->telefono_movil) && blank($persona->telefono_fijo)) {
            $alertas[] = 'El profesor no tiene teléfono registrado.';
        }

        if (!$nivel->cct) {
            $alertas[] = 'El nivel seleccionado no tiene C.C.T. configurado.';
        }

        if (!$liberacion) {
            $alertas[] = 'No se encontró clave presupuestal para el ciclo seleccionado; se mostrará S/C.';
        }

        return $alertas;
    }
}
