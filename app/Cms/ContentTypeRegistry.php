<?php

namespace App\Cms;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Central registry of CMS content types (Statamic collections analogue).
 */
class ContentTypeRegistry
{
    /** @var array<string, ContentTypeDefinition> */
    private array $types = [];

    public function register(ContentTypeDefinition $type): void
    {
        $this->types[$type->handle] = $type;
    }

    public function get(string $handle): ContentTypeDefinition
    {
        if (! isset($this->types[$handle])) {
            throw new InvalidArgumentException("Unknown CMS content type [{$handle}].");
        }

        return $this->types[$handle];
    }

    public function has(string $handle): bool
    {
        return isset($this->types[$handle]);
    }

    /**
     * @return list<ContentTypeDefinition>
     */
    public function all(): array
    {
        return array_values($this->types);
    }

    /**
     * @return list<string>
     */
    public function handles(): array
    {
        return array_keys($this->types);
    }

    /**
     * Editorial content types (pages, posts, documents, …).
     *
     * @return list<ContentTypeDefinition>
     */
    public function editorial(): array
    {
        return array_values(array_filter(
            $this->types,
            fn (ContentTypeDefinition $type): bool => $type->hasFeature('editorial'),
        ));
    }

    public function forModel(Model $model): ?ContentTypeDefinition
    {
        $class = $model::class;

        foreach ($this->types as $type) {
            if ($type->modelClass === $class) {
                return $type;
            }
        }

        return null;
    }

    public function forModelClass(string $modelClass): ?ContentTypeDefinition
    {
        foreach ($this->types as $type) {
            if ($type->modelClass === $modelClass) {
                return $type;
            }
        }

        return null;
    }
}
