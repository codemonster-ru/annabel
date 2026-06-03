<?php

namespace Codemonster\Database\Schema;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\Schema\Grammars\SQLiteGrammar;
use PDO;

class GrammarResolver
{
    public function resolve(ConnectionInterface $connection): Grammar
    {
        $driver = $this->getDriverName($connection);

        if ($driver === 'sqlite') {
            return new SQLiteGrammar();
        }

        return new MySqlGrammar();
    }

    protected function getDriverName(ConnectionInterface $connection): ?string
    {
        try {
            return $connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {
            return null;
        }
    }
}
