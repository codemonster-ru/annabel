<?php

namespace Codemonster\Database\ORM;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Query\QueryBuilder;
use Codemonster\Database\Relations\BelongsTo;
use Codemonster\Database\Relations\BelongsToMany;
use Codemonster\Database\Relations\HasMany;
use Codemonster\Database\Relations\HasOne;
use Codemonster\Database\Relations\Relation;

/**
 * @method bool runSoftDelete()
 */
abstract class Model implements \JsonSerializable
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $incrementing = true;
    protected string $keyType = 'int';

    protected bool $timestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    protected array $fillable = [];
    protected array $guarded = ['*'];
    protected array $hidden = [];
    protected array $casts = [];

    protected array $attributes = [];
    protected array $original   = [];
    protected array $relations  = [];

    protected bool $exists = false;
    protected bool $wasRecentlyCreated = false;

    /**
     * @var callable|null fn(string $modelClass): ConnectionInterface
     */
    protected static $connectionResolver;

    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->exists = $exists;
        $this->fill($attributes);
        $this->syncOriginal();
    }

    // ---------------------------------------------------------------------
    //  Static API
    // ---------------------------------------------------------------------

    public static function setConnectionResolver(callable $resolver): void
    {
        static::$connectionResolver = $resolver;
    }

    protected static function connection(): ConnectionInterface
    {
        if (!static::$connectionResolver) {
            throw new \RuntimeException('No connection resolver set for models.');
        }

        return call_user_func(static::$connectionResolver, static::class);
    }

    /**
     * @return ModelQuery<static>
     */
    public static function query(): ModelQuery
    {
        $instance = new static();

        /** @var QueryBuilder $builder */
        $builder = static::connection()->table($instance->getTable());

        return new ModelQuery($builder, static::class);
    }

    /**
     * @return ModelCollection<static>
     */
    public static function all(): ModelCollection
    {
        return static::query()->get();
    }

    public static function find($id): ?static
    {
        $instance = new static();

        return static::query()
            ->where($instance->getQualifiedKeyName(), $id)
            ->first();
    }

    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * Hydration of an array of strings into a collection of models.
     *
     * @return ModelCollection<static>
     */
    public static function hydrate(array $rows): ModelCollection
    {
        $items = [];

        foreach ($rows as $row) {
            $items[] = new static((array) $row, true);
        }

        return new ModelCollection($items);
    }

    // ---------------------------------------------------------------------
    //  Life cycle
    // ---------------------------------------------------------------------

    public function save(): bool
    {
        $this->touchTimestamps();

        $dirty = $this->getDirtyForPersistence();

        if (!$this->exists) {
            if (empty($dirty)) {
                return true;
            }

            /** @var QueryBuilder $builder */
            $builder = static::connection()->table($this->getTable());

            if ($this->incrementing) {
                $id = $builder->insertGetId($dirty);

                $this->setAttribute($this->getKeyName(), $id);
            } else {
                $builder->insert($dirty);
            }

            $this->exists = true;
            $this->wasRecentlyCreated = true;
            $this->syncOriginal();

            return true;
        }

        if (!empty($dirty)) {
            /** @var QueryBuilder $builder */
            $builder = static::connection()->table($this->getTable());

            $builder
                ->where($this->getKeyName(), $this->getKey())
                ->update($dirty);

            $this->syncOriginal();
        }

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        // support for soft deletes (via trait)
        if (method_exists($this, 'runSoftDelete')) {
            /** @var callable $m */
            return $this->runSoftDelete();
        }

        /** @var QueryBuilder $builder */
        $builder = static::connection()->table($this->getTable());

        $builder
            ->where($this->getKeyName(), $this->getKey())
            ->delete();

        $this->exists = false;

        return true;
    }

    // ---------------------------------------------------------------------
    //  Attributes / Castes
    // ---------------------------------------------------------------------

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isGuarded($key)) {
                continue;
            }

            if (!empty($this->fillable) && !in_array($key, $this->fillable, true)) {
                continue;
            }

            $this->setAttribute($key, $value);
        }

        return $this;
    }

    protected function isGuarded(string $key): bool
    {
        if (in_array('*', $this->guarded, true)) {
            return !in_array($key, $this->fillable, true);
        }

        return in_array($key, $this->guarded, true);
    }

    public function getAttributes(): array
    {
        $attributes = $this->attributes;

        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        foreach ($this->relations as $key => $value) {
            if ($value instanceof Model || $value instanceof ModelCollection) {
                $attributes[$key] = $value->toArray();
            } else {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    public function getAttribute(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }

        return null;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    protected function castAttribute(string $key, $value)
    {
        if ($value === null) {
            return null;
        }

        if (!isset($this->casts[$key])) {
            return $value;
        }

        $cast = $this->casts[$key];

        switch ($cast) {
            case 'int':
            case 'integer':
                return (int) $value;

            case 'real':
            case 'float':
            case 'double':
                return (float) $value;

            case 'string':
                return (string) $value;

            case 'bool':
            case 'boolean':
                return (bool) $value;

            case 'array':
                return (array) $value;

            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;

            case 'datetime':
                return new \DateTimeImmutable((string) $value);

            case 'date':
                return (new \DateTimeImmutable((string) $value))->setTime(0, 0);

            default:
                return $value;
        }
    }

    protected function getDirtyForPersistence(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    // ---------------------------------------------------------------------
    //  Timestamps
    // ---------------------------------------------------------------------

    protected function touchesTimestamps(): bool
    {
        return $this->timestamps;
    }

    protected function touchTimestamps(): void
    {
        if (!$this->touchesTimestamps()) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if (!$this->exists) {
            $this->setAttribute($this->createdAtColumn, $now);
        }

        $this->setAttribute($this->updatedAtColumn, $now);
    }

    // ---------------------------------------------------------------------
    //  Relations
    // ---------------------------------------------------------------------

    protected function getRelationshipFromMethod(string $method)
    {
        /** @var Relation $relation */
        $relation = $this->$method();

        if (!$relation instanceof Relation) {
            throw new \RuntimeException(
                sprintf('Relationship method %s must return instance of Relation', $method)
            );
        }

        $results = $relation->getResults();

        $this->relations[$method] = $results;

        return $results;
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return HasOne<TRelated, static>
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        /** @var Model $instance */
        $instance = new $related();

        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey   = $localKey ?? $this->getKeyName();

        return new HasOne(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $foreignKey,
            $localKey
        );
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return HasMany<TRelated, static>
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        /** @var Model $instance */
        $instance = new $related();

        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey   = $localKey ?? $this->getKeyName();

        return new HasMany(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $foreignKey,
            $localKey
        );
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return BelongsTo<TRelated, static>
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        /** @var Model $instance */
        $instance = new $related();

        // foreignKey on the CURRENT model, ownerKey on the linked one
        $foreignKey = $foreignKey ?? $instance->getForeignKey();
        $ownerKey   = $ownerKey ?? $instance->getKeyName();

        return new BelongsTo(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $foreignKey,
            $ownerKey
        );
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return BelongsToMany<TRelated, static>
     */
    public function belongsToMany(
        string $related,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null
    ): BelongsToMany {
        /** @var Model $instance */
        $instance = new $related();

        $pivotTable     = $pivotTable     ?? $this->joiningTable($instance);
        $foreignPivotKey = $foreignPivotKey ?? $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?? $instance->getForeignKey();

        $parentKey  = $parentKey  ?? $this->getKeyName();
        $relatedKey = $relatedKey ?? $instance->getKeyName();

        return new BelongsToMany(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $pivotTable,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey
        );
    }

    public function load($relations): static
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($relations as $name) {
            $this->getRelationshipFromMethod($name);
        }

        return $this;
    }

    // ---------------------------------------------------------------------
    //  Auxiliary methods
    // ---------------------------------------------------------------------

    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        $name = (new \ReflectionClass($this))->getShortName();
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

        return $this->table = $snake . 's';
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getForeignKey(): string
    {
        $name = (new \ReflectionClass($this))->getShortName();
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

        return $snake . '_id';
    }

    public function getQualifiedKeyName(): string
    {
        return $this->getTable() . '.' . $this->getKeyName();
    }

    public function joiningTable(Model $related): string
    {
        $segments = [
            strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', (new \ReflectionClass($this))->getShortName())),
            strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', (new \ReflectionClass($related))->getShortName())),
        ];

        sort($segments);

        return implode('_', $segments);
    }

    // ---------------------------------------------------------------------
    //  Magic/serialization
    // ---------------------------------------------------------------------

    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function toArray(): array
    {
        return $this->getAttributes();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
