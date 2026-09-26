<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\Certificates\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public certificate verification. Shows only what is needed to confirm authenticity:
 * validity, holder's name, course title and date of issue.
 */
class VerifyCertificateController extends Controller
{
    public function __invoke(Request $request, ?string $code = null): View|RedirectResponse
    {
        if ($code === null && $request->filled('code')) {
            return redirect()->route('certificates.verify', CertificateService::normalizeCode((string) $request->query('code')));
        }

        $normalized = $code !== null ? CertificateService::normalizeCode($code) : null;

        return view('certificates.verify', [
            'code' => $normalized,
            'certificate' => $normalized ? Certificate::where('code', $normalized)->first() : null,
        ]);
    }
}
