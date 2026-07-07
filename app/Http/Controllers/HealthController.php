<?php

namespace App\Http\Controllers;

use App\Support\HealthReporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __construct(private HealthReporter $reporter) {}

    /**
     * Public health endpoint for uptime monitors (ТЗ §16.3).
     * Returns a minimal payload. Pass `HEALTH_CHECK_TOKEN` via Bearer header or `?token=`
     * for detailed DB/cache/queue diagnostics.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if ($this->authorizedForDetails($request)) {
            $payload = $this->reporter->detailed();
            $status = $payload['status'] === 'ok' ? 200 : 503;

            return response()->json($payload, $status);
        }

        return response()->json($this->reporter->summary());
    }

    private function authorizedForDetails(Request $request): bool
    {
        $token = (string) config('deployment.health_check_token');

        if ($token === '') {
            return app()->environment('local', 'testing');
        }

        $provided = $request->bearerToken()
            ?? $request->query('token');

        return is_string($provided) && hash_equals($token, $provided);
    }
}
