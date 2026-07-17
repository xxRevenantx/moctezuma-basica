<?php

namespace App\Exports\Inscripciones;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class InstruccionesInscripcionesSheet implements FromArray, ShouldAutoSize, WithTitle
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        return [
            ['Campo', 'Regla'],
            ['curp', 'Obligatorio. Máximo 18 caracteres.'],
            ['matricula', 'Obligatorio. Si ya existe, se actualiza el alumno.'],
            ['folio', 'Opcional.'],
            ['nombre', 'Obligatorio.'],
            ['apellido_paterno', 'Obligatorio.'],
            ['apellido_materno', 'Opcional.'],
            ['fecha_nacimiento', 'Obligatorio. Formato recomendado: yyyy-mm-dd.'],
            ['genero', 'Obligatorio. Usar H o M.'],
            ['fecha_inscripcion', 'Obligatorio. Formato recomendado: yyyy-mm-dd.'],
            ['ciclo_escolar_id', 'Obligatorio. Tomarlo de la hoja Catálogos.'],
            ['ciclo_id', 'Obligatorio. Tomarlo de la hoja Catálogos.'],
            ['grupo_id', 'Obligatorio. Tomarlo de la hoja Catálogos.'],
            ['observaciones', 'Opcional. Máximo 5,000 caracteres. Se guarda para el ciclo_escolar_id indicado.'],
            ['Importante', 'Nivel, grado, generación y semestre se toman automáticamente desde el grupo seleccionado.'],
        ];
    }
}
