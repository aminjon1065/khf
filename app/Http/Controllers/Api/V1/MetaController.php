<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Self-documenting index for the internal API (ТЗ §18.3) — lists the available endpoints and how to
 * authenticate. Public (no token) so integrators can discover the surface before they are issued a
 * token.
 */
class MetaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name').' — внутренний API',
            'version' => 'v1',
            'documentation' => [
                'authentication' => 'Передайте заголовок «Authorization: Bearer <token>». Токены выпускаются командой `php artisan api:token`.',
                'locale' => 'Параметр запроса ?locale=tj|ru|en выбирает язык контента (по умолчанию — язык по умолчанию).',
                'rate_limit' => '60 запросов в минуту.',
            ],
            'endpoints' => [
                ['method' => 'GET', 'path' => '/api/v1/alerts', 'description' => 'Активные оповещения ЧС'],
                ['method' => 'GET', 'path' => '/api/v1/incidents', 'description' => 'Активные события ЧС (с пагинацией)'],
                ['method' => 'GET', 'path' => '/api/v1/news', 'description' => 'Опубликованные новости (с пагинацией)'],
                ['method' => 'GET', 'path' => '/api/v1/news/{id}', 'description' => 'Новость по идентификатору'],
            ],
        ]);
    }
}
