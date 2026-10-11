@php
    $sectionRoute = $companies ? 'admin.organizations' : 'admin.schools';
    $sectionTitle = $companies ? __('Organizácie') : __('Školy');
@endphp

<x-layouts::app :title="$school->name">
    <x-page-header :title="$school->name" :breadcrumbs="[
        ['label' => $sectionTitle, 'url' => route($sectionRoute.'.index')],
        ['label' => $school->name],
    ]" />

    <form method="POST" action="{{ route($sectionRoute.'.update', $school) }}" class="max-w-2xl">
        @method('PUT')
        @include('admin.schools._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Uložiť') }}</x-button>
            <x-link-button variant="secondary" :href="route($sectionRoute.'.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>

    <x-card :title="$companies ? __('Odstránenie organizácie') : __('Odstránenie školy')" class="mt-8 max-w-2xl border-red-200">
        <p class="text-sm text-slate-600">{{ $companies ? __('Odstrániť možno iba organizáciu bez zamestnancov a oddelení. Inak ju deaktivujte.') : __('Odstrániť možno iba školu bez používateľov a tried. Inak školu deaktivujte.') }}</p>
        <form method="POST" action="{{ route($sectionRoute.'.destroy', $school) }}" class="mt-3"
              x-data @submit="if (! confirm(@js($companies ? __('Naozaj odstrániť túto organizáciu?') : __('Naozaj odstrániť túto školu?')))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <x-button variant="danger">{{ $companies ? __('Odstrániť organizáciu') : __('Odstrániť školu') }}</x-button>
        </form>
    </x-card>
</x-layouts::app>
