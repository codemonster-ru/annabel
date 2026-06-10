<?php

namespace Codemonster\Annabel\Http;

use Codemonster\Http\Request;
use Codemonster\Validation\Validator;

trait ValidatesRequests
{
    /**
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules): array
    {
        /** @var Validator $validator */
        $validator = app(Validator::class);

        return $validator->validateOrFail($request->all(), $rules);
    }
}
