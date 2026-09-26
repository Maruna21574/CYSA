<x-layouts::app :title="__('Priradenie kurzu')">
    @include('teacher.courses._header', ['active' => 'assignments'])

    @unless ($course->isPublished())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800" role="status">
            {{ __('Kurz zatiaľ nie je publikovaný. Študenti ho uvidia až po publikovaní.') }}
        </div>
    @endunless

    <livewire:teacher.course-assignments :course="$course" />
</x-layouts::app>
