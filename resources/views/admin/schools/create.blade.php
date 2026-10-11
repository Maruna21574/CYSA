@php
    $sectionRoute = $companies ? 'admin.organizations' : 'admin.schools';
    $sectionTitle = $companies ? __('Organizácie') : __('Školy');
    $title = $companies ? __('Nová organizácia') : __('Nová škola');
@endphp

<x-layouts::app :title="$title">
    <x-page-header :title="$title" :breadcrumbs="[
        ['label' => $sectionTitle, 'url' => route($sectionRoute.'.index')],
        ['label' => $title],
    ]" />

    <form method="POST" action="{{ route($sectionRoute.'.store') }}" class="max-w-2xl">
        @include('admin.schools._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ $companies ? __('Vytvoriť organizáciu') : __('Vytvoriť školu') }}</x-button>
            <x-link-button variant="secondary" :href="route($sectionRoute.'.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>
</x-layouts::app>
