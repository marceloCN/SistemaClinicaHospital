<?php

declare(strict_types=1);

namespace lib;

use Lib\Core\Database as BaseDatabase;

/**
 * @deprecated Use Lib\Core\Database instead.
 */
class Database
{
    public function connect(): \PDO
    {
        return BaseDatabase::connection();
    }

    public function setConnection(string $host, string $user, string $pwd, string $charset): void
    {
        // No-op: new system uses .env config.
    }
}
