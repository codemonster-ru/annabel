<?php

namespace Codemonster\ApiResource;

class PaginatedResourceCollection extends ResourceCollection
{
    protected int $perPage;
    protected int $currentPage;
    protected ?int $nextPage;
    protected ?int $previousPage;
    protected string $path;
    /** @var array<string, scalar|null> */
    protected array $query;

    /**
     * @param array{data: iterable<mixed>, per_page: int, current_page: int, next_page: int|null, prev_page: int|null} $pagination
     * @param class-string<JsonResource> $resourceClass
     * @param array<string, scalar|null> $query
     */
    public function __construct(array $pagination, string $resourceClass, string $path = '', array $query = [])
    {
        parent::__construct($pagination['data'], $resourceClass);

        if ($pagination['per_page'] < 1
            || $pagination['current_page'] < 1
            || ($pagination['next_page'] !== null && $pagination['next_page'] < 1)
            || ($pagination['prev_page'] !== null && $pagination['prev_page'] < 1)) {
            throw new \InvalidArgumentException('Pagination page and per-page values must be positive.');
        }

        $this->perPage = $pagination['per_page'];
        $this->currentPage = $pagination['current_page'];
        $this->nextPage = $pagination['next_page'];
        $this->previousPage = $pagination['prev_page'];
        $this->path = $path;
        $this->query = $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        return [
            'data' => $this->transform($this->resources),
            'links' => [
                'prev' => $this->url($this->previousPage),
                'next' => $this->url($this->nextPage),
            ],
            'meta' => [
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
            ],
        ] + $this->additional;
    }

    protected function url(?int $page): ?string
    {
        if ($page === null) {
            return null;
        }

        $query = $this->query;
        $query['page'] = $page;
        $separator = str_contains($this->path, '?') ? '&' : '?';

        return $this->path . $separator . http_build_query($query);
    }
}
