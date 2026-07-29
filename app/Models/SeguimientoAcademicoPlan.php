<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoAcademicoPlan extends Model
{
    protected $table = 'seguimiento_academico_planes';
    protected $guarded = [];
    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin_prevista' => 'date', 'cerrado_at' => 'datetime'];

    public function caso() { return $this->belongsTo(SeguimientoAcademicoCaso::class, 'seguimiento_caso_id'); }
    public function acciones() { return $this->hasMany(SeguimientoAcademicoAccion::class, 'plan_id'); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
}
