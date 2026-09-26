<?php

namespace App\Services\Certificates;

final readonly class EligibilityResult
{
    /**
     * @param  list<string>  $missing  human readable conditions that are not met yet
     */
    public function __construct(
        public bool $eligible,
        public array $missing,
        public ?float $finalPercentage,
    ) {}
}
