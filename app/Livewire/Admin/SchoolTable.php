<?php

namespace App\Livewire\Admin;

use App\Models\School;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SchoolTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', School::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $like = '%'.addcslashes(trim($this->search), '%_\\').'%';

        $schools = School::query()
            ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('city', 'like', $like)))
            ->withCount(['users', 'classrooms'])
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.school-table', ['schools' => $schools]);
    }
}
