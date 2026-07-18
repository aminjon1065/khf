<?php

namespace App\Http\Controllers;

use App\Support\HealthReporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __construct(private HealthReporter $reporter) {}

    /**
     * Readiness endpoint for uptime monitors (ТЗ §16.3).
     * Public responses expose check statuses only. A valid Bearer token includes diagnostics.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $providedToken = $request->bearerToken();

        if ($providedToken !== null && ! $this->authorizedForDetails($providedToken)) {
            return response()->json(['message' => 'Invalid health check token.'], 401);
        }

        $payload = $providedToken === null
            ? $this->reporter->summary()
            : $this->reporter->detailed();
        $status = $payload['status'] === 'ok' ? 200 : 503;

        return response()->json($payload, $status);
    }

    private function authorizedForDetails(string $providedToken): bool
    {
        $token = (string) config('deployment.health_check_token');

        if ($token === '') {
            return false;
        }

        return hash_equals($token, $providedToken);
    }
}
