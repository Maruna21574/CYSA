<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLogger;
use App\Services\Users\PersonalDataExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The user's own settings. Name, e-mail and role are managed by the school, not by the user.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()->load('school')]);
    }

    /**
     * GDPR: download of all personal data the platform stores about the signed-in user.
     */
    public function export(Request $request, PersonalDataExporter $exporter, AuditLogger $audit): JsonResponse
    {
        $audit->log(AuditAction::DataExported, $request->user(), metadata: ['type' => 'personal_data']);

        return response()->json($exporter->export($request->user()), 200, [
            'Content-Disposition' => 'attachment; filename="moje-udaje-'.now()->format('Ymd').'.json"',
            'Cache-Control' => 'no-store, private',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate(['email_notifications' => ['boolean']]);

        $request->user()->forceFill(['email_notifications' => (bool) ($data['email_notifications'] ?? false)])->save();

        return back()->with('success', __('Nastavenia boli uložené.'));
    }

    public function updatePassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();
        $audit->log(AuditAction::PasswordChanged, $request->user());

        return back()->with('success', __('Heslo bolo zmenené.'));
    }
}
