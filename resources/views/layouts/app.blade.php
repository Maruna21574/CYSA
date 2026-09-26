@php
    $user = auth()->user();
    $navigation = \App\Support\Navigation::for($user);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="h-full font-sans text-slate-900 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <a href="#main" class="sr-only z-50 rounded bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:top-2 focus:left-2">{{ __('Preskočiť na obsah') }}</a>

    <x-toasts />

    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false" x-cloak></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0"
        :class="{ 'translate-x-0': sidebarOpen }"
        aria-label="{{ __('Hlavné menu') }}"
    >
        <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-indigo-700">
                <x-icon name="shield" class="size-7" />
                <span class="text-lg font-bold tracking-tight">{{ config('app.name') }}</span>
            </a>
            <button type="button" class="rounded p-1 text-slate-500 hover:text-slate-700 lg:hidden" @click="sidebarOpen = false">
                <span class="sr-only">{{ __('Zavrieť menu') }}</span>
                <x-icon name="x" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto p-3">
            <ul class="flex flex-col gap-1">
                @foreach ($navigation as $item)
                    @php $active = request()->routeIs($item['active']); @endphp
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($active) aria-current="page" @endif
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-indigo-50 text-indigo-700' => $active,
                                'text-slate-700 hover:bg-slate-100 hover:text-slate-900' => ! $active,
                            ])
                        >
                            <x-icon :name="$item['icon']" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($user->school)
            <div class="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
                <span class="block font-medium text-slate-700">{{ $user->school->name }}</span>
                {{ $user->role->label() }}
            </div>
        @endif
    </aside>

    <div class="flex min-h-full flex-col lg:pl-64">
        {{-- Top navigation --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" class="rounded p-1 text-slate-600 hover:text-slate-900 lg:hidden" @click="sidebarOpen = true" :aria-expanded="sidebarOpen">
                <span class="sr-only">{{ __('Otvoriť menu') }}</span>
                <x-icon name="menu" class="size-6" />
            </button>

            <div class="flex-1"></div>

            <livewire:notification-bell />

            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg p-1 text-left hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-indigo-600"
                    @click="open = ! open"
                    :aria-expanded="open"
                    aria-haspopup="true"
                >
                    <span class="flex size-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white" aria-hidden="true">{{ $user->initials() }}</span>
                    <span class="hidden text-sm sm:block">
                        <span class="block font-medium text-slate-900">{{ $user->name }}</span>
                        <span class="block text-xs text-slate-500">{{ $user->role->label() }}</span>
                    </span>
                    <x-icon name="chevron-down" class="size-4 text-slate-500" />
                </button>

                <div
                    x-show="open"
                    x-transition.origin.top.right
                    x-cloak
                    class="absolute right-0 mt-2 w-56 rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                >
                    <div class="border-b border-slate-100 px-4 py-2 text-sm">
                        <span class="block font-medium text-slate-900">{{ $user->name }}</span>
                        <span class="block truncate text-slate-500">{{ $user->email }}</span>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <x-icon name="adjustments" class="size-4" />
                        {{ __('Môj profil') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 focus-visible:bg-slate-50 focus-visible:outline-none">
                            <x-icon name="logout" class="size-4" />
                            {{ __('Odhlásiť sa') }}
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main id="main" class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
