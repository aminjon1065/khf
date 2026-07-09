<?php

namespace App\Providers;

use App\Cms\ContentTypeDefinition;
use App\Cms\ContentTypeRegistry;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContentTypeRegistry::class, function (): ContentTypeRegistry {
            $registry = new ContentTypeRegistry;

            foreach (config('cms.content_types', []) as $handle => $config) {
                $registry->register(new ContentTypeDefinition(
                    handle: $handle,
                    label: $config['label'],
                    modelClass: $config['model'],
                    blueprint: $config['blueprint'],
                    routePrefix: $config['route_prefix'],
                    managePermission: $config['manage_permission'],
                    features: $config['features'] ?? [],
                    sortable: $config['sortable'] ?? [],
                    defaultSort: $config['default_sort'] ?? 'created_at',
                    defaultSortDirection: $config['default_sort_direction'] ?? 'desc',
                    viewPermission: $config['view_permission'] ?? null,
                    icon: $config['icon'] ?? 'file-text',
                    listSearchField: $config['list_search_field'] ?? 'title',
                ));
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}
