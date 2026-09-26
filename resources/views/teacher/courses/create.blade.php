<x-layouts::app :title="__('Nový kurz')">
    <x-page-header :title="__('Nový kurz')" :breadcrumbs="[
        ['label' => __('Kurzy'), 'url' => route('teacher.courses.index')],
        ['label' => __('Nový kurz')],
    ]" />

    <form method="POST" action="{{ route('teacher.courses.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @include('teacher.courses._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Vytvoriť kurz') }}</x-button>
            <x-link-button variant="secondary" :href="route('teacher.courses.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>
</x-layouts::app>
