<x-layouts::app :title="$course->title">
    @if ($isPreview)
        <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800" role="status">
            {{ __('Náhľad kurzu tak, ako ho vidia študenti. Skryté kapitoly sú zobrazené iba vám.') }}
            <a href="{{ route('teacher.courses.show', $course) }}" class="font-semibold underline">{{ __('Späť na úpravu') }}</a>
        </div>
    @endif

    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs md:flex">
        <x-course-cover :course="$course" class="h-44 w-full md:h-auto md:w-72" />
        <div class="flex flex-col gap-3 p-6">
            @if (! $isPreview && Route::has('student.courses.index'))
                <nav aria-label="{{ __('Navigačná cesta') }}" class="text-sm text-slate-500">
                    <a href="{{ route('student.courses.index') }}" class="hover:underline">{{ __('Moje kurzy') }}</a>
                </nav>
            @endif
            <h1 class="text-2xl font-semibold text-slate-900">{{ $course->title }}</h1>
            <div class="flex flex-wrap gap-2">
                <x-badge>{{ $course->difficulty->label() }}</x-badge>
                @if ($course->category)
                    <x-badge color="indigo">{{ $course->category->name }}</x-badge>
                @endif
            </div>
            @if ($course->description)
                <p class="text-slate-700">{{ $course->description }}</p>
            @endif
            <p class="text-sm text-slate-500">{{ __('Autor: :name', ['name' => $course->author?->name]) }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        @forelse ($course->modules as $module)
            @continue($module->chapters->isEmpty() && ! $isPreview)
            <x-card :title="$module->title">
                <ol class="flex flex-col divide-y divide-slate-100">
                    @foreach ($module->chapters as $chapter)
                        <li>
                            <a href="{{ route('chapters.show', [$course, $chapter]) }}" class="flex items-center gap-3 py-3 text-sm hover:text-indigo-700">
                                <span class="text-slate-400"><x-icon name="book" class="size-4" /></span>
                                <span class="flex-1 font-medium">{{ $chapter->title }}</span>
                                @unless ($chapter->is_published)
                                    <x-badge color="amber">{{ __('Skrytá') }}</x-badge>
                                @endunless
                                @if ($chapter->estimated_minutes)
                                    <span class="flex items-center gap-1 text-xs text-slate-500"><x-icon name="clock" class="size-4" />{{ __(':min min', ['min' => $chapter->estimated_minutes]) }}</span>
                                @endif
                                <x-icon name="chevron-right" class="size-4 text-slate-400" />
                            </a>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        @empty
            <x-empty-state icon="book" :title="__('Kurz zatiaľ nemá obsah')" />
        @endforelse
    </div>
</x-layouts::app>
