<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowState extends Model
{
    public const STATUS_DRAFT = 'borrador';
    public const STATUS_REVIEW = 'revision';
    public const STATUS_APPROVED = 'autorizado';
    public const STATUS_CLOSED = 'cerrado';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_CLOSED,
    ];

    protected $fillable = [
        'subject_type', 'subject_id', 'module', 'context_key', 'status', 'metadata',
        'submitted_by', 'submitted_at', 'approved_by', 'approved_at',
        'closed_by', 'closed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function subject()
    {
        return $this->morphTo();
    }
}
