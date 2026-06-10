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
 * @phpstan-consistent-constructor
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

    /** @var list<string> */
    protected array $fillable = [];
    /** @var list<string> */
    protected array $guarded = ['*'];
    /** @var list<string> */
    protected array $hidden = [];
    /** @var array<string, string> */
    protected array $casts = [];

    /** @var array<string, mixed> */
    protected array $attributes = [];
    /** @var array<string, mixed> */
    protected array $original = [];
    /** @var array<string, mixed> */
    protected array $relations = [];

    protected bool $exists = false;
    protected bool $wasRecentlyCreated = false;

    /**
     * @var (callable(class-string<Model>): ConnectionInterface)|null
     */
    protected static $connectionResolver;

    /**
     * @var array<class-string<Model>, array<string, list<callable(static): mixed>>>
     */
    protected static array $modelEventListeners = [];

    /**
     * @var array<class-string<Model>, list<object|class-string>>
     */
    protected static array $modelObservers = [];

    /** @param array<string, mixed> $attributes */
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

    public static function find(mixed $id): ?static
    {
        $instance = new static();

        return static::query()
            ->where($instance->getQualifiedKeyName(), $id)
            ->first();
    }

    /** @param array<string, mixed> $attributes */
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
     * @param list<array<string, mixed>> $rows
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

    /**
     * @param callable(static): mixed $listener
     */
    public static function on(string $event, callable $listener): void
    {
        static::$modelEventListeners[static::class][$event][] = $listener;
    }

    /**
     * @param object|class-string $observer
     */
    public static function observe(object|string $observer): void
    {
        if (is_string($observer) && !class_exists($observer)) {
            throw new \InvalidArgumentException(sprintf('Observer class [%s] does not exist.', $observer));
        }

        static::$modelObservers[static::class][] = $observer;
    }

    public static function flushModelEvents(): void
    {
        unset(static::$modelEventListeners[static::class], static::$modelObservers[static::class]);
    }

    // ---------------------------------------------------------------------
    //  Life cycle
    // ---------------------------------------------------------------------

    public function save(): bool
    {
        $isCreating = !$this->exists;

        if (!$this->fireModelEvent('saving') || !$this->fireModelEvent($isCreating ? 'creating' : 'updating')) {
            return false;
        }

        $this->touchTimestamps();

        $dirty = $this->getDirtyForPersistence();

        if ($isCreating) {
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

            $this->fireModelEvent('created', false);
            $this->fireModelEvent('saved', false);

            return true;
        }

        if (!empty($dirty)) {
            /** @var QueryBuilder $builder */
            $builder = static::connection()->table($this->getTable());

            $builder
                ->where($this->getKeyName(), $this->getKey())
                ->update($dirty);

            $this->syncOriginal();
            $this->fireModelEvent('updated', false);
        }

        $this->fireModelEvent('saved', false);

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if (!$this->fireModelEvent('deleting')) {
            return false;
        }

        // support for soft deletes (via trait)
        if (method_exists($this, 'runSoftDelete')) {
            $deleted = $this->runSoftDelete();

            if ($deleted) {
                $this->fireModelEvent('deleted', false);
            }

            return $deleted;
        }

        /** @var QueryBuilder $builder */
        $builder = static::connection()->table($this->getTable());

        $builder
            ->where($this->getKeyName(), $this->getKey())
            ->delete();

        $this->exists = false;
        $this->fireModelEvent('deleted', false);

        return true;
    }

    protected function fireModelEvent(string $event, bool $halt = true): bool
    {
        $class = static::class;

        foreach (static::$modelEventListeners[$class][$event] ?? [] as $listener) {
            if ($listener($this) === false && $halt) {
                return false;
            }
        }

        foreach (static::$modelObservers[$class] ?? [] as $observer) {
            if (is_string($observer)) {
                $observer = new $observer();
            }

            if (!method_exists($observer, $event)) {
                continue;
            }

            if ($observer->{$event}($this) === false && $halt) {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------------------------------------
    //  Attributes / Castes
    // ---------------------------------------------------------------------

    /** @param array<string, mixed> $attributes */
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

    /** @return array<string, mixed> */
    public function getAttributes(): array
    {
        $attributes = [];

        foreach (array_keys($this->attributes) as $key) {
            if (in_array($key, $this->hidden, true)) {
                continue;
            }

            $attributes[$key] = $this->getAttribute($key);
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

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            $value = $this->castAttribute($key, $this->attributes[$key]);
            $accessor = $this->attributeMethodName('get', $key, 'Attribute');

            if (method_exists($this, $accessor)) {
                return $this->{$accessor}($value);
            }

            return $value;
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }

        return null;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $mutator = $this->attributeMethodName('set', $key, 'Attribute');

        if (method_exists($this, $mutator)) {
            $value = $this->{$mutator}($value);
        }

        $this->attributes[$key] = $value;
    }

    public function setRelation(string $key, mixed $value): static
    {
        $this->relations[$key] = $value;

        return $this;
    }

    protected function castAttribute(string $key, mixed $value): mixed
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
                return self::integerCast($value);

            case 'real':
            case 'float':
            case 'double':
                return self::floatCast($value);

            case 'string':
                return self::stringCast($value);

            case 'bool':
            case 'boolean':
                return self::booleanCast($value);

            case 'array':
                return self::arrayCast($value);

            case 'json':
                return self::jsonCast($value);

            case 'object':
                return self::objectCast($value);

            case 'datetime':
            case 'immutable_datetime':
                return new \DateTimeImmutable(self::stringCast($value));

            case 'date':
                return (new \DateTimeImmutable(self::stringCast($value)))->setTime(0, 0);

            default:
                if (str_starts_with($cast, 'decimal:')) {
                    $precision = (int) substr($cast, 8);

                    return number_format(self::floatCast($value), $precision, '.', '');
                }

                return $value;
        }
    }

    private static function integerCast(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }

        throw new \UnexpectedValueException('Model attribute cannot be cast to integer.');
    }

    private static function floatCast(mixed $value): float
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (float) $value;
        }

        throw new \UnexpectedValueException('Model attribute cannot be cast to float.');
    }

    private static function booleanCast(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }

            if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
        }

        return (bool) $value;
    }

    /** @return array<mixed> */
    private static function arrayCast(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return (array) $value;
    }

    private static function jsonCast(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private static function objectCast(mixed $value): object
    {
        if (is_object($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value);

            if (json_last_error() === JSON_ERROR_NONE && is_object($decoded)) {
                return $decoded;
            }
        }

        return (object) $value;
    }

    private static function stringCast(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        throw new \UnexpectedValueException('Model attribute cannot be cast to string.');
    }

    private function attributeMethodName(string $prefix, string $key, string $suffix): string
    {
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        return $prefix . $studly . $suffix;
    }

    /** @return array<string, mixed> */
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

    protected function getRelationshipFromMethod(string $method): mixed
    {
        /** @var Relation<Model, Model> $relation */
        $relation = $this->$method();

        if (!$relation instanceof Relation) {
            throw new \RuntimeException(
                sprintf('Relationship method %s must return instance of Relation', $method),
            );
        }

        $results = $relation->getResults();

        $this->relations[$method] = $results;

        return $results;
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return HasOne<TRelated, $this>
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        /** @var TRelated $instance */
        $instance = new $related();

        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasOne(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $foreignKey,
            $localKey,
        );
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return HasMany<TRelated, $this>
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        /** @var TRelated $instance */
        $instance = new $related();

        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasMany(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $foreignKey,
            $localKey,
        );
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return BelongsTo<TRelated, $this>
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        /** @var TRelated $instance */
        $instance = new $related();

        // foreignKey on the CURRENT model, ownerKey on the linked one
        $foreignKey = $foreignKey ?? $instance->getForeignKey();
        $ownerKey = $ownerKey ?? $instance->getKeyName();

        return new BelongsTo(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $foreignKey,
            $ownerKey,
        );
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $related
     * @return BelongsToMany<TRelated, $this>
     */
    public function belongsToMany(
        string $related,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null,
    ): BelongsToMany {
        /** @var TRelated $instance */
        $instance = new $related();

        $pivotTable = $pivotTable ?? $this->joiningTable($instance);
        $foreignPivotKey = $foreignPivotKey ?? $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?? $instance->getForeignKey();

        $parentKey = $parentKey ?? $this->getKeyName();
        $relatedKey = $relatedKey ?? $instance->getKeyName();

        return new BelongsToMany(
            $related::query()->getBuilder(),
            $this,
            $instance,
            $pivotTable,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
        );
    }

    /** @param string|list<string> $relations */
    public function load(string|array $relations): static
    {
        static::eagerLoadRelations([$this], $relations);

        return $this;
    }

    /**
     * @param list<static> $models
     * @param string|list<string> $relations
     */
    public static function eagerLoadRelations(array $models, string|array $relations): void
    {
        if ($models === []) {
            return;
        }

        foreach ((array) $relations as $name) {
            static::eagerLoadRelationPath($models, $name);
        }
    }

    /**
     * @param list<static> $models
     */
    protected static function eagerLoadRelationPath(array $models, string $path): void
    {
        $segments = explode('.', $path, 2);
        $relationName = $segments[0];
        $nested = $segments[1] ?? null;

        if (!method_exists($models[0], $relationName)) {
            throw new \RuntimeException(sprintf('Relationship method %s does not exist.', $relationName));
        }

        $relation = $models[0]->{$relationName}();

        if (!$relation instanceof Relation) {
            throw new \RuntimeException(
                sprintf('Relationship method %s must return instance of Relation', $relationName),
            );
        }

        $relation->eagerLoad($models, $relationName, $nested);
    }

    protected function loadRelationPath(string $path): void
    {
        $segments = explode('.', $path, 2);
        $relation = $segments[0];
        $nested = $segments[1] ?? null;

        if (!method_exists($this, $relation)) {
            throw new \RuntimeException(sprintf('Relationship method %s does not exist.', $relation));
        }

        $related = $this->getRelationshipFromMethod($relation);

        if ($nested === null) {
            return;
        }

        if ($related instanceof Model || $related instanceof ModelCollection) {
            $related->load($nested);
        }
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
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);

        return $this->table = $snake . 's';
    }

    public function getKey(): mixed
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
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);

        return $snake . '_id';
    }

    public function getQualifiedKeyName(): string
    {
        return $this->getTable() . '.' . $this->getKeyName();
    }

    public function joiningTable(Model $related): string
    {
        $parentName = (new \ReflectionClass($this))->getShortName();
        $relatedName = (new \ReflectionClass($related))->getShortName();
        $segments = [
            strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $parentName) ?? $parentName),
            strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $relatedName) ?? $relatedName),
        ];

        sort($segments);

        return implode('_', $segments);
    }

    // ---------------------------------------------------------------------
    //  Magic/serialization
    // ---------------------------------------------------------------------

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
