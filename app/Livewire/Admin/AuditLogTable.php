<?php

namespace App\Livewire\Admin;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only audit log browser for the super admin.
 */
class AuditLogTable extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $action = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'school', except: '')]
    public string $schoolId = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public ?int $expanded = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
    }

    public function updated(string $property): void
    {
        if ($property !== 'expanded') {
            $this->resetPage();
        }
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function render(): View
    {
        $search = trim($this->search);
        $like = '%'.addcslashes($search, '%_\\').'%';

        $logs = AuditLog::query()
            ->with(['user:id,first_name,last_name,email'])
            ->when(AuditAction::tryFrom($this->action), fn ($query, AuditAction $action) => $query->where('action', $action->value))
            ->when($this->schoolId !== '', fn ($query) => $query->where('school_id', (int) $this->schoolId))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('ip_address', 'like', $like)
                ->orWhereHas('user', fn ($query) => $query->search($search))))
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->from), fn ($query) => $query->where('created_at', '>=', $this->from.' 00:00:00'))
            ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->to), fn ($query) => $query->where('created_at', '<=', $this->to.' 23:59:59'))
            ->latest('id')
            ->paginate(50);

        return view('livewire.admin.audit-log-table', [
            'logs' => $logs,
            'actions' => collect(AuditAction::cases())->mapWithKeys(fn (AuditAction $action): array => [$action->value => $action->label()])->sort()->all(),
            'schools' => School::orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }
}
