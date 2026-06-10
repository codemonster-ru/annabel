<?php

namespace Codemonster\ApiResource;

use Codemonster\Http\Response;

class ResourceCollection implements \JsonSerializable
{
    /** @var list<mixed> */
    protected array $resources;
    /** @var class-string<JsonResource> */
    protected string $resourceClass;
    /** @var array<string, mixed> */
    protected array $additional = [];

    /**
     * @param iterable<mixed> $resources
     * @param class-string<JsonResource> $resourceClass
     */
    public function __construct(iterable $resources, string $resourceClass)
    {
        $this->resources = [];
        foreach ($resources as $resource) {
            $this->resources[] = $resource;
        }

        $this->resourceClass = $resourceClass;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function additional(array $data): static
    {
        $this->additional = array_replace($this->additional, $data);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        return ['data' => $this->transform($this->resources)] + $this->additional;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->resolve();
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function response(int $status = 200, array $headers = []): Response
    {
        return Response::json($this->resolve(), $status, $headers);
    }

    /**
     * @param list<mixed> $resources
     * @return list<array<string, mixed>>
     */
    protected function transform(array $resources): array
    {
        $class = $this->resourceClass;

        return array_map(
            static function (mixed $resource) use ($class): array {
                $data = (new $class($resource))->resolve()['data'];

                if (!is_array($data)) {
                    throw new \LogicException("Resource [{$class}] must resolve to an array.");
                }

                $normalized = [];
                foreach ($data as $key => $value) {
                    if (!is_string($key)) {
                        throw new \LogicException("Resource [{$class}] must resolve to an associative array.");
                    }
                    $normalized[$key] = $value;
                }

                return $normalized;
            },
            $resources,
        );
    }
}
