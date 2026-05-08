<?php

namespace App\Imports;

use App\Models\Persona;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PersonasImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function model(array $row)
    {
        $datos = [
            'titulo' => $this->limpiar($row['titulo'] ?? null),
            'nombre' => $this->limpiar($row['nombre'] ?? null),
            'apellido_paterno' => $this->limpiar($row['apellido_paterno'] ?? null),
            'apellido_materno' => $this->limpiar($row['apellido_materno'] ?? null),
            'foto' => $this->limpiar($row['foto'] ?? null),
            'curp' => $this->mayusculas($row['curp'] ?? null),
            'rfc' => $this->mayusculas($row['rfc'] ?? null),
            'correo' => $this->limpiar($row['correo'] ?? null),
            'telefono_movil' => $this->limpiar($row['telefono_movil'] ?? null),
            'telefono_fijo' => $this->limpiar($row['telefono_fijo'] ?? null),
            'fecha_nacimiento' => $this->fecha($row['fecha_nacimiento'] ?? null),
            'genero' => $this->mayusculas($row['genero'] ?? null),
            'grado_estudios' => $this->limpiar($row['grado_estudios'] ?? null),
            'especialidad' => $this->limpiar($row['especialidad'] ?? null),
            'status' => $this->status($row['status'] ?? 1),
            'calle' => $this->limpiar($row['calle'] ?? null),
            'numero_exterior' => $this->limpiar($row['numero_exterior'] ?? null),
            'numero_interior' => $this->limpiar($row['numero_interior'] ?? null),
            'colonia' => $this->limpiar($row['colonia'] ?? null),
            'municipio' => $this->limpiar($row['municipio'] ?? null),
            'estado' => $this->limpiar($row['estado'] ?? null),
            'codigo_postal' => $this->limpiar($row['codigo_postal'] ?? null),
        ];

        if (empty($datos['nombre']) && empty($datos['curp']) && empty($datos['rfc'])) {
            return null;
        }

        if (!empty($datos['curp'])) {
            return Persona::updateOrCreate(
                ['curp' => $datos['curp']],
                $datos
            );
        }

        if (!empty($datos['rfc'])) {
            return Persona::updateOrCreate(
                ['rfc' => $datos['rfc']],
                $datos
            );
        }

        return new Persona($datos);
    }

    private function limpiar($valor)
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function mayusculas($valor)
    {
        $valor = $this->limpiar($valor);

        return $valor ? Str::upper($valor) : null;
    }

    private function status($valor)
    {
        if ($valor === null || $valor === '') {
            return 1;
        }

        $valor = Str::lower(trim((string) $valor));

        return in_array($valor, ['1', 'activo', 'true', 'si', 'sí'], true) ? 1 : 0;
    }

    private function fecha($valor)
    {
        if (empty($valor)) {
            return null;
        }

        try {
            if (is_numeric($valor)) {
                return Date::excelToDateTimeObject($valor)->format('Y-m-d');
            }

            return Carbon::parse($valor)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
