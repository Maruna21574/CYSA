<?php

namespace Tests\Unit;

use App\Services\AI\AiException;
use App\Services\AI\Clients\AnthropicClient;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

/**
 * Request shape and response handling of the Claude client, without calling the real API.
 */
class AnthropicClientTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: AnthropicClient, 1: object}
     */
    private function client(array $overrides = [], int $status = 200, string $model = 'claude-opus-5'): array
    {
        $body = array_replace([
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => $model,
            'content' => [['type' => 'text', 'text' => '{"questions":[]}']],
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 321, 'output_tokens' => 45],
        ], $overrides);

        $transport = new class($status, $body) implements ClientInterface
        {
            public ?RequestInterface $request = null;

            /** @param array<string, mixed> $body */
            public function __construct(private int $status, private array $body) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->request = $request;

                return new Response($this->status, ['Content-Type' => 'application/json'], json_encode($this->body));
            }
        };

        return [new AnthropicClient('test-key', $model, 'high', 30, $transport), $transport];
    }

    public function test_request_uses_structured_output_effort_and_fallbacks(): void
    {
        [$client, $transport] = $this->client();

        $result = $client->structured('system text', 'prompt text', ['type' => 'object', 'properties' => [], 'required' => [], 'additionalProperties' => false]);

        $this->assertSame(['questions' => []], $result->data);
        $this->assertSame(321, $result->inputTokens);
        $this->assertSame(45, $result->outputTokens);

        $request = $transport->request;
        $payload = json_decode((string) $request->getBody(), true);

        $this->assertStringEndsWith('/v1/messages?beta=true', (string) $request->getUri());
        $this->assertSame('test-key', $request->getHeaderLine('x-api-key'));
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));
        $this->assertSame('claude-opus-5', $payload['model']);
        $this->assertSame('default', $payload['fallbacks']);
        $this->assertSame('high', $payload['output_config']['effort']);
        $this->assertSame('json_schema', $payload['output_config']['format']['type']);
        $this->assertSame('system text', $payload['system']);
        $this->assertSame([['role' => 'user', 'content' => 'prompt text']], $payload['messages']);
    }

    public function test_models_without_fallback_support_do_not_send_it(): void
    {
        [$client, $transport] = $this->client(model: 'claude-sonnet-5');

        $client->structured('s', 'p', ['type' => 'object']);

        $payload = json_decode((string) $transport->request->getBody(), true);
        $this->assertArrayNotHasKey('fallbacks', $payload);
        $this->assertSame('', $transport->request->getHeaderLine('anthropic-beta'));
    }

    public function test_refusal_and_truncation_raise_readable_errors(): void
    {
        [$refusing] = $this->client(['stop_reason' => 'refusal', 'content' => []]);

        try {
            $refusing->structured('s', 'p', ['type' => 'object']);
            $this->fail('Refusal must raise an exception.');
        } catch (AiException $e) {
            $this->assertSame(__('AI odmietla spracovať tento materiál.'), $e->getMessage());
        }

        [$truncated] = $this->client(['stop_reason' => 'max_tokens']);

        $this->expectException(AiException::class);
        $truncated->structured('s', 'p', ['type' => 'object']);
    }

    public function test_api_errors_do_not_leak_details(): void
    {
        [$client] = $this->client(['type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key sk-secret']], 401);

        try {
            $client->structured('s', 'p', ['type' => 'object']);
            $this->fail('Authentication error must raise an exception.');
        } catch (AiException $e) {
            $this->assertStringNotContainsString('sk-secret', $e->getMessage());
            $this->assertStringContainsString('API kľúč', $e->getMessage());
        }
    }
}
