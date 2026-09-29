@props(['title', 'description' => null, 'breadcrumbs' => []])

{{-- $breadcrumbs: list of ['label' => ..., 'url' => ...]; the last item is the current page. --}}
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if ($breadcrumbs)
            <nav aria-label="{{ __('Navigačná cesta') }}" class="mb-2">
                <ol class="flex flex-wrap items-center gap-1 text-sm text-slate-500">
                    @foreach ($breadcrumbs as $crumb)
                        <li class="flex items-center gap-1">
                            @if (! $loop->first)
                                <span aria-hidden="true">/</span>
                            @endif
                            @if ($loop->last || empty($crumb['url']))
                                <span @if ($loop->last) aria-current="page" @endif class="text-slate-700">{{ $crumb['label'] }}</span>
                            @else
                                <a href="{{ $crumb['url'] }}" class="hover:text-brand-700 hover:underline">{{ $crumb['label'] }}</a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>

        @if ($description)
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
