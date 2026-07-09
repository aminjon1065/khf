<?php

namespace App\Rules;

use App\Cms\Blueprint\BlueprintParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;

class ValidBlueprintYaml implements ValidationRule
{
    public function __construct(private string $handle) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Укажите содержимое YAML-схемы.');

            return;
        }

        try {
            app(BlueprintParser::class)->parse($this->handle, $value);
        } catch (ParseException $exception) {
            $fail('Некорректный YAML: '.$exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
