@props(['course'])

@if ($course->cover_path)
    <img src="{{ route('courses.cover', $course) }}" alt="" loading="lazy" {{ $attributes->class('object-cover') }}>
@else
    <div {{ $attributes->class('flex items-center justify-center bg-gradient-to-br from-indigo-500 to-sky-500 text-white/90') }} aria-hidden="true">
        <x-icon name="shield" class="size-12" />
    </div>
@endif
