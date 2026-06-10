<?php

namespace Codemonster\Database\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Query\QueryBuilder;

/**
 * @template TRelated of Model
 * @template TParent of Model
 * @extends Relation<TRelated, TParent>
 */
class HasOne extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    /**
     * @param TParent $parent
     * @param TRelated $related
     */
    public function __construct(
        QueryBuilder $builder,
        Model $parent,
        Model $related,
        string $foreignKey,
        string $localKey,
    ) {
        parent::__construct($builder, $parent, $related);

        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * @return TRelated|null
     */
    public function getResults(): ?Model
    {
        $row = $this->builder
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->first();

        if (!$row) {
            return null;
        }

        $class = get_class($this->related);

        return new $class((array) $row, true);
    }

    /**
     * @param list<TParent> $models
     */
    public function eagerLoad(array $models, string $relationName, ?string $nested = null): void
    {
        $keys = $this->uniqueKeys(array_map(
            fn (Model $model) => $model->{$this->localKey},
            $models,
        ));

        /** @var class-string<TRelated> $class */
        $class = get_class($this->related);
        $related = $keys === []
            ? []
            : $class::query()->whereIn($this->foreignKey, $keys)->get();

        /** @var array<string, TRelated> $matched */
        $matched = [];

        foreach ($related as $item) {
            $key = $this->dictionaryKey($item->{$this->foreignKey});

            if ($key !== null) {
                $matched[$key] ??= $item;
            }
        }

        foreach ($models as $model) {
            $key = $this->dictionaryKey($model->{$this->localKey});
            $item = $key === null ? null : ($matched[$key] ?? null);

            $model->setRelation($relationName, $item);
            $this->loadNested($item, $nested);
        }
    }
}
