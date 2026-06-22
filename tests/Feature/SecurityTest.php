<?php

use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Rules\SafeFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;

it('sanitizes HTML input via Purify on PostTranslation body', function () {
    $post = Post::factory()->create();
    $translation = new PostTranslation;
    $translation->post_id = $post->id;
    $translation->locale = 'tj';
    $translation->title = 'Test';
    $translation->slug = 'test-slug';
    $translation->body = '<p>Normal text</p><script>alert("XSS")</script><a href="javascript:alert(1)">Click</a>';
    $translation->save();

    expect($translation->body)->not->toContain('<script>')
        ->and($translation->body)->toContain('<p>Normal text</p>');
});

it('rejects malicious files in SafeFileUpload rule', function () {
    $rule = new SafeFileUpload;

    $goodFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
    $badFilePhp = UploadedFile::fake()->create('shell.php', 100, 'application/x-httpd-php');
    $badFileExe = UploadedFile::fake()->create('virus.exe', 100, 'application/x-executable');

    $validatorGood = Validator::make(['file' => $goodFile], ['file' => [$rule]]);
    expect($validatorGood->passes())->toBeTrue();

    $validatorBadPhp = Validator::make(['file' => $badFilePhp], ['file' => [$rule]]);
    expect($validatorBadPhp->fails())->toBeTrue();

    $validatorBadExe = Validator::make(['file' => $badFileExe], ['file' => [$rule]]);
    expect($validatorBadExe->fails())->toBeTrue();
});

it('logs activities on User model changes', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    $user->update(['name' => 'Jane Doe']);

    $activity = Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->orderByDesc('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('updated');
});
