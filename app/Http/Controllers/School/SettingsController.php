<?php

namespace App\Http\Controllers\School;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Settings of the school, edited by its administrator.
 */
class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('school.settings', ['school' => $request->user()->school]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['gamification' => ['boolean']]);
        $school = $request->user()->school;
        $old = $school->settings ?? [];

        $school->settings = [...$old, 'gamification' => (bool) ($data['gamification'] ?? false)];
        $school->save();

        $audit->log(AuditAction::SchoolUpdated, $school, ['settings' => $old], ['settings' => $school->settings]);

        return back()->with('success', __('Nastavenia školy boli uložené.'));
    }
}
