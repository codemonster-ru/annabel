<?php

namespace Codemonster\Database\Traits;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelQuery;

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

    public static function withTrashed(): ModelQuery
    {
        /** @var class-string<Model> $class */
        $class = static::class;

        return $class::query();
    }

    public static function withoutTrashed(): ModelQuery
    {
        /** @var class-string<Model> $class */
        $class = static::class;

        $instance = new static();

        return $class::query()
            ->whereNull($instance->deletedAtColumn);
    }

    public static function onlyTrashed(): ModelQuery
    {
        /** @var class-string<Model> $class */
        $class = static::class;

        $instance = new static();

        return $class::query()
            ->whereNotNull($instance->deletedAtColumn);
    }
}
