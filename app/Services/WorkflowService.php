<?php

namespace App\Services;

use App\Models\WorkflowState;
use InvalidArgumentException;

class WorkflowService
{
    public function state(string $module, string $context = 'global'): WorkflowState
    {
        return WorkflowState::query()->firstOrCreate(
            ['module' => $module, 'context_key' => $context],
            ['status' => WorkflowState::STATUS_DRAFT]
        );
    }

    public function transition(string $module, string $context, string $status, ?int $userId): WorkflowState
    {
        if (! in_array($status, WorkflowState::STATUSES, true)) {
            throw new InvalidArgumentException('Estado de revisión no válido.');
        }

        $state = $this->state($module, $context);
        $data = ['status' => $status];

        if ($status === WorkflowState::STATUS_REVIEW) {
            $data += ['submitted_by' => $userId, 'submitted_at' => now()];
        } elseif ($status === WorkflowState::STATUS_APPROVED) {
            $data += ['approved_by' => $userId, 'approved_at' => now()];
        } elseif ($status === WorkflowState::STATUS_CLOSED) {
            $data += ['closed_by' => $userId, 'closed_at' => now()];
        } elseif ($status === WorkflowState::STATUS_DRAFT) {
            $data += [
                'submitted_by' => null, 'submitted_at' => null,
                'approved_by' => null, 'approved_at' => null,
                'closed_by' => null, 'closed_at' => null,
            ];
        }

        $state->update($data);

        return $state->fresh();
    }
}
