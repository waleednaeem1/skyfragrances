<?php
defined('SKYFR') || exit;

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        (string) config('db.host', 'localhost'),
        (int) config('db.port', 3306),
        (string) config('db.name', '')
    );
    try {
        $pdo = new PDO($dsn, (string) config('db.user', ''), (string) config('db.pass', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ]);
        $pdo->prepare('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci')->execute();
        $pdo->prepare("SET time_zone = '+05:00'")->execute();
        $pdo->prepare("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'")->execute();
        $pdo->prepare('SET autocommit = 1')->execute();
    } catch (PDOException $exception) {
        $pdo = null;
        log_write('error', 'Database connection failed', [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'host' => (string) config('db.host', ''),
            'name' => (string) config('db.name', ''),
        ]);
        throw new RuntimeException('Database connection failed');
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    foreach ($params as $name => $value) {
        if (is_int($name)) {
            throw new InvalidArgumentException('Only named placeholders are allowed');
        }
        $placeholder = ':' . ltrim($name, ':');
        $type = match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_INT,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
        $statement->bindValue($placeholder, is_bool($value) ? (int) $value : $value, $type);
    }
    $statement->execute();
    return $statement;
}

function db_fetch(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_fetch_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_fetch_column(string $sql, array $params = [], int $col = 0): mixed
{
    $value = db_query($sql, $params)->fetchColumn($col);
    return $value === false ? null : $value;
}

function db_fetch_pairs(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll(PDO::FETCH_KEY_PAIR);
}

function db_exists(string $sql, array $params = []): bool
{
    return db_query($sql, $params)->fetchColumn() !== false;
}

function db_last_insert_id(): int
{
    return (int) db()->lastInsertId();
}

function db_identifier(string $name): string
{
    if (!preg_match('/^[a-z_][a-z0-9_]{0,63}$/', $name)) {
        throw new InvalidArgumentException('Invalid SQL identifier');
    }
    return '`' . $name . '`';
}

function db_insert(string $table, array $data): int
{
    if ($data === []) {
        throw new InvalidArgumentException('Nothing to insert');
    }
    $columns = [];
    $placeholders = [];
    $params = [];
    foreach ($data as $column => $value) {
        $columns[] = db_identifier((string) $column);
        $placeholders[] = ':' . $column;
        $params[$column] = $value;
    }
    $sql = 'INSERT INTO ' . db_identifier($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    db_query($sql, $params);
    return db_last_insert_id();
}

function db_where_clause(array $where, array &$params): string
{
    if ($where === []) {
        throw new InvalidArgumentException('A WHERE clause is required');
    }
    $conditions = [];
    foreach ($where as $column => $value) {
        $identifier = db_identifier((string) $column);
        if ($value === null) {
            $conditions[] = $identifier . ' IS NULL';
            continue;
        }
        $conditions[] = $identifier . ' = :w_' . $column;
        $params['w_' . $column] = $value;
    }
    return implode(' AND ', $conditions);
}

function db_update(string $table, array $data, array $where): int
{
    if ($data === []) {
        throw new InvalidArgumentException('Nothing to update');
    }
    $assignments = [];
    $params = [];
    foreach ($data as $column => $value) {
        $assignments[] = db_identifier((string) $column) . ' = :s_' . $column;
        $params['s_' . $column] = $value;
    }
    $sql = 'UPDATE ' . db_identifier($table) . ' SET ' . implode(', ', $assignments) . ' WHERE ' . db_where_clause($where, $params);
    return db_query($sql, $params)->rowCount();
}

function db_delete(string $table, array $where): int
{
    $params = [];
    $sql = 'DELETE FROM ' . db_identifier($table) . ' WHERE ' . db_where_clause($where, $params);
    return db_query($sql, $params)->rowCount();
}

function db_transaction(callable $fn): mixed
{
    static $active = false;
    if ($active) {
        throw new LogicException('Nested transactions are not supported');
    }
    $pdo = db();
    $active = true;
    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $throwable;
    } finally {
        $active = false;
    }
    return $result;
}
