<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class Persona extends Model
{
    protected string $table = 'persona';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'ci',
        'nombre',
        'apellido',
        'telefono',
        'direccion',
        'correo',
        'fecha_nacimiento',
        'sexo',
        'estado',
        'id_rol',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;

    /**
     * SELECT base del personal: personas con rol de sistema
     * (ADMIN, RECEP o MEDICO — cualquier rol distinto de PAC),
     * con su cuenta de acceso y su ficha de médico si existe.
     */
    private const SELECT_PERSONAL = "SELECT p.id AS id_persona, p.ci, p.nombre, p.apellido, p.telefono, p.direccion,
                p.correo, p.fecha_nacimiento, p.sexo, p.estado, p.fecha_registro, p.id_rol,
                r.clave AS rol_clave, r.nombre AS rol_nombre,
                c.usuario, c.correo AS cuenta_correo, c.intentos_fallidos, c.bloqueado,
                c.bloqueado_hasta, c.ultimo_acceso, c.fecha_creacion,
                m.nro_colegiado, m.fecha_ingreso, m.biografia, m.estado AS medico_estado
            FROM persona p
            INNER JOIN rol r ON p.id_rol = r.id AND r.clave <> 'PAC'
            LEFT JOIN cuenta_persona c ON c.id_persona = p.id
            LEFT JOIN medico m ON m.id_persona = p.id";

    /* =====================================================================
       PERSONAL (roles ADMIN / RECEP / MEDICO)
       ===================================================================== */

    /**
     * Totales del resumen: general, por rol, cuentas y fichas de médico.
     *
     * @return array{total: int, activos: int, inactivos: int, administradores: int, recepcionistas: int, medicos: int, sin_cuenta: int, bloqueados: int, fichas_medico: int}
     */
    public static function estadisticasPersonal(): array
    {
        $estadisticas = [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'administradores' => 0,
            'recepcionistas' => 0,
            'medicos' => 0,
            'sin_cuenta' => 0,
            'bloqueados' => 0,
            'fichas_medico' => 0,
        ];

        $filas = Database::raw(
            "SELECT COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE p.estado) AS activos,
                    COUNT(*) FILTER (WHERE NOT p.estado) AS inactivos,
                    COUNT(*) FILTER (WHERE r.clave = 'ADMIN') AS administradores,
                    COUNT(*) FILTER (WHERE r.clave = 'RECEP') AS recepcionistas,
                    COUNT(*) FILTER (WHERE r.clave = 'MEDICO') AS medicos,
                    COUNT(*) FILTER (WHERE c.id_persona IS NULL) AS sin_cuenta,
                    COUNT(*) FILTER (WHERE c.bloqueado) AS bloqueados,
                    COUNT(*) FILTER (WHERE m.id_persona IS NOT NULL) AS fichas_medico
             FROM persona p
             INNER JOIN rol r ON p.id_rol = r.id AND r.clave <> 'PAC'
             LEFT JOIN cuenta_persona c ON c.id_persona = p.id
             LEFT JOIN medico m ON m.id_persona = p.id"
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            foreach (array_keys($estadisticas) as $clave) {
                $estadisticas[$clave] = (int) ($filas[0][$clave] ?? 0);
            }
        }

        return $estadisticas;
    }

    /**
     * Lista el personal aplicando filtros, paginado.
     *
     * @param array{nombre?: string, ci?: string, rol?: string, estado?: string, desde?: string, hasta?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listarPersonal(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condicionesPersonal($filtros);

        $total = 0;

        $conteo = Database::raw(
            'SELECT COUNT(*) AS total
             FROM persona p
             INNER JOIN rol r ON p.id_rol = r.id AND r.clave <> \'PAC\'
             LEFT JOIN cuenta_persona c ON c.id_persona = p.id
             LEFT JOIN medico m ON m.id_persona = p.id
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
            self::SELECT_PERSONAL . ' WHERE ' . $where
            . ' ORDER BY p.fecha_registro DESC, p.id DESC'
            . ' LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = self::normalizarPersonal($fila);
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
     * Todo el personal que coincida con los filtros, sin paginar (PDF).
     *
     * @param array{nombre?: string, ci?: string, rol?: string, estado?: string, desde?: string, hasta?: string} $filtros
     * @return array<int, array>
     */
    public static function listarPersonalTodos(array $filtros): array
    {
        [$where, $params] = self::condicionesPersonal($filtros);

        $filas = Database::raw(
            self::SELECT_PERSONAL . ' WHERE ' . $where
            . ' ORDER BY p.fecha_registro DESC, p.id DESC',
            $params
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = self::normalizarPersonal($fila);
        }

        return $items;
    }

    /**
     * Detalle de una persona con rol de sistema: datos, cuenta y ficha médica.
     */
    public static function detallePersonal(int $idPersona): ?array
    {
        $fila = Database::raw(
            self::SELECT_PERSONAL . " WHERE p.id = :id AND r.clave <> 'PAC'",
            ['id' => $idPersona]
        );

        if (!is_array($fila) || $fila === []) {
            return null;
        }

        $primera = reset($fila);

        return is_array($primera) ? self::normalizarPersonal($primera) : null;
    }

    /**
     * Últimos miembros del personal registrados (para el resumen).
     *
     * @return array<int, array>
     */
    public static function recientesPersonal(int $limite = 10): array
    {
        $limite = max(1, min(20, $limite));

        $filas = Database::raw(
            self::SELECT_PERSONAL
            . ' ORDER BY p.fecha_registro DESC, p.id DESC'
            . ' LIMIT ' . $limite
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = self::normalizarPersonal($fila);
        }

        return $items;
    }

    /**
     * Roles de sistema disponibles para asignar al personal (sin PAC).
     *
     * @return array<int, array{id: int, clave: string, nombre: string}>
     */
    public static function rolesDisponibles(): array
    {
        $filas = Database::raw(
            "SELECT id, clave, nombre FROM rol WHERE clave <> 'PAC' AND estado ORDER BY id"
        );

        $roles = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $roles[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'clave' => (string) ($fila['clave'] ?? ''),
                'nombre' => (string) ($fila['nombre'] ?? ''),
            ];
        }

        return $roles;
    }

    /**
     * Valida que un rol de sistema exista; devuelve su clave o null.
     */
    public static function claveRol(int $idRol): ?string
    {
        $filas = Database::raw(
            "SELECT clave FROM rol WHERE id = :id AND clave <> 'PAC'",
            ['id' => $idRol]
        );

        if (is_array($filas) && isset($filas[0]['clave'])) {
            return (string) $filas[0]['clave'];
        }

        return null;
    }

    /* =====================================================================
       DISPONIBILIDAD (registro de personal)
       ===================================================================== */

    /**
     * Indica si un usuario de cuenta ya está registrado.
     */
    public static function usuarioDisponible(string $usuario, ?int $excluirId = null): bool
    {
        $params = ['usuario' => $usuario];
        $where = 'usuario = :usuario';

        if ($excluirId !== null) {
            $where .= ' AND id_persona <> :excluir';
            $params['excluir'] = $excluirId;
        }

        $filas = Database::raw(
            'SELECT COUNT(*) AS total FROM cuenta_persona WHERE ' . $where,
            $params
        );

        $total = is_array($filas) && isset($filas[0]['total']) ? (int) $filas[0]['total'] : 0;

        return $total === 0;
    }

    /**
     * Indica si un CI de persona ya está registrado.
     */
    public static function ciDisponible(string $ci, ?int $excluirId = null): bool
    {
        $params = ['ci' => $ci];
        $where = 'ci = :ci';

        if ($excluirId !== null) {
            $where .= ' AND id <> :excluir';
            $params['excluir'] = $excluirId;
        }

        $filas = Database::raw(
            'SELECT COUNT(*) AS total FROM persona WHERE ' . $where,
            $params
        );

        $total = is_array($filas) && isset($filas[0]['total']) ? (int) $filas[0]['total'] : 0;

        return $total === 0;
    }

    /**
     * Indica si un correo ya está registrado en persona o cuenta.
     */
    public static function correoDisponible(string $correo, ?int $excluirId = null): bool
    {
        $params = ['correo' => $correo];
        $wherePersona = 'correo = :correo';
        $whereCuenta = 'correo = :correo';

        if ($excluirId !== null) {
            $wherePersona .= ' AND id <> :excluir';
            $whereCuenta .= ' AND id_persona <> :excluir';
            $params['excluir'] = $excluirId;
        }

        $filas = Database::raw(
            'SELECT (SELECT COUNT(*) FROM persona WHERE ' . $wherePersona . ')'
            . ' + (SELECT COUNT(*) FROM cuenta_persona WHERE ' . $whereCuenta . ') AS total',
            $params
        );

        $total = is_array($filas) && isset($filas[0]['total']) ? (int) $filas[0]['total'] : 0;

        return $total === 0;
    }

    /* =====================================================================
       ESCRITURA (personal)
       ===================================================================== */

    /**
     * Crea una persona con rol de sistema, su cuenta de acceso y — si el
     * rol es MEDICO — su ficha en la tabla medico.
     *
     * @param array{ci: string, nombre: string, apellido: string, telefono: string, direccion: string, correo: string, fecha_nacimiento: string, sexo: string, id_rol: int} $datos
     * @param array{usuario: string, correo: string, password_hash: string} $cuenta
     * @param array{nro_colegiado?: string, fecha_ingreso?: string, biografia?: string} $medico
     * @return int ID de la persona creada.
     */
    public static function crearPersonal(array $datos, array $cuenta, array $medico = []): int
    {
        return (int) Database::transaction(function () use ($datos, $cuenta, $medico): int {
            Persona::query()->insert([
                'ci' => $datos['ci'],
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
                'direccion' => $datos['direccion'] !== '' ? $datos['direccion'] : null,
                'correo' => $datos['correo'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'] !== '' ? $datos['fecha_nacimiento'] : null,
                'sexo' => $datos['sexo'] !== '' ? $datos['sexo'] : null,
                'estado' => true,
                'id_rol' => (int) $datos['id_rol'],
            ]);

            $persona = Persona::query()->where('ci', $datos['ci'])->first();

            if ($persona === null) {
                throw new \RuntimeException('No se pudo recuperar la persona creada.');
            }

            $idPersona = (int) $persona['id'];

            CuentaPersona::query()->insert([
                'id_persona' => $idPersona,
                'usuario' => $cuenta['usuario'],
                'correo' => $cuenta['correo'],
                'password_hash' => $cuenta['password_hash'],
                'intentos_fallidos' => 0,
                'bloqueado' => false,
            ]);

            if (self::claveRol((int) $datos['id_rol']) === 'MEDICO') {
                Medico::query()->insert([
                    'id_persona' => $idPersona,
                    'nro_colegiado' => (string) ($medico['nro_colegiado'] ?? ''),
                    'fecha_ingreso' => (string) ($medico['fecha_ingreso'] ?? date('Y-m-d')),
                    'biografia' => trim((string) ($medico['biografia'] ?? '')) !== ''
                        ? trim((string) $medico['biografia'])
                        : null,
                    'estado' => true,
                ]);
            }

            return $idPersona;
        });
    }

    /**
     * Actualiza los datos personales (incluido el rol) de una persona.
     *
     * @param array{ci: string, nombre: string, apellido: string, telefono: string, direccion: string, correo: string, fecha_nacimiento: string, sexo: string, estado: bool, id_rol: int} $datos
     */
    public static function actualizarPersonal(int $idPersona, array $datos): void
    {
        Persona::query()->where('id', $idPersona)->update([
            'ci' => $datos['ci'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
            'direccion' => $datos['direccion'] !== '' ? $datos['direccion'] : null,
            'correo' => $datos['correo'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'] !== '' ? $datos['fecha_nacimiento'] : null,
            'sexo' => $datos['sexo'] !== '' ? $datos['sexo'] : null,
            'estado' => $datos['estado'],
            'id_rol' => (int) $datos['id_rol'],
        ]);
    }

    /**
     * Cambia el estado (activo/inactivo) de una persona.
     */
    public static function cambiarEstado(int $idPersona, bool $estado): void
    {
        Persona::query()->where('id', $idPersona)->update(['estado' => $estado]);
    }

    /**
     * Crea o actualiza la ficha de médico de una persona (rol MEDICO).
     *
     * @param array{nro_colegiado: string, fecha_ingreso: string, biografia?: string} $datos
     */
    public static function actualizarFichaMedico(int $idPersona, array $datos): void
    {
        $biografia = trim((string) ($datos['biografia'] ?? ''));

        $existe = Database::raw(
            'SELECT COUNT(*) AS total FROM medico WHERE id_persona = :id',
            ['id' => $idPersona]
        );

        $total = is_array($existe) && isset($existe[0]['total']) ? (int) $existe[0]['total'] : 0;

        if ($total > 0) {
            Medico::query()->where('id_persona', $idPersona)->update([
                'nro_colegiado' => $datos['nro_colegiado'],
                'fecha_ingreso' => $datos['fecha_ingreso'],
                'biografia' => $biografia !== '' ? $biografia : null,
            ]);

            return;
        }

        Medico::query()->insert([
            'id_persona' => $idPersona,
            'nro_colegiado' => $datos['nro_colegiado'],
            'fecha_ingreso' => $datos['fecha_ingreso'],
            'biografia' => $biografia !== '' ? $biografia : null,
            'estado' => true,
        ]);
    }

    /* =====================================================================
       INTERNOS (personal)
       ===================================================================== */

    /**
     * Traduce la fila del personal a un formato utilizable por la vista.
     */
    private static function normalizarPersonal(array $fila): array
    {
        $nombre = trim((string) ($fila['nombre'] ?? '') . ' ' . (string) ($fila['apellido'] ?? ''));
        $fechaNacimiento = (string) ($fila['fecha_nacimiento'] ?? '');
        $edad = null;

        if ($fechaNacimiento !== '') {
            $nacimiento = \DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
            $edad = $nacimiento !== false ? (int) $nacimiento->diff(new \DateTime())->y : null;
        }

        return [
            'id_persona' => (int) ($fila['id_persona'] ?? 0),
            'ci' => (string) ($fila['ci'] ?? ''),
            'nombre' => (string) ($fila['nombre'] ?? ''),
            'apellido' => (string) ($fila['apellido'] ?? ''),
            'nombre_completo' => $nombre !== '' ? $nombre : '—',
            'telefono' => (string) ($fila['telefono'] ?? ''),
            'direccion' => (string) ($fila['direccion'] ?? ''),
            'correo' => (string) ($fila['correo'] ?? ''),
            'fecha_nacimiento' => $fechaNacimiento,
            'edad' => $edad,
            'sexo' => (string) ($fila['sexo'] ?? ''),
            'estado' => (bool) ($fila['estado'] ?? false),
            'fecha_registro' => (string) ($fila['fecha_registro'] ?? ''),
            'id_rol' => (int) ($fila['id_rol'] ?? 0),
            'rol_clave' => (string) ($fila['rol_clave'] ?? ''),
            'rol_nombre' => (string) ($fila['rol_nombre'] ?? ''),
            'usuario' => (string) ($fila['usuario'] ?? ''),
            'cuenta_correo' => (string) ($fila['cuenta_correo'] ?? ''),
            'intentos_fallidos' => (int) ($fila['intentos_fallidos'] ?? 0),
            'bloqueado' => (bool) ($fila['bloqueado'] ?? false),
            'bloqueado_hasta' => (string) ($fila['bloqueado_hasta'] ?? ''),
            'ultimo_acceso' => (string) ($fila['ultimo_acceso'] ?? ''),
            'fecha_creacion' => (string) ($fila['fecha_creacion'] ?? ''),
            'tiene_cuenta' => ($fila['usuario'] ?? '') !== '',
            'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
            'fecha_ingreso' => (string) ($fila['fecha_ingreso'] ?? ''),
            'biografia' => (string) ($fila['biografia'] ?? ''),
            'medico_estado' => (bool) ($fila['medico_estado'] ?? false),
            'es_medico' => ($fila['nro_colegiado'] ?? '') !== '',
        ];
    }

    /**
     * Construye el WHERE y sus parámetros para el listado del personal.
     *
     * @param array{nombre?: string, ci?: string, rol?: string, estado?: string, desde?: string, hasta?: string} $filtros
     * @return array{0: string, 1: array}
     */
    private static function condicionesPersonal(array $filtros): array
    {
        $condiciones = ['1 = 1'];
        $params = [];

        $nombre = trim((string) ($filtros['nombre'] ?? ''));

        if ($nombre !== '') {
            $condiciones[] = "(p.nombre ILIKE :nombre OR p.apellido ILIKE :nombre OR (p.nombre || ' ' || p.apellido) ILIKE :nombre)";
            $params['nombre'] = '%' . $nombre . '%';
        }

        $ci = trim((string) ($filtros['ci'] ?? ''));

        if ($ci !== '') {
            $condiciones[] = 'p.ci ILIKE :ci';
            $params['ci'] = '%' . $ci . '%';
        }

        $rol = strtoupper(trim((string) ($filtros['rol'] ?? '')));

        if ($rol !== '' && in_array($rol, ['ADMIN', 'RECEP', 'MEDICO'], true)) {
            $condiciones[] = 'r.clave = :rol';
            $params['rol'] = $rol;
        }

        $estado = trim((string) ($filtros['estado'] ?? ''));

        if ($estado === '1' || strtoupper($estado) === 'ACTIVO') {
            $condiciones[] = 'p.estado = TRUE';
        } elseif ($estado === '0' || strtoupper($estado) === 'INACTIVO') {
            $condiciones[] = 'p.estado = FALSE';
        }

        $desde = trim((string) ($filtros['desde'] ?? ''));

        if ($desde !== '') {
            $condiciones[] = 'p.fecha_registro >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }

        $hasta = trim((string) ($filtros['hasta'] ?? ''));

        if ($hasta !== '') {
            $condiciones[] = 'p.fecha_registro <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        return [implode(' AND ', $condiciones), $params];
    }
}
