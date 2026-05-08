<?php

namespace App\Exports;

use App\Models\Persona;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PersonasExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        public string $search = ''
    ) {
    }

    public function query()
    {
        return Persona::query()
            ->where(function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('titulo', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                    ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ['%' . $this->search . '%'])
                    ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno) LIKE ?", ['%' . $this->search . '%'])
                    ->orWhere('telefono_movil', 'like', '%' . $this->search . '%')
                    ->orWhere('correo', 'like', '%' . $this->search . '%')
                    ->orWhere('curp', 'like', '%' . $this->search . '%')
                    ->orWhere('rfc', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nombre', 'asc')
            ->orderBy('apellido_paterno', 'asc')
            ->orderBy('apellido_materno', 'asc');
    }

    public function headings(): array
    {
        return [
            'id',
            'titulo',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'foto',
            'curp',
            'rfc',
            'correo',
            'telefono_movil',
            'telefono_fijo',
            'fecha_nacimiento',
            'genero',
            'grado_estudios',
            'especialidad',
            'status',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'municipio',
            'estado',
            'codigo_postal',
        ];
    }

    public function map($persona): array
    {
        return [
            $persona->id,
            $persona->titulo,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
            $persona->foto,
            $persona->curp,
            $persona->rfc,
            $persona->correo,
            $persona->telefono_movil,
            $persona->telefono_fijo,
            optional($persona->fecha_nacimiento)->format('Y-m-d'),
            $persona->genero,
            $persona->grado_estudios,
            $persona->especialidad,
            $persona->status,
            $persona->calle,
            $persona->numero_exterior,
            $persona->numero_interior,
            $persona->colonia,
            $persona->municipio,
            $persona->estado,
            $persona->codigo_postal,
        ];
    }
}
