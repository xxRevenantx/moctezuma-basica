<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAudit extends Model
{
    protected $fillable = [
        'user_id', 'action', 'module', 'auditable_type', 'auditable_id',
        'route', 'ip', 'user_agent', 'old_values', 'new_values', 'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }
}
