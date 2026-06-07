<?php

namespace Codemonster\Database\ORM;

/**
 * @template TModel of Model
 * @implements \ArrayAccess<int, TModel>
 * @implements \IteratorAggregate<int, TModel>
 */
class ModelCollection implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{
    /** @var list<TModel> */
    protected array $items = [];

    /** @param list<TModel> $items */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /** @param TModel $model */
    public function add(Model $model): void
    {
        $this->items[] = $model;
    }

    /** @param string|list<string> $relations */
    public function load(string|array $relations): static
    {
        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($this->items as $item) {
            $item->load($relations);
        }

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(
            static fn(Model $m) => $m->toArray(),
            $this->items
        );
    }

    /** @return list<array<string, mixed>> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return \Traversable<int, TModel> */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    // Array Access

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /** @return TModel|null */
    public function offsetGet(mixed $offset): ?Model
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Model) {
            throw new \InvalidArgumentException('ModelCollection accepts only Model instances.');
        }

        if ($offset === null) {
            $this->items[] = $value;
        } else {
            if (!is_int($offset) || $offset < 0) {
                throw new \InvalidArgumentException('ModelCollection offsets must be non-negative integers.');
            }

            if ($offset > count($this->items)) {
                throw new \InvalidArgumentException('ModelCollection does not support sparse offsets.');
            }

            if ($offset === count($this->items)) {
                $this->items[] = $value;
            } else {
                $items = $this->items;
                $items[$offset] = $value;
                $this->items = array_values($items);
            }
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_int($offset)) {
            $items = $this->items;
            unset($items[$offset]);
            $this->items = array_values($items);
        }
    }

    public function count(): int
    {
        return count($this->items);
    }
}
