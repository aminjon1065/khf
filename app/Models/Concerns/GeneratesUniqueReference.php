<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

trait GeneratesUniqueReference
{
    private const REFERENCE_CREATION_ATTEMPTS = 5;

    abstract protected static function referencePrefix(): string;

    public static function generateReference(): string
    {
        return static::referencePrefix().'-'.now()->year.'-'.Str::upper(Str::random(6));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createWithUniqueReference(array $attributes): static
    {
        for ($attempt = 1; $attempt <= self::REFERENCE_CREATION_ATTEMPTS; $attempt++) {
            try {
                return static::query()->create([
                    ...$attributes,
                    'reference' => static::generateReference(),
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === self::REFERENCE_CREATION_ATTEMPTS || ! static::isReferenceCollision($exception)) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Reference generation attempts were exhausted.');
    }

    private static function isReferenceCollision(UniqueConstraintViolationException $exception): bool
    {
        if ($exception->columns !== []) {
            return in_array('reference', $exception->columns, true);
        }

        if ($exception->index !== null) {
            return str_contains($exception->index, 'reference');
        }

        return true;
    }
}
