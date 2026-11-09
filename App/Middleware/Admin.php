<?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

/**
 * Middleware de rol ADMIN para el portal de pacientes/personal.
 *
 * Uso en rutas:
 *   ->middleware('Admin')
 *   ->middleware(['PortalAuth', 'Admin'])
 *
 * Requiere sesión activa del portal y que el rol del usuario sea ADMIN.
 */
class Admin
{
    public function handle(Request $request): bool|Response
    {
        if (!Session::get('portal_loged', false)) {
            return $this->noAutenticado($request);
        }

        $rolClave = (string) Session::get('portal_rol_clave', '');

        if ($rolClave !== 'ADMIN') {
            return $this->noAutorizado($request);
        }

        return true;
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

        $nombre = e((string) Session::get('portal_nombre', 'Usuario'));
        $rolClave = (string) Session::get('portal_rol_clave', '');
        $dashboardUrl = route('portal_dashboard') ?? '/portal/dashboard';
        $loginUrl = route('portal_login') ?? '/portal/login';

        $botonPrincipal = $rolClave !== ''
            ? '<a href="' . e($dashboardUrl) . '" class="btn btn-primary"><i class="fas fa-home"></i> Ir a mi dashboard</a>'
            : '<a href="' . e($loginUrl) . '" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</a>';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso denegado · Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f7f9f6 0%, #e6f5f2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .denied-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 18px 45px rgba(11, 42, 56, 0.1);
            max-width: 520px;
            width: 100%;
            padding: 2.5rem;
            text-align: center;
            animation: fadeInUp 0.5s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .denied-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(192, 57, 43, 0.1);
            color: #c0392b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.25rem;
        }
        .denied-card h1 {
            color: #0b2a38;
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 0 0.75rem;
        }
        .denied-card p {
            color: #5d7078;
            font-size: 1rem;
            line-height: 1.6;
            margin: 0 0 1.5rem;
        }
        .denied-user {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #f4f7f6;
            color: #44545c;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .denied-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.75rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary {
            background: #0f8b84;
            color: #fff;
            border: 1px solid #0f8b84;
        }
        .btn-primary:hover {
            background: #0a6b67;
            box-shadow: 0 6px 18px rgba(15, 139, 132, 0.25);
        }
        .btn-outline {
            background: transparent;
            color: #0f8b84;
            border: 1px solid #0f8b84;
        }
        .btn-outline:hover {
            background: rgba(15, 139, 132, 0.06);
        }
        @media (max-width: 480px) {
            .denied-card { padding: 1.75rem; }
            .denied-card h1 { font-size: 1.25rem; }
            .denied-actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="denied-card">
        <div class="denied-icon"><i class="fas fa-lock"></i></div>
        <h1>Acceso denegado</h1>
        <p>Esta sección está reservada para usuarios con rol <strong>Administrador</strong>. Si crees que esto es un error, contacta al administrador del sistema.</p>
        <div class="denied-user"><i class="fas fa-user-circle"></i> {$nombre}</div>
        <div class="denied-actions">
            {$botonPrincipal}
            <a href="javascript:history.back();" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver atrás</a>
        </div>
    </div>
</body>
</html>
HTML;

        return Response::make($html, 403);
    }
}
