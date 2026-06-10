<?php

namespace Codemonster\Database\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Query\QueryBuilder;

/**
 * @template TRelated of Model
 * @template TParent of Model
 * @extends Relation<TRelated, TParent>
 */
class HasMany extends Relation
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
     * @return ModelCollection<TRelated>
     */
    public function getResults(): ModelCollection
    {
        $rows = $this->builder
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->get();

        $class = get_class($this->related);

        return $class::hydrate($rows);
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
            ? new ModelCollection()
            : $class::query()->whereIn($this->foreignKey, $keys)->get();

        /** @var array<string, list<TRelated>> $grouped */
        $grouped = [];

        foreach ($related as $item) {
            $key = $this->dictionaryKey($item->{$this->foreignKey});

            if ($key !== null) {
                $grouped[$key][] = $item;
            }
        }

        foreach ($models as $model) {
            $key = $this->dictionaryKey($model->{$this->localKey});
            /** @var list<TRelated> $items */
            $items = $key === null ? [] : ($grouped[$key] ?? []);
            $collection = new ModelCollection($items);

            $model->setRelation($relationName, $collection);
            $this->loadNested($collection, $nested);
        }
    }
}
