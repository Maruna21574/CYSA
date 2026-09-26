<x-layouts::app :title="$course->title">
    @include('teacher.courses._header', ['active' => 'structure'])

    <livewire:teacher.course-builder :course="$course" />
</x-layouts::app>
