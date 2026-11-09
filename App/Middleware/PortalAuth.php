<?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

/**
 * Middleware de autenticación para el portal de pacientes/personal.
 * Usa sesiones con prefijo 'portal_' para no interferir con el panel del System.
 */
class PortalAuth
{
    public function handle(Request $request): bool|Response
    {
        if (!Session::get('portal_loged', false)) {
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No autenticado.'], 401);
            }

            Response::redirect(route('portal_login') ?? '/portal/login')->send();
            return false;
        }

        return true;
    }
}
