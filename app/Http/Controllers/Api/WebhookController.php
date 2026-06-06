<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $webhooks = $request->user()->webhooks()->get();
        return response()->json(['success' => true, 'data' => $webhooks]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:validation.completed,bulk.completed,bulk.failed,credits.low,credits.depleted',
        ]);

        if ($request->user()->webhooks()->count() >= 10) {
            return response()->json(['success' => false, 'error' => 'Maximum 10 webhooks allowed.'], 422);
        }

        $webhook = $request->user()->webhooks()->create([
            'name'   => $request->input('name', 'Webhook ' . ($request->user()->webhooks()->count() + 1)),
            'url'    => $request->url,
            'events' => $request->events,
            'secret' => 'whsec_' . bin2hex(random_bytes(32)),
            'status' => 'active',
        ]);

        return response()->json(['success' => true, 'data' => $webhook], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $webhook = $request->user()->webhooks()->findOrFail($id);
        $webhook->update($request->only(['url', 'events', 'status']));
        return response()->json(['success' => true, 'data' => $webhook]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->user()->webhooks()->findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function test(Request $request, int $id): JsonResponse
    {
        $webhook = $request->user()->webhooks()->findOrFail($id);

        // Fire a test ping
        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'X-Webhook-Secret' => $webhook->secret,
                'X-Event'          => 'webhook.test',
            ])->post($webhook->url, [
                'event'     => 'webhook.test',
                'message'   => 'This is a test webhook from Email Validator Pro',
                'timestamp' => now()->toISOString(),
            ]);
            return response()->json(['success' => true, 'message' => 'Test ping sent.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Could not reach webhook URL: ' . $e->getMessage()], 422);
        }
    }

    public function logs(Request $request, int $id): JsonResponse
    {
        $webhook = $request->user()->webhooks()->findOrFail($id);
        // Return empty logs for now — extend with WebhookLog model as needed
        return response()->json(['success' => true, 'data' => []]);
    }
}
