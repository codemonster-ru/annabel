<?php

namespace Codemonster\Database\ORM;

use Codemonster\Database\Query\QueryBuilder;

/**
 * @template TModel of Model
 */
class ModelQuery
{
    /** @var QueryBuilder */
    protected QueryBuilder $builder;
    /** @var class-string<TModel> */
    protected string $modelClass;

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

        return $model::hydrate($rows);
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

        return new $model((array) $row, true);
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

    /**
     * @param list<mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        $result = $this->builder->$name(...$arguments);

        if ($result instanceof QueryBuilder) {
            $this->builder = $result;

            return $this;
        }

        return $result;
    }
}
