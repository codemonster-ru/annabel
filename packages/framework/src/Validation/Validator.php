<?php

namespace Codemonster\Annabel\Validation;

use Closure;
use InvalidArgumentException;

class Validator
{
    /**
     * @var array<string, Closure(string, mixed, array<string, mixed>, list<string>): ?string>
     */
    protected array $extensions = [];

    public function extend(string $rule, Closure $validator): void
    {
        if ($rule === '') {
            throw new InvalidArgumentException('Validation rule name cannot be empty.');
        }

        $this->extensions[$rule] = $validator;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     */
    public function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $definition) {
            $fieldRules = $this->normalizeRules($definition);
            $exists = $this->hasValue($data, $field);
            $value = $this->value($data, $field);

            if (!$exists && !in_array('required', $fieldRules, true)) {
                continue;
            }

            if (in_array('nullable', $fieldRules, true) && ($value === null || $value === '')) {
                if ($exists) {
                    $validated[$field] = $value;
                }

                continue;
            }

            foreach ($fieldRules as $rule) {
                [$name, $parameters] = $this->parseRule($rule);

                if ($name === 'nullable') {
                    continue;
                }

                $message = $this->validateRule($name, $field, $value, $data, $parameters, $exists);

                if ($message !== null) {
                    $errors[$field][] = $message;
                }
            }

            if (!isset($errors[$field]) && $exists) {
                $validated[$field] = $value;
            }
        }

        return new ValidationResult($errors, $validated);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    public function validateOrFail(array $data, array $rules): array
    {
        $result = $this->validate($data, $rules);

        if ($result->fails()) {
            throw new ValidationException($result);
        }

        return $result->validated();
    }

    /**
     * @param string|list<string> $rules
     * @return list<string>
     */
    protected function normalizeRules(string|array $rules): array
    {
        if (is_string($rules)) {
            return array_values(array_filter(explode('|', $rules), fn(string $rule): bool => $rule !== ''));
        }

        return array_values(array_filter($rules, fn(string $rule): bool => $rule !== ''));
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    protected function parseRule(string $rule): array
    {
        [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');

        return [$name, $parameters === '' ? [] : explode(',', $parameters)];
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $parameters
     */
    protected function validateRule(
        string $rule,
        string $field,
        mixed $value,
        array $data,
        array $parameters,
        bool $exists
    ): ?string {
        if (isset($this->extensions[$rule])) {
            return ($this->extensions[$rule])($field, $value, $data, $parameters);
        }

        return match ($rule) {
            'required' => $this->present($value, $exists) ? null : "The {$field} field is required.",
            'string' => (!$exists || is_string($value)) ? null : "The {$field} field must be a string.",
            'integer' => (!$exists || filter_var($value, FILTER_VALIDATE_INT) !== false) ? null : "The {$field} field must be an integer.",
            'numeric' => (!$exists || is_numeric($value)) ? null : "The {$field} field must be numeric.",
            'boolean' => (!$exists || is_bool($value) || in_array($value, [0, 1, '0', '1'], true)) ? null : "The {$field} field must be true or false.",
            'array' => (!$exists || is_array($value)) ? null : "The {$field} field must be an array.",
            'email' => (!$exists || filter_var($value, FILTER_VALIDATE_EMAIL) !== false) ? null : "The {$field} field must be a valid email address.",
            'url' => (!$exists || filter_var($value, FILTER_VALIDATE_URL) !== false) ? null : "The {$field} field must be a valid URL.",
            'confirmed' => $value === $this->value($data, "{$field}_confirmation") ? null : "The {$field} confirmation does not match.",
            'same' => $value === $this->value($data, $this->parameter($rule, $parameters)) ? null : "The {$field} field must match {$parameters[0]}.",
            'in' => $this->parameters($rule, $parameters) && is_scalar($value) && in_array((string) $value, $parameters, true) ? null : "The {$field} field must be one of: " . implode(', ', $parameters) . '.',
            'min' => $this->compareSize($value, $parameters[0] ?? null, 'min') ? null : "The {$field} field must be at least {$parameters[0]}.",
            'max' => $this->compareSize($value, $parameters[0] ?? null, 'max') ? null : "The {$field} field must not be greater than {$parameters[0]}.",
            default => throw new InvalidArgumentException("Unknown validation rule [{$rule}]."),
        };
    }

    protected function present(mixed $value, bool $exists): bool
    {
        return $exists && $value !== null && $value !== '' && $value !== [];
    }

    protected function compareSize(mixed $value, ?string $expected, string $operator): bool
    {
        if ($expected === null || !is_numeric($expected)) {
            throw new InvalidArgumentException("Validation rule [{$operator}] requires a numeric parameter.");
        }

        $actual = match (true) {
            is_numeric($value) => (float) $value,
            is_array($value) => count($value),
            is_string($value) => strlen($value),
            default => 0,
        };

        $expected = (float) $expected;

        return $operator === 'min'
            ? $actual >= $expected
            : $actual <= $expected;
    }

    /**
     * @param list<string> $parameters
     */
    protected function parameter(string $rule, array $parameters, int $index = 0): string
    {
        if (!isset($parameters[$index]) || $parameters[$index] === '') {
            throw new InvalidArgumentException("Validation rule [{$rule}] requires parameter {$index}.");
        }

        return $parameters[$index];
    }

    /**
     * @param list<string> $parameters
     */
    protected function parameters(string $rule, array $parameters): bool
    {
        if ($parameters === []) {
            throw new InvalidArgumentException("Validation rule [{$rule}] requires parameters.");
        }

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function hasValue(array $data, string $field): bool
    {
        if (array_key_exists($field, $data)) {
            return true;
        }

        $current = $data;

        foreach (explode('.', $field) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function value(array $data, string $field): mixed
    {
        if (array_key_exists($field, $data)) {
            return $data[$field];
        }

        $current = $data;

        foreach (explode('.', $field) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
