<?php

declare(strict_types=1);

namespace Lib\Support;

/**
 * Petición no autorizada. El router responde JSON
 * {"success": false, "message": ...} con el status indicado (403 por defecto).
 */
class AuthorizationException extends \Exception
{
    public function __construct(string $message = 'Esta acción no está autorizada.', private int $status = 403)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
