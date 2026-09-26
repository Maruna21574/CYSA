<x-layouts::app :title="__('Nastavenia testu')">
    @include('teacher.quizzes._header', ['active' => 'edit'])

    <form method="POST" action="{{ route('teacher.quizzes.update', $quiz) }}" class="max-w-3xl">
        @method('PUT')
        @include('teacher.quizzes._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Uložiť nastavenia') }}</x-button>
            <x-link-button variant="secondary" :href="route('teacher.quizzes.show', $quiz)">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>

    <x-card :title="__('Odstránenie testu')" class="mt-8 max-w-3xl border-red-200">
        <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}"
              x-data @submit="if (! confirm(@js(__('Naozaj odstrániť tento test?')))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <x-button variant="danger">{{ __('Odstrániť test') }}</x-button>
        </form>
    </x-card>
</x-layouts::app>
