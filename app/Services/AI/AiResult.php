<?php

namespace App\Services\AI;

final readonly class AiResult
{
    /**
     * @param  array<string, mixed>  $data  decoded JSON answer
     */
    public function __construct(
        public array $data,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public string $model = '',
    ) {}
}
