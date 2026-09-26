{{-- Scrollable table container; horizontal scroll keeps wide tables usable on phones. --}}
<div {{ $attributes->class('overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs') }}>
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        {{ $slot }}
    </table>
</div>
