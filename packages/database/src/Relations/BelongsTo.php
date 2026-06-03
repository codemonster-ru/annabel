<?php

namespace Codemonster\Database\Relations;

use Codemonster\Database\ORM\Model;

/**
 * @template TRelated of Model
 * @template TParent of Model
 * @extends Relation<TRelated, TParent>
 */
class BelongsTo extends Relation
{
    protected string $foreignKey;
    protected string $ownerKey;

    public function __construct($builder, Model $parent, Model $related, string $foreignKey, string $ownerKey)
    {
        parent::__construct($builder, $parent, $related);

        $this->foreignKey = $foreignKey;
        $this->ownerKey   = $ownerKey;
    }

    /**
     * @return TRelated|null
     */
    public function getResults(): ?Model
    {
        $value = $this->parent->{$this->foreignKey};

        if ($value === null) {
            return null;
        }

        $row = $this->builder
            ->where($this->ownerKey, $value)
            ->first();

        if (!$row) {
            return null;
        }

        $class = get_class($this->related);

        return new $class((array) $row, true);
    }
}
