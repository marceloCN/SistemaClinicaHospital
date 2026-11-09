<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

/**
 * Paciente (extensión 1:1 de persona).
 *
 * Además del Active Record básico, expone los listados del dashboard
 * administrativo: estadísticas, listado paginado con filtros y detalle
 * con cuenta de acceso y contactos de emergencia.
 */
class Paciente extends Model
{
    protected string $table = 'paciente';
    protected string $primaryKey = 'id_persona';

    protected array $fillable = [
        'id_persona',
        'fecha_alta',
        'observaciones',
    ];

    protected bool $timestamps = false;

    private const SELECT = "SELECT p.id AS id_persona, p.ci, p.nombre, p.apellido, p.telefono, p.direccion, p.correo,
                p.fecha_nacimiento, p.sexo, p.estado, p.fecha_registro,
                pa.fecha_alta, pa.observaciones,
                c.usuario, c.correo AS cuenta_correo, c.intentos_fallidos,
                c.bloqueado, c.bloqueado_hasta, c.ultimo_acceso, c.fecha_creacion
            FROM persona p
            INNER JOIN rol r ON p.id_rol = r.id AND r.clave = 'PAC'
            LEFT JOIN paciente pa ON pa.id_persona = p.id
            LEFT JOIN cuenta_persona c ON c.id_persona = p.id";

    /**
     * Cambia el estado (activo/inactivo) de la persona del paciente.
     */
    public static function cambiarEstado(int $idPersona, bool $estado): void
    {
        Persona::query()->where('id', $idPersona)->update(['estado' => $estado]);
    }

    /**
     * Buscador de pacientes activos por nombre o CI: se usa en el
     * registro de citas de la recepción para elegir a quién se reserva.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function buscar(string $texto, int $limite = 10): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return [];
        }

        $limite = max(1, min(50, $limite));

        $filas = Database::raw(
            self::SELECT . "
             WHERE p.estado = TRUE
               AND EXISTS (SELECT 1 FROM paciente pa2 WHERE pa2.id_persona = p.id)
               AND ((p.nombre || ' ' || p.apellido) ILIKE :texto OR p.ci ILIKE :texto)
             ORDER BY p.apellido, p.nombre
             LIMIT {$limite}",
            ['texto' => '%' . $texto . '%']
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $nombre = trim((string) ($fila['nombre'] ?? '') . ' ' . (string) ($fila['apellido'] ?? ''));

            $items[] = [
                'id' => (int) ($fila['id_persona'] ?? 0),
                'nombre_completo' => $nombre !== '' ? $nombre : '—',
                'ci' => (string) ($fila['ci'] ?? ''),
                'correo' => (string) ($fila['correo'] ?? ''),
                'telefono' => (string) ($fila['telefono'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Calcula la edad en años a partir de la fecha de nacimiento.
     */
    private static function calcularEdad(string $fechaNacimiento): ?int
    {
        if ($fechaNacimiento === '') {
            return null;
        }

        $nacimiento = \DateTime::createFromFormat('Y-m-d', $fechaNacimiento);

        if ($nacimiento === false) {
            return null;
        }

        return (int) $nacimiento->diff(new \DateTime())->y;
    }

    /**
     * Totales del resumen: registrados (rol PAC), activos, inactivos
     * y pacientes regulares (con ficha). El donut usa los segmentos
     * excluyentes: regulares, activos sin ficha e inactivos sin ficha.
     *
     * @return array{total: int, activos: int, inactivos: int, regulares: int, activos_sin_ficha: int, inactivos_sin_ficha: int}
     */
    public static function estadisticas(): array
    {
        $estadisticas = [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'regulares' => 0,
            'activos_sin_ficha' => 0,
            'inactivos_sin_ficha' => 0,
        ];

        $filas = Database::raw(
            "SELECT COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE p.estado = TRUE) AS activos,
                    COUNT(*) FILTER (WHERE p.estado = FALSE) AS inactivos,
                    COUNT(*) FILTER (WHERE pa.id_persona IS NOT NULL) AS regulares,
                    COUNT(*) FILTER (WHERE pa.id_persona IS NULL AND p.estado = TRUE) AS activos_sin_ficha,
                    COUNT(*) FILTER (WHERE pa.id_persona IS NULL AND p.estado = FALSE) AS inactivos_sin_ficha
             FROM persona p
             INNER JOIN rol r ON p.id_rol = r.id AND r.clave = 'PAC'
             LEFT JOIN paciente pa ON pa.id_persona = p.id"
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            $estadisticas['total'] = (int) ($filas[0]['total'] ?? 0);
            $estadisticas['activos'] = (int) ($filas[0]['activos'] ?? 0);
            $estadisticas['inactivos'] = (int) ($filas[0]['inactivos'] ?? 0);
            $estadisticas['regulares'] = (int) ($filas[0]['regulares'] ?? 0);
            $estadisticas['activos_sin_ficha'] = (int) ($filas[0]['activos_sin_ficha'] ?? 0);
            $estadisticas['inactivos_sin_ficha'] = (int) ($filas[0]['inactivos_sin_ficha'] ?? 0);
        }

        return $estadisticas;
    }

    /**
     * Lista los pacientes (personas con rol PAC) aplicando filtros, paginados.
     *
     * @param array{nombre?: string, ci?: string, sexo?: string, estado?: string, regular?: string, desde?: string, hasta?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listar(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condiciones($filtros);

        $total = 0;

        $conteo = Database::raw(
            'SELECT COUNT(*) AS total
             FROM persona p
             INNER JOIN rol r ON p.id_rol = r.id AND r.clave = \'PAC\'
             LEFT JOIN paciente pa ON pa.id_persona = p.id
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
            . ' ORDER BY p.fecha_registro DESC, p.id DESC'
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
     * Devuelve todos los pacientes que coincidan con los filtros, sin
     * paginar. Utilizado para las exportaciones a PDF y CSV.
     *
     * @param array{nombre?: string, ci?: string, sexo?: string, estado?: string, regular?: string, desde?: string, hasta?: string} $filtros
     * @return array<int, array>
     */
    public static function listarTodos(array $filtros): array
    {
        [$where, $params] = self::condiciones($filtros);

        $filas = Database::raw(
            self::SELECT . ' WHERE ' . $where
            . ' ORDER BY p.fecha_registro DESC, p.id DESC',
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
     * Detalle de una persona con rol PAC: datos personales, cuenta y
     * contactos de emergencia. Si además tiene ficha en la tabla
     * paciente, se incluyen fecha de alta y observaciones.
     */
    public static function detalle(int $idPersona): ?array
    {
        $fila = Database::raw(self::SELECT . ' WHERE p.id = :id AND r.clave = \'PAC\'', ['id' => $idPersona]);

        if (!is_array($fila) || $fila === []) {
            return null;
        }

        $primera = reset($fila);

        if (!is_array($primera)) {
            return null;
        }

        $paciente = self::normalizar($primera);

        $contactos = Database::raw(
            'SELECT id, nombre, parentesco, telefono, correo, estado, fecha_registro
             FROM contacto_emergencia
             WHERE id_paciente = :id
             ORDER BY estado DESC, id ASC',
            ['id' => $idPersona]
        );

        $lista = [];

        foreach ((array) $contactos as $contacto) {
            if (!is_array($contacto)) {
                continue;
            }

            $lista[] = [
                'id' => (int) ($contacto['id'] ?? 0),
                'nombre' => (string) ($contacto['nombre'] ?? ''),
                'parentesco' => (string) ($contacto['parentesco'] ?? ''),
                'telefono' => (string) ($contacto['telefono'] ?? ''),
                'correo' => (string) ($contacto['correo'] ?? ''),
                'estado' => (bool) ($contacto['estado'] ?? false),
            ];
        }

        $paciente['contactos'] = $lista;

        return $paciente;
    }

    /**
     * Traduce la fila de la base a un formato utilizable por la vista.
     */
    private static function normalizar(array $fila): array
    {
        $nombre = trim((string) ($fila['nombre'] ?? '') . ' ' . (string) ($fila['apellido'] ?? ''));

        return [
            'id_persona' => (int) ($fila['id_persona'] ?? 0),
            'ci' => (string) ($fila['ci'] ?? ''),
            'nombre' => (string) ($fila['nombre'] ?? ''),
            'apellido' => (string) ($fila['apellido'] ?? ''),
            'nombre_completo' => $nombre !== '' ? $nombre : '—',
            'telefono' => (string) ($fila['telefono'] ?? ''),
            'direccion' => (string) ($fila['direccion'] ?? ''),
            'correo' => (string) ($fila['correo'] ?? ''),
            'fecha_nacimiento' => (string) ($fila['fecha_nacimiento'] ?? ''),
            'edad' => self::calcularEdad((string) ($fila['fecha_nacimiento'] ?? '')),
            'sexo' => (string) ($fila['sexo'] ?? ''),
            'estado' => (bool) ($fila['estado'] ?? false),
            'fecha_registro' => (string) ($fila['fecha_registro'] ?? ''),
            'fecha_alta' => (string) ($fila['fecha_alta'] ?? ''),
            'observaciones' => (string) ($fila['observaciones'] ?? ''),
            'usuario' => (string) ($fila['usuario'] ?? ''),
            'cuenta_correo' => (string) ($fila['cuenta_correo'] ?? ''),
            'intentos_fallidos' => (int) ($fila['intentos_fallidos'] ?? 0),
            'bloqueado' => (bool) ($fila['bloqueado'] ?? false),
            'bloqueado_hasta' => (string) ($fila['bloqueado_hasta'] ?? ''),
            'ultimo_acceso' => (string) ($fila['ultimo_acceso'] ?? ''),
            'fecha_creacion' => (string) ($fila['fecha_creacion'] ?? ''),
            'tiene_cuenta' => ($fila['usuario'] ?? '') !== '',
            'tiene_registro' => ($fila['fecha_alta'] ?? null) !== null,
        ];
    }

    /**
     * Construye el WHERE y sus parámetros a partir de los filtros.
     *
     * @param array{nombre?: string, ci?: string, sexo?: string, estado?: string, regular?: string, desde?: string, hasta?: string} $filtros
     * @return array{0: string, 1: array}
     */
    private static function condiciones(array $filtros): array
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

        $sexo = strtoupper(trim((string) ($filtros['sexo'] ?? '')));

        if ($sexo !== '' && in_array($sexo, ['MASCULINO', 'FEMENINO', 'OTRO'], true)) {
            $condiciones[] = 'UPPER(p.sexo) = :sexo';
            $params['sexo'] = $sexo;
        }

        $estado = trim((string) ($filtros['estado'] ?? ''));

        if ($estado === '1' || strtoupper($estado) === 'ACTIVO') {
            $condiciones[] = 'p.estado = TRUE';
        } elseif ($estado === '0' || strtoupper($estado) === 'INACTIVO') {
            $condiciones[] = 'p.estado = FALSE';
        }

        // Ficha en la tabla paciente: 1 = solo regulares, 0 = sin ficha.
        $regular = trim((string) ($filtros['regular'] ?? ''));

        if ($regular === '1') {
            $condiciones[] = 'pa.id_persona IS NOT NULL';
        } elseif ($regular === '0') {
            $condiciones[] = 'pa.id_persona IS NULL';
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

    /**
     * Indica si un usuario de cuenta ya está registrado.
     */
    public static function usuarioDisponible(string $usuario): bool
    {
        $filas = Database::raw(
            'SELECT COUNT(*) AS total FROM cuenta_persona WHERE usuario = :usuario',
            ['usuario' => $usuario]
        );

        $total = 0;

        if (is_array($filas) && isset($filas[0]['total'])) {
            $total = (int) $filas[0]['total'];
        }

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

        $total = 0;

        if (is_array($filas) && isset($filas[0]['total'])) {
            $total = (int) $filas[0]['total'];
        }

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

        $total = 0;

        if (is_array($filas) && isset($filas[0]['total'])) {
            $total = (int) $filas[0]['total'];
        }

        return $total === 0;
    }

    /**
     * Crea una persona con rol PAC, su ficha de paciente y su cuenta.
     *
     * @param array{ci: string, nombre: string, apellido: string, telefono: string, direccion: string, correo: string, fecha_nacimiento: string, sexo: string} $datos
     * @param array{usuario: string, correo: string, password_hash: string} $cuenta
     * @return int ID de la persona creada.
     */
    public static function crear(array $datos, array $cuenta): int
    {
        $rol = Database::raw("SELECT id FROM rol WHERE clave = 'PAC' LIMIT 1");

        if (!is_array($rol) || $rol === []) {
            throw new \RuntimeException('No se encontró el rol PAC.');
        }

        $primero = reset($rol);

        if (!is_array($primero)) {
            throw new \RuntimeException('No se encontró el rol PAC.');
        }

        $idRol = (int) ($primero['id'] ?? 0);

        if ($idRol <= 0) {
            throw new \RuntimeException('No se encontró el rol PAC.');
        }

        return (int) Database::transaction(function () use ($datos, $cuenta, $idRol): int {
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
                'id_rol' => $idRol,
            ]);

            $persona = Persona::query()->where('ci', $datos['ci'])->first();

            if ($persona === null) {
                throw new \RuntimeException('No se pudo recuperar la persona creada.');
            }

            $idPersona = (int) $persona['id'];

            Paciente::query()->insert([
                'id_persona' => $idPersona,
                'fecha_alta' => date('Y-m-d'),
                'observaciones' => null,
            ]);

            CuentaPersona::query()->insert([
                'id_persona' => $idPersona,
                'usuario' => $cuenta['usuario'],
                'correo' => $cuenta['correo'],
                'password_hash' => $cuenta['password_hash'],
                'intentos_fallidos' => 0,
                'bloqueado' => false,
            ]);

            return $idPersona;
        });
    }

    /**
     * Actualiza los datos personales (persona) de un paciente.
     *
     * @param array{ci: string, nombre: string, apellido: string, telefono: string, direccion: string, correo: string, fecha_nacimiento: string, sexo: string, estado: bool} $datos
     */
    public static function actualizarDatos(int $idPersona, array $datos): void
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
        ]);
    }

    /**
     * Registra un contacto de emergencia para un paciente.
     *
     * @param array{nombre: string, parentesco: string, telefono: string, correo: string, estado: bool} $datos
     */
    public static function guardarContacto(int $idPaciente, array $datos): int
    {
        ContactoEmergencia::query()->insert([
            'id_paciente' => $idPaciente,
            'nombre' => $datos['nombre'],
            'parentesco' => $datos['parentesco'] !== '' ? $datos['parentesco'] : null,
            'telefono' => $datos['telefono'],
            'correo' => $datos['correo'] !== '' ? $datos['correo'] : null,
            'estado' => $datos['estado'],
        ]);

        $fila = Database::raw(
            'SELECT id FROM contacto_emergencia
             WHERE id_paciente = :id AND nombre = :nombre
             ORDER BY id DESC LIMIT 1',
            ['id' => $idPaciente, 'nombre' => $datos['nombre']]
        );

        if (is_array($fila) && isset($fila[0]['id'])) {
            return (int) $fila[0]['id'];
        }

        return 0;
    }

    /**
     * Últimos pacientes registrados (para el resumen).
     */
    public static function recientes(int $limite = 5): array
    {
        $limite = max(1, min(20, $limite));

        $filas = Database::raw(
            self::SELECT
            . ' ORDER BY p.fecha_registro DESC, p.id DESC'
            . ' LIMIT ' . $limite
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
     * Contadores adicionales para el resumen: sin cuenta, bloqueados, sin ficha.
     *
     * @return array{sin_cuenta: int, bloqueados: int, sin_ficha: int}
     */
    public static function adicionales(): array
    {
        $contadores = ['sin_cuenta' => 0, 'bloqueados' => 0, 'sin_ficha' => 0];

        $filas = Database::raw(
            "SELECT COUNT(*) FILTER (WHERE c.id_persona IS NULL) AS sin_cuenta,
                    COUNT(*) FILTER (WHERE c.bloqueado = TRUE) AS bloqueados,
                    COUNT(*) FILTER (WHERE pa.id_persona IS NULL) AS sin_ficha
             FROM persona p
             INNER JOIN rol r ON p.id_rol = r.id AND r.clave = 'PAC'
             LEFT JOIN paciente pa ON pa.id_persona = p.id
             LEFT JOIN cuenta_persona c ON c.id_persona = p.id"
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            $contadores['sin_cuenta'] = (int) ($filas[0]['sin_cuenta'] ?? 0);
            $contadores['bloqueados'] = (int) ($filas[0]['bloqueados'] ?? 0);
            $contadores['sin_ficha'] = (int) ($filas[0]['sin_ficha'] ?? 0);
        }

        return $contadores;
    }
}
