<?php

declare(strict_types=1);

namespace Lib\Core;

use PDO;

class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $casts = [];
    protected bool $timestamps = true;

    private array $wheres = [];
    private array $joins = [];
    private array $selects = [];
    private array $groups = [];
    private ?array $orderBy = null;
    private ?int $limit = null;
    private ?int $offset = null;
    private array $with = [];

    private const OPERATORS = [
        '=', '!=', '<>', '<', '>', '<=', '>=', '<=>',
        'like', 'not like', 'ilike', 'regexp',
    ];

    public static function query(): static
    {
        return new static();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    // ---------------------------------------------------------------------
    // Selección, joins y agrupación
    // ---------------------------------------------------------------------

    public function select(string ...$columns): self
    {
        $this->selects = array_merge($this->selects, $columns);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function groupBy(string ...$columns): self
    {
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    // ---------------------------------------------------------------------
    // Condiciones
    // ---------------------------------------------------------------------

    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'boolean' => 'and',
            'column' => $column,
            'operator' => self::normalizeOperator($operator),
            'value' => $value,
        ];
        return $this;
    }

    public function orWhere(string $column, mixed $value, string $operator = '='): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'boolean' => 'or',
            'column' => $column,
            'operator' => self::normalizeOperator($operator),
            'value' => $value,
        ];
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'boolean' => 'and',
            'column' => $column,
            'values' => array_values($values),
        ];
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = ['type' => 'null', 'boolean' => 'and', 'column' => $column];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = ['type' => 'notnull', 'boolean' => 'and', 'column' => $column];
        return $this;
    }

    public function whereRaw(string $sql, array $params = []): self
    {
        $this->wheres[] = ['type' => 'raw', 'boolean' => 'and', 'sql' => $sql, 'params' => $params];
        return $this;
    }

    // ---------------------------------------------------------------------
    // Orden y paginación manual
    // ---------------------------------------------------------------------

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy = [
            'column' => $column,
            'direction' => self::normalizeDirection($direction),
        ];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // ---------------------------------------------------------------------
    // Lectura
    // ---------------------------------------------------------------------

    public function find(int|string $id): ?array
    {
        return $this->where($this->primaryKey, $id)->first();
    }

    public function first(): ?array
    {
        $this->limit = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function get(): array
    {
        $sql = 'SELECT ' . $this->buildSelect() . ' FROM ' . $this->wrap($this->table);
        $sql .= $this->buildJoins();

        $params = [];
        $sql .= $this->buildWhere($params);

        if ($this->groups !== []) {
            $sql .= ' GROUP BY ' . implode(', ', array_map(
                fn (string $column): string => $this->wrap($column),
                $this->groups
            ));
        }

        if ($this->orderBy !== null) {
            $sql .= ' ORDER BY ' . $this->wrap($this->orderBy['column'])
                . ' ' . $this->orderBy['direction'];
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($this->with !== [] && $rows !== []) {
            $rows = $this->loadRelations($rows);
        }

        $this->resetQuery();

        return $rows;
    }

    public function pluck(string $column): array
    {
        return array_column($this->get(), $column);
    }

    /**
     * COUNT compatible con consultas agrupadas: un GROUP BY no puede convivir
     * con un COUNT(*) agregado, así que se cuenta el resultado agrupado.
     */
    private function buildCountSql(array &$params): string
    {
        $from = $this->wrap($this->table)
            . $this->buildJoins()
            . $this->buildWhere($params);

        if ($this->groups === []) {
            return 'SELECT COUNT(*) as total FROM ' . $from;
        }

        $groups = implode(', ', array_map(
            fn (string $column): string => $this->wrap($column),
            $this->groups
        ));

        return 'SELECT COUNT(*) as total FROM (SELECT 1 FROM ' . $from
            . ' GROUP BY ' . $groups . ') AS grouped';
    }

    public function count(): int
    {
        $params = [];
        $sql = $this->buildCountSql($params);

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->resetQuery();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Pagina la consulta actual. Devuelve un objeto con los mismos datos
     * que devolvería una página de Laravel.
     */
    public function paginate(int $perPage = 15, ?int $page = null): object
    {
        $perPage = max(1, $perPage);
        $page = max(1, $page ?? (int) ($_GET['page'] ?? 1));

        $params = [];
        $sql = $this->buildCountSql($params);

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        $items = $this->get();

        return new class ($items, $total, $perPage, $page) {
            public function __construct(
                private array $items,
                private int $total,
                private int $perPage,
                private int $page
            ) {
            }

            public function items(): array
            {
                return $this->items;
            }

            public function total(): int
            {
                return $this->total;
            }

            public function count(): int
            {
                return count($this->items);
            }

            public function perPage(): int
            {
                return $this->perPage;
            }

            public function currentPage(): int
            {
                return $this->page;
            }

            public function lastPage(): int
            {
                return max(1, (int) ceil($this->total / $this->perPage));
            }

            public function hasMorePages(): bool
            {
                return $this->page < $this->lastPage();
            }

            public function toArray(): array
            {
                return [
                    'data' => $this->items,
                    'total' => $this->total,
                    'per_page' => $this->perPage,
                    'current_page' => $this->page,
                    'last_page' => $this->lastPage(),
                ];
            }
        };
    }

    // ---------------------------------------------------------------------
    // Escritura
    // ---------------------------------------------------------------------

    public function insert(array $data): int|string
    {
        $data = $this->filterFillable($data);
        $data = $this->applyCastsForSave($data);
        $data = $this->addTimestamps($data, true);

        if ($data === []) {
            throw new \InvalidArgumentException('Insert requires data.');
        }

        $columns = array_keys($data);
        $wrapped = implode(', ', array_map(
            fn (string $column): string => $this->wrap($column),
            $columns
        ));
        $placeholders = ':' . implode(', :', $columns);

        $sql = 'INSERT INTO ' . $this->wrap($this->table)
            . " ({$wrapped}) VALUES ({$placeholders})";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);

        $id = Database::connection()->lastInsertId();

        return ctype_digit($id) ? (int) $id : $id;
    }

    public function update(array $data, array $conditions = []): int
    {
        $data = $this->filterFillable($data);
        $data = $this->applyCastsForSave($data);
        $data = $this->addTimestamps($data, false);

        if ($data === []) {
            return 0;
        }

        $params = [];
        $sets = [];

        foreach ($data as $key => $value) {
            $sets[] = $this->wrap($key) . " = :set_{$key}";
            $params["set_{$key}"] = $value;
        }

        if ($conditions !== []) {
            $whereSql = $this->buildConditions($conditions, $params);
        } elseif ($this->wheres !== []) {
            $whereSql = $this->buildWhere($params);
        } else {
            throw new \InvalidArgumentException('Update requires conditions.');
        }

        $sql = 'UPDATE ' . $this->wrap($this->table)
            . ' SET ' . implode(', ', $sets)
            . $whereSql;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->resetQuery();

        return $stmt->rowCount();
    }

    public function delete(array $conditions = []): int
    {
        $params = [];

        if ($conditions !== []) {
            $whereSql = $this->buildConditions($conditions, $params);
        } elseif ($this->wheres !== []) {
            $whereSql = $this->buildWhere($params);
        } else {
            throw new \InvalidArgumentException('Delete requires conditions.');
        }

        $sql = 'DELETE FROM ' . $this->wrap($this->table) . $whereSql;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $this->resetQuery();

        return $stmt->rowCount();
    }

    public function raw(string $sql, array $params = []): mixed
    {
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|WITH)\b/i', $sql)
            ? $stmt->fetchAll(PDO::FETCH_ASSOC)
            : $stmt->rowCount();
    }

    // ---------------------------------------------------------------------
    // Relaciones (descriptores, Opción B)
    // ---------------------------------------------------------------------

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): array
    {
        $instance = new $related();

        return [
            'type' => 'belongsTo',
            'model' => $related,
            'foreignKey' => $foreignKey ?? self::guessForeignKey($related),
            'ownerKey' => $ownerKey ?? $instance->primaryKey,
        ];
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        return [
            'type' => 'hasMany',
            'model' => $related,
            'foreignKey' => $foreignKey ?? self::guessForeignKey($related),
            'localKey' => $localKey ?? $this->primaryKey,
        ];
    }

    public function with(string ...$relations): self
    {
        foreach ($relations as $relation) {
            $this->with[] = $relation;
        }

        return $this;
    }

    // ---------------------------------------------------------------------
    // Construcción de SQL
    // ---------------------------------------------------------------------

    private function buildSelect(): string
    {
        if ($this->selects === []) {
            return '*';
        }

        return implode(', ', array_map(
            fn (string $column): string => $column === '*'
                ? '*'
                : $this->wrap($column),
            $this->selects
        ));
    }

    private function buildJoins(): string
    {
        $sql = '';

        foreach ($this->joins as $join) {
            $type = in_array(strtoupper($join['type']), ['INNER', 'LEFT', 'RIGHT'], true)
                ? strtoupper($join['type'])
                : 'INNER';

            $sql .= ' ' . $type . ' JOIN ' . $this->wrap($join['table'])
                . ' ON ' . $this->wrap($join['first'])
                . ' ' . self::normalizeOperator($join['operator'])
                . ' ' . $this->wrap($join['second']);
        }

        return $sql;
    }

    private function buildWhere(array &$params): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $parts = [];

        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0
                ? ''
                : ($where['boolean'] === 'or' ? ' OR ' : ' AND ');

            $parts[] = $prefix . $this->compileWhere($where, $index, $params);
        }

        return ' WHERE ' . implode('', $parts);
    }

    private function compileWhere(array $where, int $index, array &$params): string
    {
        switch ($where['type']) {
            case 'in':
                if ($where['values'] === []) {
                    return '0 = 1';
                }

                $placeholders = [];

                foreach ($where['values'] as $key => $value) {
                    $placeholder = "w{$index}_{$key}";
                    $params[$placeholder] = $value;
                    $placeholders[] = ':' . $placeholder;
                }

                return $this->wrap($where['column'])
                    . ' IN (' . implode(', ', $placeholders) . ')';

            case 'null':
                return $this->wrap($where['column']) . ' IS NULL';

            case 'notnull':
                return $this->wrap($where['column']) . ' IS NOT NULL';

            case 'raw':
                foreach ($where['params'] as $key => $value) {
                    $params[$key] = $value;
                }

                return '(' . $where['sql'] . ')';

            default:
                // where('col', null) debe compilar a IS NULL (no a "= NULL",
                // que nunca coincide). Igual para la negación.
                if ($where['value'] === null) {
                    if (in_array($where['operator'], ['!=', '<>'], true)) {
                        return $this->wrap($where['column']) . ' IS NOT NULL';
                    }

                    return $this->wrap($where['column']) . ' IS NULL';
                }

                $placeholder = "w{$index}";
                $params[$placeholder] = $where['value'];

                return $this->wrap($where['column'])
                    . ' ' . $where['operator']
                    . ' :' . $placeholder;
        }
    }

    /**
     * Condiciones explícitas tipo ['id' => 1] usadas por update()/delete().
     */
    private function buildConditions(array $conditions, array &$params): string
    {
        $clauses = [];

        foreach ($conditions as $key => $value) {
            $placeholder = 'c_' . $key;
            $params[$placeholder] = $value;
            $clauses[] = $this->wrap($key) . ' = :' . $placeholder;
        }

        return ' WHERE ' . implode(' AND ', $clauses);
    }

    private function loadRelations(array $rows): array
    {
        foreach ($this->with as $name) {
            if (!method_exists($this, $name)) {
                continue;
            }

            $descriptor = $this->{$name}();

            if (!is_array($descriptor) || !isset($descriptor['type'])) {
                continue;
            }

            if ($descriptor['type'] === 'belongsTo') {
                $rows = $this->loadBelongsTo($rows, $name, $descriptor);
            } elseif ($descriptor['type'] === 'hasMany') {
                $rows = $this->loadHasMany($rows, $name, $descriptor);
            }
        }

        return $rows;
    }

    private function loadBelongsTo(array $rows, string $name, array $descriptor): array
    {
        $related = new $descriptor['model']();

        $keys = array_values(array_unique(array_filter(
            array_column($rows, $descriptor['foreignKey']),
            static fn (mixed $value): bool => $value !== null
        )));

        $map = [];

        if ($keys !== []) {
            foreach ($related->whereIn($descriptor['ownerKey'], $keys)->get() as $row) {
                $map[$row[$descriptor['ownerKey']]] = $row;
            }
        }

        foreach ($rows as &$row) {
            $row[$name] = $map[$row[$descriptor['foreignKey']] ?? ''] ?? null;
        }
        unset($row);

        return $rows;
    }

    private function loadHasMany(array $rows, string $name, array $descriptor): array
    {
        $related = new $descriptor['model']();

        $keys = array_values(array_unique(array_filter(
            array_column($rows, $descriptor['localKey']),
            static fn (mixed $value): bool => $value !== null
        )));

        $grouped = [];

        if ($keys !== []) {
            foreach ($related->whereIn($descriptor['foreignKey'], $keys)->get() as $row) {
                $grouped[$row[$descriptor['foreignKey']]][] = $row;
            }
        }

        foreach ($rows as &$row) {
            $row[$name] = $grouped[$row[$descriptor['localKey']] ?? ''] ?? [];
        }
        unset($row);

        return $rows;
    }

    private static function guessForeignKey(string $class): string
    {
        $base = substr($class, (int) strrpos($class, '\\') + 1);
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $base));

        if (str_ends_with($snake, 's')) {
            $snake = substr($snake, 0, -1);
        }

        return $snake . '_id';
    }

    // ---------------------------------------------------------------------
    // Identificadores y valores
    // ---------------------------------------------------------------------

    /**
     * Escapa un identificador simple o con tabla ("users.id").
     * Lanza excepción si no es un identificador válido (evita inyección).
     */
    private function wrap(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        if ($identifier === '') {
            throw new \InvalidArgumentException('Empty identifier.');
        }

        $parts = explode('.', $identifier);

        foreach ($parts as $part) {
            if ($part === '*') {
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new \InvalidArgumentException("Invalid identifier [{$identifier}].");
            }
        }

        return implode('.', array_map(
            static fn (string $part): string => $part === '*'
                ? '*'
                : Database::quoteIdentifier($part),
            $parts
        ));
    }

    private static function normalizeOperator(string $operator): string
    {
        $operator = strtolower(trim($operator));

        if (!in_array($operator, self::OPERATORS, true)) {
            throw new \InvalidArgumentException("Unsupported operator [{$operator}].");
        }

        return strtoupper($operator);
    }

    private static function normalizeDirection(string $direction): string
    {
        $direction = strtoupper(trim($direction));

        return in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'ASC';
    }

    // ---------------------------------------------------------------------
    // Fillable, casts y timestamps
    // ---------------------------------------------------------------------

    protected function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function applyCastsForSave(array $data): array
    {
        foreach ($this->casts as $column => $type) {
            if (!array_key_exists($column, $data)) {
                continue;
            }

            $value = $data[$column];

            $data[$column] = match ($type) {
                'int', 'integer' => (int) $value,
                'bool', 'boolean' => (int) (bool) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                'json' => is_string($value) ? $value : json_encode($value),
                default => $value,
            };
        }

        return $data;
    }

    protected function addTimestamps(array $data, bool $creating): array
    {
        if (!$this->timestamps) {
            unset($data['created_at'], $data['updated_at']);
            return $data;
        }

        $now = date('Y-m-d H:i:s');

        if ($creating && !isset($data['created_at'])) {
            $data['created_at'] = $now;
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        return $data;
    }

    private function resetQuery(): void
    {
        $this->wheres = [];
        $this->joins = [];
        $this->selects = [];
        $this->groups = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->with = [];
    }
}
