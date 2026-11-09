<?php

declare(strict_types=1);

namespace Lib\Support;

use Dotenv\Exception\ValidationException;
use Lib\Core\Response;
use Lib\Core\View;

/**
 * Panel de configuracion inicial de base de datos (primera vez).
 *
 * Se muestra UNICAMENTE cuando bootstrap no logra conectar a la base de
 * datos (o cuando faltan parametros requeridos del .env). En un sitio
 * sano esta clase nunca se ejecuta; en CLI la excepción original se
 * relanza para no alterar scripts de consola ni tests.
 */
class DbSetup
{
    private const REQUIRED_KEYS = ['APP_NAME', 'APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

    private const DRIVERS = ['mysql', 'pgsql'];

    private const SENSITIVE_PATTERN = '/(PASSWORD|SECRET|TOKEN|KEY|SALT)/i';

    public static function handle(\Throwable $error): never
    {
        if (PHP_SAPI === 'cli') {
            throw $error;
        }

        $envMode = $error instanceof ValidationException;

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (($_POST['_bd_setup'] ?? '') === '1')) {
            self::handlePost($envMode);
        }

        self::render($envMode, $error->getMessage());
    }

    private static function handlePost(bool $envMode): never
    {
        $isXhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $action = (string) ($_POST['action'] ?? 'save');

        if (!Csrf::validate(isset($_POST['_token']) ? (string) $_POST['_token'] : null)) {
            self::abort($isXhr, 'La sesion expiro. Recarga la pagina e intenta otra vez.', 419, $envMode);
        }

        $env = self::readEnvFile();
        $cfg = self::candidate($env);

        $validationError = self::validateCandidate($cfg);
        if ($validationError !== null) {
            self::abort($isXhr, $validationError, 422, $envMode);
        }

        if ($action === 'test') {
            [$ok, $message, $ms] = self::probe($cfg);

            $payload = ['success' => $ok, 'message' => $message];

            if ($ms !== null) {
                $payload['ms'] = $ms;
            }

            if (!$ok) {
                $diagnosis = self::diagnose($message);
                if ($diagnosis !== null) {
                    $payload['hint'] = $diagnosis['pista'];
                    $payload['categoria'] = $diagnosis['categoria'];
                }
            }

            Response::json($payload, $ok ? 200 : 422)->send();
        }

        $appName = '';
        $appUrl = '';

        if ($envMode) {
            $appName = trim((string) ($_POST['app_name'] ?? ''));
            $appUrl = trim((string) ($_POST['app_url'] ?? ''));

            if ($appName === '' || $appUrl === '') {
                self::abort($isXhr, 'Completa los parametros APP_NAME y APP_URL para continuar.', 422, $envMode);
            }
        }

        [$ok, $message] = self::probe($cfg);

        if (!$ok) {
            self::abort($isXhr, 'La conexion no fue valida: ' . $message, 422, $envMode);
        }

        $values = [
            'DB_CONNECTION' => $cfg['connection'],
            'DB_HOST' => $cfg['host'],
            'DB_PORT' => $cfg['port'],
            'DB_DATABASE' => $cfg['database'],
            'DB_USERNAME' => $cfg['username'],
            'DB_PASSWORD' => $cfg['password'],
            'DB_CHARSET' => $cfg['charset'],
        ];

        if ($envMode) {
            $values['APP_NAME'] = $appName;
            $values['APP_URL'] = rtrim($appUrl, '/');
        }

        try {
            self::writeEnv($values);
        } catch (\Throwable $e) {
            error_log('DB setup: no se pudo escribir .env — ' . $e->getMessage());
            self::abort($isXhr, 'No se pudo escribir el archivo .env: ' . $e->getMessage(), 500, $envMode);
        }

        // Forzar la re-ejecucion idempotente del setup minimo (config/setup.php)
        // en la proxima peticion, por si la base quedo vacia o cambio de nombre.
        $marker = BASE_PATH . '/storage/.installed';
        if (is_file($marker)) {
            @unlink($marker);
        }

        error_log(sprintf(
            'DB setup: parametros guardados (conexion=%s, host=%s, puerto=%s, base=%s, usuario=%s)',
            $cfg['connection'],
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['username']
        ));

        if ($isXhr) {
            Response::json(['success' => true, 'message' => 'Conexion guardada. Recargando…'])->send();
        }

        Response::redirect((string) ($_SERVER['REQUEST_URI'] ?? '/'))->send();
    }

    private static function abort(bool $isXhr, string $message, int $status, bool $envMode): never
    {
        if ($isXhr) {
            Response::json(['success' => false, 'message' => $message], $status)->send();
        }

        self::render($envMode, $message, $_POST, $status);
    }

    private static function render(bool $envMode, string $error, ?array $post = null, int $status = 200): never
    {
        $env = self::readEnvFile();

        $missing = [];
        foreach (self::REQUIRED_KEYS as $key) {
            if (($env[$key] ?? '') === '') {
                $missing[] = $key;
            }
        }

        $database = config('database');
        $driver = (string) ($database['default'] ?? 'mysql');
        $knownDriver = in_array($driver, self::DRIVERS, true);

        if (!$knownDriver) {
            $driver = 'pgsql';
        }

        $connection = $database['connections'][$driver] ?? [];

        $values = [
            'connection' => $driver,
            'host' => (string) ($connection['host'] ?? 'localhost'),
            'port' => (string) ($connection['port'] ?? ''),
            'database' => (string) ($connection['database'] ?? ''),
            'username' => (string) ($connection['username'] ?? ''),
            'charset' => (string) ($connection['charset'] ?? 'utf8mb4'),
            'app_name' => (string) ($env['APP_NAME'] ?? ''),
            'app_url' => (string) ($env['APP_URL'] ?? ''),
        ];

        if ($post !== null) {
            foreach (array_keys($values) as $key) {
                if (isset($post[$key])) {
                    $values[$key] = (string) $post[$key];
                }
            }
        }

        $rows = [];
        foreach ($env as $key => $value) {
            $secret = preg_match(self::SENSITIVE_PATTERN, (string) $key) === 1;

            if ($secret) {
                $display = $value === '' ? '(vacio)' : '••••••••';
            } else {
                $display = $value === '' ? '(vacio)' : (string) $value;
            }

            $rows[] = ['key' => (string) $key, 'value' => $display, 'secret' => $secret];
        }

        // Detalle real del fallo actual: se vuelve a probar con la
        // configuracion activa para mostrar el motivo (el error de
        // bootstrap es generico; el SQLSTATE real solo va al log).
        $detail = null;
        $diagnosis = null;

        if (!$envMode && $knownDriver) {
            [$probeOk, $probeMessage] = self::probe([
                'connection' => $driver,
                'host' => $values['host'],
                'port' => $values['port'],
                'database' => $values['database'],
                'username' => $values['username'],
                'password' => (string) ($env['DB_PASSWORD'] ?? ''),
                'charset' => $values['charset'],
            ]);

            if (!$probeOk) {
                $detail = $probeMessage;
                $diagnosis = self::diagnose($probeMessage);
            }
        }

        $assetBase = self::assetBase($env);

        $html = View::render('setup.database', [
            'error' => $error,
            'detail' => $detail,
            'diagnosis' => $diagnosis,
            'missing' => $missing,
            'envRows' => $rows,
            'envMode' => $envMode,
            'values' => $values,
            'passwordSet' => ($env['DB_PASSWORD'] ?? '') !== '',
            'assetBase' => $assetBase,
        ]);

        Response::make($html, $status)->send();
    }

    private static function assetBase(array $env): string
    {
        $appUrl = trim((string) ($env['APP_URL'] ?? ''));

        if ($appUrl !== '') {
            return rtrim($appUrl, '/');
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = ($host !== null && $host !== '') ? $host : 'localhost';

        return ($https ? 'https' : 'http') . '://' . $host;
    }

    private static function candidate(array $env): array
    {
        $connection = (string) ($_POST['connection'] ?? $env['DB_CONNECTION'] ?? 'mysql');

        if (!in_array($connection, self::DRIVERS, true)) {
            $connection = 'pgsql';
        }

        $defaultPort = $connection === 'mysql' ? '3306' : '5432';
        $port = trim((string) ($_POST['port'] ?? ''));

        if ($port === '') {
            $port = (string) ($env['DB_PORT'] ?? $defaultPort);
        }

        $password = isset($_POST['password']) ? trim((string) $_POST['password']) : null;

        if ($password === null || $password === '') {
            $password = (string) ($env['DB_PASSWORD'] ?? '');
        }

        $sanitize = static fn (string $value): string => str_replace(["\r", "\n"], '', $value);

        return [
            'connection' => $connection,
            'host' => $sanitize(trim((string) ($_POST['host'] ?? ($env['DB_HOST'] ?? '')))),
            'port' => $sanitize($port),
            'database' => $sanitize(trim((string) ($_POST['database'] ?? ($env['DB_DATABASE'] ?? '')))),
            'username' => $sanitize(trim((string) ($_POST['username'] ?? ($env['DB_USERNAME'] ?? '')))),
            'password' => $sanitize($password),
            'charset' => $sanitize(trim((string) ($_POST['charset'] ?? ($env['DB_CHARSET'] ?? 'utf8mb4')))) ?: 'utf8mb4',
        ];
    }

    private static function validateCandidate(array $cfg): ?string
    {
        if ($cfg['database'] === '') {
            return 'Indica el nombre de la base de datos.';
        }

        if ($cfg['host'] === '') {
            return 'Indica el host de la base de datos.';
        }

        if ($cfg['username'] === '') {
            return 'Indica el usuario de la base de datos.';
        }

        if ($cfg['port'] !== '' && ctype_digit($cfg['port']) !== true) {
            return 'El puerto debe ser un numero.';
        }

        return null;
    }

    /**
     * Prueba real de conexion con los datos candidatos (sin tocar el
     * estado de Lib\Core\Database ni el .env actual).
     *
     * @return array{0: bool, 1: string, 2: int|null} [ok, mensaje, milisegundos]
     */
    public static function probe(array $cfg): array
    {
        $inicio = microtime(true);

        try {
            $pdo = self::openPdo($cfg);
            $pdo->query('SELECT 1');

            $ms = (int) round((microtime(true) - $inicio) * 1000);

            return [true, 'Conexion establecida correctamente con la base de datos.', $ms];
        } catch (\Throwable $e) {
            $ms = (int) round((microtime(true) - $inicio) * 1000);
            error_log('DB setup: prueba de conexion fallida — ' . $e->getMessage());

            $message = trim($e->getMessage());

            return [false, $message !== '' ? $message : 'Error desconocido al conectar.', $ms];
        }
    }

    /**
     * Clasifica un error de conexion PDO en una categoria accionable
     * con una pista concreta para resolverlo.
     *
     * @return array{categoria: string, pista: string}|null
     */
    public static function diagnose(?string $message): ?array
    {
        if ($message === null || trim($message) === '') {
            return null;
        }

        $msg = mb_strtolower(trim($message));

        // --- Base de datos inexistente ---
        if (
            str_contains($msg, '3d000')
            || str_contains($msg, 'does not exist')
            || str_contains($msg, 'unknown database')
            || str_contains($msg, 'no existe')
            || str_contains($msg, "can't create database")
        ) {
            return [
                'categoria' => 'base_inexistente',
                'pista' => 'La base de datos no existe en ese servidor. Créala primero (pgAdmin / psql / MySQL Workbench) y vuelve a probar.',
            ];
        }

        // --- Autenticacion ---
        if (
            str_contains($msg, '28000')
            || str_contains($msg, '28p01')
            || str_contains($msg, 'access denied')
            || str_contains($msg, 'password authentication failed')
            || str_contains($msg, 'invalid authorization')
            || str_contains($msg, 'ident authentication failed')
            || str_contains($msg, 'authent')
        ) {
            return [
                'categoria' => 'autenticacion',
                'pista' => 'Usuario o contraseña incorrectos (o el usuario no tiene permiso desde ese host). Verifica DB_USERNAME y DB_PASSWORD.',
            ];
        }

        // --- Host/puerto inalcanzable (timeout, refused, DNS) ---
        if (
            str_contains($msg, '08006')
            || str_contains($msg, '08001')
            || str_contains($msg, 'connection refused')
            || str_contains($msg, 'could not translate host name')
            || str_contains($msg, 'name or service not known')
            || str_contains($msg, 'no such file or directory')
            || str_contains($msg, 'getaddrinfo')
            || str_contains($msg, 'timeout')
            || str_contains($msg, 'timed out')
            || str_contains($msg, 'actively refused')
            || str_contains($msg, 'unreachable')
            || str_contains($msg, 'network is unreachable')
            || str_contains($msg, 'no se puede conectar')
        ) {
            return [
                'categoria' => 'red',
                'pista' => 'El servidor no responde en ese host/puerto. Revisa que el gestor esté corriendo, el puerto sea correcto y (en Docker) usar host.docker.internal o el nombre del servicio.',
            ];
        }

        // --- SSL ---
        if (
            str_contains($msg, 'ssl')
            || str_contains($msg, 'pg_hba')
            || str_contains($msg, 'certificate')
        ) {
            return [
                'categoria' => 'ssl',
                'pista' => 'El servidor rechaza la conexión por SSL / pg_hba.conf. Revisa la política de seguridad del gestor o habilita SSL adecuadamente.',
            ];
        }

        // --- Demasiadas conexiones ---
        if (
            str_contains($msg, 'too many connections')
            || str_contains($msg, 'too many clients')
        ) {
            return [
                'categoria' => 'recursos',
                'pista' => 'El servidor alcanzó su límite de conexiones. Libera conexiones o aumenta max_connections / max_connections del gestor.',
            ];
        }

        // --- Permisos sobre el objeto ---
        if (
            str_contains($msg, 'permission denied')
            || str_contains($msg, '42501')
        ) {
            return [
                'categoria' => 'permisos',
                'pista' => 'El usuario conecta pero no tiene permisos sobre ese objeto. Revisa los privilegios (GRANT) del usuario en la base.',
            ];
        }

        return [
            'categoria' => 'desconocido',
            'pista' => 'No se pudo clasificar el error. Revisa el mensaje técnico y el log del servidor de base de datos.',
        ];
    }

    private static function openPdo(array $cfg): \PDO
    {
        $port = $cfg['port'] !== '' ? $cfg['port'] : ($cfg['connection'] === 'mysql' ? '3306' : '5432');

        if ($cfg['connection'] === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'],
                $port,
                $cfg['database'],
                $cfg['charset'] !== '' ? $cfg['charset'] : 'utf8mb4'
            );

            return new \PDO($dsn, $cfg['username'], $cfg['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;connect_timeout=5', $cfg['host'], $port, $cfg['database']);

        return new \PDO($dsn, $cfg['username'], $cfg['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * Lee el .env actual como pares clave=valor (solo para mostrar
     * informacion y prellenar el formulario; nunca escribe).
     */
    public static function readEnvFile(?string $path = null): array
    {
        $path ??= BASE_PATH . '/.env';

        if (!is_file($path)) {
            return [];
        }

        $pairs = [];
        $lines = preg_split('/\r\n|\r|\n/', (string) file_get_contents($path)) ?: [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*?)\s*$/', $line, $m) !== 1) {
                continue;
            }

            $value = $m[2];
            $length = strlen($value);

            if ($length >= 2) {
                $quote = $value[0];

                if (($quote === '"' || $quote === "'") && str_ends_with($value, $quote)) {
                    $value = substr($value, 1, -1);
                }
            }

            $pairs[$m[1]] = $value;
        }

        return $pairs;
    }

    /**
     * Escribe/actualiza claves del .env preservando comentarios,
     * orden y el resto de parametros. No toca valores no listados.
     * Antes de sobrescribir crea un backup con fecha y conserva
     * los 5 mas recientes.
     */
    public static function writeEnv(array $values, ?string $path = null): void
    {
        $path ??= BASE_PATH . '/.env';

        if (is_file($path)) {
            self::backupEnv($path);
            $content = (string) file_get_contents($path);
        } else {
            $example = BASE_PATH . '/.env.example';
            $content = is_file($example)
                ? (string) file_get_contents($example)
                : "APP_ENV=local\nAPP_DEBUG=true\n";
            $content = rtrim($content) . PHP_EOL;
        }

        foreach ($values as $key => $value) {
            $quoted = self::quoteValue((string) $value);
            $pattern = '/^([ \t]*(?:export[ \t]+)?' . preg_quote((string) $key, '/') . '[ \t]*=).*/m';

            if (preg_match($pattern, $content) === 1) {
                $content = preg_replace_callback(
                    $pattern,
                    static fn (array $m): string => $m[1] . $quoted,
                    $content,
                    1
                );
            } else {
                if (!str_ends_with($content, "\n")) {
                    $content .= PHP_EOL;
                }

                $content .= $key . '=' . $quoted . PHP_EOL;
            }
        }

        $tmp = $path . '.tmp.' . getmypid();

        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            throw new \RuntimeException('no se pudo escribir el archivo temporal');
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);

            if (file_put_contents($path, $content, LOCK_EX) === false) {
                throw new \RuntimeException('no se pudo sobrescribir .env');
            }
        }
    }

    /**
     * Crea un backup del .env con fecha y hora antes de sobrescribirlo.
     * Conserva los 5 backups mas recientes y elimina los demas.
     */
    private static function backupEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $backup = $path . '.bak-' . date('Ymd-His');

        if (@copy($path, $backup) !== true) {
            error_log('DB setup: no se pudo crear backup de .env');
            return;
        }

        // Limitar a los 5 backups mas recientes
        $dir = dirname($path);
        $patron = basename($path) . '.bak-*';
        $backups = glob($dir . '/' . $patron) ?: [];

        if (count($backups) <= 5) {
            return;
        }

        usort($backups, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        foreach (array_slice($backups, 5) as $viejo) {
            @unlink($viejo);
        }
    }

    /**
     * Codifica un valor en formato valido para phpdotenv:
     * sin comillas si es simple, comillas simples si contiene
     * caracteres especiales, comillas dobles como alternativa.
     */
    public static function quoteValue(string $value): string
    {
        if ($value === '') {
            return "''";
        }

        if (preg_match('/^[^\s\'"#$\\\\]+$/', $value) === 1) {
            return $value;
        }

        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        if (!str_contains($value, '"') && !str_contains($value, '\\') && !str_contains($value, '$')) {
            return '"' . $value . '"';
        }

        throw new \InvalidArgumentException(
            'el valor contiene caracteres no soportados en .env (apostrofos junto con signos $ o comillas)'
        );
    }
}
