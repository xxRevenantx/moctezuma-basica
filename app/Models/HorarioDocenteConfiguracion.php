<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioDocenteConfiguracion extends Model
{
    protected $table = 'horario_docente_configuraciones';

    protected $fillable = [
        'persona_id', 'ciclo_escolar_id', 'nivel_id', 'max_grupos_simultaneos',
        'max_horas_diarias', 'max_horas_consecutivas', 'min_descanso_bloques',
        'max_huecos_diarios', 'primera_hora_id', 'ultima_hora_id',
        'permitir_multigrado', 'permitir_materias_distintas',
        'requiere_motivo_traslape', 'activo', 'actualizado_por',
    ];

    protected $casts = [
        'max_grupos_simultaneos' => 'integer',
        'max_horas_diarias' => 'integer',
        'max_horas_consecutivas' => 'integer',
        'min_descanso_bloques' => 'integer',
        'max_huecos_diarios' => 'integer',
        'permitir_multigrado' => 'boolean',
        'permitir_materias_distintas' => 'boolean',
        'requiere_motivo_traslape' => 'boolean',
        'activo' => 'boolean',
    ];

    public function persona() { return $this->belongsTo(Persona::class); }
    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function primeraHora() { return $this->belongsTo(Hora::class, 'primera_hora_id'); }
    public function ultimaHora() { return $this->belongsTo(Hora::class, 'ultima_hora_id'); }
    public function disponibilidades() { return $this->hasMany(HorarioDocenteDisponibilidad::class, 'configuracion_id'); }
    public function usuarioActualizacion() { return $this->belongsTo(User::class, 'actualizado_por'); }
}
