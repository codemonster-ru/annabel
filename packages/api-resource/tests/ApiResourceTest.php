<?php

namespace Codemonster\ApiResource\Tests;

use Codemonster\ApiResource\JsonResource;
use PHPUnit\Framework\TestCase;

class ApiResourceTest extends TestCase
{
    public function test_single_resource_resolves_and_creates_response(): void
    {
        $resource = (new UserResource([
            'id' => 7,
            'name' => 'Annabel',
            'secret' => 'hidden',
        ]))->additional(['meta' => ['version' => 1]]);

        self::assertSame([
            'data' => [
                'id' => 7,
                'name' => 'Annabel',
            ],
            'meta' => ['version' => 1],
        ], $resource->resolve());

        $response = $resource->response(201);

        self::assertSame(201, $response->getStatusCode());
        self::assertTrue($response->isJson());
        self::assertSame($resource->resolve(), json_decode($response->getContent(), true));
    }

    public function test_collection_accepts_iterables(): void
    {
        $items = new \ArrayIterator([
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second'],
        ]);

        $resolved = UserResource::collection($items)
            ->additional(['meta' => ['count' => 2]])
            ->resolve();

        self::assertSame([
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second'],
        ], $resolved['data']);
        self::assertSame(['count' => 2], $resolved['meta']);
    }

    public function test_paginated_collection_has_stable_links_and_meta(): void
    {
        $resolved = UserResource::paginated([
            'data' => [
                ['id' => 2, 'name' => 'Second'],
            ],
            'per_page' => 1,
            'current_page' => 2,
            'next_page' => 3,
            'prev_page' => 1,
        ], '/users', ['filter' => 'active'])->resolve();

        self::assertSame([
            ['id' => 2, 'name' => 'Second'],
        ], $resolved['data']);
        self::assertSame([
            'prev' => '/users?filter=active&page=1',
            'next' => '/users?filter=active&page=3',
        ], $resolved['links']);
        self::assertSame([
            'current_page' => 2,
            'per_page' => 1,
        ], $resolved['meta']);
    }

    public function test_paginated_collection_rejects_invalid_page_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserResource::paginated([
            'data' => [],
            'per_page' => 15,
            'current_page' => 1,
            'next_page' => 0,
            'prev_page' => null,
        ]);
    }
}

class UserResource extends JsonResource
{
    public function toArray(): array
    {
        if (!is_array($this->resource)) {
            throw new \InvalidArgumentException('User resource expects an array.');
        }

        return [
            'id' => $this->resource['id'] ?? null,
            'name' => $this->resource['name'] ?? null,
        ];
    }
}
