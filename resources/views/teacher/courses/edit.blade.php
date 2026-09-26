<x-layouts::app :title="__('Nastavenia kurzu')">
    @include('teacher.courses._header', ['active' => 'edit'])

    <form method="POST" action="{{ route('teacher.courses.update', $course) }}" enctype="multipart/form-data" class="max-w-3xl">
        @method('PUT')
        @include('teacher.courses._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Uložiť') }}</x-button>
            <x-link-button variant="secondary" :href="route('teacher.courses.show', $course)">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>

    <x-card :title="__('Odstránenie kurzu')" class="mt-8 max-w-3xl border-red-200">
        <p class="text-sm text-slate-600">{{ __('Kurz zmizne študentom aj učiteľom. Ak ho chcete len skryť a zachovať výsledky, radšej ho archivujte.') }}</p>
        <form method="POST" action="{{ route('teacher.courses.destroy', $course) }}" class="mt-3"
              x-data @submit="if (! confirm(@js(__('Naozaj odstrániť tento kurz?')))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <x-button variant="danger">{{ __('Odstrániť kurz') }}</x-button>
        </form>
    </x-card>
</x-layouts::app>
