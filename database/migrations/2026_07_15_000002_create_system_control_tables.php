<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_audits')) {
            Schema::create('system_audits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 40)->index();
                $table->string('module', 80)->nullable()->index();
                $table->nullableMorphs('auditable');
                $table->string('route')->nullable();
                $table->string('ip', 64)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['created_at', 'action']);
            });
        }

        if (! Schema::hasTable('system_notifications')) {
            Schema::create('system_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('source_key')->nullable()->index();
                $table->string('type', 60)->default('system')->index();
                $table->string('severity', 20)->default('info')->index();
                $table->string('title');
                $table->text('message');
                $table->string('action_url')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamp('dismissed_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
                $table->index(['user_id', 'read_at', 'dismissed_at'], 'system_notifications_inbox_idx');
            });
        }

        if (! Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('group', 80)->default('general')->index();
                $table->string('key', 120);
                $table->json('value')->nullable();
                $table->string('type', 30)->default('json');
                $table->boolean('is_public')->default(false);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['group', 'key']);
            });
        }

        if (! Schema::hasTable('system_backups')) {
            Schema::create('system_backups', function (Blueprint $table): void {
                $table->id();
                $table->string('type', 40)->default('academic');
                $table->string('status', 30)->default('pending')->index();
                $table->string('disk', 60)->default('local');
                $table->string('path')->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('sha256', 64)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('details')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('workflow_states')) {
            Schema::create('workflow_states', function (Blueprint $table): void {
                $table->id();
                $table->nullableMorphs('subject');
                $table->string('module', 80)->index();
                $table->string('context_key', 160)->default('global');
                $table->string('status', 30)->default('borrador')->index();
                $table->json('metadata')->nullable();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->unique(['module', 'context_key']);
            });
        }

        if (! Schema::hasTable('import_previews')) {
            Schema::create('import_previews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('type', 60)->index();
                $table->string('original_name');
                $table->string('temporary_path')->nullable();
                $table->string('checksum', 64)->index();
                $table->string('status', 30)->default('ready')->index();
                $table->json('summary')->nullable();
                $table->json('errors')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_previews');
        Schema::dropIfExists('workflow_states');
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('system_audits');
    }
};
