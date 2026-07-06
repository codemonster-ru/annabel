<?php

namespace Codemonster\Annabel\Http;

use Codemonster\Http\Request;
use Codemonster\Validation\Validator;

trait ValidatesRequests
{
    /**
     * @param array<string, string|list<string>> $rules
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules, array $attributes = []): array
    {
        /** @var Validator $validator */
        $validator = app(Validator::class);

        $input = [];
        foreach ($request->all() as $key => $value) {
            if (is_string($key)) {
                $input[$key] = $value;
            }
        }

        return $validator->validateOrFail($input, $rules, $attributes);
    }
}
