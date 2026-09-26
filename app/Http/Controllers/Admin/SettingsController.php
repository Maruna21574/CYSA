<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * System-wide settings managed by the super admin.
 */
class SettingsController extends Controller
{
    private const KEYS = ['banner_enabled', 'banner_type', 'banner_message', 'default_gamification'];

    public function edit(): View
    {
        return view('admin.settings', [
            'settings' => collect(self::KEYS)->mapWithKeys(fn (string $key): array => [$key => SystemSetting::get($key)])->all(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'banner_enabled' => ['boolean'],
            'banner_type' => ['required', Rule::in(['info', 'warning'])],
            'banner_message' => ['nullable', 'string', 'max:300', 'required_if:banner_enabled,1'],
            'default_gamification' => ['boolean'],
        ]);

        $values = [
            'banner_enabled' => (bool) ($data['banner_enabled'] ?? false),
            'banner_type' => $data['banner_type'],
            'banner_message' => $data['banner_message'] ?? null,
            'default_gamification' => (bool) ($data['default_gamification'] ?? false),
        ];

        SystemSetting::put($values);
        $audit->log(AuditAction::SystemSettingsChanged, newValues: $values);

        return back()->with('success', __('Systémové nastavenia boli uložené.'));
    }
}
