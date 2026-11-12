<?php

declare(strict_types=1);

namespace Tests\Database;

use Nitro\Database\Connection;
use Nitro\Database\Exception\QueryBuilderException;
use Nitro\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class QueryBuilderTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->connection->statement('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                age INTEGER NOT NULL
            )
        ');
    }

    public function testInsertAndSelect(): void
    {
        $this->table()->insert(['email' => 'a@example.test', 'age' => 30]);
        $this->table()->insert(['email' => 'b@example.test', 'age' => 40]);

        $rows = $this->table()->orderBy('id')->get();

        self::assertCount(2, $rows);
        self::assertSame('a@example.test', $rows[0]['email']);
    }

    public function testWhereWithOperator(): void
    {
        $this->seed();

        $rows = $this->table()->where('age', '>=', 30)->orderBy('age')->get();

        self::assertCount(2, $rows);
        self::assertSame(30, $rows[0]['age']);
    }

    public function testWhereIn(): void
    {
        $this->seed();

        $rows = $this->table()->whereIn('age', [20, 30])->get();

        self::assertCount(2, $rows);
    }

    public function testUpdateRequiresWhereClause(): void
    {
        $this->expectException(QueryBuilderException::class);

        $this->table()->update(['age' => 99]);
    }

    public function testDeleteRequiresWhereClause(): void
    {
        $this->expectException(QueryBuilderException::class);

        $this->table()->delete();
    }

    public function testUpdateAndDelete(): void
    {
        $this->seed();

        $updated = $this->table()->where('email', 'a@example.test')->update(['age' => 35]);
        self::assertSame(1, $updated);

        $deleted = $this->table()->where('age', 40)->delete();
        self::assertSame(1, $deleted);

        self::assertSame(2, $this->table()->count());
    }

    public function testPagination(): void
    {
        $this->seed();

        $page = $this->table()->orderBy('id')->paginate(2, 2);

        self::assertSame(3, $page['total']);
        self::assertSame(2, $page['page']);
        self::assertCount(1, $page['data']);
        self::assertSame(2, $page['last_page']);
    }

    public function testTransactionRollback(): void
    {
        $this->seed();

        try {
            $this->connection->transaction(static function (Connection $c): void {
                $c->insert('users', ['email' => 'tx@example.test', 'age' => 1]);

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
            // expected
        }

        self::assertSame(3, $this->table()->count());
    }

    public function testToSqlQuotesIdentifiers(): void
    {
        $sql = $this->table()->select(['id', 'email'])->where('age', '>', 18)->limit(10)->toSql();

        $this->assertSame(
            'SELECT "id", "email" FROM "users" WHERE "age" > :p1 LIMIT 10',
            $sql
        );
    }

    private function table(): QueryBuilder
    {
        return new QueryBuilder($this->connection, 'users');
    }

    private function seed(): void
    {
        $this->table()->insert(['email' => 'a@example.test', 'age' => 20]);
        $this->table()->insert(['email' => 'b@example.test', 'age' => 30]);
        $this->table()->insert(['email' => 'c@example.test', 'age' => 40]);
    }
}
