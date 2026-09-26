{{--
    Toast stack. Shows session flashes ("status", "success", "error") and listens for the browser
    event "toast", e.g. from Livewire: $this->dispatch('toast', type: 'success', message: '...').
--}}
@php
    $initial = collect([
        ['type' => 'success', 'message' => session('success') ?? session('status')],
        ['type' => 'error', 'message' => session('error')],
    ])->filter(fn ($toast) => filled($toast['message']))->values();
@endphp

<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: toast.type ?? 'success', message: toast.message });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
    }"
    x-init="@js($initial).forEach(t => add(t))"
    @toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 sm:items-end"
    aria-live="polite"
    role="status"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition.opacity.duration.200ms
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-white p-4 shadow-lg"
            :class="toast.type === 'error' ? 'border-red-200' : 'border-emerald-200'"
        >
            <span :class="toast.type === 'error' ? 'text-red-600' : 'text-emerald-600'">
                <template x-if="toast.type === 'error'"><x-icon name="alert" /></template>
                <template x-if="toast.type !== 'error'"><x-icon name="check" /></template>
            </span>
            <p class="flex-1 text-sm text-slate-800" x-text="toast.message"></p>
            <button type="button" @click="remove(toast.id)" class="rounded text-slate-400 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-600">
                <span class="sr-only">{{ __('Zavrieť') }}</span>
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
