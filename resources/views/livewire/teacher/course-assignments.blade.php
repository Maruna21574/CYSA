<div class="grid gap-6 lg:grid-cols-3">
    <x-card :title="__('Komu je kurz priradený')" class="lg:col-span-2">
        @forelse ($assignments as $assignment)
            <div wire:key="assignment-{{ $assignment->id }}" class="flex flex-wrap items-center gap-3 border-b border-slate-100 py-3 last:border-0">
                <span class="text-slate-500"><x-icon :name="$assignment->classroom_id ? 'squares' : 'users'" /></span>
                <div class="min-w-0 flex-1">
                    @if ($assignment->classroom)
                        <span class="block text-sm font-medium text-slate-900">{{ __('Trieda :name', ['name' => $assignment->classroom->name]) }} <span class="font-normal text-slate-500">({{ $assignment->classroom->school_year }})</span></span>
                        <span class="text-xs text-slate-500">{{ trans_choice('{0} bez študentov|{1} :count študent|[2,4] :count študenti|[5,*] :count študentov', $assignment->classroom->students_count, ['count' => $assignment->classroom->students_count]) }}</span>
                    @else
                        <span class="block text-sm font-medium text-slate-900">{{ $assignment->user?->name }}</span>
                        <span class="text-xs text-slate-500">{{ $assignment->user?->email }}</span>
                    @endif
                </div>
                <div class="text-xs text-slate-600">
                    @if ($assignment->available_from)
                        <span class="block">{{ __('Od :date', ['date' => $assignment->available_from->translatedFormat('j. n. Y H:i')]) }}</span>
                    @endif
                    @if ($assignment->due_at)
                        <span class="block">{{ __('Termín :date', ['date' => $assignment->due_at->translatedFormat('j. n. Y H:i')]) }}</span>
                    @endif
                </div>
                <button type="button" wire:click="unassign({{ $assignment->id }})" wire:confirm="{{ __('Zrušiť toto priradenie?') }}" class="text-sm font-medium text-red-700 hover:underline">
                    {{ __('Zrušiť') }}
                </button>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Kurz zatiaľ nie je priradený žiadnej triede ani študentovi.') }}</p>
        @endforelse
    </x-card>

    <div class="flex flex-col gap-4">
        <x-card :title="__('Termíny')" class="flex flex-col gap-3">
            <p class="text-xs text-slate-500">{{ __('Použijú sa pri ďalšom priradení. Obe polia sú nepovinné.') }}</p>
            <x-form.input name="availableFrom" id="available-from" type="datetime-local" :label="__('Sprístupniť od')" wire:model="availableFrom" />
            <x-form.input name="dueAt" id="due-at" type="datetime-local" :label="__('Termín dokončenia')" wire:model="dueAt" />
        </x-card>

        <x-card :title="__('Priradiť triede')">
            <form wire:submit="assignClassroom" class="flex flex-col gap-3">
                <div>
                    <label for="assign-classroom" class="sr-only">{{ __('Trieda') }}</label>
                    <select id="assign-classroom" wire:model="classroomId" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">{{ __('— vyberte triedu —') }}</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }} ({{ $classroom->school_year }})</option>
                        @endforeach
                    </select>
                    @error('classroomId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-button>{{ __('Priradiť triede') }}</x-button>
            </form>
        </x-card>

        <x-card :title="__('Priradiť študentovi')">
            <x-search-input id="assign-student" wire:model.live.debounce.300ms="studentSearch" :label="__('Hľadať študenta (min. 2 znaky)')" />
            <ul class="mt-2 flex flex-col gap-1">
                @foreach ($this->studentResults as $student)
                    <li wire:key="student-result-{{ $student->id }}" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                        <span class="text-sm"><span class="block font-medium">{{ $student->name }}</span><span class="text-xs text-slate-500">{{ $student->email }}</span></span>
                        <button type="button" wire:click="assignStudent({{ $student->id }})" class="rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">{{ __('Priradiť') }}</button>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</div>
