<?php

namespace App\Services\Admin;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use App\Enums\ContentStatus;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;

/**
 * Unified Statamic-style entry browser across CMS collections.
 */
class ContentBrowserService
{
    public function __construct(private ContentTypeRegistry $contentTypes) {}

    /**
     * @return list<array{handle: string, label: string, icon: string, count: int, url: string}>
     */
    public function hubTypesFor(?User $user): array
    {
        return collect($this->accessibleTypes($user))
            ->map(fn (ContentTypeDefinition $type): array => [
                'handle' => $type->handle,
                'label' => $type->label,
                'icon' => $type->icon,
                'count' => $this->entryCount($type),
                'url' => route($type->browserRoute(), $type->handle),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{handle: string, label: string, url: string}>
     */
    public function switcherTypesFor(?User $user): array
    {
        return collect($this->accessibleTypes($user))
            ->map(fn (ContentTypeDefinition $type): array => [
                'handle' => $type->handle,
                'label' => $type->label,
                'url' => route($type->browserRoute(), $type->handle),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function indexProps(ContentTypeDefinition $type, Request $request): array
    {
        $locale = app()->getLocale();
        $filters = $this->filters($type, $request);
        $entries = $this->paginate($type, $request, $filters);

        return [
            'contentType' => [
                'handle' => $type->handle,
                'label' => $type->label,
                'icon' => $type->icon,
                'features' => $type->features,
                'sortable' => $type->sortable,
                'route_prefix' => $type->routePrefix,
            ],
            'entries' => $entries,
            'filters' => $filters,
            'statuses' => $this->statusOptions($type),
            'types' => $this->switcherTypesFor($request->user()),
            'trashedCount' => $type->hasFeature('soft_deletes')
                ? $this->trashedCount($type)
                : 0,
            'createUrl' => route("admin.{$type->routePrefix}.create"),
            'trashUrl' => $type->hasFeature('soft_deletes')
                ? route($type->trashRoute())
                : null,
            'searchPlaceholder' => $this->searchPlaceholder($type),
            'showSubtype' => $type->handle === 'post',
        ];
    }

    /**
     * @return array{search: string, sort: string, direction: string, status: string|null, trashed: bool}
     */
    public function filtersFromRequest(ContentTypeDefinition $type, Request $request): array
    {
        return $this->filters($type, $request);
    }

    /**
     * @param  array{search: string, sort: string, direction: string, status: string|null, trashed: bool}  $filters
     * @return Builder<Model>
     */
    public function entryQuery(ContentTypeDefinition $type, array $filters): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;

        $query = $modelClass::query();

        if ($type->hasFeature('translations')) {
            $query->with('translations');
        }

        if ($filters['trashed'] && $type->hasFeature('soft_deletes')) {
            $query->onlyTrashed();
        }

        if ($filters['status'] !== null && $this->supportsContentStatus($type)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['search'] !== '' && $type->hasFeature('translations')) {
            $searchField = $type->listSearchField;
            $search = $filters['search'];

            $query->whereHas(
                'translations',
                fn (Builder $translationQuery) => $translationQuery->where($searchField, 'like', "%{$search}%"),
            );
        }

        $sort = in_array($filters['sort'], $type->sortable, true)
            ? $filters['sort']
            : $type->defaultSort;

        return $query->orderBy($sort, $filters['direction']);
    }

    private function paginate(ContentTypeDefinition $type, Request $request, array $filters): LengthAwarePaginator
    {
        $locale = app()->getLocale();

        $page = $request->integer('page');
        $currentPage = $page > 0 ? $page : 1;

        return $this->entryQuery($type, $filters)
            ->paginate(15, ['*'], 'page', $currentPage)
            ->appends($request->query())
            ->through(fn (Model $record) => $this->toRow($type, $record, $locale));
    }

    /**
     * @return array{search: string, sort: string, direction: string, status: string|null, trashed: bool}
     */
    private function filters(ContentTypeDefinition $type, Request $request): array
    {
        $sort = (string) $request->string('sort');
        $direction = (string) $request->string('direction');
        $status = (string) $request->string('status');

        return [
            'search' => trim((string) $request->string('search')),
            'sort' => $sort !== '' ? $sort : $type->defaultSort,
            'direction' => $direction === 'asc' ? 'asc' : ($direction === 'desc' ? 'desc' : $type->defaultSortDirection),
            'status' => $status !== '' ? $status : null,
            'trashed' => $request->boolean('trashed') && $type->hasFeature('soft_deletes'),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(ContentTypeDefinition $type): array
    {
        if (! $this->supportsContentStatus($type)) {
            return [];
        }

        return array_map(
            fn (ContentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            ContentStatus::cases(),
        );
    }

    private function supportsContentStatus(ContentTypeDefinition $type): bool
    {
        $model = new $type->modelClass;

        return ($model->getCasts()['status'] ?? null) === ContentStatus::class;
    }

    private function entryCount(ContentTypeDefinition $type): int
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;

        return $modelClass::query()->count();
    }

    private function trashedCount(ContentTypeDefinition $type): int
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $type->modelClass;

        return $modelClass::onlyTrashed()->count();
    }

    /**
     * @return list<ContentTypeDefinition>
     */
    private function accessibleTypes(?User $user): array
    {
        return array_values(array_filter(
            $this->contentTypes->all(),
            fn (ContentTypeDefinition $type): bool => $user !== null && $user->can($type->managePermission),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(ContentTypeDefinition $type, Model $record, string $locale): array
    {
        $row = [
            'id' => $record->getKey(),
            'title' => $this->resolveTitle($record, $locale),
            'locales' => method_exists($record, 'translatedLocales')
                ? $record->translatedLocales()
                : [],
            'updated_at' => $record->updated_at?->toDateString(),
            'edit_url' => route("admin.{$type->routePrefix}.edit", $record),
            'destroy_url' => route("admin.{$type->routePrefix}.destroy", $record),
        ];

        if ($this->supportsContentStatus($type)) {
            /** @var ContentStatus $status */
            $status = $record->status;
            $row['status'] = $status->value;
            $row['status_label'] = $status->label();
        }

        if ($type->handle === 'post' && isset($record->type)) {
            $postType = $record->type;
            $row['subtype'] = $postType instanceof BackedEnum ? $postType->value : (string) $postType;
            $row['subtype_label'] = $postType instanceof BackedEnum && method_exists($postType, 'label')
                ? $postType->label()
                : (string) $postType;
        }

        return $row;
    }

    private function resolveTitle(Model $record, string $locale): string
    {
        if (! method_exists($record, 'translation')) {
            return '—';
        }

        $translation = $record->translation($locale) ?? $record->translation();

        if ($translation === null) {
            return '—';
        }

        foreach (['title', 'name', 'question', 'full_name', 'label'] as $field) {
            $value = $translation->{$field} ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '—';
    }

    /**
     * @return list<array{id: string, title: string, type: string, type_label: string, url: string}>
     */
    public function searchAcrossCollections(?User $user, string $query, int $perType = 5): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        $locale = app()->getLocale();
        $results = [];

        foreach ($this->accessibleTypes($user) as $type) {
            if (! $type->hasFeature('translations')) {
                continue;
            }

            if (! Route::has("admin.{$type->routePrefix}.edit")) {
                continue;
            }

            $records = $this->entryQuery($type, [
                'search' => $query,
                'sort' => $type->defaultSort,
                'direction' => $type->defaultSortDirection,
                'status' => null,
                'trashed' => false,
            ])->limit($perType)->get();

            foreach ($records as $record) {
                $results[] = [
                    'id' => "{$type->handle}:{$record->getKey()}",
                    'title' => $this->resolveTitle($record, $locale),
                    'type' => $type->handle,
                    'type_label' => $type->label,
                    'url' => route("admin.{$type->routePrefix}.edit", $record),
                ];
            }
        }

        return $results;
    }

    private function searchPlaceholder(ContentTypeDefinition $type): string
    {
        return match ($type->listSearchField) {
            'question' => 'Поиск по вопросу…',
            'name' => 'Поиск по названию…',
            'full_name' => 'Поиск по ФИО…',
            default => 'Поиск по заголовку…',
        };
    }
}
