<?php

declare(strict_types=1);

namespace App\Controllers;
use App\Models\RolPermiso;
use App\Models\Permiso;
use Lib\Core\Controller as BaseController;
use Lib\Core\Database;

use Lib\Support\Session;

/**
 * Controlador base de la aplicación.
 *
 * Extiende el controlador del framework y expone métodos comunes para
 * registrar bitácoras de accesos y acciones, reutilizables desde cualquier
 * controlador de App\Controllers\.
 */
abstract class Controller extends BaseController
{
    /**
     * Registra un acceso en bitacora_accesos.
     */
    protected function registrarBitacoraAcceso(
        ?int $idPersona,
        ?string $usuarioIntentado,
        string $estado,
        string $detalle
    ): void {
        try {
            Database::call('sp_bitacora_acceso_insertar', [
                $idPersona,
                $usuarioIntentado,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $estado,
                $detalle,
            ]);
        } catch (\Throwable $e) {
            error_log('Error en registrarBitacoraAcceso: ' . $e->getMessage());
        }
    }

    /**
     * Registra una acción en bitacora_acciones.
     */
    protected function registrarBitacoraAccion(
        ?int $idPersona,
        string $tablaAfectada,
        string $accion,
        ?int $registroId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?string $detalle = null
    ): void {
        try {
            Database::call('sp_bitacora_accion_insertar', [
                $idPersona,
                $tablaAfectada,
                $accion,
                $registroId,
                $datosAnteriores === null ? null : json_encode($datosAnteriores),
                $datosNuevos === null ? null : json_encode($datosNuevos),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $detalle,
            ]);
        } catch (\Throwable $e) {
            error_log('Error en registrarBitacoraAccion: ' . $e->getMessage());
        }
    }

    /**
     * Gestiona intentos fallidos, bloqueo y bitácora de accesos del portal.
     *
     * @return int Número actual de intentos fallidos.
     */
    protected function portalRegistrarAcceso(
        ?int $idCuentaPersona,
        ?int $idPersona,
        ?string $usuarioIntentado,
        string $estado,
        string $detalle,
        int $intentosMaximos = 3,
        int $minutosBloqueo = 30
    ): int {
        try {
            $resultado = Database::call('sp_portal_registrar_acceso', [
                $idCuentaPersona,
                $idPersona,
                $usuarioIntentado,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $estado,
                $detalle,
                $intentosMaximos,
                $minutosBloqueo,
            ]);

            if (!empty($resultado) && is_array($resultado[0])) {
                return (int) array_values($resultado[0])[0];
            }
        } catch (\Throwable $e) {
            error_log('Error en portalRegistrarAcceso: ' . $e->getMessage());
        }

        return 0;
    }

    //pendiente revisar
    protected function cargarPermisos(int $idRol): array
    {
        $asignaciones = RolPermiso::query()
            ->where('id_rol', $idRol)
            ->where('estado', true)
            ->get();

        if ($asignaciones === []) {
            return [];
        }

        $idsPermiso = array_column($asignaciones, 'id_permiso');
        $permisos = Permiso::query()->whereIn('id', $idsPermiso)->get();

        // Se incluyen claves e IDs (como string) para que el middleware
        // Permiso acepte tanto 'pacientes.ver' como '120'.
        $claves = array_column($permisos, 'clave');
        $ids = array_map('strval', $idsPermiso);

        return array_values(array_unique(array_merge($claves, $ids)));
    }

    protected function fechaValida(string $fecha): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $fecha);

        return $date !== false && $date->format('Y-m-d') === $fecha;
    }

    /**
     * Cálculo de los números de página que se muestran en un paginador.
     *
     * Genera una secuencia del tipo 1 2 3 ... 54 55: las primeras páginas, un
     * salto, la ventana alrededor de la página actual, otro salto y las últimas
     * páginas (para poder ir directamente a la última). Las vistas solo pintan
     * el array que reciben de su controlador.
     *
     * @param int $pagina Página actual
     * @param int $paginas Cantidad total de páginas
     * @param int $inicio Cuántas páginas del inicio se muestran siempre
     * @param int $final Cuántas páginas del final se muestran siempre
     * @return array<int, int|string> Números de página y '...' para los saltos
     */
    protected function numerosPagina(int $pagina, int $paginas, int $inicio = 3, int $final = 2): array
    {
        $paginas = max(1, $paginas);
        $pagina = max(1, min($pagina, $paginas));
        $inicio = max(1, $inicio);
        $final = max(1, $final);

        // Si hay pocas páginas se muestran todas.
        if ($paginas <= ($inicio + $final + 1)) {
            return range(1, $paginas);
        }

        $visibles = [];

        for ($i = 1; $i <= $inicio; $i++) {
            $visibles[$i] = $i;
        }

        for ($i = $pagina - 1; $i <= $pagina + 1; $i++) {
            if ($i >= 1 && $i <= $paginas) {
                $visibles[$i] = $i;
            }
        }

        for ($i = $paginas - $final + 1; $i <= $paginas; $i++) {
            if ($i >= 1 && $i <= $paginas) {
                $visibles[$i] = $i;
            }
        }

        ksort($visibles);

        $resultado = [];
        $previo = 0;

        foreach ($visibles as $numero) {
            if ($previo > 0 && $numero - $previo > 1) {
                $resultado[] = '...';
            }

            $resultado[] = $numero;
            $previo = $numero;
        }

        return $resultado;
    }

    // estas variables son metodos auxiliares en toda la estructura
    private const SESION_PREFIJO = 'portal_';
    protected function idPersona(): int
    {
        return (int) Session::get(self::SESION_PREFIJO . 'id_persona', 0);
    }

    protected function rolClave(): string
    {
        return (string) Session::get(self::SESION_PREFIJO . 'rol_clave', '');
    }

}
