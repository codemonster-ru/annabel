<?php

use Codemonster\Annabel\Validation\ValidationResult;
use Codemonster\Annabel\Validation\Validator;

if (!function_exists('validator')) {
    /**
     * @param array<string, mixed>|null $data
     * @param array<string, string|list<string>>|null $rules
     */
    function validator(?array $data = null, ?array $rules = null): Validator|ValidationResult
    {
        /** @var Validator $validator */
        $validator = app(Validator::class);

        if ($data === null && $rules === null) {
            return $validator;
        }

        if ($data === null || $rules === null) {
            throw new InvalidArgumentException('validator() requires both data and rules.');
        }

        return $validator->validate($data, $rules);
    }
}
