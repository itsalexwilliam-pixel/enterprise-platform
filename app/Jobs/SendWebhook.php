<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * Send Webhook Job
 * Delivers webhook payloads with retry logic and HMAC signing
 * ============================================================
 */
class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $timeout = 30;

    public function __construct(
        private readonly int    $webhookId,
        private readonly string $event,
        private readonly array  $payload
    ) {}

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (! $webhook || $webhook->status !== 'active') {
            return;
        }

        $body = json_encode([
            'event'     => $this->event,
            'timestamp' => now()->toISOString(),
            'data'      => $this->payload,
        ]);

        // Generate HMAC signature
        $signature = hash_hmac('sha256', $body, $webhook->secret ?? '');

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event'      => $this->event,
            'payload'    => $this->payload,
            'status'     => 'pending',
            'attempt'    => $this->attempts(),
            'created_at' => now(),
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type'           => 'application/json',
                'X-Webhook-Signature'    => "sha256={$signature}",
                'X-Webhook-Event'        => $this->event,
                'X-Webhook-Delivery'     => $delivery->id,
                'User-Agent'             => 'EmailValidator/1.0 Webhooks',
            ])
            ->timeout($webhook->timeout_seconds)
            ->post($webhook->url, json_decode($body, true));

            $delivery->update([
                'response_code'    => $response->status(),
                'response_body'    => substr($response->body(), 0, 5000),
                'response_time_ms' => 0,
                'status'           => $response->successful() ? 'success' : 'failed',
                'delivered_at'     => now(),
            ]);

            if ($response->successful()) {
                $webhook->update([
                    'success_count'   => $webhook->success_count + 1,
                    'last_success_at' => now(),
                    'last_triggered_at' => now(),
                ]);
            } else {
                $this->handleFailure($webhook, $delivery, "HTTP {$response->status()}");
            }

        } catch (\Exception $e) {
            $this->handleFailure($webhook, $delivery, $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }

    private function handleFailure(Webhook $webhook, WebhookDelivery $delivery, string $error): void
    {
        $delivery->update([
            'status'     => 'failed',
            'delivered_at' => now(),
        ]);

        $webhook->update([
            'failure_count'    => $webhook->failure_count + 1,
            'last_failure_at'  => now(),
            'last_error'       => $error,
        ]);

        // Disable webhook after too many failures
        if ($webhook->failure_count >= 50) {
            $webhook->update(['status' => 'failed']);
        }

        Log::warning("Webhook delivery failed for webhook {$webhook->id}: {$error}");
    }

    public function backoff(): array
    {
        return [10, 30, 60, 300, 900]; // 10s, 30s, 1m, 5m, 15m
    }
}
