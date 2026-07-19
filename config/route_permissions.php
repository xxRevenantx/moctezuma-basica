<?php

return [
    'names' => [
        'misrutas.inscripcion' => 'alumnos.crear',
        'misrutas.matricula.editar' => 'alumnos.editar',
        'misrutas.alumnos*' => 'alumnos.consultar',
        'misrutas.tutores*' => 'alumnos.consultar',

        'misrutas.personal*' => 'personal.consultar',
        'misrutas.autoridades*' => 'personal.consultar',
        'misrutas.role-persona*' => 'personal.consultar',
        'misrutas.plantilla*' => 'personal.consultar',
        'misrutas.profesores*' => 'personal.consultar',
        'credenciales.profesores*' => 'personal.consultar',
        'profesor.*' => 'personal.consultar',
        'profesores.*' => 'personal.consultar',

        'misrutas.niveles*' => 'academico.consultar',
        'misrutas.ciclos*' => 'academico.consultar',
        'misrutas.escuela*' => 'academico.consultar',
        'misrutas.grados*' => 'academico.consultar',
        'misrutas.generaciones*' => 'academico.consultar',
        'misrutas.grupos*' => 'academico.consultar',
        'misrutas.semestres*' => 'academico.consultar',
        'misrutas.periodos*' => 'academico.consultar',
        'misrutas.materias*' => 'academico.consultar',
        'niveles.seleccionar-nivel' => 'academico.consultar',

        'misrutas.calificaciones*' => 'calificaciones.consultar',
        'misrutas.boleta*' => 'calificaciones.consultar',
        'misrutas.promedios*' => 'calificaciones.consultar',
        'misrutas.diploma*' => 'calificaciones.consultar',
        'misrutas.reconocimiento*' => 'calificaciones.consultar',
        'calificaciones.*' => 'calificaciones.consultar',
        'generales.promedios*' => 'calificaciones.consultar',
        'generales.cuadro-honor*' => 'calificaciones.consultar',
        'generales.bachillerato*' => 'calificaciones.consultar',

        'misrutas.horarios*' => 'horarios.consultar',
        'generales.horarios*' => 'horarios.consultar',

        'misrutas.constancias*' => 'documentos.consultar',
        'misrutas.oficios*' => 'documentos.consultar',
        'misrutas.expedientes*' => 'documentos.consultar',
        'misrutas.fichas*' => 'fichas.consultar',
        'media-superior.documentos*' => 'documentos.consultar',
        'generales.documentos-academicos*' => 'documentos.consultar',
        'generales.credenciales*' => 'documentos.consultar',

        'misrutas.centro-control' => 'administracion.acceder',
        'misrutas.respaldos-academicos' => 'respaldos.gestionar',
    ],

    'submodules' => [
        'generales' => 'academico.consultar',
        'matricula' => 'alumnos.editar',
        'asignacion-de-materias' => 'academico.editar',
        'horarios' => 'horarios.editar',
        'calificaciones' => 'calificaciones.capturar',
        'fichas' => 'fichas.capturar',
    ],
];
