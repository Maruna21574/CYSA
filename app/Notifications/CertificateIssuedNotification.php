<?php

namespace App\Notifications;

use App\Models\Certificate;

class CertificateIssuedNotification extends CysaNotification
{
    public function __construct(public Certificate $certificate) {}

    public function title(): string
    {
        return __('Získal(a) si certifikát!');
    }

    public function body(): string
    {
        return __('Gratulujeme, splnil(a) si podmienky kurzu „:course“. Certifikát si môžeš stiahnuť ako PDF.', [
            'course' => $this->certificate->course_title,
        ]);
    }

    public function url(): string
    {
        return route('student.certificates.index');
    }

    public function icon(): string
    {
        return 'badge';
    }
}
