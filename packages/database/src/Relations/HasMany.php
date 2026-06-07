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
        string $localKey
    )
    {
        parent::__construct($builder, $parent, $related);

        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
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
}
