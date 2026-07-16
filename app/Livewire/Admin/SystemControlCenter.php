<?php

namespace App\Livewire\Admin;

use App\Models\Constancia;
use App\Models\DocumentoAlumno;
use App\Models\DocumentoPersonal;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\Oficio;
use App\Models\Persona;
use App\Models\SystemAudit;
use App\Models\SystemBackup;
use App\Models\SystemNotification;
use App\Models\User;
use App\Models\WorkflowState;
use App\Services\AcademicIntegrityService;
use App\Services\DocumentConfigurationService;
use App\Services\SystemAuditService;
use App\Services\SystemBackupService;
use App\Services\SystemHealthService;
use App\Services\SystemNotificationService;
use App\Services\SystemAssistantService;
use App\Services\WorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Throwable;

class SystemControlCenter extends Component
{
    public string $tab = 'resumen';

    /** @var array<int,array<string,mixed>> */
    public array $issues = [];

    /** @var array<string,array<string,mixed>> */
    public array $health = [];

    /** @var array<string,mixed> */
    public array $configuration = [];

    public ?int $selectedUserId = null;
    public string $selectedRole = 'consulta';
    public bool $selectedIsAdmin = false;
    public bool $selectedActive = true;

    /** @var array<int,string> */
    public array $selectedPermissions = [];

    public string $forceDeleteConfirmation = '';
    public string $auditSearch = '';
    public string $auditModule = '';
    public string $notificationSeverity = '';
    public string $closureMessage = '';
    public string $assistantQuery = '';

    /** @var array<string,mixed> */
    public array $assistantResponse = [];

    public function mount(
        AcademicIntegrityService $integrity,
        SystemHealthService $health,
        DocumentConfigurationService $documents,
    ): void {
        abort_unless(auth()->user()?->canAccess('administracion.acceder'), 403);

        $this->issues = $integrity->analyze();
        $this->health = $health->inspect();
        $this->configuration = $documents->get();
    }

    public function setTab(string $tab): void
    {
        $allowed = [
            'resumen', 'integridad', 'notificaciones', 'auditoria', 'respaldos',
            'papelera', 'permisos', 'configuracion', 'flujos', 'cierre', 'asistente',
        ];

        if (in_array($tab, $allowed, true)) {
            $this->tab = $tab;
        }
    }

    public function runIntegrity(
        AcademicIntegrityService $integrity,
        SystemNotificationService $notifications,
        SystemAuditService $audit,
    ): void {
        $this->authorizePermission('integridad.consultar');
        $this->issues = $integrity->analyze();
        $notifications->syncIntegrityIssues($this->issues);
        $audit->record('integrity_check', 'integridad', ['issues' => count($this->issues)]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Revisión terminada',
            'text' => 'Se actualizaron las incidencias y las notificaciones internas.',
            'position' => 'top-end',
        ]);
    }

    public function createBackup(SystemBackupService $service, SystemAuditService $audit): void
    {
        $this->authorizePermission('respaldos.gestionar');
        $backup = $service->create(auth()->id());
        $audit->record('backup_created', 'respaldos', ['backup_id' => $backup->id, 'status' => $backup->status]);

        $this->health = app(SystemHealthService::class)->inspect();

        $this->dispatch('swal', [
            'icon' => $backup->status === 'completed' ? 'success' : 'error',
            'title' => $backup->status === 'completed' ? 'Respaldo verificado' : 'El respaldo no pudo completarse',
            'text' => $backup->status === 'completed' ? 'Se guardaron alumnos, calificaciones y manifiesto de verificación.' : ($backup->error ?: 'Consulta el registro de Laravel.'),
            'position' => 'top-end',
        ]);
    }

    public function markNotificationRead(int $id): void
    {
        $this->authorizePermission('notificaciones.gestionar');
        $notification = SystemNotification::query()->visibleFor(auth()->id())->findOrFail($id);
        $notification->update(['read_at' => now()]);
    }

    public function dismissNotification(int $id): void
    {
        $this->authorizePermission('notificaciones.gestionar');
        $notification = SystemNotification::query()->visibleFor(auth()->id())->findOrFail($id);
        $notification->update(['dismissed_at' => now()]);
    }

    public function selectUser(int $id): void
    {
        $this->authorizePermission('usuarios.gestionar');
        $user = User::query()->findOrFail($id);
        $this->selectedUserId = $user->id;
        $this->selectedRole = $user->rol_sistema ?: 'consulta';
        $this->selectedIsAdmin = (bool) $user->is_admin;
        $this->selectedActive = (bool) ($user->activo ?? true);
        $this->selectedPermissions = array_values($user->permisos ?? []);
    }

    public function saveUserAccess(SystemAuditService $audit): void
    {
        $this->authorizePermission('usuarios.gestionar');

        $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'selectedRole' => ['required', 'string', 'in:'.implode(',', array_keys(config('system_permissions.roles', [])))],
            'selectedIsAdmin' => ['boolean'],
            'selectedActive' => ['boolean'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string'],
        ]);

        $user = User::query()->findOrFail($this->selectedUserId);

        if ($user->is(auth()->user()) && (! $this->selectedIsAdmin || ! $this->selectedActive)) {
            $this->addError('selectedIsAdmin', 'No puedes quitarte el acceso administrativo ni desactivar tu propia cuenta.');
            return;
        }

        $before = $user->only(['is_admin', 'rol_sistema', 'permisos', 'activo']);
        $user->update([
            'is_admin' => $this->selectedIsAdmin,
            'rol_sistema' => $this->selectedRole,
            'permisos' => array_values(array_unique($this->selectedPermissions)),
            'activo' => $this->selectedActive,
        ]);

        $audit->record('permissions_updated', 'usuarios', [
            'user_id' => $user->id,
            'before' => $before,
            'after' => $user->fresh()->only(['is_admin', 'rol_sistema', 'permisos', 'activo']),
        ]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Accesos actualizados',
            'position' => 'top-end',
        ]);
    }

    public function saveConfiguration(DocumentConfigurationService $service, SystemAuditService $audit): void
    {
        $this->authorizePermission('configuracion.gestionar');

        $validated = $this->validate([
            'configuration.institution_name' => ['required', 'string', 'max:255'],
            'configuration.primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'configuration.secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'configuration.place' => ['required', 'string', 'max:255'],
            'configuration.margin_top' => ['required', 'numeric', 'min:0', 'max:50'],
            'configuration.margin_right' => ['required', 'numeric', 'min:0', 'max:50'],
            'configuration.margin_bottom' => ['required', 'numeric', 'min:0', 'max:50'],
            'configuration.margin_left' => ['required', 'numeric', 'min:0', 'max:50'],
            'configuration.show_cycle' => ['boolean'],
            'configuration.ai_mode' => ['required', 'in:suggest_only,prepare_confirm'],
            'configuration.notification_channels' => ['array'],
            'configuration.backup_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->configuration = $service->save($validated['configuration'], auth()->id());
        $audit->record('configuration_updated', 'configuracion', ['group' => 'documents']);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Configuración guardada',
            'text' => 'Los nuevos documentos pueden consumir estos valores sin alterar las plantillas existentes.',
            'position' => 'top-end',
        ]);
    }

    public function transitionWorkflow(string $module, string $status, WorkflowService $service, SystemAuditService $audit): void
    {
        $this->authorizePermission('flujos.gestionar');
        $state = $service->transition($module, 'global', $status, auth()->id());
        $audit->record('workflow_transition', 'flujos', [
            'module' => $module,
            'status' => $state->status,
        ]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Estado actualizado',
            'text' => ucfirst($module).': '.$state->status,
            'position' => 'top-end',
        ]);
    }

    public function askAssistant(SystemAssistantService $assistant, SystemAuditService $audit): void
    {
        $this->authorizePermission('integridad.consultar');

        $this->validate([
            'assistantQuery' => ['required', 'string', 'min:3', 'max:300'],
        ]);

        $this->assistantResponse = $assistant->answer($this->assistantQuery);
        $audit->record('assistant_query', 'asistente', [
            'query' => $this->assistantQuery,
            'supported' => $this->assistantResponse['supported'] ?? false,
            'key' => $this->assistantResponse['key'] ?? null,
        ]);
    }

    public function useAssistantExample(string $question): void
    {
        $this->assistantQuery = mb_substr($question, 0, 300);
        $this->assistantResponse = [];
    }

    public function prepareClosure(AcademicIntegrityService $integrity, SystemNotificationService $notifications): void
    {
        $this->authorizePermission('flujos.gestionar');
        $this->issues = $integrity->analyze();
        $notifications->syncIntegrityIssues($this->issues);

        $critical = collect($this->issues)->where('severity', 'critical')->sum('count');
        $warnings = collect($this->issues)->where('severity', 'warning')->sum('count');

        $this->closureMessage = $critical > 0
            ? "El cierre no está recomendado: hay {$critical} incidencias críticas y {$warnings} advertencias."
            : "La revisión no encontró incidencias críticas. Quedan {$warnings} advertencias para validar antes de cerrar.";
    }

    public function restoreItem(string $type, int $id, SystemAuditService $audit): void
    {
        $this->authorizePermission('papelera.gestionar');
        $class = $this->trashMap()[$type] ?? null;
        abort_unless($class, 404);

        /** @var Model $model */
        $model = $class::onlyTrashed()->findOrFail($id);
        $model->restore();
        $audit->record('trash_restore', 'papelera', ['type' => $type, 'id' => $id]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Registro restaurado',
            'position' => 'top-end',
        ]);
    }

    public function forceDeleteItem(string $type, int $id, SystemAuditService $audit): void
    {
        $this->authorizePermission('papelera.gestionar');

        if ($this->forceDeleteConfirmation !== 'ELIMINAR') {
            $this->addError('forceDeleteConfirmation', 'Escribe ELIMINAR para confirmar la eliminación permanente.');
            return;
        }

        $class = $this->trashMap()[$type] ?? null;
        abort_unless($class, 404);

        /** @var Model $model */
        $model = $class::onlyTrashed()->findOrFail($id);

        if (isset($model->disco, $model->ruta) && $model->disco && $model->ruta) {
            try {
                Storage::disk($model->disco)->delete($model->ruta);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $audit->record('trash_force_delete', 'papelera', ['type' => $type, 'id' => $id]);
        $model->forceDelete();
        $this->forceDeleteConfirmation = '';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Registro eliminado permanentemente',
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        $notifications = Schema::hasTable('system_notifications')
            ? SystemNotification::query()
                ->visibleFor(auth()->id())
                ->when($this->notificationSeverity !== '', fn ($query) => $query->where('severity', $this->notificationSeverity))
                ->latest()
                ->limit(80)
                ->get()
            : collect();

        $audits = Schema::hasTable('system_audits')
            ? SystemAudit::query()
                ->with('user:id,name,email')
                ->when($this->auditModule !== '', fn ($query) => $query->where('module', $this->auditModule))
                ->when(trim($this->auditSearch) !== '', function ($query): void {
                    $term = '%'.trim($this->auditSearch).'%';
                    $query->where(function ($inner) use ($term): void {
                        $inner->where('action', 'like', $term)
                            ->orWhere('auditable_type', 'like', $term)
                            ->orWhere('route', 'like', $term);
                    });
                })
                ->latest()
                ->limit(100)
                ->get()
            : collect();

        $backups = Schema::hasTable('system_backups')
            ? SystemBackup::query()->with('creator:id,name')->latest()->limit(30)->get()
            : collect();

        $users = User::query()->orderByDesc('is_admin')->orderBy('name')->get();
        $workflows = Schema::hasTable('workflow_states')
            ? collect(['calificaciones', 'documentos', 'cierre_ciclo'])->mapWithKeys(function (string $module): array {
                $state = app(WorkflowService::class)->state($module);
                return [$module => $state];
            })
            : collect();

        return view('livewire.admin.system-control-center', [
            'notifications' => $notifications,
            'audits' => $audits,
            'backups' => $backups,
            'users' => $users,
            'trash' => $this->trashRows(),
            'roles' => config('system_permissions.roles', []),
            'permissions' => config('system_permissions.permissions', []),
            'workflows' => $workflows,
            'unreadNotifications' => $notifications->whereNull('read_at')->count(),
            'criticalIssues' => collect($this->issues)->where('severity', 'critical')->sum('count'),
            'warningIssues' => collect($this->issues)->where('severity', 'warning')->sum('count'),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->canAccess($permission), 403);
    }

    /** @return array<string,class-string<Model>> */
    private function trashMap(): array
    {
        return [
            'alumnos' => Inscripcion::class,
            'personal' => Persona::class,
            'grupos' => Grupo::class,
            'materias' => Materia::class,
            'documentos_alumnos' => DocumentoAlumno::class,
            'documentos_personal' => DocumentoPersonal::class,
            'constancias' => Constancia::class,
            'oficios' => Oficio::class,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function trashRows(): array
    {
        $rows = [];

        foreach ($this->trashMap() as $type => $class) {
            $model = new $class();
            if (! Schema::hasTable($model->getTable()) || ! Schema::hasColumn($model->getTable(), 'deleted_at')) {
                continue;
            }

            $class::onlyTrashed()->latest('deleted_at')->limit(20)->get()->each(function (Model $item) use (&$rows, $type): void {
                $rows[] = [
                    'type' => $type,
                    'id' => $item->getKey(),
                    'label' => $this->modelLabel($item),
                    'deleted_at' => $item->getAttribute('deleted_at'),
                ];
            });
        }

        return collect($rows)->sortByDesc('deleted_at')->take(100)->values()->all();
    }

    private function modelLabel(Model $model): string
    {
        $fullName = collect([
            $model->getAttribute('nombre'),
            $model->getAttribute('apellido_paterno'),
            $model->getAttribute('apellido_materno'),
        ])->filter()->join(' ');

        return $fullName
            ?: (string) ($model->getAttribute('materia')
                ?: $model->getAttribute('folio')
                ?: $model->getAttribute('nombre_original')
                ?: class_basename($model).' #'.$model->getKey());
    }
}
