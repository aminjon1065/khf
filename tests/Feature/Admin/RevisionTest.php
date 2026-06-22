<?php

use App\Models\Post;
use App\Models\Revision;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([LanguageSeeder::class, RolePermissionSeeder::class]);
    $this->user = User::factory()->withTwoFactor()->create();
    $this->user->assignRole('super-admin');
});

it('creates a revision via the trait', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations([
        'ru' => ['title' => 'Original Title', 'slug' => 'original-title'],
    ]);

    $revision = $post->saveRevision($this->user->id);

    expect($revision)->toBeInstanceOf(Revision::class)
        ->and($revision->revisionable_id)->toBe($post->id)
        ->and($revision->revisionable_type)->toBe(Post::class)
        ->and($revision->payload['translations'][0]['title'])->toBe('Original Title');
});

it('restores a revision correctly including translations', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations([
        'ru' => ['title' => 'Original Title', 'slug' => 'original'],
    ]);
    $revision1 = $post->saveRevision($this->user->id);

    // Update
    $post->upsertTranslations([
        'ru' => ['title' => 'Updated Title', 'slug' => 'updated'],
        'en' => ['title' => 'English Title', 'slug' => 'english'],
    ]);
    $post->saveRevision($this->user->id);

    // Restore to revision 1
    $post->restoreRevision($revision1);

    $post->refresh();
    $post->load('translations');

    expect($post->translations)->toHaveCount(1)
        ->and($post->translation('ru')->title)->toBe('Original Title');
});

it('fetches revisions via api', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations([
        'ru' => ['title' => 'Title', 'slug' => 'slug'],
    ]);
    $post->saveRevision($this->user->id);

    $this->actingAs($this->user)
        ->get("/admin/revisions/post/{$post->id}")
        ->assertOk()
        ->assertJsonCount(1);
});

it('restores a revision via endpoint', function () {
    $post = Post::factory()->create();
    $post->upsertTranslations([
        'ru' => ['title' => 'Title', 'slug' => 'slug'],
    ]);
    $revision = $post->saveRevision($this->user->id);

    $this->actingAs($this->user)
        ->post("/admin/revisions/{$revision->id}/restore")
        ->assertRedirect();
});
