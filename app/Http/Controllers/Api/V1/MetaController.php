<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\V1\CollectionApiService;
use Illuminate\Http\JsonResponse;

/**
 * Self-documenting index for the internal API (ТЗ §18.3) — lists the available endpoints and how to
 * authenticate. Public (no token) so integrators can discover the surface before they are issued a
 * token.
 */
class MetaController extends Controller
{
    public function __construct(private CollectionApiService $collections) {}

    public function index(): JsonResponse
    {
        $handles = explode('|', $this->collections->routePattern());

        $collectionEndpoints = collect($handles)
            ->unique()
            ->sort()
            ->values()
            ->flatMap(fn (string $handle): array => [
                ['method' => 'GET', 'path' => "/api/v1/{$handle}", 'description' => "Коллекция «{$handle}» (с пагинацией)"],
                ['method' => 'GET', 'path' => "/api/v1/{$handle}/{slug}", 'description' => "Запись коллекции «{$handle}» по slug"],
            ])
            ->all();

        return response()->json([
            'name' => config('app.name').' — внутренний API',
            'version' => 'v1',
            'documentation' => [
                'authentication' => 'Передайте заголовок «Authorization: Bearer <token>». Токены выпускаются командой `php artisan api:token`.',
                'locale' => 'Параметр запроса ?locale=tj|ru|en выбирает язык контента (по умолчанию — язык по умолчанию).',
                'rate_limit' => '60 запросов в минуту.',
                'collections' => 'Универсальные маршруты /api/v1/{collection} и /api/v1/{collection}/{slug} для всех CMS-коллекций. Алиас `news` → `post`.',
                'globals' => 'Глобальные настройки: GET /api/v1/globals/{handle}.',
                'taxonomies' => 'Таксономии (рубрики, теги): GET /api/v1/taxonomies и GET /api/v1/taxonomies/{handle}.',
            ],
            'endpoints' => [
                ['method' => 'GET', 'path' => '/api/v1/alerts', 'description' => 'Активные оповещения ЧС'],
                ['method' => 'GET', 'path' => '/api/v1/incidents', 'description' => 'Активные события ЧС (с пагинацией)'],
                ['method' => 'GET', 'path' => '/api/v1/news', 'description' => 'Опубликованные новости (legacy, с пагинацией)'],
                ['method' => 'GET', 'path' => '/api/v1/news/{id}', 'description' => 'Новость по идентификатору (legacy)'],
                ['method' => 'GET', 'path' => '/api/v1/globals/{handle}', 'description' => 'Глобальные настройки CMS'],
                ['method' => 'GET', 'path' => '/api/v1/taxonomies', 'description' => 'Каталог таксономий CMS'],
                ['method' => 'GET', 'path' => '/api/v1/taxonomies/{handle}', 'description' => 'Термины таксономии (categories, tags)'],
                ...$collectionEndpoints,
            ],
        ]);
    }
}
