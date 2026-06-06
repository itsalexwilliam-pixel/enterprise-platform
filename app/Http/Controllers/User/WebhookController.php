<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index()
    {
        $webhooks = Webhook::where('user_id', auth()->id())
            ->withCount('deliveries')
            ->orderByDesc('created_at')
            ->get();

        return view('user.webhooks', compact('webhooks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:100',
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'in:job.completed,job.failed,email.validated',
        ]);

        // Max 10 webhooks per user
        if (Webhook::where('user_id', auth()->id())->count() >= 10) {
            return back()->withErrors(['name' => 'Maximum 10 webhooks allowed.']);
        }

        Webhook::create([
            'user_id' => auth()->id(),
            'name'    => $validated['name'],
            'url'     => $validated['url'],
            'secret'  => Str::random(32),
            'events'  => $validated['events'],
            'status'  => 'active',
        ]);

        return back()->with('success', 'Webhook created successfully!');
    }

    public function destroy(int $id)
    {
        Webhook::where('id', $id)->where('user_id', auth()->id())->firstOrFail()->delete();
        return back()->with('success', 'Webhook deleted.');
    }

    public function toggle(int $id)
    {
        $webhook = Webhook::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $webhook->update(['status' => $webhook->status === 'active' ? 'inactive' : 'active']);
        return back()->with('success', 'Webhook status updated.');
    }
}
