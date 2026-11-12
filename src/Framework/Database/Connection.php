<?php

declare(strict_types=1);

namespace Nitro\Database;

use PDO;
use PDOStatement;

/**
 * Thin PDO wrapper exposing prepared-statement execution and metrics.
 */
final class Connection
{
    private ?PDO $pdo = null;

    private int $queryCount = 0;

    /** @var array<int, string> */
    private array $queryLog = [];

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= $this->connect();
    }

    private function connect(): PDO
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $dsn = $this->buildDsn($driver);

        $pdo = new PDO(
            $dsn,
            (string) ($this->config['username'] ?? ''),
            (string) ($this->config['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]
        );

        if ($driver === 'mysql' && isset($this->config['charset'])) {
            $pdo->prepare('SET NAMES ?')->execute([$this->config['charset']]);
        }

        return $pdo;
    }

    private function buildDsn(string $driver): string
    {
        return match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'] ?? '127.0.0.1',
                $this->config['port'] ?? '3306',
                $this->config['database'] ?? '',
                $this->config['charset'] ?? 'utf8mb4'
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $this->config['host'] ?? '127.0.0.1',
                $this->config['port'] ?? '5432',
                $this->config['database'] ?? ''
            ),
            'sqlite' => sprintf('sqlite:%s', $this->config['database'] ?? ':memory:'),
            default => throw new \InvalidArgumentException(sprintf('Unsupported driver "%s".', $driver)),
        };
    }

    /**
     * Execute a prepared statement and return it.
     *
     * @param array<int|string, mixed> $bindings
     */
    public function statement(string $sql, array $bindings = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);

        foreach ($bindings as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : $key;

            $statement->bindValue(
                $parameter,
                $value,
                is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR))
            );
        }

        $statement->execute();

        $this->queryCount++;
        $this->queryLog[] = $sql;

        return $statement;
    }

    /**
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->statement($sql, $bindings)->fetchAll();
    }

    /**
     * @param array<int|string, mixed> $bindings
     * @return array<string, mixed>|null
     */
    public function first(string $sql, array $bindings = []): ?array
    {
        $row = $this->statement($sql, $bindings)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string, mixed> $bindings
     */
    public function scalar(string $sql, array $bindings = []): mixed
    {
        return $this->statement($sql, $bindings)->fetchColumn();
    }

    /**
     * @param array<string, mixed> $bindings
     */
    public function insert(string $table, array $bindings): bool
    {
        $columns = array_keys($bindings);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return $this->statement($sql, $bindings)->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $bindings
     */
    public function update(string $table, array $values, string $where, array $bindings = []): int
    {
        $sets = [];

        foreach (array_keys($values) as $column) {
            $sets[] = sprintf('%s = :set_%s', $column, $column);
        }

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $sets), $where);

        foreach ($values as $column => $value) {
            $bindings['set_' . $column] = $value;
        }

        return $this->statement($sql, $bindings)->rowCount();
    }

    /**
     * @param array<string, mixed> $bindings
     */
    public function delete(string $table, string $where, array $bindings = []): int
    {
        return $this->statement(sprintf('DELETE FROM %s WHERE %s', $table, $where), $bindings)->rowCount();
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($this);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function beginTransaction(): void
    {
        $this->pdo()->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo()->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo()->inTransaction()) {
            $this->pdo()->rollBack();
        }
    }

    /** @return array<int, string> */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }
}
