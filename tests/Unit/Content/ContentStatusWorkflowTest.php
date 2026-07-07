<?php

use App\Enums\ContentStatus;

it('defines the draft workflow transitions', function () {
    expect(ContentStatus::Draft->allowedTransitions())->toBe([
        ContentStatus::Moderation,
        ContentStatus::Published,
    ]);
});

it('defines the moderation workflow transitions', function () {
    expect(ContentStatus::Moderation->allowedTransitions())->toBe([
        ContentStatus::Draft,
        ContentStatus::Published,
    ]);
});

it('defines the published workflow transitions', function () {
    expect(ContentStatus::Published->allowedTransitions())->toBe([
        ContentStatus::Archived,
    ]);
});

it('defines the archived workflow transitions', function () {
    expect(ContentStatus::Archived->allowedTransitions())->toBe([
        ContentStatus::Draft,
    ]);
});

it('rejects invalid status transitions', function () {
    expect(ContentStatus::Draft->canTransitionTo(ContentStatus::Archived))->toBeFalse()
        ->and(ContentStatus::Published->canTransitionTo(ContentStatus::Draft))->toBeFalse();
});
