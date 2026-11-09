<?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Session;

/**
 * Middleware de permisos basado en la tabla rol_permiso del portal.
 *
 * Uso en rutas:
 *   ->middleware('Permiso:100')
 *   ->middleware('Permiso:100,101,102')
 *   ->middleware(['PortalAuth', 'Permiso:100,101'])
 *
 * Las claves pueden ser números o cadenas; se comparan contra las claves
 * almacenadas en sesión (portal_permisos). Varios permisos actúan con OR
 * lógico: basta con tener uno de los indicados.
 *
 * El rol ADMIN tiene acceso total.
 */
class Permiso
{
    public function handle(Request $request, string ...$permisos): bool|Response
    {
        if (!Session::get('portal_loged', false)) {
            return $this->noAutenticado($request);
        }

        $rolClave = (string) Session::get('portal_rol_clave', '');

        // El rol ADMIN tiene acceso total
        if ($rolClave === 'ADMIN') {
            return true;
        }

        $requeridos = $this->normalizarPermisos($permisos);

        if ($requeridos === []) {
            return $this->noAutorizado($request);
        }

        $poseidos = (array) Session::get('portal_permisos', []);

        foreach ($requeridos as $requerido) {
            if (in_array($requerido, $poseidos, true)) {
                return true;
            }
        }

        return $this->noAutorizado($request);
    }

    /**
     * @param string[] $permisos
     * @return string[]
     */
    private function normalizarPermisos(array $permisos): array
    {
        $resultado = [];

        foreach ($permisos as $permiso) {
            foreach (explode(',', $permiso) as $parte) {
                $parte = trim($parte);
                if ($parte !== '') {
                    $resultado[] = $parte;
                }
            }
        }

        return array_values(array_unique($resultado));
    }

    private function noAutenticado(Request $request): bool|Response
    {
        if ($request->isAjax()) {
            return Response::json(['success' => false, 'message' => 'No autenticado.'], 401);
        }

        Response::redirect(route('portal_login') ?? '/portal/login')->send();
        return false;
    }

    private function noAutorizado(Request $request): bool|Response
    {
        if ($request->isAjax()) {
            return Response::json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        $content = View::exists('error.error403')
            ? View::render('error.error403')
            : '<h1>403 - Acceso denegado</h1><p>No tienes permiso para acceder a esta sección.</p>';

        return Response::make($content, 403);
    }
}
