<?php

namespace Codemonster\Database\Schema;

abstract class Grammar
{
    /**
     * CREATE TABLE statements.
     *
     * @return list<string>
     */
    abstract public function compileCreate(Blueprint $blueprint): array;

    /**
     * DROP TABLE statements.
     *
     * @return list<string>
     */
    abstract public function compileDrop(string $table): array;

    /**
     * DROP TABLE IF EXISTS statements.
     *
     * @return list<string>
     */
    abstract public function compileDropIfExists(string $table): array;

    /**
     * ALTER TABLE commands (add, drop, modify, rename, index)
     *
     * @return list<string>
     */
    abstract public function compileAlter(Blueprint $blueprint): array;

    /**
     * Rename table.
     *
     * @return list<string>
     */
    abstract public function compileRenameTable(Blueprint $blueprint): array;

    /**
     * Compile a single column (VARCHAR, INT, etc.)
     */
    abstract protected function compileColumn(ColumnDefinition $column): string;

    /**
     * Compile inline foreign key (inside CREATE TABLE)
     */
    abstract protected function compileInlineForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string;

    /**
     * Compile foreign key (ALTER TABLE ADD CONSTRAINT ...)
     */
    abstract protected function compileForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string;
}
