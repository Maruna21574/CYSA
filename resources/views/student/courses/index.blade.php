<x-layouts::app :title="__('Moje kurzy')">
    <x-page-header :title="__('Moje kurzy')" :description="__('Kurzy, ktoré ti priradil učiteľ.')" />

    @if ($courses->isEmpty())
        <x-empty-state icon="book" :title="__('Zatiaľ nemáš priradené žiadne kurzy')" :description="__('Keď ti učiteľ priradí kurz, nájdeš ho tu.')" />
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $course)
                <a href="{{ route('courses.show', $course) }}" class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs transition hover:shadow-md focus-visible:outline-2 focus-visible:outline-brand-600">
                    <x-course-cover :course="$course" class="h-36 w-full" />
                    <div class="flex flex-1 flex-col gap-2 p-4">
                        <div class="flex flex-wrap gap-2">
                            <x-badge>{{ $course->difficulty->label() }}</x-badge>
                            @if ($course->category)
                                <x-badge color="brand">{{ $course->category->name }}</x-badge>
                            @endif
                        </div>
                        <h2 class="font-semibold text-slate-900 group-hover:text-brand-700">{{ $course->title }}</h2>
                        <p class="mt-auto pt-2 text-xs text-slate-500">
                            {{ trans_choice('{0} bez kapitol|{1} :count kapitola|[2,4] :count kapitoly|[5,*] :count kapitol', $course->chapters_count, ['count' => $course->chapters_count]) }}
                            · {{ $course->author?->name }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</x-layouts::app>
