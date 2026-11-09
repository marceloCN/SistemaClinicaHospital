<?php

declare(strict_types=1);

namespace Lib\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function driver(): string
    {
        $name = self::$config['default'] ?? 'mysql';

        return self::$config['connections'][$name]['driver'] ?? $name;
    }

    public static function isPgsql(): bool
    {
        return self::driver() === 'pgsql';
    }

    public static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    public static function quoteIdentifier(string $identifier): string
    {
        if (self::isPgsql() || self::isSqlite()) {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = self::$config['connections'][self::$config['default']] ?? null;

            if (!$config) {
                throw new \RuntimeException('No database connection configured.');
            }

            $driver = $config['driver'] ?? 'mysql';

            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . $config['database'];
            } else {
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s',
                    $driver,
                    $config['host'],
                    $config['port'],
                    $config['database']
                );

                // El parametro charset solo es valido en el DSN de MySQL/PDO_MySQL.
                if ($driver === 'mysql') {
                    $dsn .= ';charset=' . ($config['charset'] ?? 'utf8mb4');
                }
            }
            try {
                self::$connection = new PDO(
                    $dsn,
                    $config['username'] ?? null,
                    $config['password'] ?? null,
                    $config['options'] ?? []
                );
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new \RuntimeException('Database connection failed. Please try again later.');
            }
        }

        return self::$connection;
    }

    public static function reconnect(): PDO
    {
        self::$connection = null;
        return self::connection();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public static function raw(string $sql, array $params = []): mixed
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|WITH)\b/i', $sql)) {
            return $stmt->fetchAll();
        }

        return $stmt->rowCount();
    }

    public static function call(string $routine, array $params = []): array
    {
        $quoted = implode('.', array_map(
            static fn (string $part): string => self::quoteIdentifier($part),
            explode('.', $routine)
        ));

        $placeholders = $params === [] ? '' : implode(', ', array_fill(0, count($params), '?'));

        $sql = self::isPgsql()
            ? "SELECT * FROM {$quoted}({$placeholders})"
            : "CALL {$quoted}({$placeholders})";

        $stmt = self::connection()->prepare($sql);
        $stmt->execute(array_values($params));

        return $stmt->fetchAll();
    }
}
