<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RiskAlertSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertSubscriptionController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_if(! $request->user()->hasRole('SuperAdmin'), 403);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'data' => RiskAlertSubscription::orderBy('created_at', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'country_id'     => ['nullable', 'string'],
            'alert_type'     => ['nullable', 'string'],
            'severity'       => ['nullable', 'string'],
            'channel'        => ['required', 'string'],
            'channel_config' => ['required', 'array'],
            'active'         => ['boolean'],
        ]);

        $subscription = RiskAlertSubscription::create($validated);

        return response()->json(['data' => $subscription], 201);
    }

    public function show(Request $request, string $alertSubscription): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'data' => RiskAlertSubscription::findOrFail($alertSubscription),
        ]);
    }

    public function update(Request $request, string $alertSubscription): JsonResponse
    {
        $this->authorizeAdmin($request);

        $subscription = RiskAlertSubscription::findOrFail($alertSubscription);

        $validated = $request->validate([
            'country_id'     => ['nullable', 'string'],
            'alert_type'     => ['nullable', 'string'],
            'severity'       => ['nullable', 'string'],
            'channel'        => ['sometimes', 'required', 'string'],
            'channel_config' => ['sometimes', 'required', 'array'],
            'active'         => ['boolean'],
        ]);

        $subscription->update($validated);

        return response()->json(['data' => $subscription->fresh()]);
    }

    public function destroy(Request $request, string $alertSubscription): JsonResponse
    {
        $this->authorizeAdmin($request);

        RiskAlertSubscription::findOrFail($alertSubscription)->delete();

        return response()->json(null, 204);
    }
}
