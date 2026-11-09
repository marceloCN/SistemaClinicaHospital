<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Controller;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;

/**
 * Gestor de permisos por rol (matriz de tildes).
 *
 * Muestra los 17 permisos del sistema agrupados por módulo y permite
 * tildar/desactivar qué permisos tiene cada rol (ADMIN, RECEP, MEDICO y
 * PAC). La matriz completa se envía y guarda en una sola transacción.
 */
class PortalPermisosController extends Controller
{
    /**
     * Pantalla del dashboard de permisos.
     */
    public function index(Request $request): Response
    {
        $roles = $this->roles();
        $permisos = $this->permisos();
        $matriz = $this->matriz();
        $conteos = $this->conteos($roles, $matriz);

        $modulos = [];

        foreach ($permisos as $permiso) {
            $modulo = $permiso['modulo'] !== '' && $permiso['modulo'] !== null
                ? (string) $permiso['modulo']
                : 'general';

            $modulos[$modulo][] = $permiso;
        }

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => (string) \Lib\Support\Session::get('portal_nombre', 'Usuario'),
            'active' => 'permisos',
            'content' => View::render('portal.permisos.index', [
                'roles' => $roles,
                'permisos' => $permisos,
                'modulos' => $modulos,
                'matriz' => $matriz,
                'conteos' => $conteos,
                'totalPermisos' => count($permisos),
            ]),
        ]);
    }

    /**
     * Guarda la matriz completa de permisos por rol (POST).
     *
     * Espera `permisos[id_rol][id_permiso] = 1`. Las casillas ausentes se
     * consideran desactivadas.
     */
    public function guardar(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $roles = $this->roles();
        $permisos = $this->permisos();

        if ($roles === [] || $permisos === []) {
            return $this->json([
                'success' => false,
                'message' => 'No hay roles ni permisos configurados en el sistema.',
            ], 422);
        }

        $idRoles = array_map(static fn (array $rol): int => (int) $rol['id'], $roles);
        $idPermisos = array_map(static fn (array $p): int => (int) $p['id'], $permisos);

        $recibido = $request->input('permisos', []);

        if (!is_array($recibido)) {
            return $this->json(['success' => false, 'message' => 'Matriz de permisos no válida.'], 422);
        }

        // Construye la matriz deseada: solo claves válidas del catálogo.
        $deseado = [];

        foreach ($idRoles as $idRol) {
            $deseado[$idRol] = [];

            foreach ($idPermisos as $idPermiso) {
                $valor = $recibido[$idRol][$idPermiso] ?? $recibido[(string) $idRol][(string) $idPermiso] ?? null;
                $deseado[$idRol][$idPermiso] = $valor === '1'
                    || $valor === 1
                    || $valor === true
                    || $valor === 'true';
            }
        }

        $actual = $this->matriz();

        $antes = $this->conteos($roles, $actual);

        $sinCambios = $antes === $this->conteos($roles, $deseado)
            && $actual == $deseado;

        if ($sinCambios) {
            return $this->json([
                'success' => true,
                'message' => 'No hubo cambios en los permisos.',
                'sinCambios' => true,
            ]);
        }

        try {
            Database::transaction(function () use ($idRoles, $deseado): void {
                $placeholders = implode(',', array_fill(0, count($idRoles), '?'));

                Database::raw(
                    "DELETE FROM rol_permiso WHERE id_rol IN ({$placeholders})",
                    $idRoles
                );

                $filas = [];

                foreach ($deseado as $idRol => $permisosRol) {
                    foreach ($permisosRol as $idPermiso => $activo) {
                        if ($activo) {
                            $filas[] = ['id_rol' => (int) $idRol, 'id_permiso' => (int) $idPermiso];
                        }
                    }
                }

                if ($filas === []) {
                    return;
                }

                $sql = 'INSERT INTO rol_permiso (id_rol, id_permiso, estado) VALUES ';

                foreach ($filas as $i => $fila) {
                    $sql .= $i > 0 ? ', ' : '';
                    $sql .= '(:' . 'r' . $i . ', :' . 'p' . $i . ', TRUE)';
                }

                $params = [];

                foreach ($filas as $i => $fila) {
                    $params['r' . $i] = $fila['id_rol'];
                    $params['p' . $i] = $fila['id_permiso'];
                }

                Database::raw($sql, $params);
            });
        } catch (\Throwable $e) {
            error_log('Error al guardar permisos: ' . $e->getMessage());

            $mensaje = 'No se pudieron guardar los permisos. Intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }

        $despues = $this->conteos($roles, $deseado);

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'rol_permiso',
            'UPDATE',
            null,
            ['conteo_por_rol' => $antes],
            ['conteo_por_rol' => $despues],
            'Actualización de la matriz de permisos por rol'
        );

        $totalNuevo = array_sum($despues);
        $totalAntes = array_sum($antes);

        return $this->json([
            'success' => true,
            'message' => sprintf(
                'Permisos guardados correctamente (%d asignaciones activas, %+d vs. antes).',
                $totalNuevo,
                $totalNuevo - $totalAntes
            ),
            'conteos' => $despues,
        ]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Valida el token CSRF para peticiones POST (body o header).
     */
    private function csrfValido(Request $request): bool
    {
        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN', ''));

        return Csrf::validate($token);
    }

    /**
     * @return list<array{id: int, clave: string, nombre: string, descripcion: string}>
     */
    private function roles(): array
    {
        $filas = Database::raw(
            'SELECT id, clave, nombre, COALESCE(descripcion, \'\') AS descripcion
               FROM rol
              WHERE estado = TRUE
              ORDER BY id'
        );

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'clave' => (string) $fila['clave'],
            'nombre' => (string) $fila['nombre'],
            'descripcion' => (string) $fila['descripcion'],
        ], $filas);
    }

    /**
     * @return list<array{id: int, clave: string, nombre: string, descripcion: string, modulo: string}>
     */
    private function permisos(): array
    {
        $filas = Database::raw(
            'SELECT id, clave, nombre, COALESCE(descripcion, \'\') AS descripcion,
                    COALESCE(modulo, \'general\') AS modulo
               FROM permiso
              ORDER BY modulo, nombre, id'
        );

        return array_map(static fn (array $fila): array => [
            'id' => (int) $fila['id'],
            'clave' => (string) $fila['clave'],
            'nombre' => (string) $fila['nombre'],
            'descripcion' => (string) $fila['descripcion'],
            'modulo' => (string) $fila['modulo'],
        ], $filas);
    }

    /**
     * Matriz actual: [id_rol][id_permiso] => bool.
     *
     * @return array<int, array<int, bool>>
     */
    private function matriz(): array
    {
        $filas = Database::raw(
            'SELECT id_rol, id_permiso, estado FROM rol_permiso'
        );

        $matriz = [];

        foreach ($filas as $fila) {
            $matriz[(int) $fila['id_rol']][(int) $fila['id_permiso']] = (bool) $fila['estado'];
        }

        return $matriz;
    }

    /**
     * Asignaciones activas por rol: [id_rol] => cantidad.
     *
     * @param list<array{id: int}> $roles
     * @param array<int, array<int, bool>> $matriz
     * @return array<int, int>
     */
    private function conteos(array $roles, array $matriz): array
    {
        $conteos = [];

        foreach ($roles as $rol) {
            $idRol = (int) $rol['id'];
            $conteos[$idRol] = count(array_filter(
                $matriz[$idRol] ?? [],
                static fn (bool $estado): bool => $estado
            ));
        }

        return $conteos;
    }
}
