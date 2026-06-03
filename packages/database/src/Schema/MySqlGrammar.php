<?php

namespace Codemonster\Database\Schema;

class MySqlGrammar extends Grammar
{
    /**
     * CREATE TABLE
     */
    public function compileCreate(Blueprint $blueprint): array
    {
        $columnsSql = [];

        // Columns
        foreach ($blueprint->columns as $column) {
            $columnsSql[] = $this->compileColumn($column);
        }

        // Inline foreign keys
        foreach ($blueprint->foreignKeys as $fk) {
            $columnsSql[] = $this->compileInlineForeign($blueprint, $fk);
        }

        // Inline indexes
        foreach ($blueprint->indexes as $index) {
            $columnsSql[] = $this->compileInlineIndex($blueprint, $index);
        }

        $sql = sprintf(
            'CREATE TABLE `%s` (%s)',
            $blueprint->table,
            implode(', ', $columnsSql)
        );

        return [$sql];
    }

    /**
     * DROP TABLE
     */
    public function compileDrop(string $table): array
    {
        return ["DROP TABLE `$table`"];
    }

    /**
     * DROP TABLE IF EXISTS
     */
    public function compileDropIfExists(string $table): array
    {
        return ["DROP TABLE IF EXISTS `$table`"];
    }

    /**
     * RENAME TABLE (abstract method implementation)
     */
    public function compileRenameTable(Blueprint $blueprint): array
    {
        if (!$blueprint->renameTable) {
            return [];
        }

        return [
            sprintf(
                'RENAME TABLE `%s` TO `%s`',
                $blueprint->table,
                $blueprint->renameTable
            )
        ];
    }

    /**
     * ALTER TABLE
     */
    public function compileAlter(Blueprint $blueprint): array
    {
        //
        // RENAME TABLE — special case, must be returned as single command
        //
        if ($blueprint->renameTable) {
            return $this->compileRenameTable($blueprint);
        }

        $statements = [];

        //
        // ADD & MODIFY columns
        //
        foreach ($blueprint->columns as $column) {
            if ($column->change === true) {
                // Modify existing column
                $statements[] = sprintf(
                    'ALTER TABLE `%s` MODIFY COLUMN %s',
                    $blueprint->table,
                    $this->compileColumn($column)
                );
            } else {
                // Add new column
                $statements[] = sprintf(
                    'ALTER TABLE `%s` ADD COLUMN %s',
                    $blueprint->table,
                    $this->compileColumn($column)
                );
            }
        }

        //
        // RENAME COLUMN
        //
        foreach ($blueprint->renameColumns as $rename) {
            $statements[] = sprintf(
                'ALTER TABLE `%s` RENAME COLUMN `%s` TO `%s`',
                $blueprint->table,
                $rename['from'],
                $rename['to']
            );
        }

        //
        // DROP COLUMNS
        //
        foreach ($blueprint->dropColumns as $column) {
            $statements[] = sprintf(
                'ALTER TABLE `%s` DROP COLUMN `%s`',
                $blueprint->table,
                $column
            );
        }

        //
        // FOREIGN KEYS (ALTER TABLE ADD CONSTRAINT)
        //
        foreach ($blueprint->foreignKeys as $fk) {
            $statements[] = $this->compileForeign($blueprint, $fk);
        }

        //
        // INDEXES (ALTER TABLE)
        //
        foreach ($blueprint->indexes as $index) {
            $statements[] = $this->compileAlterIndex($blueprint, $index);
        }

        //
        // DROP INDEXES
        //
        foreach ($blueprint->dropIndexes as $name) {
            $statements[] = sprintf(
                'ALTER TABLE `%s` DROP INDEX `%s`',
                $blueprint->table,
                $name
            );
        }

        //
        // DROP PRIMARY KEY
        //
        foreach ($blueprint->dropPrimaryKeys as $name) {
            $statements[] = sprintf(
                'ALTER TABLE `%s` DROP PRIMARY KEY',
                $blueprint->table
            );
        }

        //
        // DROP FOREIGN KEYS
        //
        foreach ($blueprint->dropForeignKeys as $name) {
            $statements[] = sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $blueprint->table,
                $name
            );
        }

        return $statements;
    }

    /**
     * Compile individual column
     */
    protected function compileColumn(ColumnDefinition $column): string
    {
        $name = sprintf('`%s`', $column->name);
        $type = $this->compileType($column);

        $sql = $name . ' ' . $type;

        //
        // UNSIGNED
        //
        if (!empty($column->modifiers['unsigned'])) {
            $sql .= ' UNSIGNED';
        }

        //
        // AUTO_INCREMENT
        //
        if (!empty($column->modifiers['autoIncrement'])) {
            $sql .= ' AUTO_INCREMENT';
        }

        //
        // NULL / NOT NULL
        //
        if (!($column->modifiers['nullable'] ?? false)) {
            $sql .= ' NOT NULL';
        }

        //
        // DEFAULT
        //
        if (array_key_exists('default', $column->modifiers)) {
            $value = $column->modifiers['default'];
            $sql .= ' DEFAULT ' . $this->quoteDefault($value);
        }

        //
        // COMMENT
        //
        if (isset($column->modifiers['comment'])) {
            $comment = addslashes($column->modifiers['comment']);
            $sql .= " COMMENT '$comment'";
        }

        //
        // UNIQUE / PRIMARY KEY (inline for convenience)
        //
        if (!empty($column->modifiers['unique'])) {
            $sql .= ' UNIQUE';
        }

        if (!empty($column->modifiers['primary'])) {
            $sql .= ' PRIMARY KEY';
        }

        return $sql;
    }

    /**
     * Quote default values
     */
    protected function quoteDefault(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? '1' : '0',
            is_string($value) => "'" . addslashes($value) . "'",
            default => (string)$value,
        };
    }

    /**
     * MySQL column type definitions
     */
    protected function compileType(ColumnDefinition $column): string
    {
        return match ($column->type) {
            // Default types
            'id'        => 'INT UNSIGNED AUTO_INCREMENT',
            'string'    => "VARCHAR(" . ($column->options['length'] ?? 255) . ")",

            // Integer types
            'integer'       => 'INT',
            'bigInteger'    => 'BIGINT',
            'smallInteger'  => 'SMALLINT',
            'mediumInteger' => 'MEDIUMINT',
            'tinyInteger'   => 'TINYINT',

            // Decimal / float
            'decimal'   => sprintf(
                'DECIMAL(%d, %d)',
                $column->options['precision'] ?? 8,
                $column->options['scale'] ?? 2
            ),
            'double'    => sprintf(
                'DOUBLE(%d, %d)',
                $column->options['precision'] ?? 8,
                $column->options['scale'] ?? 2
            ),
            'float'     => sprintf(
                'FLOAT(%d, %d)',
                $column->options['precision'] ?? 8,
                $column->options['scale'] ?? 2
            ),

            // Text types
            'text'        => 'TEXT',
            'mediumText'  => 'MEDIUMTEXT',
            'longText'    => 'LONGTEXT',
            'char'        => "CHAR(" . ($column->options['length'] ?? 255) . ")",

            // JSON
            'json'       => 'JSON',

            // Boolean
            'boolean'    => 'TINYINT(1)',

            // Date/Time types
            'timestamp'  => 'TIMESTAMP',
            'datetime'   => 'DATETIME',
            'date'       => 'DATE',
            'time'       => 'TIME',
            'year'       => 'YEAR',

            // UUID
            'uuid'       => 'CHAR(36)',

            default      => strtoupper($column->type),
        };
    }

    /**
     * Inline foreign keys inside CREATE TABLE
     */
    protected function compileInlineForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string
    {
        $name = $fk->name ?: "{$blueprint->table}_{$fk->column}_foreign";

        $sql = sprintf(
            'CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`)',
            $name,
            $fk->column,
            $fk->on,
            $fk->references
        );

        if ($fk->onDelete) {
            $sql .= " ON DELETE {$fk->onDelete}";
        }

        if ($fk->onUpdate) {
            $sql .= " ON UPDATE {$fk->onUpdate}";
        }

        return $sql;
    }

    /**
     * ALTER TABLE ... ADD CONSTRAINT
     */
    protected function compileForeign(Blueprint $blueprint, ForeignKeyDefinition $fk): string
    {
        $name = $fk->name ?: "{$blueprint->table}_{$fk->column}_foreign";

        $sql = sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`)',
            $blueprint->table,
            $name,
            $fk->column,
            $fk->on,
            $fk->references
        );

        if ($fk->onDelete) {
            $sql .= " ON DELETE {$fk->onDelete}";
        }

        if ($fk->onUpdate) {
            $sql .= " ON UPDATE {$fk->onUpdate}";
        }

        return $sql;
    }

    /**
     * Inline indexes inside CREATE TABLE
     */
    protected function compileInlineIndex(Blueprint $blueprint, array $index): string
    {
        $columns = implode('`, `', $index['columns']);
        $name = $index['name'] ?: $this->createIndexName($blueprint, $index);

        return match ($index['type']) {
            'index'   => sprintf('INDEX `%s` (`%s`)', $name, $columns),
            'unique'  => sprintf('UNIQUE `%s` (`%s`)', $name, $columns),
            'primary' => sprintf('PRIMARY KEY (`%s`)', $columns),
        };
    }

    /**
     * ALTER TABLE ... ADD INDEX / UNIQUE / PRIMARY
     */
    protected function compileAlterIndex(Blueprint $blueprint, array $index): string
    {
        $columns = implode('`, `', $index['columns']);
        $name = $index['name'] ?: $this->createIndexName($blueprint, $index);

        return match ($index['type']) {
            'index' => sprintf(
                'ALTER TABLE `%s` ADD INDEX `%s` (`%s`)',
                $blueprint->table,
                $name,
                $columns
            ),
            'unique' => sprintf(
                'ALTER TABLE `%s` ADD UNIQUE `%s` (`%s`)',
                $blueprint->table,
                $name,
                $columns
            ),
            'primary' => sprintf(
                'ALTER TABLE `%s` ADD PRIMARY KEY (`%s`)',
                $blueprint->table,
                $columns
            ),
        };
    }

    /**
     * Create automatic index names
     */
    protected function createIndexName(Blueprint $blueprint, array $index): string
    {
        $cols = implode('_', $index['columns']);

        return "{$blueprint->table}_{$cols}_{$index['type']}";
    }
}
