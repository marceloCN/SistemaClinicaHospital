<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;
use App\Models\Permiso;

/**
 * Bitácora de acciones realizadas sobre los datos del sistema.
 *
 * Cada fila indica la tabla afectada, la acción ejecutada, el registro
 * involucrado, el permiso que la habilitó, el equipo, la IP y el resultado
 * de la operación.
 */
class BitacoraAccion extends Model
{
    protected string $table = 'bitacora_acciones';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_persona',
        'tabla_afectada',
        'accion',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'user_agent',
        'fecha_hora',
        'detalle',
        'resultado',
    ];

    protected bool $timestamps = false;

    /**
     * Permiso que habilita cada área del sistema.
     */
    private const PERMISOS_POR_TABLA = [
        'PERMISOS' => 'permisos.gestionar',
        'CONFIGURACION' => 'configuracion.gestionar',
        'PERFIL' => 'cuenta.actualizar',
        'PACIENTE' => 'pacientes.registrar',
        'USUARIOS' => 'usuarios.gestionar',
        'BITACORA' => 'bitacora.listar',
        'MEDICOS' => 'medicos.gestionar',
        'ESPECIALIDADES' => 'especialidades.gestionar',
        'HORARIOS' => 'horarios.gestionar',
        'SERVICIOS' => 'servicios.gestionar',
        'AGENDA' => 'agenda.bloquear',
        'CITAS' => 'citas.bloqueo_gestionar',
        'CATALOGO' => 'catalogo.consultar',
    ];

    /**
     * Tipo de acción (create / update / delete) según la acción registrada.
     */
    private const TIPOS_ACCION = [
        'REGISTRAR' => 'create',
        'CREAR' => 'create',
        'INSERTAR' => 'create',
        'ALTA' => 'create',
        'ACTUALIZAR' => 'update',
        'MODIFICAR' => 'update',
        'EDITAR' => 'update',
        'ELIMINAR' => 'delete',
        'BORRAR' => 'delete',
    ];

    private const SELECT = "SELECT a.id, a.id_persona, a.tabla_afectada, a.accion, a.registro_id,
                a.datos_anteriores, a.datos_nuevos,
                a.ip_address, a.user_agent, a.fecha_hora, a.detalle, a.resultado,
                p.nombre, p.apellido,
                c.usuario,
                r.clave AS rol_clave, r.nombre AS rol_nombre
            FROM bitacora_acciones a
            LEFT JOIN persona p ON a.id_persona = p.id
            LEFT JOIN cuenta_persona c ON c.id_persona = p.id
            LEFT JOIN rol r ON p.id_rol = r.id";

    /**
     * Asegura que exista la columna "resultado" (operación idempotente).
     */
    public static function asegurarColumna(): void
    {
        static $verificada = false;

        if ($verificada) {
            return;
        }

        $verificada = true;

        try {
            $filas = Database::raw(
                "SELECT COUNT(*) AS total FROM information_schema.columns
                 WHERE table_name = 'bitacora_acciones' AND column_name = 'resultado'"
            );

            $existe = is_array($filas) && (int) ($filas[0]['total'] ?? 0) > 0;

            if (!$existe) {
                Database::raw(
                    "ALTER TABLE bitacora_acciones
                     ADD COLUMN IF NOT EXISTS resultado VARCHAR(10) NOT NULL DEFAULT 'EXITOSO'"
                );
            }
        } catch (\Throwable $e) {
            error_log('No se pudo verificar la columna resultado de bitacora_acciones: ' . $e->getMessage());
        }
    }

    /**
     * Lista las acciones aplicando los filtros, paginadas.
     *
     * @param array{nombre?: string, desde?: string, hasta?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listar(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        self::asegurarColumna();

        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condiciones($filtros);

        $total = 0;

        $conteo = Database::raw(
            'SELECT COUNT(*) AS total FROM bitacora_acciones a
             LEFT JOIN persona p ON a.id_persona = p.id
             LEFT JOIN cuenta_persona c ON c.id_persona = p.id
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
            . ' ORDER BY a.fecha_hora DESC, a.id DESC'
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
     * Devuelve todas las acciones que coincidan con los filtros, sin paginar.
     * Útil para exportaciones.
     *
     * @param array{nombre?: string, desde?: string, hasta?: string} $filtros
     * @return array<int, array>
     */
    public static function listarTodos(array $filtros): array
    {
        self::asegurarColumna();

        [$where, $params] = self::condiciones($filtros);

        $filas = Database::raw(
            self::SELECT . ' WHERE ' . $where
            . ' ORDER BY a.fecha_hora DESC, a.id DESC',
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
     * Detalle de una acción concreta.
     */
    public static function detalle(int $id): ?array
    {
        self::asegurarColumna();

        $fila = Database::raw(self::SELECT . ' WHERE a.id = :id', ['id' => $id]);

        if (!is_array($fila) || $fila === []) {
            return null;
        }

        $primera = reset($fila);

        return is_array($primera) ? self::normalizar($primera) : null;
    }

    /** Cache en memoria de permisos consultados por clave. */
    private static array $permisosCache = [];

    /**
     * Obtiene los datos de un permiso desde la base de datos usando su clave.
     * Usa cache para evitar consultas repetidas dentro del mismo proceso.
     */
    private static function permisoPorClave(?string $clave): ?array
    {
        if ($clave === null || $clave === '') {
            return null;
        }

        if (array_key_exists($clave, self::$permisosCache)) {
            return self::$permisosCache[$clave];
        }

        $permiso = Permiso::query()->where('clave', $clave)->first();

        if ($permiso === null) {
            self::$permisosCache[$clave] = null;
            return null;
        }

        $datos = [
            'id' => (int) $permiso['id'],
            'clave' => (string) $permiso['clave'],
            'nombre' => (string) $permiso['nombre'],
            'descripcion' => (string) ($permiso['descripcion'] ?? ''),
            'modulo' => (string) ($permiso['modulo'] ?? ''),
        ];

        self::$permisosCache[$clave] = $datos;
        return $datos;
    }

    /**
     * Formatea un campo JSON/JSONB para mostrarlo de forma legible.
     * Devuelve null si no hay datos.
     */
    private static function formatearDatosJson(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $decodificado = is_string($valor) ? json_decode($valor, true) : $valor;

        if (!is_array($decodificado) || $decodificado === []) {
            return null;
        }

        return json_encode($decodificado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Traduce la fila de la base a un formato utilizable por la vista.
     */
    private static function normalizar(array $fila): array
    {
        $nombre = trim((string) ($fila['nombre'] ?? '') . ' ' . (string) ($fila['apellido'] ?? ''));
        $tabla = (string) ($fila['tabla_afectada'] ?? '');
        $accion = (string) ($fila['accion'] ?? '');

        $permisoClave = self::PERMISOS_POR_TABLA[$tabla] ?? null;

        if ($tabla === 'PACIENTE' && $accion === 'ACTUALIZAR') {
            $permisoClave = 'pacientes.actualizar';
        }

        $permiso = self::permisoPorClave($permisoClave);

        $registroId = $fila['registro_id'] !== null ? (int) $fila['registro_id'] : null;

        return [
            'id' => (int) ($fila['id'] ?? 0),
            'id_persona' => $fila['id_persona'] !== null ? (int) $fila['id_persona'] : null,
            'tabla_afectada' => $tabla,
            'accion' => $accion,
            'tipo_accion' => self::TIPOS_ACCION[strtoupper($accion)] ?? mb_strtolower($accion),
            'registro_id' => $registroId,
            'registro_afectado' => ($registroId !== null && $registroId > 0) ? (string) $registroId : '----',
            'ip_address' => (string) ($fila['ip_address'] ?? ''),
            'user_agent' => (string) ($fila['user_agent'] ?? ''),
            'fecha_hora' => (string) ($fila['fecha_hora'] ?? ''),
            'detalle' => (string) ($fila['detalle'] ?? ''),
            'resultado' => strtoupper((string) ($fila['resultado'] ?? 'EXITOSO')),
            'resultado_etiqueta' => self::etiquetaResultado((string) ($fila['resultado'] ?? 'EXITOSO')),
            'nombre_completo' => $nombre !== '' ? $nombre : '—',
            'usuario' => (string) ($fila['usuario'] ?? ''),
            'rol_clave' => (string) ($fila['rol_clave'] ?? ''),
            'rol_nombre' => (string) ($fila['rol_nombre'] ?? ''),
            'permiso_clave' => $permiso['clave'] ?? ($permisoClave ?? ''),
            'permiso_codigo' => $permiso !== null ? (int) $permiso['id'] : null,
            'permiso_nombre' => $permiso['nombre'] ?? '',
            'datos_anteriores' => self::formatearDatosJson($fila['datos_anteriores'] ?? null),
            'datos_nuevos' => self::formatearDatosJson($fila['datos_nuevos'] ?? null),
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
            $condiciones[] = '(c.usuario ILIKE :nombre OR p.nombre ILIKE :nombre OR p.apellido ILIKE :nombre)';
            $params['nombre'] = '%' . $nombre . '%';
        }

        $desde = trim((string) ($filtros['desde'] ?? ''));

        if ($desde !== '') {
            $condiciones[] = 'a.fecha_hora >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }

        $hasta = trim((string) ($filtros['hasta'] ?? ''));

        if ($hasta !== '') {
            $condiciones[] = 'a.fecha_hora <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        return [implode(' AND ', $condiciones), $params];
    }

    /**
     * Etiqueta legible del resultado de la acción.
     */
    public static function etiquetaResultado(string $resultado): string
    {
        return match (strtoupper($resultado)) {
            'EXITOSO' => 'Exitoso',
            'FALLIDO' => 'Fallido',
            default => $resultado !== '' ? $resultado : 'Exitoso',
        };
    }
}
