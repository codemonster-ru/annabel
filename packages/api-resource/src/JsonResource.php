<?php

namespace Codemonster\ApiResource;

use Codemonster\Http\Response;

abstract class JsonResource implements \JsonSerializable
{
    /** @var array<string, mixed> */
    protected array $additional = [];

    public function __construct(protected mixed $resource)
    {
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

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
        return ['data' => $this->normalize($this->toArray())] + $this->additional;
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
     * @param iterable<mixed> $resources
     */
    public static function collection(iterable $resources): ResourceCollection
    {
        return new ResourceCollection($resources, static::class);
    }

    /**
     * @param array{data: iterable<mixed>, per_page: int, current_page: int, next_page: int|null, prev_page: int|null} $pagination
     * @param array<string, scalar|null> $query
     */
    public static function paginated(
        array $pagination,
        string $path = '',
        array $query = [],
    ): PaginatedResourceCollection {
        return new PaginatedResourceCollection($pagination, static::class, $path, $query);
    }

    protected function normalize(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->resolve()['data'];
        }

        if ($value instanceof \JsonSerializable) {
            return $this->normalize($value->jsonSerialize());
        }

        if ($value instanceof \Traversable) {
            return $this->normalize(iterator_to_array($value));
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }

            return $normalized;
        }

        return $value;
    }
}
