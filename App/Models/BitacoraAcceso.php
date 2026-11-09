<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

/**
 * Bitácora de accesos al sistema.
 *
 * Registra cada intento de acceso (correcto, fallido, cierre de sesión o
 * bloqueo) con su fecha y hora, la IP, el equipo (user agent) y el detalle.
 */
class BitacoraAcceso extends Model
{
    protected string $table = 'bitacora_accesos';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_persona',
        'usuario_intentado',
        'ip_address',
        'user_agent',
        'fecha_hora',
        'estado',
        'detalle',
    ];

    protected bool $timestamps = false;

    private const SELECT = "SELECT b.id, b.id_persona, b.usuario_intentado, b.ip_address, b.user_agent,
                b.fecha_hora, b.estado, b.detalle,
                p.nombre, p.apellido,
                r.clave AS rol_clave, r.nombre AS rol_nombre
            FROM bitacora_accesos b
            LEFT JOIN persona p ON b.id_persona = p.id
            LEFT JOIN rol r ON p.id_rol = r.id";

    /**
     * Lista los accesos aplicando los filtros, paginados.
     *
     * @param array{nombre?: string, desde?: string, hasta?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listar(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condiciones($filtros);

        $total = 0;

        $conteo = Database::raw(
            'SELECT COUNT(*) AS total FROM bitacora_accesos b
             LEFT JOIN persona p ON b.id_persona = p.id
             LEFT JOIN rol r ON p.id_rol = r.id
             WHERE ' . $where,
            $params
        );

        if (is_array($conteo) && isset($conteo[0]['total'])) {
            $total = (int) $conteo[0]['total'];
        }

        $paginas = (int) max(1, ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;

        $filas = Database::raw(
            self::SELECT . ' WHERE ' . $where
            . ' ORDER BY b.fecha_hora DESC, b.id DESC'
            . ' LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = self::normalizar($fila);
        }

        return [
            'items' => $items,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'porPagina' => $porPagina,
        ];
    }

    /**
     * Devuelve todos los accesos que coincidan con los filtros, sin paginar.
     * Útil para exportaciones.
     *
     * @param array{nombre?: string, desde?: string, hasta?: string} $filtros
     * @return array<int, array>
     */
    public static function listarTodos(array $filtros): array
    {
        [$where, $params] = self::condiciones($filtros);

        $filas = Database::raw(
            self::SELECT . ' WHERE ' . $where
            . ' ORDER BY b.fecha_hora DESC, b.id DESC',
            $params
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = self::normalizar($fila);
        }

        return $items;
    }

    /**
     * Detalle de un acceso concreto.
     */
    public static function detalle(int $id): ?array
    {
        $fila = Database::raw(self::SELECT . ' WHERE b.id = :id', ['id' => $id]);

        if (!is_array($fila) || $fila === []) {
            return null;
        }

        $primera = reset($fila);

        return is_array($primera) ? self::normalizar($primera) : null;
    }

    /**
     * Traduce la fila de la base a un formato utilizable por la vista.
     */
    private static function normalizar(array $fila): array
    {
        $nombre = trim((string) ($fila['nombre'] ?? '') . ' ' . (string) ($fila['apellido'] ?? ''));

        return [
            'id' => (int) ($fila['id'] ?? 0),
            'id_persona' => $fila['id_persona'] !== null ? (int) $fila['id_persona'] : null,
            'usuario_intentado' => (string) ($fila['usuario_intentado'] ?? ''),
            'ip_address' => (string) ($fila['ip_address'] ?? ''),
            'user_agent' => (string) ($fila['user_agent'] ?? ''),
            'fecha_hora' => (string) ($fila['fecha_hora'] ?? ''),
            'estado' => (string) ($fila['estado'] ?? ''),
            'detalle' => (string) ($fila['detalle'] ?? ''),
            'nombre' => $nombre,
            'nombre_completo' => $nombre !== '' ? $nombre : '—',
            'rol_clave' => (string) ($fila['rol_clave'] ?? ''),
            'rol_nombre' => (string) ($fila['rol_nombre'] ?? ''),
        ];
    }

    /**
     * Construye el WHERE y sus parámetros a partir de los filtros.
     *
     * @param array{nombre?: string, desde?: string, hasta?: string} $filtros
     * @return array{0: string, 1: array}
     */
    private static function condiciones(array $filtros): array
    {
        $condiciones = ['1 = 1'];
        $params = [];

        $nombre = trim((string) ($filtros['nombre'] ?? ''));

        if ($nombre !== '') {
            $condiciones[] = '(b.usuario_intentado ILIKE :nombre OR p.nombre ILIKE :nombre OR p.apellido ILIKE :nombre)';
            $params['nombre'] = '%' . $nombre . '%';
        }

        $desde = trim((string) ($filtros['desde'] ?? ''));

        if ($desde !== '') {
            $condiciones[] = 'b.fecha_hora >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }

        $hasta = trim((string) ($filtros['hasta'] ?? ''));

        if ($hasta !== '') {
            $condiciones[] = 'b.fecha_hora <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        return [implode(' AND ', $condiciones), $params];
    }

    /**
     * Etiqueta legible del tipo de acceso.
     */
    public static function etiquetaEstado(string $estado): string
    {
        return match (strtoupper($estado)) {
            'EXITOSO' => 'Acceso válido',
            'FALLIDO' => 'Intento fallido',
            'CERRADO' => 'Cierre de sesión',
            'BLOQUEADO' => 'Cuenta bloqueada',
            default => $estado !== '' ? $estado : '—',
        };
    }
}
