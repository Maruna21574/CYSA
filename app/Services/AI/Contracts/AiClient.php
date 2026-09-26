<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\AiException;
use App\Services\AI\AiResult;

/**
 * Provider-independent access to a language model. The application only ever asks for
 * JSON matching a schema; switching providers means adding one implementation of this
 * interface and changing AI_PROVIDER.
 */
interface AiClient
{
    /**
     * @param  array<string, mixed>  $schema  JSON schema the answer must follow
     *
     * @throws AiException with a message that can be shown to the teacher
     */
    public function structured(string $system, string $prompt, array $schema, int $maxTokens = 16000): AiResult;

    public function provider(): string;

    public function model(): string;
}
