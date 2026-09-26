<x-layouts::app :title="__('Nový používateľ')">
    <x-page-header :title="__('Nový používateľ')" :breadcrumbs="[
        ['label' => __('Používatelia'), 'url' => route('users.index')],
        ['label' => __('Nový používateľ')],
    ]" />

    <form method="POST" action="{{ route('users.store') }}" class="max-w-2xl">
        @include('users._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Vytvoriť účet') }}</x-button>
            <x-link-button variant="secondary" :href="route('users.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>
</x-layouts::app>
