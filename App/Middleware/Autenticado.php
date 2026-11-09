<?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

/**
 * Middleware de autenticación propio del dominio del examen.
 * Valida la sesión contra las tablas persona y cuenta_persona.
 */
class Autenticado
{
    public function handle(Request $request): bool|Response
    {
        $idPersona = (int) Session::get('id_persona', 0);

        if (!Session::isLoggedIn() || $idPersona <= 0) {
            Session::destroy();

            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No autenticado.'], 401);
            }

            Response::redirect(route('login') ?? '/join/access/dashboard')->send();
            return false;
        }

        return true;
    }
}
