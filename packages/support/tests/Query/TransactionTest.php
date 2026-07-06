<?php

namespace Codemonster\Support\Tests\Query;

use Codemonster\Support\Tests\Fakes\FakeConnection;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    protected FakeConnection $connection;
    protected function setUp(): void
    {
        $this->connection = new FakeConnection();
    }

    public function testBeginTransactionCallsPdoBegin(): void
    {
        $this->connection->beginTransaction();

        $this->assertSame(
            ['beginTransaction'],
            $this->connection->queries,
            'beginTransaction() must be written to the queries array',
        );
    }

    public function testCommitCallsPdoCommit(): void
    {
        $this->connection->commit();

        $this->assertSame(
            ['commit'],
            $this->connection->queries,
            'commit() must be written to the queries array',
        );
    }

    public function testRollbackCallsPdoRollback(): void
    {
        $this->connection->rollBack();

        $this->assertSame(
            ['rollBack'],
            $this->connection->queries,
            'rollBack() must be written to the queries array',
        );
    }

    public function testTransactionCommitsOnSuccess(): void
    {
        $result = $this->connection->transaction(function ($db) {
            return 123;
        });

        $this->assertSame(
            ['beginTransaction', 'commit'],
            $this->connection->queries,
            'transaction() must begin a transaction and complete the commit',
        );

        $this->assertSame(123, $result);
    }

    public function testTransactionRollsBackOnException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fail');

        try {
            $this->connection->transaction(function ($db) {
                throw new \RuntimeException('fail');
            });
        } finally {
            $this->assertSame(
                ['beginTransaction', 'rollBack'],
                $this->connection->queries,
                'transaction() should call rollBack on exception',
            );
        }
    }
}
