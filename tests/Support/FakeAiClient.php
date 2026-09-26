<?php

namespace Tests\Support;

use App\Services\AI\AiException;
use App\Services\AI\AiResult;
use App\Services\AI\Contracts\AiClient;

/**
 * Test double of the AI provider: returns queued answers and records every request.
 */
class FakeAiClient implements AiClient
{
    /** @var list<array{system: string, prompt: string, schema: array<string, mixed>}> */
    public array $requests = [];

    /**
     * @param  list<array<string, mixed>|AiException>  $responses
     */
    public function __construct(private array $responses = []) {}

    public function structured(string $system, string $prompt, array $schema, int $maxTokens = 16000): AiResult
    {
        $this->requests[] = ['system' => $system, 'prompt' => $prompt, 'schema' => $schema];
        $next = array_shift($this->responses) ?? ['questions' => []];

        if ($next instanceof AiException) {
            throw $next;
        }

        return new AiResult($next, inputTokens: 1200, outputTokens: 800, model: 'fake-model');
    }

    public function provider(): string
    {
        return 'fake';
    }

    public function model(): string
    {
        return 'fake-model';
    }
}
