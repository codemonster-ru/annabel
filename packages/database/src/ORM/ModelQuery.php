<?php

namespace Codemonster\Database\ORM;

use Codemonster\Database\Query\QueryBuilder;

/**
 * @template TModel of Model
 * @method list<mixed> pluck(string $column)
 * @method mixed value(string $column)
 * @method array<string, mixed> simplePaginate(int $perPage = 15, int $page = 1)
 */
class ModelQuery
{
    /** @var QueryBuilder */
    protected QueryBuilder $builder;
    /** @var class-string<TModel> */
    protected string $modelClass;
    /** @var list<string> */
    protected array $eagerLoads = [];

    /**
     * @param class-string<TModel> $modelClass
     */
    public function __construct(QueryBuilder $builder, string $modelClass)
    {
        $this->builder = $builder;
        $this->modelClass = $modelClass;
    }

    public function getBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    /**
     * @return ModelCollection<TModel>
     */
    public function get(): ModelCollection
    {
        $rows = $this->builder->get();

        $model = $this->modelClass;

        $collection = $model::hydrate($rows);

        if ($this->eagerLoads !== []) {
            $collection->load($this->eagerLoads);
        }

        return $collection;
    }

    /**
     * @return TModel|null
     */
    public function first(): ?Model
    {
        $row = $this->builder->first();

        if (!$row) {
            return null;
        }

        $model = $this->modelClass;

        $instance = new $model((array) $row, true);

        if ($this->eagerLoads !== []) {
            $instance->load($this->eagerLoads);
        }

        return $instance;
    }

    /** @param string|list<string> $relations */
    public function with(string|array $relations): static
    {
        foreach ((array) $relations as $relation) {
            $this->eagerLoads[] = $relation;
        }

        $this->eagerLoads = array_values(array_unique($this->eagerLoads));

        return $this;
    }

    public function exists(): bool
    {
        return $this->builder->exists();
    }

    public function count(): int
    {
        return (int) $this->builder->count();
    }

    /**
     * @return array{
     *     data: ModelCollection<TModel>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int,
     *     from: int|null,
     *     to: int|null,
     *     next_page: int|null,
     *     prev_page: int|null
     * }
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $total = $this->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = clone $this->builder;
        $builder->forPage($page, $perPage);

        $query = new self($builder, $this->modelClass);
        $query->with($this->eagerLoads);

        $data = $query->get();
        $count = count($data);

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $count === 0 ? null : $offset + 1,
            'to' => $count === 0 ? null : min($total, $offset + $count),
            'next_page' => $page < $lastPage ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null,
        ];
    }

    /**
     * @param string|callable(QueryBuilder): void $column
     * @param mixed ...$arguments
     */
    public function where(string|callable $column, mixed ...$arguments): static
    {
        $this->builder->where($column, ...$arguments);

        return $this;
    }

    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        $this->builder->whereNull($column, $boolean);

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $this->builder->whereNotNull($column, $boolean);

        return $this;
    }

    /** @param list<mixed> $values */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        $this->builder->whereIn($column, $values, $boolean);

        return $this;
    }

    /**
     * @param list<mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        $scope = 'scope' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
        $model = new $this->modelClass();

        if (method_exists($model, $scope)) {
            $result = $model->{$scope}($this, ...$arguments);

            if ($result instanceof self) {
                return $result;
            }

            if ($result instanceof QueryBuilder) {
                $this->builder = $result;
            }

            return $this;
        }

        $result = $this->builder->$name(...$arguments);

        if ($result instanceof QueryBuilder) {
            $this->builder = $result;

            return $this;
        }

        return $result;
    }
}
