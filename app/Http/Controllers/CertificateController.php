<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Services\Certificates\CertificatePdf;
use App\Services\Certificates\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    /**
     * The student's certificates and the state of the conditions in courses still in progress.
     */
    public function index(Request $request, CertificateService $certificates): View
    {
        $user = $request->user();

        $owned = Certificate::where('user_id', $user->id)->latest('issued_at')->get();

        $inProgress = Course::availableTo($user)
            ->where('certificate_enabled', true)
            ->whereNotIn('id', $owned->pluck('course_id'))
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course): array => ['course' => $course, 'result' => $certificates->evaluate($user, $course)]);

        return view('certificates.index', ['certificates' => $owned, 'inProgress' => $inProgress]);
    }

    public function download(Certificate $certificate, CertificatePdf $pdf): Response
    {
        Gate::authorize('view', $certificate);
        abort_unless($certificate->isValid(), 410, __('Certifikát bol zrušený.'));

        return $pdf->download($certificate);
    }

    public function revoke(Request $request, Certificate $certificate, CertificateService $certificates): RedirectResponse
    {
        Gate::authorize('revoke', $certificate);

        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];
        $certificates->revoke($certificate, $reason);

        return back()->with('success', __('Certifikát bol zrušený.'));
    }
}
