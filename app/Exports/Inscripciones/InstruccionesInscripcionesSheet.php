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
            ['matricula', 'Obligatorio. Si ya existe, solo se actualiza si conserva el mismo grupo.'],
            ['folio', 'Opcional.'],
            ['nombre', 'Obligatorio.'],
            ['apellido_paterno', 'Obligatorio.'],
            ['apellido_materno', 'Opcional.'],
            ['fecha_nacimiento', 'Obligatorio. Formato recomendado: yyyy-mm-dd.'],
            ['genero', 'Obligatorio. Usar H o M.'],
            ['fecha_inscripcion', 'Fecha real de ingreso. Formato recomendado: yyyy-mm-dd.'],
            ['clave_grupo', 'Obligatoria. Seleccionarla de la hoja Catálogos. Determina ciclo escolar, nivel, grado, generación, grupo y semestre.'],
            ['momento_ingreso_id', 'Obligatorio. 1 = inicio, 2 = medio, 3 = fin, según el catálogo disponible.'],
            ['tipo_ingreso', 'Usar: nuevo_ingreso, traslado o captura_historica. Los ciclos cerrados solo aceptan captura_historica.'],
            ['motivo_captura_historica', 'Obligatorio cuando tipo_ingreso sea captura_historica. Mínimo 10 caracteres.'],
            ['estado_inscripcion', 'Usar preinscrito o inscrito. La preinscripción no activa al alumno todavía.'],
            ['observaciones', 'Opcional. Máximo 5,000 caracteres. Se guarda en el ciclo escolar derivado del grupo.'],
            ['Cupo', 'Todos los grupos tienen cupo ilimitado. La cantidad de alumnos se muestra solo como referencia.'],
            ['Importante', 'No cambies de grupo a un alumno existente por Excel. Usa “Cambiar asignación escolar” para conservar el historial.'],
        ];
    }
}
