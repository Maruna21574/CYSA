<x-layouts::app :title="__('Certifikáty')">
    <x-page-header :title="__('Moje certifikáty')" :description="__('Certifikát získaš automaticky po splnení podmienok kurzu.')" />

    @if ($certificates->isEmpty() && $inProgress->isEmpty())
        <x-empty-state icon="badge" :title="__('Zatiaľ nemáš žiadne certifikáty')" :description="__('Tvoje kurzy zatiaľ nevydávajú certifikát.')" />
    @endif

    @if ($certificates->isNotEmpty())
        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($certificates as $certificate)
                <article class="flex flex-col gap-3 rounded-xl border border-brand-200 bg-gradient-to-br from-brand-50 to-white p-5 shadow-xs">
                    <div class="flex items-center gap-2 text-brand-700"><x-icon name="badge" class="size-6" /><span class="text-xs font-semibold tracking-wide uppercase">{{ __('Certifikát') }}</span></div>
                    <h2 class="font-semibold text-slate-900">{{ $certificate->course_title }}</h2>
                    <p class="text-sm text-slate-600">{{ __('Vydaný :date', ['date' => $certificate->issued_at->translatedFormat('j. n. Y')]) }}</p>
                    <p class="font-mono text-xs text-slate-500">{{ $certificate->code }}</p>
                    @if ($certificate->isValid())
                        <x-link-button :href="route('certificates.download', $certificate)" class="mt-auto"><x-icon name="download" class="size-4" />{{ __('Stiahnuť PDF') }}</x-link-button>
                    @else
                        <x-badge color="red">{{ __('Zrušený') }}</x-badge>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    @if ($inProgress->isNotEmpty())
        <h2 class="mb-3 text-lg font-semibold text-slate-900">{{ __('Čo ti chýba k certifikátu') }}</h2>
        <div class="flex flex-col gap-4">
            @foreach ($inProgress as $item)
                <x-card :title="$item['course']->title">
                    <ul class="flex flex-col gap-1 text-sm text-slate-700">
                        @foreach ($item['result']->missing as $condition)
                            <li class="flex items-start gap-2"><x-icon name="lock" class="mt-0.5 size-4 text-slate-400" />{{ $condition }}</li>
                        @endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    @endif
</x-layouts::app>
