@php
    $sectionRoute = $companies ? 'admin.organizations' : 'admin.schools';
    $title = $companies ? __('Organizácie') : __('Školy');
    $description = $companies ? __('Firmy a organizácie zapojené do platformy.') : __('Školy zapojené do platformy.');
@endphp

<x-layouts::app :title="$title">
    <x-page-header :title="$title" :description="$description">
        <x-slot:actions>
            <x-link-button :href="route($sectionRoute.'.create')">
                {{ $companies ? __('Nová organizácia') : __('Nová škola') }}
            </x-link-button>
        </x-slot:actions>
    </x-page-header>

    <livewire:admin.school-table :companies="$companies" />
</x-layouts::app>
