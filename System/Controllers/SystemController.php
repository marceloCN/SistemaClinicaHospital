<?php

declare(strict_types=1);

namespace System\Controllers;

use Lib\Core\Controller;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\Route;
use Lib\Core\View;
use Lib\Support\Csrf;
use Lib\Support\Session;
use Lib\Support\System;
use System\Models\Settings;
use System\Models\Users;

class SystemController extends Controller
{
    private const ADMIN_PREFIX = 'join/access/dashboard';

    public function index(Request $request): Response
    {
        if ($response = $this->adminPage()) {
            return $response;
        }

        $settings = Settings::query()->first() ?? [];
        $tables = $this->tables();

        return $this->render('dashboard.system.overview', 'Resumen del sistema', 'Sistema', [
            'phpVersion' => PHP_VERSION,
            'appName' => config('app.name'),
            'appEnv' => config('app.env'),
            'appUrl' => config('app.url'),
            'appDebug' => (bool) config('app.debug'),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'basePath' => BASE_PATH,
            'database' => config('database.connections.' . config('database.default') . '.database'),
            'tableCount' => count($tables),
            'maintenance' => (bool) ($settings['maintenance_mode'] ?? false),
            'homeRoute' => $this->homeRouteName(),
            'routeCount' => count(Route::all()),
            'userCount' => $this->safeCount(fn (): int => Users::query()->count()),
        ]);
    }

    private function homeRouteName(): string
    {
        $name = System::homeRoute();

        return Route::route($name) !== null ? $name : '—';
    }

    public function routes(Request $request): Response
    {
        if ($response = $this->adminPage()) {
            return $response;
        }

        return $this->render('dashboard.system.routes', 'Rutas y enlaces', 'Sistema / Rutas', [
            'routes' => $this->routeRows(),
            'homeRoute' => $this->homeRouteName(),
        ]);
    }

    public function routeToggle(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $key = (string) $request->input('key', '');
        $enabled = (string) $request->input('enabled', '1') === '1';

        $found = $this->findRouteByKey($key);

        if ($found === null) {
            return $this->json(['success' => false, 'message' => 'Ruta no encontrada.'], 404);
        }

        if (str_starts_with($found['uri'], self::ADMIN_PREFIX)) {
            return $this->json(['success' => false, 'message' => 'Las rutas del panel de administración no se pueden deshabilitar.'], 403);
        }

        System::setRouteEnabled($key, $enabled);

        return $this->json(['success' => true, 'enabled' => $enabled]);
    }

    public function middleware(Request $request): Response
    {
        if ($response = $this->adminPage()) {
            return $response;
        }

        $usage = [];

        foreach (Route::all() as $route) {
            foreach ($route['middleware'] as $middleware) {
                $name = explode(':', $middleware)[0];
                $usage[$name]['routes'][] = $route['method'] . ' /' . $route['uri'];
            }
        }

        $rows = [];

        foreach ($usage as $name => $info) {
            $rows[] = [
                'name' => $name,
                'count' => count($info['routes']),
                'routes' => $info['routes'],
                'enabled' => System::middlewareEnabled($name),
                'protected' => System::isProtectedMiddleware($name),
            ];
        }

        usort($rows, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $this->render('dashboard.system.middleware', 'Middlewares', 'Sistema / Middlewares', [
            'middlewares' => $rows,
        ]);
    }

    public function middlewareToggle(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $name = (string) $request->input('name', '');
        $enabled = (string) $request->input('enabled', '1') === '1';

        $exists = class_exists('System\\Middleware\\' . ucfirst($name))
            || class_exists('App\\Middleware\\' . ucfirst($name));

        if ($name === '' || !$exists) {
            return $this->json(['success' => false, 'message' => 'Middleware no encontrado.'], 404);
        }

        if (!System::setMiddlewareEnabled($name, $enabled)) {
            return $this->json(['success' => false, 'message' => "El middleware [{$name}] está protegido y no puede deshabilitarse."], 403);
        }

        return $this->json(['success' => true, 'enabled' => $enabled]);
    }

    public function home(Request $request): Response
    {
        if ($response = $this->adminPage()) {
            return $response;
        }

        return $this->render('dashboard.system.home', 'Página de inicio', 'Sistema / Inicio', [
            'options' => $this->homeCandidates(),
            'current' => System::homeRoute(),
            'settings' => Settings::query()->first() ?? [],
        ]);
    }

    public function homeUpdate(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $name = (string) $request->input('route', '');
        $candidates = array_column($this->homeCandidates(), 'name');

        if (!in_array($name, $candidates, true)) {
            return $this->json(['success' => false, 'message' => 'Ruta de inicio no válida.'], 422);
        }

        System::setHomeRoute($name);

        return $this->json(['success' => true, 'route' => $name]);
    }

    public function maintenanceToggle(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $enabled = (string) $request->input('enabled', '1') === '1';

        Settings::query()->update(
            ['maintenance_mode' => $enabled ? 1 : 0],
            ['id' => 1]
        );

        Session::set('MAINTENANCE', $enabled);

        return $this->json(['success' => true, 'enabled' => $enabled]);
    }

    /**
     * Activa/desactiva el modo depuracion escribiendo APP_DEBUG en el .env.
     * Usa el mismo writeEnv (con backup) del panel de configuracion de BD.
     */
    public function debugToggle(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $enabled = (string) $request->input('enabled', '1') === '1';

        try {
            \Lib\Support\DbSetup::writeEnv([
                'APP_DEBUG' => $enabled ? 'true' : 'false',
            ]);
        } catch (\Throwable $e) {
            error_log('Debug toggle: no se pudo escribir .env — ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo escribir APP_DEBUG en el archivo .env: ' . $e->getMessage(),
            ], 500);
        }

        // El valor real se recien al recargar (bootstrap lo lee del .env).
        return $this->json(['success' => true, 'enabled' => $enabled]);
    }

    public function database(Request $request): Response
    {
        if ($response = $this->adminPage()) {
            return $response;
        }

        $config = config('database.connections.' . config('database.default')) ?? [];
        $tables = $this->tables();

        $selected = (string) $request->input('table', '');
        $columns = [];

        if ($selected !== '') {
            $valid = false;
            foreach ($tables as $table) {
                if ($table['name'] === $selected) {
                    $valid = true;
                    break;
                }
            }

            if ($valid) {
                if (Database::isPgsql()) {
                    $sql = "SELECT
                                c.column_name AS \"COLUMN_NAME\",
                                CASE WHEN c.character_maximum_length IS NOT NULL
                                    THEN c.data_type || '(' || c.character_maximum_length || ')'
                                    ELSE c.data_type
                                END AS \"COLUMN_TYPE\",
                                c.is_nullable AS \"IS_NULLABLE\",
                                COALESCE((
                                    SELECT 'PRI'
                                    FROM information_schema.table_constraints tc
                                    JOIN information_schema.key_column_usage kcu
                                        ON kcu.constraint_name = tc.constraint_name
                                        AND kcu.table_schema = tc.table_schema
                                    WHERE tc.constraint_type = 'PRIMARY KEY'
                                        AND tc.table_schema = c.table_schema
                                        AND tc.table_name = c.table_name
                                        AND kcu.column_name = c.column_name
                                ), '') AS \"COLUMN_KEY\",
                                c.column_default AS \"COLUMN_DEFAULT\",
                                '' AS \"EXTRA\"
                            FROM information_schema.columns c
                            WHERE c.table_schema = current_schema() AND c.table_name = :table
                            ORDER BY c.ordinal_position";
                } else {
                    $sql = 'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table
                            ORDER BY ORDINAL_POSITION';
                }

                $stmt = Database::connection()->prepare($sql);
                $stmt->execute(['table' => $selected]);
                $columns = $stmt->fetchAll();
            } else {
                $selected = '';
            }
        }

        $config['password'] = '••••••••';

        return $this->render('dashboard.system.database', 'Base de datos', 'Sistema / Base de datos', [
            'connection' => $config,
            'tables' => $tables,
            'selected' => $selected,
            'columns' => $columns,
        ]);
    }

    /**
     * Consola SQL del panel (estilo pgAdmin): ejecuta un comando enviado por
     * el modo consola de la pagina de Base de datos. Detecta el tipo de
     * comando, devuelve las filas en SELECT y depura los errores de PDO con
     * su SQLSTATE y detalle, como hace pgAdmin.
     */
    public function databaseQuery(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $sql = trim((string) $request->input('sql', ''));

        if ($sql === '') {
            return $this->json(['success' => false, 'message' => 'Escribe un comando SQL para ejecutar.'], 422);
        }

        if (strlen($sql) > 20000) {
            return $this->json(['success' => false, 'message' => 'El comando es demasiado largo (maximo 20000 caracteres).'], 422);
        }

        $command = $this->detectSqlCommand($sql);
        $isQuery = $this->isSelectLike($command);

        $start = microtime(true);

        try {
            $pdo = Database::connection();

            if ($isQuery) {
                $stmt = $pdo->query($sql);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $columns = [];
                if ($rows !== []) {
                    $columns = array_map('strval', array_keys($rows[0]));
                } else {
                    // Sin filas: intentar extraer los alias de las columnas.
                    for ($i = 0; $i < $stmt->columnCount(); $i++) {
                        $meta = $stmt->getColumnMeta($i);
                        $columns[] = (string) ($meta['name'] ?? ('col' . ($i + 1)));
                    }
                }

                return $this->json([
                    'success' => true,
                    'command' => $command,
                    'columns' => $columns,
                    'rows' => $rows,
                    'rowcount' => count($rows),
                    'duration' => round((microtime(true) - $start) * 1000, 2),
                    'message' => count($rows) === 1
                        ? 'Consulta ejecutada correctamente. 1 fila devuelta.'
                        : 'Consulta ejecutada correctamente. ' . count($rows) . ' filas devueltas.',
                ]);
            }

            // DML / DDL: no devuelven filas, solo filas afectadas.
            $affected = (int) $pdo->exec($sql);

            return $this->json([
                'success' => true,
                'command' => $command,
                'columns' => [],
                'rows' => [],
                'rowcount' => $affected,
                'duration' => round((microtime(true) - $start) * 1000, 2),
                'message' => 'Comando ejecutado correctamente. Filas afectadas: ' . $affected,
            ]);
        } catch (\PDOException $e) {
            $errorInfo = $e->errorInfo ?? [];

            // errorInfo: [SQLSTATE, codigo del driver, mensaje del driver]
            return $this->json([
                'success' => false,
                'command' => $command,
                'columns' => [],
                'rows' => [],
                'rowcount' => 0,
                'duration' => round((microtime(true) - $start) * 1000, 2),
                'message' => 'Error al ejecutar el comando.',
                'error' => [
                    'sqlstate' => (string) ($errorInfo[0] ?? $e->getCode()),
                    'driver_code' => isset($errorInfo[1]) ? (string) $errorInfo[1] : null,
                    'detail' => (string) ($errorInfo[2] ?? $e->getMessage()),
                    'hint' => $this->sqlErrorHint((string) ($errorInfo[2] ?? $e->getMessage()), (string) ($errorInfo[0] ?? $e->getCode())),
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('Console SQL error: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'command' => $command,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Detecta el tipo de comando SQL quitando comentarios y espacios. */
    private function detectSqlCommand(string $sql): string
    {
        $clean = preg_replace('/\/\*.*?\*\//s', ' ', $sql);
        $clean = preg_replace('/--[^\n]*/', ' ', (string) $clean);
        $clean = trim((string) $clean);

        if (preg_match('/^([A-Za-z_]+)/', $clean, $m) === 1) {
            return strtoupper($m[1]);
        }

        return 'UNKNOWN';
    }

    /** True si el comando devuelve filas (SELECT/SHOW/EXPLAIN/WITH...). */
    private function isSelectLike(string $command): bool
    {
        return in_array($command, ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'WITH', 'VALUES'], true);
    }

    /**
     * Sugerencia simple en espanol segun el error de PostgreSQL/MySQL,
     * al estilo de los "hints" de pgAdmin.
     */
    private function sqlErrorHint(string $detail, string $sqlstate): string
    {
        $detailLower = mb_strtolower($detail);

        if (str_contains($detailLower, 'syntax error')) {
            return 'Revisa la sintaxis del comando: falta alguna palabra clave, coma, parentesis o hay un error de ortografia.';
        }

        if (str_contains($detailLower, 'does not exist')) {
            return 'El objeto (tabla, columna, esquema o tipo) no existe. Verifica el nombre o crea el objeto primero.';
        }

        if (str_contains($detailLower, 'already exists')) {
            return 'El objeto ya existe. Usa CREATE OR REPLACE / ALTER, o borra el objeto antes de crearlo de nuevo.';
        }

        if (str_contains($detailLower, 'permission denied') || $sqlstate === '42501') {
            return 'El usuario de conexion no tiene permisos para esa operacion en el esquema actual.';
        }

        if (str_contains($detailLower, 'column') && str_contains($detailLower, 'does not exist')) {
            return 'La columna indicada no existe en esa tabla. Revisa el nombre de la columna.';
        }

        if (str_contains($detailLower, 'relation') && str_contains($detailLower, 'does not exist')) {
            return 'La tabla indicada no existe en el esquema actual (public por defecto).';
        }

        if ($sqlstate === '23505') {
            return 'Violacion de clave unica: ya existe una fila con ese valor en la columna unique/primary key.';
        }

        if ($sqlstate === '23503') {
            return 'Violacion de llave foranea: el valor referenciado no existe en la tabla padre, o tiene hijos que lo referencian.';
        }

        if ($sqlstate === '23502') {
            return 'Violacion de not null: se intento insertar NULL en una columna que no lo permite.';
        }

        return '';
    }

    private function routeRows(): array
    {
        $rows = [];
        $originMap = $this->routeOriginMap();

        foreach (Route::all() as $route) {
            $area = str_starts_with($route['uri'], self::ADMIN_PREFIX) ? 'admin' : 'frontend';
            $key = $route['name'] ?? $route['uri'];
            $origin = $originMap[$key] ?? null;

            $rows[] = [
                'method' => $route['method'],
                'uri' => '/' . $route['uri'],
                'name' => $route['name'],
                'middleware' => $route['middleware'],
                'area' => $area,
                'key' => $key,
                'enabled' => System::routeEnabled($key),
                'origin_file' => $origin['file'] ?? ($area === 'admin' ? 'routes/app.php' : 'routes/web.php'),
                'origin_line' => $origin['line'] ?? null,
            ];
        }

        return $rows;
    }

    private function routeOriginMap(): array
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $map = [];

        foreach (['app', 'web'] as $file) {
            $path = BASE_PATH . '/routes/' . $file . '.php';

            if (!is_file($path)) {
                continue;
            }

            $lines = file($path) ?: [];

            foreach ($lines as $index => $line) {
                if (preg_match("/->name\(\s*'([^']+)'\s*\)/", $line, $matches)) {
                    $name = $matches[1];

                    if (!isset($map[$name])) {
                        $map[$name] = [
                            'file' => 'routes/' . $file . '.php',
                            'line' => $index + 1,
                        ];
                    }
                }
            }
        }

        return $map;
    }

    private function findRouteByKey(string $key): ?array
    {
        foreach (Route::all() as $route) {
            if (($route['name'] ?? $route['uri']) === $key) {
                return $route;
            }
        }

        return null;
    }

    private function homeCandidates(): array
    {
        $candidates = [];

        foreach (Route::all() as $route) {
            if (str_starts_with($route['uri'], self::ADMIN_PREFIX) || $route['name'] === null) {
                continue;
            }

            if ($route['method'] !== 'GET') {
                continue;
            }

            $candidates[] = [
                'name' => $route['name'],
                'uri' => '/' . $route['uri'],
            ];
        }

        return $candidates;
    }

    private function tables(): array
    {
        try {
            if (Database::isPgsql()) {
                $sql = "SELECT c.relname AS name,
                               COALESCE(am.amname, 'table') AS engine,
                               GREATEST(c.reltuples, 0)::BIGINT AS rows_estimate,
                               ROUND(pg_total_relation_size(c.oid) / 1024.0, 1) AS size_kb,
                               NULL AS collation
                        FROM pg_class c
                        JOIN pg_namespace n ON n.oid = c.relnamespace
                        LEFT JOIN pg_am am ON am.oid = c.relam
                        WHERE n.nspname = current_schema() AND c.relkind = 'r'
                        ORDER BY c.relname";
            } else {
                $sql = 'SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_ROWS AS rows_estimate,
                               ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1) AS size_kb, TABLE_COLLATION AS collation
                        FROM information_schema.TABLES
                        WHERE TABLE_SCHEMA = DATABASE()
                        ORDER BY TABLE_NAME';
            }

            return Database::connection()->query($sql)->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function safeCount(callable $counter): ?int
    {
        try {
            return (int) $counter();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function panel(Request $request): Response
    {
        return $this->render('dashboard.system.panel', 'Bienvenido al panel', 'Panel', [
            'nombre' => (string) Session::get('nombre', 'Usuario'),
        ]);
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
