<?php

use Codemonster\Annabel\Validation\ValidationException;
use Codemonster\Annabel\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function test_it_validates_required_and_type_rules()
    {
        $result = (new Validator())->validate([
            'name' => 'Annabel',
            'age' => '18',
        ], [
            'name' => 'required|string|min:3',
            'age' => 'required|integer',
            'email' => 'required|email',
        ]);

        $this->assertTrue($result->fails());
        $this->assertSame('The email field is required.', $result->first('email'));
        $this->assertSame([
            'name' => 'Annabel',
            'age' => '18',
        ], $result->validated());
    }

    public function test_it_supports_nullable_nested_fields_and_confirmation()
    {
        $result = (new Validator())->validate([
            'user' => ['email' => 'hello@example.com'],
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'nickname' => null,
        ], [
            'user.email' => 'required|email',
            'password' => 'required|string|confirmed|min:6',
            'nickname' => 'nullable|string|min:3',
        ]);

        $this->assertTrue($result->passes());
        $this->assertSame('hello@example.com', $result->validated()['user.email']);
        $this->assertArrayHasKey('nickname', $result->validated());
    }

    public function test_validate_or_fail_throws_with_errors()
    {
        $validator = new Validator();

        $this->expectException(ValidationException::class);

        $validator->validateOrFail(['role' => 'guest'], [
            'role' => 'in:admin,editor',
        ]);
    }

    public function test_optional_missing_fields_are_not_validated()
    {
        $result = (new Validator())->validate([], [
            'name' => 'string|min:3',
        ]);

        $this->assertTrue($result->passes());
        $this->assertSame([], $result->validated());
    }

    public function test_custom_rules_can_be_registered()
    {
        $validator = new Validator();
        $validator->extend('starts_with_a', function (string $field, mixed $value): ?string {
            return str_starts_with((string) $value, 'a') ? null : "The {$field} field must start with a.";
        });

        $result = $validator->validate(['slug' => 'annabel'], [
            'slug' => 'required|starts_with_a',
        ]);

        $this->assertTrue($result->passes());
    }
}
