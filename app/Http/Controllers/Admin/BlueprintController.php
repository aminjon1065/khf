<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Blueprint\BlueprintRepository;
use App\Cms\Blueprint\BlueprintSerializer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBlueprintRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class BlueprintController extends Controller
{
    public function __construct(
        private BlueprintRepository $blueprints,
        private BlueprintSerializer $serializer,
    ) {}

    public function index(): Response
    {
        $items = collect($this->blueprints->all())
            ->map(fn (array $item): array => [
                ...$item,
                'show_url' => route('admin.blueprints.show', [
                    'collection' => $item['collection'],
                    'name' => $item['name'],
                ]),
                'edit_url' => route('admin.blueprints.edit', [
                    'collection' => $item['collection'],
                    'name' => $item['name'],
                ]),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/blueprints/index', [
            'blueprints' => $items,
        ]);
    }

    public function show(string $collection, string $name = 'default'): Response
    {
        $reference = "{$collection}.{$name}";

        abort_unless($this->blueprints->exists($reference), 404);

        $blueprint = $this->blueprints->find($reference);
        $path = $this->blueprints->sourcePath($collection, $name);

        return Inertia::render('admin/blueprints/show', [
            'blueprint' => $blueprint->toArray(),
            'source' => [
                'path' => "resources/blueprints/{$collection}/{$name}.yaml",
                'yaml' => File::get($path),
            ],
            'edit_url' => route('admin.blueprints.edit', compact('collection', 'name')),
        ]);
    }

    public function edit(string $collection, string $name = 'default'): Response
    {
        $reference = "{$collection}.{$name}";

        abort_unless($this->blueprints->exists($reference), 404);

        $blueprint = $this->blueprints->find($reference);
        $path = $this->blueprints->sourcePath($collection, $name);

        return Inertia::render('admin/blueprints/edit', [
            'blueprint' => [
                'handle' => $reference,
                'title' => $blueprint->title,
                'collection' => $collection,
                'name' => $name,
            ],
            'schema' => $blueprint->toArray(),
            'source' => [
                'path' => "resources/blueprints/{$collection}/{$name}.yaml",
                'yaml' => File::get($path),
            ],
            'show_url' => route('admin.blueprints.show', compact('collection', 'name')),
        ]);
    }

    public function update(UpdateBlueprintRequest $request, string $collection, string $name = 'default'): RedirectResponse
    {
        $reference = "{$collection}.{$name}";

        abort_unless($this->blueprints->exists($reference), 404);

        $yaml = $request->has('schema')
            ? $this->serializer->toYaml($reference, $request->validated('schema'))
            : $request->validated('yaml');

        $this->blueprints->write($collection, $name, $yaml);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Blueprint-схема сохранена.',
        ]);

        return redirect()->route('admin.blueprints.show', compact('collection', 'name'));
    }
}
