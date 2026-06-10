<?php

namespace Codemonster\Support\Tests\Fakes;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\DatabaseManager;

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
