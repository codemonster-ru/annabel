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
        $this->parent  = $parent;
        $this->related = $related;
    }

    abstract public function getResults(): mixed;

    /**
     * @param list<array<string, mixed>> $rows
     * @return ModelCollection<TRelated>
     */
    protected function hydrate(array $rows): ModelCollection
    {
        $class = get_class($this->related);

        return $class::hydrate($rows);
    }
}
