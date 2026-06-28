<?php

namespace App\Services\Squarespace;

use App\Enums\SquarespaceLogDirection;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Writes a request/response audit trail for every Squarespace HTTP exchange —
 * inbound webhooks and outbound API calls — to a dedicated log channel that is
 * browsable via Log Viewer. Sensitive values (tokens, secrets, auth headers)
 * are masked before being written.
 */
class SquarespaceLogger
{
    /**
     * @var list<string>
     */
    private array $sensitiveKeys = [
        'client_secret',
        'access_token',
        'refresh_token',
        'code',
        'secret',
    ];

    public function enabled(): bool
    {
        return (bool) config('squarespace.logging_enabled', true);
    }

    private function channel(): LoggerInterface
    {
        return Log::channel((string) config('squarespace.log_channel', 'squarespace'));
    }

    /**
     * Record an inbound webhook notification together with the response we sent.
     */
    public function logIncomingWebhook(
        Request $request,
        ?bool $signatureValid,
        int $responseStatus,
        ?string $responseBody,
        ?string $topic = null,
        ?string $notificationId = null,
    ): void {
        if (! $this->enabled()) {
            return;
        }

        $context = [
            'direction' => SquarespaceLogDirection::INCOMING,
            'topic' => $topic,
            'notification_id' => $notificationId,
            'signature' => $request->header('Squarespace-Signature'),
            'signature_valid' => $signatureValid,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'request_headers' => $this->maskHeaders($request->headers->all()),
            'request_body' => $this->decode($request->getContent()),
            'response_status' => $responseStatus,
            'response_body' => $this->decode($responseBody),
        ];

        $message = sprintf(
            'Webhook IN %s [%s] -> %d%s',
            $topic ?? 'unknown',
            $notificationId ?? '-',
            $responseStatus,
            $signatureValid === false ? ' (invalid signature)' : '',
        );

        $signatureValid === false
            ? $this->channel()->warning($message, $context)
            : $this->channel()->info($message, $context);
    }

    /**
     * Record an outbound API call.
     *
     * @param  array<string, mixed>  $requestBody
     */
    public function logOutgoing(
        string $label,
        string $method,
        string $url,
        array $requestBody,
        ?Response $response,
        int $durationMs,
        ?string $error = null,
    ): void {
        if (! $this->enabled()) {
            return;
        }

        $status = $response?->status();

        $context = [
            'direction' => SquarespaceLogDirection::OUTGOING,
            'label' => $label,
            'method' => strtoupper($method),
            'url' => $url,
            'request_body' => $this->mask($requestBody),
            'response_status' => $status,
            'response_body' => $response !== null ? $this->decode($this->maskRawBody($response->body())) : null,
            'duration_ms' => $durationMs,
            'error' => $error,
        ];

        $message = sprintf('API OUT %s %s %s -> %s (%dms)', $label, strtoupper($method), $url, $status ?? 'ERR', $durationMs);

        if ($error !== null || ($status !== null && $status >= 400)) {
            $this->channel()->error($message, $context);
        } else {
            $this->channel()->info($message, $context);
        }
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function maskHeaders(array $headers): array
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === 'authorization') {
                $headers[$key] = ['***redacted***'];
            }
        }

        return $headers;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mask($value);
                continue;
            }

            if (in_array(strtolower((string) $key), $this->sensitiveKeys, true) && is_string($value) && $value !== '') {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }

    private function maskRawBody(string $body): string
    {
        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            return (string) json_encode($this->mask($decoded), JSON_UNESCAPED_SLASHES);
        }

        return $body;
    }

    private function redact(string $value): string
    {
        return substr($value, 0, 4) . '…***redacted***';
    }

    /**
     * Decode a JSON string to an array for structured logging; fall back to the
     * raw string when it isn't JSON.
     */
    private function decode(?string $body): mixed
    {
        if ($body === null || $body === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $body;
    }
}
