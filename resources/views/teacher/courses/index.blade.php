<x-layouts::app :title="__('Kurzy')">
    <x-page-header :title="__('Kurzy')" :description="__('Kurzy, ktoré spravujete.')">
        <x-slot:actions>
            @can('create', \App\Models\Course::class)
                <x-link-button :href="route('teacher.courses.create')"><x-icon name="plus" class="size-4" />{{ __('Nový kurz') }}</x-link-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('teacher.courses.index') }}" class="mb-6 flex flex-col gap-2 sm:flex-row" role="search">
        <x-search-input name="q" :value="$search" :label="__('Hľadať kurz')" />
        <div class="sm:w-48">
            <label for="status" class="sr-only">{{ __('Stav') }}</label>
            <select id="status" name="status" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs">
                <option value="">{{ __('Všetky stavy') }}</option>
                @foreach (\App\Enums\CourseStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($status === $case)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <x-button variant="secondary">{{ __('Filtrovať') }}</x-button>
    </form>

    @if ($courses->isEmpty())
        <x-empty-state icon="book" :title="__('Žiadne kurzy')" :description="__('Vytvorte prvý kurz a pridajte doň kapitoly a materiály.')">
            @can('create', \App\Models\Course::class)
                <x-link-button :href="route('teacher.courses.create')">{{ __('Vytvoriť kurz') }}</x-link-button>
            @endcan
        </x-empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $course)
                <a href="{{ route('teacher.courses.show', $course) }}" class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs transition hover:shadow-md focus-visible:outline-2 focus-visible:outline-indigo-600">
                    <x-course-cover :course="$course" class="h-36 w-full" />
                    <div class="flex flex-1 flex-col gap-2 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :color="$course->status->color()">{{ $course->status->label() }}</x-badge>
                            <x-badge>{{ $course->difficulty->label() }}</x-badge>
                        </div>
                        <h2 class="font-semibold text-slate-900 group-hover:text-indigo-700">{{ $course->title }}</h2>
                        <p class="text-sm text-slate-600">{{ $course->category?->name }}</p>
                        <p class="mt-auto pt-2 text-xs text-slate-500">
                            {{ trans_choice('{0} bez kapitol|{1} :count kapitola|[2,4] :count kapitoly|[5,*] :count kapitol', $course->chapters_count, ['count' => $course->chapters_count]) }}
                            · {{ trans_choice('{0} nepriradený|{1} :count priradenie|[2,4] :count priradenia|[5,*] :count priradení', $course->assignments_count, ['count' => $course->assignments_count]) }}
                            @unless ($course->author_id === auth()->id())
                                · {{ $course->author?->name }}
                            @endunless
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $courses->links() }}</div>
    @endif
</x-layouts::app>
