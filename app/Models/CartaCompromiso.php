<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartaCompromiso extends Model
{
    protected $table = 'cartas_compromiso';

    protected $fillable = [
        'inscripcion_id',
        'tutor_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'ciclo_escolar_id',
        'documento_alumno_id',
        'folio',
        'fecha_expedicion',
        'lugar',
        'asunto',
        'leyenda_anual',
        'membrete_tipo',
        'encabezado_linea_1',
        'encabezado_linea_2',
        'destinatario_nombre',
        'destinatario_cargo',
        'destinatario_institucion',
        'suscriptor_nombre',
        'suscriptor_parentesco',
        'suscriptor_calidad',
        'referencia_alumno',
        'grado_texto',
        'motivo_tipo',
        'motivo_texto',
        'contenido_cuerpo',
        'docente_nombre',
        'directora_nombre',
        'estado_documento',
        'cancelada_at',
        'cancelada_por',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_expedicion' => 'date',
        'cancelada_at' => 'datetime',
    ];

    public function alumno()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function documentoAlumno()
    {
        return $this->belongsTo(DocumentoAlumno::class, 'documento_alumno_id');
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usuarioCancelacion()
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }
}
