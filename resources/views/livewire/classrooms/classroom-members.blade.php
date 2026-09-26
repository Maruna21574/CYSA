<div class="grid gap-6 lg:grid-cols-3">
    <div class="flex flex-col gap-6 lg:col-span-2">
        <x-card :title="__('Učitelia') . ' (' . $teachers->count() . ')'">
            @forelse ($teachers as $teacher)
                <div wire:key="teacher-{{ $teacher->id }}" class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 last:border-0">
                    <div>
                        <span class="block text-sm font-medium text-slate-900">{{ $teacher->name }}</span>
                        <span class="text-xs text-slate-500">{{ $teacher->email }}</span>
                    </div>
                    @if ($canManage)
                        <button type="button" wire:click="remove({{ $teacher->id }})" wire:confirm="{{ __('Odobrať :name z triedy?', ['name' => $teacher->name]) }}" class="text-sm font-medium text-red-700 hover:underline">
                            {{ __('Odobrať') }}<span class="sr-only"> {{ $teacher->name }}</span>
                        </button>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Trieda zatiaľ nemá priradeného učiteľa.') }}</p>
            @endforelse
        </x-card>

        <x-card :title="__('Študenti') . ' (' . $students->count() . ')'">
            @forelse ($students as $student)
                <div wire:key="student-{{ $student->id }}" class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 last:border-0">
                    <div>
                        <span class="block text-sm font-medium text-slate-900">{{ $student->name }}</span>
                        <span class="text-xs text-slate-500">{{ $student->email }}</span>
                    </div>
                    @if ($canManage)
                        <button type="button" wire:click="remove({{ $student->id }})" wire:confirm="{{ __('Odobrať :name z triedy?', ['name' => $student->name]) }}" class="text-sm font-medium text-red-700 hover:underline">
                            {{ __('Odobrať') }}<span class="sr-only"> {{ $student->name }}</span>
                        </button>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Trieda zatiaľ nemá študentov.') }}</p>
            @endforelse
        </x-card>
    </div>

    @if ($canManage)
        <x-card :title="__('Pridať do triedy')" class="h-fit">
            <div class="mb-3 flex rounded-lg bg-slate-100 p-1" role="radiogroup" aria-label="{{ __('Typ člena') }}">
                @foreach (['student' => __('Študenta'), 'teacher' => __('Učiteľa')] as $value => $label)
                    <label @class([
                        'flex-1 cursor-pointer rounded-md px-3 py-1.5 text-center text-sm font-medium has-focus-visible:outline-2 has-focus-visible:outline-indigo-600',
                        'bg-white text-slate-900 shadow-xs' => $addRole === $value,
                        'text-slate-600' => $addRole !== $value,
                    ])>
                        <input type="radio" wire:model.live="addRole" value="{{ $value }}" class="sr-only">
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            <x-search-input id="member-search" wire:model.live.debounce.300ms="search" :label="__('Hľadať podľa mena alebo e-mailu')" />

            <ul class="mt-3 flex flex-col gap-1" wire:loading.class="opacity-60">
                @forelse ($this->candidates as $candidate)
                    <li wire:key="candidate-{{ $candidate->id }}" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                        <span class="text-sm">
                            <span class="block font-medium text-slate-900">{{ $candidate->name }}</span>
                            <span class="text-xs text-slate-500">{{ $candidate->email }}</span>
                        </span>
                        <button type="button" wire:click="add({{ $candidate->id }})" class="rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                            {{ __('Pridať') }}<span class="sr-only"> {{ $candidate->name }}</span>
                        </button>
                    </li>
                @empty
                    <li class="px-2 py-3 text-sm text-slate-500">{{ __('Nenašli sa žiadni ďalší používatelia.') }}</li>
                @endforelse
            </ul>
        </x-card>
    @endif
</div>
