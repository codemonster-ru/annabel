<?php

namespace Codemonster\Database\Schema;

use Codemonster\Database\Contracts\ConnectionInterface;

class Schema
{
    protected ConnectionInterface $connection;

    protected Grammar $grammar;

    public function __construct(ConnectionInterface $connection, Grammar $grammar)
    {
        $this->connection = $connection;
        $this->grammar = $grammar;
    }

    public static function forConnection(ConnectionInterface $connection): self
    {
        $resolver = new GrammarResolver();

        return new self($connection, $resolver->resolve($connection));
    }

    /**
     * CREATE TABLE
     */
    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);

        $callback($blueprint);

        $statements = $this->grammar->compileCreate($blueprint);

        $this->runStatements($statements);
    }

    /**
     * DROP TABLE
     */
    public function drop(string $table): void
    {
        $statements = $this->grammar->compileDrop($table);

        $this->runStatements($statements);
    }

    /**
     * DROP TABLE IF EXISTS
     */
    public function dropIfExists(string $table): void
    {
        $statements = $this->grammar->compileDropIfExists($table);

        $this->runStatements($statements);
    }

    /**
     * ALTER TABLE
     */
    public function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);

        $callback($blueprint);

        $statements = $this->grammar->compileAlter($blueprint);

        $this->runStatements($statements);
    }

    /**
     * Execute array of SQL statements.
     */
    /** @param list<string> $sqls */
    protected function runStatements(array $sqls): void
    {
        foreach ($sqls as $sql) {
            $this->connection->statement($sql);
        }
    }
}
