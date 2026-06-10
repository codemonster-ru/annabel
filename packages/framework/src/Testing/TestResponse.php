<?php

namespace Codemonster\Annabel\Testing;

use Codemonster\Http\Response;
use PHPUnit\Framework\Assert;

class TestResponse
{
    public function __construct(protected Response $response)
    {
    }

    public function baseResponse(): Response
    {
        return $this->response;
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function content(): string
    {
        return $this->response->getContent();
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        $decoded = json_decode($this->response->getContent(), true);

        if ($key === null) {
            return $decoded;
        }

        if (!is_array($decoded)) {
            return $default;
        }

        return $this->arrayGet($decoded, $key, $default);
    }

    public function assertStatus(int $status): static
    {
        Assert::assertSame($status, $this->status(), "Expected response status [{$status}], got [{$this->status()}].");

        return $this;
    }

    public function assertOk(): static
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): static
    {
        return $this->assertStatus(201);
    }

    public function assertNoContent(int $status = 204): static
    {
        $this->assertStatus($status);
        Assert::assertSame('', $this->content(), 'Expected response to have no content.');

        return $this;
    }

    public function assertRedirect(?string $location = null): static
    {
        Assert::assertTrue(
            in_array($this->status(), [301, 302, 303, 307, 308], true),
            "Expected response status to be a redirect, got [{$this->status()}].",
        );

        if ($location !== null) {
            $this->assertHeader('Location', $location);
        }

        return $this;
    }

    public function assertHeader(string $name, ?string $value = null): static
    {
        Assert::assertTrue($this->response->hasHeader($name), "Expected response header [{$name}] to be present.");

        if ($value !== null) {
            Assert::assertSame($value, $this->response->getHeaderLine($name));
        }

        return $this;
    }

    public function assertSee(string $value): static
    {
        Assert::assertStringContainsString($value, $this->content());

        return $this;
    }

    public function assertDontSee(string $value): static
    {
        Assert::assertStringNotContainsString($value, $this->content());

        return $this;
    }

    /**
     * @param array<mixed> $expected
     */
    public function assertJson(array $expected): static
    {
        Assert::assertTrue($this->response->isJson(), 'Expected response to be JSON.');

        $actual = $this->json();
        Assert::assertIsArray($actual, 'Expected JSON response to decode to an array.');
        $this->assertArrayContains($expected, $actual);

        return $this;
    }

    public function assertJsonPath(string $path, mixed $expected): static
    {
        Assert::assertSame($expected, $this->json($path), "Expected JSON path [{$path}] to match.");

        return $this;
    }

    /**
     * @param array<mixed> $array
     */
    private function arrayGet(array $array, string $key, mixed $default): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * @param array<mixed> $expected
     * @param array<mixed> $actual
     */
    private function assertArrayContains(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            Assert::assertArrayHasKey($key, $actual);

            if (is_array($value)) {
                Assert::assertIsArray($actual[$key]);
                $this->assertArrayContains($value, $actual[$key]);

                continue;
            }

            Assert::assertSame($value, $actual[$key]);
        }
    }
}
