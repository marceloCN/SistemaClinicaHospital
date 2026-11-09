<?php

declare(strict_types=1);

namespace System\Controllers;

use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Csrf;
use Lib\Support\Hash;
use Lib\Support\Session;
use System\Models\Users;
use System\Requests\LoginRequest;

class AuthController extends Controller
{
    public function index(Request $request): Response
    {
        if (Session::isLoggedIn()) {
            return $this->redirect('system');
        }

        return $this->view('dashboard.base.login');
    }

    public function verificar_credenciales(LoginRequest $request): Response
    {
        $data = $request->validated();

        $user = Users::query()
            ->where('email', $data['email'])
            ->where('status', 1)
            ->first();

        if (!$user || !$this->passwordMatches($data['password'], $user['password'])) {
            return $this->json(['success' => false, 'message' => 'Credenciales incorrectas o usuario dado de baja.'], 401);
        }

        if (!str_starts_with((string) $user['password'], '$2y$') && !str_starts_with((string) $user['password'], '$argon2')) {
            Users::query()->update(['password' => Hash::make($data['password'])], ['id' => $user['id']]);
            $user['password'] = Hash::make($data['password']);
        }

        Session::regenerate(true);

        unset($user['password']);

        Session::set('loged', true);
        Session::set('user', $user);
        Session::set('id_usuario', (int) $user['id']);
        Session::set('nombre', $user['name']);
        Session::set('email', $user['email']);

        Csrf::regenerate();

        return $this->json([
            'success' => true,
            'redirect' => route('system') ?? '/',
        ]);
    }

    public function logout(Request $request): Response
    {
        if (!$request->isPost()) {
            return Response::make('Método no permitido.', 405);
        }

        if (!Csrf::validate((string) $request->input('_token'))) {
            return Response::make('Token CSRF inválido.', 403);
        }

        Session::destroy();

        return $this->redirect('login');
    }

    private function passwordMatches(string $plain, ?string $stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
            return Hash::check($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }
}
