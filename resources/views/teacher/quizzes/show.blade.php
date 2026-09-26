<x-layouts::app :title="$quiz->title">
    @include('teacher.quizzes._header', ['active' => 'questions'])

    <livewire:teacher.quiz-builder :quiz="$quiz" />
</x-layouts::app>
