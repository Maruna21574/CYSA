<x-layouts::app :title="$course->title">
    @if ($isPreview)
        <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800" role="status">
            {{ __('Náhľad kurzu tak, ako ho vidia študenti. Skryté kapitoly a nepublikované testy sú zobrazené iba vám.') }}
            <a href="{{ route('teacher.courses.show', $course) }}" class="font-semibold underline">{{ __('Späť na úpravu') }}</a>
        </div>
    @endif

    <div class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs md:flex">
        <x-course-cover :course="$course" class="h-44 w-full md:h-auto md:w-72" />
        <div class="flex flex-1 flex-col gap-3 p-6">
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

            @unless ($isPreview)
                @php $percent = (float) ($courseProgress?->percentage ?? 0); @endphp
                <div class="mt-2">
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ __('Tvoj progres') }}</span>
                        <span class="tabular-nums text-slate-600">{{ round($percent) }} %</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ round($percent) }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Progres kurzu') }}">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @endunless
        </div>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-4 lg:col-span-2">
            @forelse ($course->modules as $module)
                @continue($module->chapters->isEmpty() && ! $isPreview)
                <x-card :title="$module->title">
                    <ol class="flex flex-col divide-y divide-slate-100">
                        @foreach ($module->chapters as $chapter)
                            @php
                                $isDone = $completed->contains($chapter->id);
                                $isOpen = $unlocked[$chapter->id] ?? true;
                            @endphp
                            <li>
                                @if ($isOpen)
                                    <a href="{{ route('chapters.show', [$course, $chapter]) }}" class="flex items-center gap-3 py-3 text-sm hover:text-indigo-700">
                                @else
                                    <div class="flex items-center gap-3 py-3 text-sm text-slate-400" aria-disabled="true">
                                @endif
                                    <span @class(['text-emerald-600' => $isDone, 'text-slate-400' => ! $isDone])>
                                        <x-icon :name="$isDone ? 'check-circle' : ($isOpen ? 'book' : 'lock')" class="size-5" />
                                        <span class="sr-only">{{ $isDone ? __('dokončená') : ($isOpen ? '' : __('zamknutá')) }}</span>
                                    </span>
                                    <span class="flex-1 font-medium">{{ $chapter->title }}</span>
                                    @unless ($chapter->is_published)
                                        <x-badge color="amber">{{ __('Skrytá') }}</x-badge>
                                    @endunless
                                    @if ($chapter->estimated_minutes)
                                        <span class="flex items-center gap-1 text-xs text-slate-500"><x-icon name="clock" class="size-4" />{{ __(':min min', ['min' => $chapter->estimated_minutes]) }}</span>
                                    @endif
                                @if ($isOpen)
                                    <x-icon name="chevron-right" class="size-4 text-slate-400" /></a>
                                @else
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </x-card>
            @empty
                <x-empty-state icon="book" :title="__('Kurz zatiaľ nemá obsah')" />
            @endforelse
        </div>

        <div class="flex flex-col gap-4">
            @if ($announcements->isNotEmpty())
                <x-card :title="__('Oznámenia učiteľa')">
                    @foreach ($announcements as $announcement)
                        <article class="border-b border-slate-100 py-3 first:pt-0 last:border-0">
                            <h3 class="text-sm font-semibold text-slate-900">{{ $announcement->title }}</h3>
                            <p class="text-xs text-slate-500">{{ $announcement->created_at->translatedFormat('j. n. Y') }}</p>
                            <p class="mt-1 text-sm whitespace-pre-line text-slate-700">{{ $announcement->body }}</p>
                        </article>
                    @endforeach
                </x-card>
            @endif

            <x-card :title="__('Testy a kvízy')">
                @if ($quizzes->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('Kurz zatiaľ nemá žiadne testy.') }}</p>
                @else
                    @include('learning._quiz-list', ['quizzes' => $quizzes])
                @endif
            </x-card>
        </div>
    </div>
</x-layouts::app>
