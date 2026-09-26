<x-layouts::app :title="__('Nová škola')">
    <x-page-header :title="__('Nová škola')" :breadcrumbs="[
        ['label' => __('Školy'), 'url' => route('admin.schools.index')],
        ['label' => __('Nová škola')],
    ]" />

    <form method="POST" action="{{ route('admin.schools.store') }}" class="max-w-2xl">
        @include('admin.schools._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Vytvoriť školu') }}</x-button>
            <x-link-button variant="secondary" :href="route('admin.schools.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>
</x-layouts::app>
