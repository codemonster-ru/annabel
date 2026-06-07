<?php

namespace Codemonster\Annabel\Validation;

class ValidationResult
{
    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed> $validated
     */
    public function __construct(
        protected array $errors,
        protected array $validated
    ) {}

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            if (isset($messages[0])) {
                return $messages[0];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }
}
