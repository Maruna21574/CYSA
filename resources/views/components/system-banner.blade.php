{{-- System-wide message set by the super admin (Admin\SettingsController). --}}
@php
    $enabled = \App\Models\SystemSetting::get('banner_enabled', false);
    $message = \App\Models\SystemSetting::get('banner_message');
    $warning = \App\Models\SystemSetting::get('banner_type') === 'warning';
@endphp

@if ($enabled && filled($message))
    <div @class([
        'flex items-center gap-2 px-4 py-2 text-sm sm:px-6 lg:px-8',
        'bg-amber-100 text-amber-900' => $warning,
        'bg-indigo-50 text-indigo-900' => ! $warning,
    ]) role="status">
        <x-icon :name="$warning ? 'alert' : 'bell'" class="size-4" />
        <span>{{ $message }}</span>
    </div>
@endif
