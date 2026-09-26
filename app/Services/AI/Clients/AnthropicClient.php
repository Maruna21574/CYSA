<?php

namespace App\Services\AI\Clients;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Services\AI\AiException;
use App\Services\AI\AiResult;
use App\Services\AI\Contracts\AiClient;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientInterface;

/**
 * Claude (Anthropic Messages API) through the official PHP SDK, with structured JSON output.
 * The API key is read from the server configuration only and never reaches the browser.
 */
class AnthropicClient implements AiClient
{
    /** Models that support the server-side refusal fallback ("fallbacks": "default"). */
    private const FALLBACK_MODELS = ['claude-opus-5', 'claude-opus-5-5', 'claude-fable-5', 'claude-fable-5-1'];

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $effort = 'high',
        private int $timeout = 300,
        private ?ClientInterface $transporter = null, // custom PSR-18 HTTP client (tests)
    ) {}

    public function provider(): string
    {
        return 'anthropic';
    }

    public function model(): string
    {
        return $this->model;
    }

    public function structured(string $system, string $prompt, array $schema, int $maxTokens = 16000): AiResult
    {
        $client = new Client(apiKey: $this->apiKey, requestOptions: [
            'timeout' => (float) $this->timeout,
            'maxRetries' => 2, // the SDK retries 408/409/429/5xx and connection errors
            'transporter' => $this->transporter,
        ]);

        $withFallback = in_array($this->model, self::FALLBACK_MODELS, true);

        try {
            $message = $client->beta->messages->create(
                maxTokens: $maxTokens,
                messages: [['role' => 'user', 'content' => $prompt]],
                model: $this->model,
                system: $system,
                outputConfig: [
                    'effort' => $this->effort,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                // If the model declines, the API re-runs the request on a suitable fallback model.
                fallbacks: $withFallback ? 'default' : null,
                betas: $withFallback ? ['server-side-fallback-2026-07-01'] : null,
            );
        } catch (AuthenticationException|PermissionDeniedException $e) {
            throw $this->fail(__('AI služba odmietla prístup. Skontrolujte API kľúč v nastaveniach servera.'), $e);
        } catch (RateLimitException $e) {
            throw $this->fail(__('AI služba je momentálne preťažená. Skúste to o chvíľu znova.'), $e);
        } catch (BadRequestException $e) {
            throw $this->fail(__('AI služba požiadavku neprijala (napríklad je materiál príliš dlhý).'), $e);
        } catch (APIStatusException $e) {
            throw $this->fail(__('AI služba vrátila chybu. Skúste to neskôr.'), $e);
        } catch (APIConnectionException $e) {
            throw $this->fail(__('Nepodarilo sa spojiť s AI službou. Skúste to neskôr.'), $e);
        }

        if ($message->stopReason === 'refusal') {
            throw new AiException(__('AI odmietla spracovať tento materiál.'));
        }

        if ($message->stopReason === 'max_tokens') {
            throw new AiException(__('Odpoveď AI bola príliš dlhá. Skúste vygenerovať menej otázok naraz.'));
        }

        $text = '';

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new AiException(__('AI vrátila odpoveď v neočakávanom formáte. Skúste to znova.'));
        }

        return new AiResult(
            data: $data,
            inputTokens: (int) $message->usage->inputTokens,
            outputTokens: (int) $message->usage->outputTokens,
            model: $message->model,
        );
    }

    private function fail(string $message, \Throwable $previous): AiException
    {
        // Log the technical reason for the administrator; the teacher sees only the message.
        Log::warning('AI request failed', ['provider' => 'anthropic', 'exception' => $previous::class, 'message' => $previous->getMessage()]);

        return new AiException($message, previous: $previous);
    }
}
