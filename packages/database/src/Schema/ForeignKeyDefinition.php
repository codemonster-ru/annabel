<?php

namespace Codemonster\Database\Schema;

class ForeignKeyDefinition
{
    public string $column;
    public ?string $references = null;
    public ?string $on = null;
    public ?string $onDelete = null;
    public ?string $onUpdate = null;
    public ?string $name = null;

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    public function references(string $column): static
    {
        $this->references = $column;

        return $this;
    }

    public function on(string $table): static
    {
        $this->on = $table;

        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->onDelete = strtoupper($action);

        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->onUpdate = strtoupper($action);

        return $this;
    }

    // --- Shortcuts ---

    public function cascadeOnDelete(): static
    {
        return $this->onDelete('CASCADE');
    }

    public function restrictOnDelete(): static
    {
        return $this->onDelete('RESTRICT');
    }

    public function nullOnDelete(): static
    {
        return $this->onDelete('SET NULL');
    }

    public function cascadeOnUpdate(): static
    {
        return $this->onUpdate('CASCADE');
    }

    public function restrictOnUpdate(): static
    {
        return $this->onUpdate('RESTRICT');
    }

    public function nullOnUpdate(): static
    {
        return $this->onUpdate('SET NULL');
    }
}
