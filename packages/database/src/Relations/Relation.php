<?php

namespace Codemonster\Database\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Query\QueryBuilder;

/**
 * @template TRelated of Model
 * @template TParent of Model
 */
abstract class Relation
{
    /** @var QueryBuilder */
    protected QueryBuilder $builder;
    /** @var TParent */
    protected Model $parent;
    /** @var TRelated */
    protected Model $related;

    /**
     * @param TParent $parent
     * @param TRelated $related
     */
    public function __construct(QueryBuilder $builder, Model $parent, Model $related)
    {
        $this->builder = $builder;
        $this->parent = $parent;
        $this->related = $related;
    }

    abstract public function getResults(): mixed;

    /**
     * Fallback eager loader for relations that do not have a batched matcher yet.
     *
     * @param list<TParent> $models
     */
    public function eagerLoad(array $models, string $relationName, ?string $nested = null): void
    {
        foreach ($models as $model) {
            $relation = $model->{$relationName}();

            if (!$relation instanceof self) {
                throw new \RuntimeException(
                    sprintf('Relationship method %s must return instance of Relation', $relationName),
                );
            }

            $related = $relation->getResults();
            $model->setRelation($relationName, $related);
            $this->loadNested($related, $nested);
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return ModelCollection<TRelated>
     */
    protected function hydrate(array $rows): ModelCollection
    {
        $class = get_class($this->related);

        return $class::hydrate($rows);
    }

    protected function loadNested(mixed $related, ?string $nested): void
    {
        if ($nested === null) {
            return;
        }

        if ($related instanceof Model || $related instanceof ModelCollection) {
            $related->load($nested);
        }
    }

    /**
     * @param list<mixed> $values
     * @return list<int|string>
     */
    protected function uniqueKeys(array $values): array
    {
        $keys = [];

        foreach ($values as $value) {
            if (is_int($value) || is_string($value)) {
                $keys[(string) $value] = $value;
            }
        }

        return array_values($keys);
    }

    protected function dictionaryKey(mixed $value): ?string
    {
        return is_int($value) || is_string($value) ? (string) $value : null;
    }
}
