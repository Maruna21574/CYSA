<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <x-toasts />

    <main class="flex min-h-full flex-col items-center justify-center px-4 py-12">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 text-indigo-700">
            <x-icon name="shield" class="size-9" />
            <span class="text-2xl font-bold tracking-tight">{{ config('app.name') }}</span>
        </a>

        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            {{ $slot }}
        </div>

        <p class="mt-8 text-center text-xs text-slate-500">{{ __('Vzdelávacia platforma kybernetickej bezpečnosti pre školy') }}</p>
    </main>

    @livewireScripts
</body>
</html>
