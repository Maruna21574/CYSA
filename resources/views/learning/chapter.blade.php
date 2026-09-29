<x-layouts::app :title="$chapter->title">
    @if ($isPreview)
        <div class="mb-4 rounded-lg border border-brand-200 bg-brand-50 p-3 text-sm text-brand-800" role="status">
            {{ __('Náhľad kapitoly.') }}
            <a href="{{ route('teacher.courses.chapters.edit', [$course, $chapter]) }}" class="font-semibold underline">{{ __('Upraviť kapitolu') }}</a>
        </div>
    @endif

    <x-page-header :title="$chapter->title" :description="$chapter->module->title" :breadcrumbs="[
        ['label' => $course->title, 'url' => route('courses.show', $course)],
        ['label' => __('Kapitola :current z :total', ['current' => $position, 'total' => $total])],
    ]" />

    <div class="mx-auto flex max-w-3xl flex-col gap-6">
        @if ($chapter->content)
            <article class="prose-content rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
                {{-- Stored HTML is sanitized on write by App\Support\ContentSanitizer. --}}
                {!! $chapter->content !!}
            </article>
        @endif

        @if ($chapter->materials->isNotEmpty())
            <section aria-labelledby="materials-heading" class="flex flex-col gap-4">
                <h2 id="materials-heading" class="text-lg font-semibold text-slate-900">{{ __('Študijné materiály') }}</h2>
                @foreach ($chapter->materials as $material)
                    @include('learning._material', ['material' => $material])
                @endforeach
            </section>
        @endif

        @if (! $chapter->content && $chapter->materials->isEmpty())
            <x-empty-state icon="document" :title="__('Kapitola zatiaľ nemá obsah')" />
        @endif

        @if ($quizzes->isNotEmpty())
            <x-card :title="__('Over si, čo vieš')">
                @include('learning._quiz-list', ['quizzes' => $quizzes])
            </x-card>
        @endif

        @unless ($isPreview)
            @if ($isCompleted)
                <p class="flex items-center justify-center gap-2 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-800" role="status">
                    <x-icon name="check-circle" class="size-5" />{{ __('Túto kapitolu máš dokončenú.') }}
                </p>
            @else
                <form method="POST" action="{{ route('student.chapters.complete', [$course, $chapter]) }}" class="flex justify-center">
                    @csrf
                    <x-button><x-icon name="check" class="size-4" />{{ __('Označiť kapitolu ako dokončenú') }}</x-button>
                </form>
            @endif
        @endunless

        <nav class="flex items-center justify-between gap-4 border-t border-slate-200 pt-6" aria-label="{{ __('Navigácia medzi kapitolami') }}">
            @if ($previous)
                <x-link-button variant="secondary" :href="route('chapters.show', [$course, $previous])">
                    <x-icon name="chevron-left" class="size-4" /><span class="max-w-40 truncate sm:max-w-xs">{{ $previous->title }}</span>
                </x-link-button>
            @else
                <span></span>
            @endif

            @if ($next)
                <x-link-button :href="route('chapters.show', [$course, $next])">
                    <span class="max-w-40 truncate sm:max-w-xs">{{ $next->title }}</span><x-icon name="chevron-right" class="size-4" />
                </x-link-button>
            @else
                <x-link-button variant="secondary" :href="route('courses.show', $course)">{{ __('Späť na prehľad kurzu') }}</x-link-button>
            @endif
        </nav>
    </div>
</x-layouts::app>
