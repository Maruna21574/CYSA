<x-layouts::app :title="__('Školy')">
    <x-page-header :title="__('Školy')" :description="__('Školy zapojené do platformy.')">
        <x-slot:actions>
            <x-link-button :href="route('admin.schools.create')">{{ __('Nová škola') }}</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <livewire:admin.school-table />
</x-layouts::app>
