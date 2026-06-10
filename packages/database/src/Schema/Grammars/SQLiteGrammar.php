<?php

namespace Codemonster\Database\Schema\Grammars;

use Codemonster\Database\Schema\Blueprint;
use Codemonster\Database\Schema\ColumnDefinition;
use Codemonster\Database\Schema\ForeignKeyDefinition;
use Codemonster\Database\Schema\Grammar;

class SQLiteGrammar extends Grammar
{
    public function compileCreate(Blueprint $blueprint): array
    {
        $columns = [];

        foreach ($blueprint->columns as $col) {
            $columns[] = $this->compileColumn($col);
        }

        foreach ($blueprint->foreignKeys as $fk) {
            $columns[] = $this->compileInlineForeign($blueprint, $fk);
        }

        $sql = sprintf(
            'CREATE TABLE "%s" (%s)',
            $blueprint->table,
            implode(', ', $columns),
        );

        return [$sql];
    }

    public function compileDrop(string $table): array
    {
        return [sprintf('DROP TABLE "%s"', $table)];
    }

    public function compileDropIfExists(string $table): array
    {
        return [sprintf('DROP TABLE IF EXISTS "%s"', $table)];
    }

    public function compileAlter(Blueprint $blueprint): array
    {
        $statements = [];

        // RENAME TABLE
        if ($blueprint->renameTable !== null) {
            $statements[] = $this->compileRenameTable($blueprint)[0];
        }

        // RENAME COLUMN
        foreach ($blueprint->renameColumns as $rename) {
            $statements[] = sprintf(
                'ALTER TABLE "%s" RENAME COLUMN "%s" TO "%s"',
                $blueprint->table,
                $rename['from'],
                $rename['to'],
            );
        }

        // ADD COLUMN
        foreach ($blueprint->columns as $column) {
            if (!$column->change) {
                $statements[] = sprintf(
                    'ALTER TABLE "%s" ADD COLUMN %s',
                    $blueprint->table,
                    $this->compileColumn($column),
                );
            }
        }

        return $statements;
    }

    public function compileRenameTable(Blueprint $blueprint): array
    {
        return [
            sprintf(
                'ALTER TABLE "%s" RENAME TO "%s"',
                $blueprint->table,
                $blueprint->renameTable,
            ),
        ];
    }

    protected function compileInlineForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string
    {
        $sql = sprintf(
            'FOREIGN KEY ("%s") REFERENCES "%s"("%s")',
            $fk->column,
            $fk->on,
            $fk->references,
        );

        if ($fk->onDelete) {
            $sql .= ' ON DELETE ' . $fk->onDelete;
        }

        if ($fk->onUpdate) {
            $sql .= ' ON UPDATE ' . $fk->onUpdate;
        }

        return $sql;
    }

    protected function compileForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string
    {
        // SQLite doesn't have a proper ALTER TABLE ADD CONSTRAINT FOREIGN KEY,
        // so we're leaving a placeholder/comment.
        return '-- SQLite does not support ADD CONSTRAINT FOREIGN KEY after table creation';
    }

    /**
     * IMPORTANT: The name and signature must match Grammar::compileColumn()
     */
    protected function compileColumn(ColumnDefinition $col): string
    {
        $type = $this->mapType($col);

        $sql = sprintf('"%s" %s', $col->name, $type);

        // NOT NULL / NULL (SQLite allows NULL by default)
        if (($col->modifiers['nullable'] ?? true) === false) {
            $sql .= ' NOT NULL';
        }

        // DEFAULT
        if (array_key_exists('default', $col->modifiers)) {
            $default = $this->quoteDefault($col->modifiers['default']);
            $sql .= ' DEFAULT ' . $default;
        }

        // UNIQUE
        if (!empty($col->modifiers['unique'])) {
            $sql .= ' UNIQUE';
        }

        // PRIMARY KEY
        if (!empty($col->modifiers['primary'])) {
            $sql .= ' PRIMARY KEY';
        }

        // AUTOINCREMENT (SQLite: only on INTEGER PRIMARY KEY)
        if (!empty($col->modifiers['autoIncrement'])) {
            $sql .= ' AUTOINCREMENT';
        }

        return $sql;
    }

    protected function quoteDefault(mixed $value): string
    {
        if (is_string($value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new \InvalidArgumentException('Unsupported column default value.');
    }

    protected function mapType(ColumnDefinition $col): string
    {
        return match ($col->type) {
            'id',
            'integer',
            'bigInteger',
            'mediumInteger',
            'smallInteger',
            'tinyInteger' => 'INTEGER',

            'boolean' => 'INTEGER',

            'decimal',
            'double',
            'float' => 'REAL',

            'string',
            'char',
            'text',
            'mediumText',
            'longText',
            'json',
            'date',
            'datetime',
            'timestamp',
            'time',
            'year',
            'uuid' => 'TEXT',

            default => 'TEXT',
        };
    }
}
