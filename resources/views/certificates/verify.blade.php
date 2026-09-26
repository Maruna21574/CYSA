<x-layouts::guest :title="__('Overenie certifikátu')">
    <h1 class="text-xl font-semibold text-slate-900">{{ __('Overenie certifikátu') }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ __('Zadajte kód certifikátu, napríklad CYSA-ABCD-EFGH-JKMN.') }}</p>

    <form method="GET" action="{{ route('certificates.verify') }}" class="mt-5 flex gap-2" role="search">
        <label for="code" class="sr-only">{{ __('Kód certifikátu') }}</label>
        <input id="code" name="code" type="text" value="{{ $code }}" maxlength="24" autocomplete="off" required
               class="block w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm uppercase focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
        <x-button>{{ __('Overiť') }}</x-button>
    </form>

    @if ($code !== null)
        <div class="mt-6" role="status">
            @if (! $certificate)
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">{{ __('Certifikát s týmto kódom neexistuje.') }}</p>
                    <p class="mt-1">{{ __('Skontrolujte, či ste kód zadali správne.') }}</p>
                </div>
            @elseif (! $certificate->isValid())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">{{ __('Certifikát bol zrušený a nie je platný.') }}</p>
                </div>
            @else
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p class="flex items-center gap-2 font-semibold"><x-icon name="check-circle" class="size-5" />{{ __('Certifikát je platný') }}</p>
                    <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                        <dt class="text-emerald-800">{{ __('Držiteľ') }}</dt><dd class="font-medium">{{ $certificate->holder_name }}</dd>
                        <dt class="text-emerald-800">{{ __('Kurz') }}</dt><dd class="font-medium">{{ $certificate->course_title }}</dd>
                        <dt class="text-emerald-800">{{ __('Vydaný') }}</dt><dd class="font-medium">{{ $certificate->issued_at->format('j. n. Y') }}</dd>
                    </dl>
                </div>
            @endif
        </div>
    @endif
</x-layouts::guest>
