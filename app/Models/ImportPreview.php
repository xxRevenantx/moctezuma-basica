<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportPreview extends Model
{
    protected $fillable = [
        'user_id', 'type', 'original_name', 'temporary_path', 'checksum',
        'status', 'summary', 'errors', 'expires_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'errors' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
