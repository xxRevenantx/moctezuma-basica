<?php

namespace App\Observers;

use App\Services\SystemAuditService;
use Illuminate\Database\Eloquent\Model;

class SystemAuditObserver
{
    public function __construct(private readonly SystemAuditService $audit)
    {
    }

    public function created(Model $model): void
    {
        $this->audit->recordModel($model, 'created');
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged()) {
            $this->audit->recordModel($model, 'updated');
        }
    }

    public function deleted(Model $model): void
    {
        $action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
            ? 'force_deleted'
            : 'deleted';

        $this->audit->recordModel($model, $action);
    }

    public function restored(Model $model): void
    {
        $this->audit->recordModel($model, 'restored');
    }
}
