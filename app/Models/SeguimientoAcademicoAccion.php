<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoAcademicoAccion extends Model
{
    protected $table = 'seguimiento_academico_acciones';
    protected $guarded = [];
    protected $casts = ['fecha_limite' => 'date', 'completada_at' => 'datetime'];

    public function caso() { return $this->belongsTo(SeguimientoAcademicoCaso::class, 'seguimiento_caso_id'); }
    public function plan() { return $this->belongsTo(SeguimientoAcademicoPlan::class, 'plan_id'); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
}
