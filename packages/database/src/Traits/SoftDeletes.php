<?php

namespace Codemonster\Database\Traits;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelQuery;

/** @phpstan-require-extends Model */
trait SoftDeletes
{
    protected string $deletedAtColumn = 'deleted_at';

    public function trashed(): bool
    {
        return $this->getAttribute($this->deletedAtColumn) !== null;
    }

    protected function runSoftDelete(): bool
    {
        if (!$this instanceof Model) {
            return false;
        }

        $time = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->setAttribute($this->deletedAtColumn, $time);
        $this->touchTimestamps();

        $dirty = $this->getDirtyForPersistence();

        if (!empty($dirty)) {
            static::query()
                ->getBuilder()
                ->where($this->getKeyName(), $this->getKey())
                ->update($dirty);
        }

        $this->syncOriginal();

        return true;
    }

    public function restore(): bool
    {
        if (!$this instanceof Model) {
            return false;
        }

        if (!$this->fireModelEvent('restoring')) {
            return false;
        }

        $this->setAttribute($this->deletedAtColumn, null);
        $this->touchTimestamps();

        $dirty = $this->getDirtyForPersistence();

        if (!empty($dirty)) {
            static::query()
                ->getBuilder()
                ->where($this->getKeyName(), $this->getKey())
                ->update($dirty);
        }

        $this->syncOriginal();
        $this->fireModelEvent('restored', false);

        return true;
    }

    /** @return ModelQuery<static> */
    public static function withTrashed(): ModelQuery
    {
        return static::query();
    }

    /** @return ModelQuery<static> */
    public static function withoutTrashed(): ModelQuery
    {
        $instance = new static();

        return static::query()
            ->whereNull($instance->deletedAtColumn);
    }

    /** @return ModelQuery<static> */
    public static function onlyTrashed(): ModelQuery
    {
        $instance = new static();

        return static::query()
            ->whereNotNull($instance->deletedAtColumn);
    }
}
