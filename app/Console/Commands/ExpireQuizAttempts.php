<?php

namespace App\Console\Commands;

use App\Services\Quiz\AttemptService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quiz:expire-attempts')]
#[Description('Automaticky odovzdá pokusy, ktorým vypršal čas')]
class ExpireQuizAttempts extends Command
{
    public function handle(AttemptService $attempts): int
    {
        $count = $attempts->expireOverdue();

        $this->info("Odovzdané pokusy: {$count}");

        return self::SUCCESS;
    }
}
