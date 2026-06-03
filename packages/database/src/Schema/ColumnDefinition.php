<?php

namespace Codemonster\Database\Schema;

class ColumnDefinition
{
    public string $type;
    public string $name;

    public array $options = [];
    public array $modifiers = [];

    /** Changing existing column */
    public bool $change = false;

    public function __construct(string $type, string $name, array $options = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->options = $options;
    }

    public function nullable(bool $value = true): static
    {
        $this->modifiers['nullable'] = $value;

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->modifiers['default'] = $value;

        return $this;
    }

    public function unique(): static
    {
        $this->modifiers['unique'] = true;

        return $this;
    }

    public function primary(): static
    {
        $this->modifiers['primary'] = true;

        return $this;
    }

    public function autoIncrement(): static
    {
        $this->modifiers['autoIncrement'] = true;

        return $this;
    }

    public function unsigned(bool $value = true): static
    {
        $this->modifiers['unsigned'] = $value;

        return $this;
    }

    public function comment(string $comment): static
    {
        $this->modifiers['comment'] = $comment;

        return $this;
    }

    public function change(): static
    {
        $this->change = true;

        return $this;
    }
}
