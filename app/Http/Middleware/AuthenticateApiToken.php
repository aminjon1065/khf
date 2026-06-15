<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates internal-API requests by an `Authorization: Bearer <token>` header (ТЗ §10.9,
 * §12.3). The plaintext is matched against the SHA-256 hashes in `api_tokens`; expired or unknown
 * tokens get a 401 JSON error. The resolved token is stamped as last-used and attached to the
 * request for downstream use.
 */
class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer === null || $bearer === '') {
            return $this->unauthorized('Отсутствует токен доступа.');
        }

        $token = ApiToken::findByPlainText($bearer);

        if ($token === null) {
            return $this->unauthorized('Недействительный или истёкший токен доступа.');
        }

        $token->markAsUsed();
        $request->attributes->set('api_token', $token);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message], Response::HTTP_UNAUTHORIZED);
    }
}
