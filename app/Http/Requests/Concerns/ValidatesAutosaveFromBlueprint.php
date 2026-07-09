<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Relaxes blueprint rules for silent autosave (optional fields, no status/cover).
 */
trait ValidatesAutosaveFromBlueprint
{
    use ValidatesFromBlueprint;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function autosaveRules(): array
    {
        $rules = $this->blueprintRules();
        unset($rules['status'], $rules['cover'], $rules['cover_media_id'], $rules['remove_cover']);

        $relaxed = [];

        foreach ($rules as $key => $rule) {
            $relaxed[$key] = $this->relaxRuleForAutosave($rule);
        }

        return $relaxed;
    }

    /**
     * @param  ValidationRule|array<mixed>|string  $rule
     * @return list<string|ValidationRule>
     */
    protected function relaxRuleForAutosave(ValidationRule|array|string $rule): array
    {
        $parts = is_array($rule) ? $rule : explode('|', (string) $rule);

        $parts = array_values(array_filter(
            $parts,
            fn (mixed $part): bool => $part !== 'required'
                && ! (is_string($part) && str_starts_with($part, 'required_')),
        ));

        array_unshift($parts, 'sometimes');

        return $parts;
    }
}
