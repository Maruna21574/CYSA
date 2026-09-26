@props(['filter', 'action', 'classrooms' => [], 'courses' => [], 'quizzes' => []])

{{-- GET filter form shared by the analytics pages. --}}
<form method="GET" action="{{ $action }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
    @foreach ([['classroom', __('Trieda'), $classrooms, $filter->classroomId], ['course', __('Kurz'), $courses, $filter->courseId], ['quiz', __('Test'), $quizzes, $filter->quizId]] as [$name, $label, $options, $selected])
        @if ($options !== [])
            <div>
                <label for="filter-{{ $name }}" class="block text-xs font-medium text-slate-600">{{ $label }}</label>
                <select id="filter-{{ $name }}" name="{{ $name }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                    <option value="">{{ __('Všetky') }}</option>
                    @foreach ($options as $id => $title)
                        <option value="{{ $id }}" @selected($selected === $id)>{{ $title }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    @endforeach
    <div>
        <label for="filter-from" class="block text-xs font-medium text-slate-600">{{ __('Od') }}</label>
        <input id="filter-from" type="date" name="from" value="{{ $filter->from?->toDateString() }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
    </div>
    <div>
        <label for="filter-to" class="block text-xs font-medium text-slate-600">{{ __('Do') }}</label>
        <input id="filter-to" type="date" name="to" value="{{ $filter->to?->toDateString() }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
    </div>
    <div class="flex gap-2">
        <x-button class="py-1.5">{{ __('Filtrovať') }}</x-button>
        <x-link-button variant="secondary" :href="$action" class="py-1.5">{{ __('Zrušiť') }}</x-link-button>
    </div>
</form>
