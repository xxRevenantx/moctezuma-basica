<?php

use App\Models\User;
use App\Models\WorkflowState;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('workflow can move from draft to review approval and closure', function () {
    $user = User::factory()->create();
    $service = app(WorkflowService::class);

    $review = $service->transition('calificaciones', 'global', WorkflowState::STATUS_REVIEW, $user->id);
    expect($review->status)->toBe(WorkflowState::STATUS_REVIEW)
        ->and($review->submitted_by)->toBe($user->id)
        ->and($review->submitted_at)->not->toBeNull();

    $approved = $service->transition('calificaciones', 'global', WorkflowState::STATUS_APPROVED, $user->id);
    expect($approved->status)->toBe(WorkflowState::STATUS_APPROVED)
        ->and($approved->approved_by)->toBe($user->id)
        ->and($approved->approved_at)->not->toBeNull();

    $closed = $service->transition('calificaciones', 'global', WorkflowState::STATUS_CLOSED, $user->id);
    expect($closed->status)->toBe(WorkflowState::STATUS_CLOSED)
        ->and($closed->closed_by)->toBe($user->id)
        ->and($closed->closed_at)->not->toBeNull();
});

test('workflow rejects unknown states', function () {
    $service = app(WorkflowService::class);

    expect(fn () => $service->transition('calificaciones', 'global', 'estado-invalido', null))
        ->toThrow(\InvalidArgumentException::class);
});
