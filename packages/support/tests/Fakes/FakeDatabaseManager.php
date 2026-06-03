<?php

namespace Tests\Fakes;

use Codemonster\Database\DatabaseManager;
use Codemonster\Database\Contracts\ConnectionInterface;

class FakeDatabaseManager extends DatabaseManager
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct([
            'default' => 'fake',
            'connections' => [],
        ]);

        $this->connection = $connection;
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        return $this->connection;
    }
}
