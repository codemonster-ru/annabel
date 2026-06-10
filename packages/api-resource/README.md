# Codemonster API Resource

JSON resource and simple pagination primitives for Annabel applications.

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

Collections and existing database pagination results use the same resource:

```php
return UserResource::collection(User::all())->response();

return UserResource::paginated(
    User::query()->simplePaginate(20, $page),
    '/api/users',
    ['filter' => 'active'],
)->response();
```

Paginated responses contain `data`, `links.prev`, `links.next`, and
`meta.current_page` / `meta.per_page`.
