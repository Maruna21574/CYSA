<x-layouts::app :title="__('Používatelia')">
    <x-page-header
        :title="__('Používatelia')"
        :description="auth()->user()->isSuperAdmin() ? __('Všetky účty v systéme.') : __('Učitelia a študenti školy :school.', ['school' => auth()->user()->school?->name])"
    >
        <x-slot:actions>
            @if (Route::has('school.students.import') && auth()->user()->can('create', \App\Models\User::class) && ! auth()->user()->isSuperAdmin())
                <x-link-button variant="secondary" :href="route('school.students.import')">{{ __('Import študentov (CSV)') }}</x-link-button>
            @endif
            <x-link-button :href="route('users.create')">{{ __('Nový používateľ') }}</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <livewire:users.user-table />
</x-layouts::app>
