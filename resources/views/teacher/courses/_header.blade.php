{{-- Shared header of the course management pages: title, status actions and tabs. --}}
@php
    $tabs = [
        'structure' => [__('Obsah kurzu'), route('teacher.courses.show', $course)],
        'assignments' => [__('Priradenie'), route('teacher.courses.assignments', $course)],
        'edit' => [__('Nastavenia'), route('teacher.courses.edit', $course)],
    ];
@endphp

<x-page-header :title="$course->title" :breadcrumbs="[
    ['label' => __('Kurzy'), 'url' => route('teacher.courses.index')],
    ['label' => $course->title],
]">
    <x-slot:actions>
        <x-badge :color="$course->status->color()" class="self-center">{{ $course->status->label() }}</x-badge>

        <x-link-button variant="secondary" :href="route('courses.show', $course)">
            <x-icon name="eye" class="size-4" />{{ __('Náhľad') }}
        </x-link-button>

        @foreach (\App\Enums\CourseStatus::cases() as $target)
            @continue($target === $course->status)
            <form method="POST" action="{{ route('teacher.courses.status', $course) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $target->value }}">
                <x-button :variant="$target === \App\Enums\CourseStatus::Published ? 'primary' : 'secondary'">
                    {{ match ($target) {
                        \App\Enums\CourseStatus::Published => __('Publikovať'),
                        \App\Enums\CourseStatus::Draft => __('Vrátiť do konceptu'),
                        \App\Enums\CourseStatus::Archived => __('Archivovať'),
                    } }}
                </x-button>
            </form>
        @endforeach
    </x-slot:actions>
</x-page-header>

<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="{{ __('Sekcie kurzu') }}">
    @foreach ($tabs as $key => [$label, $url])
        <a href="{{ $url }}" @if ($active === $key) aria-current="page" @endif @class([
            '-mb-px whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium',
            'border-indigo-600 text-indigo-700' => $active === $key,
            'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900' => $active !== $key,
        ])>{{ $label }}</a>
    @endforeach
</nav>
