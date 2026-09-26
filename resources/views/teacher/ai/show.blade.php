<x-layouts::app :title="__('AI návrhy otázok')">
    <x-page-header :title="__('AI návrhy otázok')" :description="$generation->sourceLabel()" :breadcrumbs="[
        ['label' => __('AI návrhy'), 'url' => route('teacher.ai.index')],
        ['label' => $generation->course->title, 'url' => route('teacher.courses.show', $generation->course)],
        ['label' => $generation->sourceLabel()],
    ]" />

    <livewire:teacher.ai-question-review :generation="$generation" />
</x-layouts::app>
