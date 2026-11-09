<?php

declare(strict_types=1);

namespace System\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;
use System\Models\Users;

class Auth
{
    public function handle(Request $request): bool|Response
    {
        $id = (int) Session::get('id_usuario', 0);

        // No basta con la marca de sesión: el usuario pudo ser eliminado o
        // desactivado después de iniciar sesión. Se revalida en cada petición.
        $user = $id > 0
            ? Users::query()->where('id', $id)->where('status', 1)->first()
            : null;

        if (!Session::isLoggedIn() || $user === null) {
            Session::destroy();

            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No autenticado.'], 401);
            }

            Response::redirect(route('login') ?? '/join/access/dashboard')->send();
            return false;
        }

        unset($user['password']);

        Session::set('user', $user);
        Session::set('nombre', $user['name']);
        Session::set('email', $user['email']);

        return true;
    }
}
