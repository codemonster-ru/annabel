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
        string $localKey
    )
    {
        parent::__construct($builder, $parent, $related);

        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
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
}
