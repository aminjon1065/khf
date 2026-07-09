<?php

use App\Cms\Blueprint\BlueprintRepository;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->withTwoFactor()->create();
    $this->admin->assignRole(Role::SuperAdmin->value);
});

it('redirects guests from blueprints admin', function () {
    $this->get(route('admin.blueprints.index'))->assertRedirect(route('login'));
});

it('forbids a moderator from viewing blueprints', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->get(route('admin.blueprints.index'))
        ->assertForbidden();
});

it('lists blueprints for a super admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blueprints.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/blueprints/index')
            ->has('blueprints', 6)
            ->where('blueprints.0.handle', 'footer.default')
            ->where('blueprints.1.handle', 'page.default')
            ->where('blueprints.2.handle', 'post.default')
        );
});

it('shows a blueprint schema with yaml source', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blueprints.show', ['collection' => 'footer', 'name' => 'default']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/blueprints/show')
            ->where('blueprint.handle', 'footer.default')
            ->where('source.path', 'resources/blueprints/footer/default.yaml')
            ->where('source.yaml', fn (string $yaml) => str_contains($yaml, 'resource_links'))
            ->has('blueprint.sections.main.fields', 5)
        );
});

it('returns 404 for a missing blueprint', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blueprints.show', ['collection' => 'missing', 'name' => 'default']))
        ->assertNotFound();
});

it('shows the blueprint yaml editor', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blueprints.edit', ['collection' => 'footer', 'name' => 'default']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/blueprints/edit')
            ->where('blueprint.handle', 'footer.default')
            ->where('source.path', 'resources/blueprints/footer/default.yaml')
            ->has('source.yaml')
            ->has('schema.title')
            ->has('schema.sections.main.fields', 5)
        );
});

it('updates a blueprint yaml file', function () {
    $path = resource_path('blueprints/footer/default.yaml');
    $original = file_get_contents($path);

    $updated = str_replace('title: Подвал сайта', 'title: Подвал портала', $original);

    try {
        $this->actingAs($this->admin)
            ->put(route('admin.blueprints.update', ['collection' => 'footer', 'name' => 'default']), [
                'yaml' => $updated,
            ])
            ->assertRedirect(route('admin.blueprints.show', ['collection' => 'footer', 'name' => 'default']));

        expect(file_get_contents($path))->toContain('title: Подвал портала');
    } finally {
        file_put_contents($path, $original);
    }
});

it('rejects invalid blueprint yaml', function () {
    $this->actingAs($this->admin)
        ->from(route('admin.blueprints.edit', ['collection' => 'footer', 'name' => 'default']))
        ->put(route('admin.blueprints.update', ['collection' => 'footer', 'name' => 'default']), [
            'yaml' => "title: Broken\nsections: [",
        ])
        ->assertRedirect(route('admin.blueprints.edit', ['collection' => 'footer', 'name' => 'default']))
        ->assertSessionHasErrors('yaml');
});

it('forbids a moderator from updating blueprints', function () {
    $moderator = User::factory()->withTwoFactor()->create();
    $moderator->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->put(route('admin.blueprints.update', ['collection' => 'footer', 'name' => 'default']), [
            'yaml' => 'title: Hack',
        ])
        ->assertForbidden();
});

it('updates a blueprint via the visual builder schema', function () {
    $path = resource_path('blueprints/footer/default.yaml');
    $original = file_get_contents($path);
    $schema = app(BlueprintRepository::class)->find('footer.default')->toArray();
    $schema['title'] = 'Подвал портала (builder)';

    try {
        $this->actingAs($this->admin)
            ->put(route('admin.blueprints.update', ['collection' => 'footer', 'name' => 'default']), [
                'schema' => $schema,
            ])
            ->assertRedirect(route('admin.blueprints.show', ['collection' => 'footer', 'name' => 'default']));

        expect(file_get_contents($path))->toContain('Подвал портала (builder)');
    } finally {
        file_put_contents($path, $original);
    }
});
