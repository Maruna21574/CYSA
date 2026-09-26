<?php

namespace App\Services\Certificates;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the certificate as an A4 landscape PDF with a QR code pointing to the public
 * verification page. Generated on demand from the stored snapshot, nothing is cached on disk.
 */
class CertificatePdf
{
    public function download(Certificate $certificate): Response
    {
        $qr = (new PngWriter)->write(new QrCode(
            data: $certificate->verificationUrl(),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 240,
            margin: 0,
        ));

        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'qr' => $qr->getDataUri(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('certifikat-'.Str::slug($certificate->course_title).'-'.$certificate->code.'.pdf');
    }
}
