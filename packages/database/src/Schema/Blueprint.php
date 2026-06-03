<?php

namespace Codemonster\Database\Schema;

class Blueprint
{
    public string $table;

    /** @var ColumnDefinition[] */
    public array $columns = [];

    /** @var ForeignKeyDefinition[] */
    public array $foreignKeys = [];

    /** @var array[] */
    public array $indexes = [];

    /** @var string[] */
    public array $dropColumns = [];

    /** @var string[] */
    public array $dropIndexes = [];

    /** @var string[] */
    public array $dropForeignKeys = [];

    /** @var string[] */
    public array $dropPrimaryKeys = [];

    /** @var array<int, array{from: string, to: string}> */
    public array $renameColumns = [];

    public ?string $renameTable = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // ----------------------- Columns -----------------------

    public function id(string $name = 'id'): ColumnDefinition
    {
        $column = $this->addColumn('id', $name);

        // Auto-increment columns must be indexed; default to primary key if none defined yet.
        $hasPrimary = array_filter(
            $this->indexes,
            fn(array $index) => ($index['type'] ?? null) === 'primary'
        );

        if (empty($hasPrimary)) {
            $this->primary($name);
        }

        return $column;
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('string', $name, compact('length'));
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn('integer', $name);
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn('boolean', $name);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn('text', $name);
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn('timestamp', $name);
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $name);
    }

    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('smallInteger', $name);
    }

    public function mediumInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('mediumInteger', $name);
    }

    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->addColumn('tinyInteger', $name);
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('decimal', $name, compact('precision', 'scale'));
    }

    public function double(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('double', $name, compact('precision', 'scale'));
    }

    public function float(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn('float', $name, compact('precision', 'scale'));
    }

    public function char(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('char', $name, compact('length'));
    }

    public function mediumText(string $name): ColumnDefinition
    {
        return $this->addColumn('mediumText', $name);
    }

    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn('longText', $name);
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn('json', $name);
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn('date', $name);
    }

    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn('datetime', $name);
    }

    public function time(string $name): ColumnDefinition
    {
        return $this->addColumn('time', $name);
    }

    public function year(string $name): ColumnDefinition
    {
        return $this->addColumn('year', $name);
    }

    public function uuid(string $name): ColumnDefinition
    {
        return $this->addColumn('uuid', $name);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    protected function addColumn(string $type, string $name, array $options = []): ColumnDefinition
    {
        $column = new ColumnDefinition($type, $name, $options);

        $this->columns[] = $column;

        return $column;
    }

    // ----------------------- Indexes -----------------------

    public function index(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = [
            'type' => 'index',
            'columns' => (array) $columns,
            'name' => $name,
        ];
    }

    public function unique(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => (array) $columns,
            'name' => $name,
        ];
    }

    public function primary(string|array $columns, ?string $name = null): void
    {
        $this->indexes[] = [
            'type' => 'primary',
            'columns' => (array) $columns,
            'name' => $name,
        ];
    }

    // ----------------------- Drops -----------------------

    public function dropColumn(string $name): void
    {
        $this->dropColumns[] = $name;
    }

    public function dropIndex(string $name): void
    {
        $this->dropIndexes[] = $name;
    }

    public function dropUnique(string $name): void
    {
        $this->dropIndexes[] = $name;
    }

    public function dropPrimary(string $name = 'PRIMARY'): void
    {
        $this->dropPrimaryKeys[] = $name;
    }

    public function dropForeign(string $name): void
    {
        $this->dropForeignKeys[] = $name;
    }

    // ----------------------- Foreign keys -----------------------

    public function foreign(string $column): ForeignKeyDefinition
    {
        $fk = new ForeignKeyDefinition($column);

        $this->foreignKeys[] = $fk;

        return $fk;
    }

    // ----------------------- Rename -----------------------

    public function renameColumn(string $from, string $to): void
    {
        $this->renameColumns[] = compact('from', 'to');
    }

    public function rename(string $newName): void
    {
        $this->renameTable = $newName;
    }
}
