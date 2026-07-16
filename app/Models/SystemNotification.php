<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    protected $fillable = [
        'user_id', 'source_key', 'type', 'severity', 'title', 'message',
        'action_url', 'metadata', 'read_at', 'dismissed_at', 'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleFor(Builder $query, ?int $userId): Builder
    {
        return $query->where(function (Builder $builder) use ($userId): void {
            $builder->whereNull('user_id');
            if ($userId) {
                $builder->orWhere('user_id', $userId);
            }
        })->whereNull('dismissed_at')
            ->where(function (Builder $builder): void {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
