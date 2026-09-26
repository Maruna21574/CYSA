<?php

namespace App\Events;

use App\Models\Certificate;
use Illuminate\Foundation\Events\Dispatchable;

class CertificateIssued
{
    use Dispatchable;

    public function __construct(public Certificate $certificate) {}
}
