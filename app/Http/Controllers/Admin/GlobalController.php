<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Blueprint\BlueprintRepository;
use App\Http\Controllers\Admin\Concerns\ProvidesBlueprintForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGlobalRequest;
use App\Models\Language;
use App\Models\SiteGlobal;
use App\Services\Cms\GlobalResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GlobalController extends Controller
{
    use ProvidesBlueprintForm;

    public function __construct(private GlobalResolver $globals) {}

    public function index(): Response
    {
        $items = collect($this->globals->definitions())
            ->map(fn ($definition): array => [
                'handle' => $definition->handle,
                'label' => $definition->label,
                'icon' => $definition->icon,
                'edit_url' => route('admin.globals.edit', $definition->handle),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/globals/index', [
            'globals' => $items,
        ]);
    }

    public function edit(string $handle): Response
    {
        $definition = $this->globals->definition($handle);

        abort_if($definition === null, 404);

        $global = SiteGlobal::query()
            ->where('handle', $handle)
            ->with('translations')
            ->first();

        $blueprint = app(BlueprintRepository::class)->find($definition->blueprint);
        $locale = Language::defaultCode();
        $stored = $global?->fieldData($locale) ?? [];
        $fields = array_merge($definition->fallback, $stored);

        return Inertia::render('admin/globals/edit', [
            'global' => [
                'handle' => $handle,
                'label' => $definition->label,
            ],
            'fields' => $fields,
            ...$this->blueprintFormPropsForReference($definition->blueprint),
        ]);
    }

    public function update(UpdateGlobalRequest $request, string $handle): RedirectResponse
    {
        $definition = $this->globals->definition($handle);

        abort_if($definition === null, 404);

        $global = SiteGlobal::query()->firstOrCreate(
            ['handle' => $handle],
            ['blueprint' => $definition->blueprint],
        );

        /** @var array<string, mixed> $fields */
        $fields = $request->validated('fields');

        $global->upsertTranslations([
            Language::defaultCode() => ['data' => $fields],
        ]);

        $this->globals->forget($handle);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Глобальные настройки сохранены.']);

        return redirect()->route('admin.globals.edit', $handle);
    }

    /**
     * @return array{blueprint: array<string, mixed>}
     */
    private function blueprintFormPropsForReference(string $reference): array
    {
        $blueprint = app(BlueprintRepository::class)->find($reference);

        return [
            'blueprint' => $blueprint->toArray(),
        ];
    }
}
