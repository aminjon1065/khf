<?php

/**
 * Guardrail: mutating admin routes must declare `can:` middleware, unless they are
 * authorized inside a Form Request / controller against the content-type manage permission.
 */
it('requires can: middleware on mutating admin routes outside the documented allowlist', function () {
    $allowWithoutCanMiddleware = [
        // Unified content browser authorizes via managePermission in Form Request / controller.
        'admin.content.import',
        'admin.content.bulk-destroy',
        // Revision restore authorizes against the revisionable content type manage permission.
        'admin.revisions.restore',
    ];

    $violations = collect(app('router')->getRoutes())
        ->filter(function ($route) {
            $name = $route->getName() ?? '';

            if (! str_starts_with($name, 'admin.')) {
                return false;
            }

            $methods = array_diff($route->methods(), ['HEAD']);

            return (bool) array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE']);
        })
        ->reject(fn ($route) => in_array($route->getName(), $allowWithoutCanMiddleware, true))
        ->reject(function ($route) {
            return collect($route->gatherMiddleware())
                ->contains(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'can:'));
        })
        ->map(fn ($route) => $route->getName())
        ->values()
        ->all();

    expect($violations)->toBeEmpty(
        'Mutating admin routes missing can: middleware: '.implode(', ', $violations),
    );
});

it('keeps the no-can allowlist limited to known exceptions', function () {
    $allowWithoutCanMiddleware = [
        'admin.content.import',
        'admin.content.bulk-destroy',
        'admin.revisions.restore',
    ];

    $namedAdminMutating = collect(app('router')->getRoutes())
        ->filter(function ($route) {
            $name = $route->getName() ?? '';

            if (! str_starts_with($name, 'admin.')) {
                return false;
            }

            $methods = array_diff($route->methods(), ['HEAD']);

            return (bool) array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE']);
        })
        ->map(fn ($route) => $route->getName())
        ->all();

    foreach ($allowWithoutCanMiddleware as $name) {
        expect($namedAdminMutating)->toContain($name);
    }
});
