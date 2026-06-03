<?php

namespace Codemonster\Database\ORM;

class ModelCollection implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{
    /** @var Model[] */
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function add(Model $model): void
    {
        $this->items[] = $model;
    }

    public function load($relations): self
    {
        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($this->items as $item) {
            $item->load($relations);
        }

        return $this;
    }

    public function toArray(): array
    {
        return array_map(
            static fn(Model $m) => $m->toArray(),
            $this->items
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    // Array Access

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): ?Model
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof Model) {
            throw new \InvalidArgumentException('ModelCollection accepts only Model instances.');
        }

        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
