---
title: "Getting started"
description: "First standalone usage of codemonster-ru/api-resource"
order: 1
---

# Getting started

`codemonster-ru/api-resource` turns objects, arrays, collections, and paginated
results into consistent JSON API responses.

## Basic usage

```php
use Codemonster\ApiResource\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
        ];
    }
}

return (new UserResource($user))->response();
```

Collections and simple pagination use the same resource class:

```php
return UserResource::collection($users)->response();

return UserResource::paginated($pagination, '/api/users')->response();
```
