<?php

declare(strict_types=1);

namespace Nitro\Database;

use Nitro\Database\Exception\QueryBuilderException;

/**
 * Fluent SQL query builder producing parameterised statements.
 *
 * Supports select/where/join/order/limit/insert/update/delete with
 * identifier quoting appropriate to the connection driver.
 */
final class QueryBuilder
{
    /** @var array<int, string> */
    private array $columns = ['*'];

    /** @var array<int, array{type: string, column: string, value: mixed, boolean: string, operator: string}> */
    private array $wheres = [];

    /** @var array<int, array{table: string, first: string, second: string, type: string}> */
    private array $joins = [];

    /** @var array<int, array{column: string, direction: string}> */
    private array $orders = [];

    /** @var array<int, array{column: string, direction: string}> */
    private array $groupings = [];

    private ?int $limitValue = null;

    private ?int $offsetValue = null;

    /** @var array<int|string, mixed> */
    private array $bindings = [];

    private int $bindingCounter = 0;

    public function __construct(
        private readonly Connection $connection,
        private readonly string $table
    ) {
    }

    /**
     * @param string|array<int, string> $columns
     */
    public function select(string|array $columns = ['*']): self
    {
        $this->columns = (array) $columns;

        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        [$operator, $value] = $this->normalizeOperator($operator, $value);

        return $this->addWhere('and', $column, $operator, $value);
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        [$operator, $value] = $this->normalizeOperator($operator, $value);

        return $this->addWhere('or', $column, $operator, $value);
    }

    public function whereIn(string $column, array $values): self
    {
        return $this->addWhereIn('and', $column, $values, false);
    }

    public function whereNotIn(string $column, array $values): self
    {
        return $this->addWhereIn('and', $column, $values, true);
    }

    public function whereNull(string $column): self
    {
        return $this->addWhere('and', $column, 'IS', null);
    }

    public function whereNotNull(string $column): self
    {
        return $this->addWhere('and', $column, 'IS NOT', null);
    }

    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->addWhere('and', $column, '>=', $min);

        return $this->addWhere('and', $column, '<=', $max);
    }

    public function whereLike(string $column, string $value): self
    {
        return $this->addWhere('and', $column, 'LIKE', $value);
    }

    public function join(string $table, string $first, string $second): self
    {
        return $this->addJoin('INNER', $table, $first, $second);
    }

    public function leftJoin(string $table, string $first, string $second): self
    {
        return $this->addJoin('LEFT', $table, $first, $second);
    }

    public function rightJoin(string $table, string $first, string $second): self
    {
        return $this->addJoin('RIGHT', $table, $first, $second);
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = ['column' => $column, 'direction' => $direction];

        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->groupings[] = ['column' => $column, 'direction' => ''];
        }

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limitValue = max(0, $limit);

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offsetValue = max(0, $offset);

        return $this;
    }

    public function toSql(): string
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->quoteColumns()), $this->quote($this->table));

        foreach ($this->joins as $join) {
            $sql .= sprintf(
                ' %s JOIN %s ON %s = %s',
                $join['type'],
                $this->quote($join['table']),
                $this->quote($join['first']),
                $this->quote($join['second'])
            );
        }

        if ($this->wheres !== []) {
            $sql .= ' WHERE ' . $this->compileWheres(true);
        }

        if ($this->groupings !== []) {
            $sql .= ' GROUP BY ' . implode(', ', $this->quoteColumns(array_column($this->groupings, 'column')));
        }

        if ($this->orders !== []) {
            $orders = [];

            foreach ($this->orders as $order) {
                $orders[] = sprintf('%s %s', $this->quote($order['column']), $order['direction']);
            }

            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }

        if ($this->limitValue !== null) {
            $sql .= sprintf(' LIMIT %d', $this->limitValue);
        }

        if ($this->offsetValue !== null) {
            $sql .= sprintf(' OFFSET %d', $this->offsetValue);
        }

        return $sql;
    }

    /** @return array<int, mixed> */
    public function getBindings(): array
    {
        return array_values($this->bindings);
    }

    /** @return array<int, array<string, mixed>> */
    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings());
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    public function count(): int
    {
        $clone = clone $this;
        $clone->columns = ['COUNT(*) AS aggregate'];
        $clone->orders = [];
        $clone->limitValue = null;
        $clone->offsetValue = null;

        return (int) ($clone->connection->first($clone->toSql(), $clone->getBindings())['aggregate'] ?? 0);
    }

    public function value(string $column): mixed
    {
        $clone = clone $this;
        $clone->columns = [$column];
        $clone->orders = [];
        $clone->limitValue = 1;

        $row = $clone->connection->first($clone->toSql(), $clone->getBindings());

        return $row === null ? null : reset($row);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insert(array $values): bool
    {
        $columns = array_keys($values);
        $placeholders = [];

        foreach ($columns as $column) {
            $placeholders[] = $this->nextBinding($values[$column]);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quote($this->table),
            implode(', ', $this->quoteColumns($columns)),
            implode(', ', $placeholders)
        );

        return $this->connection->statement($sql, array_values($this->bindings))->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): int
    {
        if ($this->wheres === []) {
            throw new QueryBuilderException('Refusing to run an UPDATE without a WHERE clause.');
        }

        $sets = [];

        foreach ($values as $column => $value) {
            $sets[] = sprintf('%s = %s', $this->quote($column), $this->nextBinding($value));
        }

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->quote($this->table), implode(', ', $sets), $this->compileWheres(true));

        return $this->connection->statement($sql, array_values($this->bindings))->rowCount();
    }

    public function delete(): int
    {
        if ($this->wheres === []) {
            throw new QueryBuilderException('Refusing to run a DELETE without a WHERE clause.');
        }

        $sql = sprintf('DELETE FROM %s WHERE %s', $this->quote($this->table), $this->compileWheres(true));

        return $this->connection->statement($sql, array_values($this->bindings))->rowCount();
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $total = $this->count();
        $rows = (clone $this)
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    private function compileWheres(bool $withoutKeyword = false): string
    {
        $compiled = [];
        $first = true;

        foreach ($this->wheres as $where) {
            $boolean = $first ? '' : strtoupper($where['boolean']) . ' ';

            if ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                $compiled[] = sprintf('%s%s %s (%s)', $boolean, $this->quote($where['column']), $where['operator'], $where['value']);
            } elseif ($where['operator'] === 'IS' || $where['operator'] === 'IS NOT') {
                $compiled[] = sprintf('%s%s %s NULL', $boolean, $this->quote($where['column']), $where['operator']);
            } else {
                $compiled[] = sprintf('%s%s %s %s', $boolean, $this->quote($where['column']), $where['operator'], $where['value']);
            }

            $first = false;
        }

        $clause = implode(' ', $compiled);

        return $withoutKeyword ? $clause : 'WHERE ' . $clause;
    }

    private function addWhere(string $boolean, string $column, string $operator, mixed $value): self
    {
        $placeholder = $this->nextBinding($value);

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $placeholder,
            'boolean' => $boolean,
        ];

        return $this;
    }

    private function addWhereIn(string $boolean, string $column, array $values, bool $negate): self
    {
        $placeholders = [];

        foreach ($values as $value) {
            $placeholders[] = $this->nextBinding($value);
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'operator' => $negate ? 'NOT IN' : 'IN',
            'value' => implode(', ', $placeholders),
            'boolean' => $boolean,
        ];

        return $this;
    }

    private function addJoin(string $type, string $table, string $first, string $second): self
    {
        $this->joins[] = compact('table', 'first', 'second') + ['type' => $type];

        return $this;
    }

    private function nextBinding(mixed $value): string
    {
        $this->bindingCounter++;
        $placeholder = ':p' . $this->bindingCounter;
        $this->bindings[$this->bindingCounter] = $value;

        return $placeholder;
    }

    private function normalizeOperator(mixed $operator, mixed $value): array
    {
        if ($value === null) {
            return ['=', $operator];
        }

        $operator = strtoupper((string) $operator);

        if (! in_array($operator, ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE'], true)) {
            throw new QueryBuilderException(sprintf('Unsupported operator "%s".', $operator));
        }

        return [$operator, $value];
    }

    private function quote(string $identifier): string
    {
        $parts = explode('.', $identifier);

        return implode('.', array_map(
            static fn (string $part): string => $part === '*' ? '*' : '"' . $part . '"',
            $parts
        ));
    }

    /**
     * @param array<int, string>|null $columns
     * @return array<int, string>
     */
    private function quoteColumns(?array $columns = null): array
    {
        return array_map($this->quote(...), $columns ?? $this->columns);
    }
}
