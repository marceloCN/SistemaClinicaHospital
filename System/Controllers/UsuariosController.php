<?php

declare(strict_types=1);

namespace System\Controllers;

use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;
use Lib\Support\Hash;
use Lib\Support\Session;
use System\Models\Users;
use System\Requests\StoreUsuarioRequest;
use System\Requests\UpdateUsuarioRequest;

/**
 * Administración de usuarios del panel.
 *
 * Rutas en routes/app.php: /join/access/dashboard/Area-of-work/usuarios
 * Requiere sesión iniciada (comprobación en cada acción).
 */
class UsuariosController extends Controller
{
    public function index(Request $request): Response
    {
        if ($response = $this->adminPage()) {
            return $response;
        }

        return $this->render('dashboard.usuarios.index', 'Usuarios', 'Usuarios / Listado', [
            'usuarios' => Users::query()->orderBy('id', 'asc')->get(),
            'currentId' => (int) Session::get('id_usuario', 0),
        ]);
    }

    public function guardar(StoreUsuarioRequest $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        $data = $request->validated();

        if ($this->emailExists($data['email'])) {
            return $this->json(['success' => false, 'message' => 'Ya existe un usuario con ese correo o nombre de acceso.'], 422);
        }

        $data['password'] = Hash::make($data['password']);
        $data['status'] = 1;

        $id = Users::query()->insert($data);

        return $this->json([
            'success' => true,
            'id' => $id,
            'message' => 'Usuario creado correctamente.',
        ]);
    }

    public function actualizar(UpdateUsuarioRequest $request, int $id): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        $user = Users::query()->find($id);

        if ($user === null) {
            return $this->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $data = $request->validated();

        if ($this->emailExists($data['email'], $id)) {
            return $this->json(['success' => false, 'message' => 'Ya existe otro usuario con ese correo o nombre de acceso.'], 422);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        Users::query()->update($data, ['id' => $id]);

        if ($id === (int) Session::get('id_usuario', 0)) {
            Session::set('nombre', $data['name']);
            Session::set('email', $data['email']);
        }

        return $this->json(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
    }

    public function estado(Request $request, int $id): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $user = Users::query()->find($id);

        if ($user === null) {
            return $this->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        $enabled = (string) $request->input('enabled', '1') === '1';

        if (!$enabled && $id === (int) Session::get('id_usuario', 0)) {
            return $this->json(['success' => false, 'message' => 'No puedes desactivar tu propia cuenta.'], 422);
        }

        if (!$enabled && $this->isLastActiveUser($user)) {
            return $this->json(['success' => false, 'message' => 'Debe quedar al menos un usuario activo.'], 422);
        }

        Users::query()->update(['status' => $enabled ? 1 : 0], ['id' => $id]);

        return $this->json(['success' => true, 'enabled' => $enabled]);
    }

    public function eliminar(Request $request, int $id): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $user = Users::query()->find($id);

        if ($user === null) {
            return $this->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        if ($id === (int) Session::get('id_usuario', 0)) {
            return $this->json(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta.'], 422);
        }

        if ($this->isLastActiveUser($user)) {
            return $this->json(['success' => false, 'message' => 'Debe quedar al menos un usuario activo.'], 422);
        }

        Users::query()->delete(['id' => $id]);

        return $this->json(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
    }

    private function emailExists(string $email, ?int $exceptId = null): bool
    {
        $found = Users::query()->where('email', $email)->first();

        if ($found === null) {
            return false;
        }

        return $exceptId === null || (int) $found['id'] !== $exceptId;
    }

    private function isLastActiveUser(array $user): bool
    {
        if ((int) $user['status'] !== 1) {
            return false;
        }

        return Users::query()
            ->where('status', 1)
            ->count() <= 1;
    }

    private function validatePost(Request $request): ?Response
    {
        if (!$request->isPost()) {
            return $this->json(['success' => false, 'message' => 'Método no permitido.'], 405);
        }

        if (!Csrf::validate($request->input('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token inválido.'], 403);
        }

        return null;
    }

    private function adminPage(): ?Response
    {
        if ($this->isAuthenticated()) {
            return null;
        }

        return Response::redirect(route(Session::isLoggedIn() ? 'panel' : 'login') ?? '/');
    }

    private function adminJson(): ?Response
    {
        return $this->isAuthenticated() ? null : $this->json(['success' => false, 'message' => 'No autorizado.'], 403);
    }

    private function render(string $view, string $title, string $breadcrumb, array $data = []): Response
    {
        return $this->view('dashboard.base.dashboard', [
            'head' => View::render('dashboard.base.head', [
                'title' => $title,
                'icon' => asset('/code/dist/img/AdminLTELogo.png'),
            ]),
            'title' => $title,
            'session' => $breadcrumb,
            'content' => View::component($view, $data),
        ]);
    }
}
