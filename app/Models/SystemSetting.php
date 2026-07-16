<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_public', 'updated_by'];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'boolean',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function value(string $group, string $key, mixed $default = null): mixed
    {
        $record = static::query()->where('group', $group)->where('key', $key)->first();

        return $record?->value ?? $default;
    }
}
