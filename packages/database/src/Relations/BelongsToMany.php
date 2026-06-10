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
class BelongsToMany extends Relation
{
    protected string $pivotTable;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;
    protected string $parentKey;
    protected string $relatedKey;

    /**
     * @param TParent $parent
     * @param TRelated $related
     */
    public function __construct(
        QueryBuilder $builder,
        Model $parent,
        Model $related,
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey,
    ) {
        parent::__construct($builder, $parent, $related);

        $this->pivotTable = $pivotTable;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
    }

    /**
     * @return ModelCollection<TRelated>
     */
    public function getResults(): ModelCollection
    {
        $parentId = $this->parent->{$this->parentKey};
        $relatedTable = $this->related->getTable();

        $rows = $this->builder
            ->join(
                $this->pivotTable,
                $this->pivotTable . '.' . $this->relatedPivotKey,
                '=',
                $relatedTable . '.' . $this->relatedKey,
            )
            ->where($this->pivotTable . '.' . $this->foreignPivotKey, $parentId)
            ->select($relatedTable . '.*')
            ->get();

        $class = get_class($this->related);

        return $class::hydrate($rows);
    }
}
