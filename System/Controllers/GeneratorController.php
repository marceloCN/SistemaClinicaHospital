<?php

declare(strict_types=1);

namespace System\Controllers;

use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;
use Lib\Support\Session;

/**
 * Autogestion del CMS: explorador de codigo y generadores.
 *
 * - scan()/source(): leen y explican los controllers, middlewares y
 *   FormRequests existentes en App/ (solo lectura).
 * - preview()/create(): generan archivos NUEVOS en App/Middleware,
 *   App/Requests o App/Controllers. Nunca sobreescribe archivos existentes
 *   ni toca ningun archivo previo de App/.
 *
 * Rutas (routes/app.php): /join/access/dashboard/Area-of-work/system/generators
 */
class GeneratorController extends Controller
{
    /** Tipos de generador soportados. */
    private const TYPES = ['middleware', 'request', 'controller'];

    /** Subcarpeta de destino por tipo dentro de App/. */
    private const TARGET_DIR = [
        'middleware' => 'App/Middleware',
        'request' => 'App/Requests',
        'controller' => 'App/Controllers',
    ];

    /** Sufijo de nombre de clase por tipo (el usuario escribe la base). */
    private const CLASS_SUFFIX = [
        'middleware' => '',
        'request' => 'Request',
        'controller' => 'Controller',
    ];

    /** Plantillas de handle() para middlewares. */
    private const MIDDLEWARE_TEMPLATES = ['auth', 'rol', 'permiso', 'custom'];

    /** Metodos estandar que se pueden incluir en un controlador. */
    private const CONTROLLER_METHODS = ['index', 'store', 'show', 'update', 'destroy'];

    public function index(Request $request): Response
    {
        if ($response = $this->adminPage()) {
            return $response;
        }

        return $this->render('dashboard.system.generators', 'Autogestión del CMS', 'Sistema / Generadores', [
            'middlewareTemplates' => self::MIDDLEWARE_TEMPLATES,
            'controllerMethods' => self::CONTROLLER_METHODS,
            'csrf' => csrf_token(),
        ]);
    }

    /* ==================================================================
       EXPLORADOR DE CODIGO (solo lectura)
       ================================================================== */

    /**
     * Lista las clases de App/ (controllers, middlewares, requests) con
     * sus metodos, firmas y docblocks. Solo lectura.
     */
    public function scan(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        // Solo lectura: no exige CSRF.
        $result = [];

        foreach (self::TYPES as $type) {
            $result[$type] = $this->scanDir($type);
        }

        return $this->json(['success' => true, 'types' => $result]);
    }

    /**
     * Devuelve el codigo fuente de una clase existente y el analisis de
     * flujo de cada metodo (a que llama, que devuelve, por donde pasa).
     */
    public function source(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        // Solo lectura: no exige CSRF.
        $type = (string) $request->input('type', '');
        $class = trim((string) $request->input('class', ''));

        if (!in_array($type, self::TYPES, true)) {
            return $this->json(['success' => false, 'message' => 'Tipo no valido.'], 422);
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $class) !== 1) {
            return $this->json(['success' => false, 'message' => 'Nombre de clase no valido.'], 422);
        }

        $absolute = BASE_PATH . '/' . self::TARGET_DIR[$type] . '/' . $class . '.php';

        if (!is_file($absolute)) {
            return $this->json(['success' => false, 'message' => 'No existe la clase ' . $class . '.'], 404);
        }

        $code = (string) file_get_contents($absolute);
        $methods = $this->extractMethods($code);

        foreach ($methods as $i => $method) {
            $body = $this->extractMethodBody($code, $method['name']);
            $methods[$i]['code'] = $body ?? '';
            $methods[$i]['flow'] = $this->analyzeFlow((string) $body);
        }

        // No se devuelve el archivo completo ('code'): pesa demasiado (ej:
        // PortalAuthController ~58KB) y la vista solo usa el cuerpo de cada
        // metodo (m.code), ya incluido en 'methods'.
        // utf8Safe(): si algun extractor dejara un multibyte cortado, json_encode
        // fallaria ('Malformed UTF-8') y devolveria un body vacio con status 200.
        return $this->json($this->utf8Safe([
            'success' => true,
            'type' => $type,
            'class' => $class,
            'file' => self::TARGET_DIR[$type] . '/' . $class . '.php',
            'methods' => $methods,
        ]));
    }

    /* ==================================================================
       GENERADORES (solo crea archivos NUEVOS)
       ================================================================== */

    /**
     * Devuelve el codigo generado (para la vista previa en vivo).
     */
    public function preview(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        // Solo lectura (no escribe archivos): no exige CSRF.
        $type = (string) $request->input('type', '');
        $input = $this->readInput($request);

        if (!in_array($type, self::TYPES, true)) {
            return $this->json(['success' => false, 'message' => 'Tipo de generador no valido.'], 422);
        }

        $validation = $this->validateInput($type, $input);

        if ($validation !== null) {
            return $this->json(['success' => false, 'message' => $validation], 422);
        }

        $className = $this->className($type, (string) $input['name']);
        $code = $this->buildCode($type, $input, $className);

        return $this->json($this->utf8Safe([
            'success' => true,
            'class' => $className,
            'file' => $this->relativePath($type, $className),
            'code' => $code,
            'integration' => $this->integrationHint($type, $className, $input),
        ]));
    }

    /**
     * Crea el archivo nuevo en App/ (solo si no existe).
     */
    public function create(Request $request): Response
    {
        if ($response = $this->adminJson()) {
            return $response;
        }

        if ($response = $this->validatePost($request)) {
            return $response;
        }

        $type = (string) $request->input('type', '');
        $input = $this->readInput($request);

        if (!in_array($type, self::TYPES, true)) {
            return $this->json(['success' => false, 'message' => 'Tipo de generador no valido.'], 422);
        }

        $validation = $this->validateInput($type, $input);

        if ($validation !== null) {
            return $this->json(['success' => false, 'message' => $validation], 422);
        }

        $className = $this->className($type, (string) $input['name']);
        $absolute = $this->absolutePath($type, $className);

        if (is_file($absolute)) {
            return $this->json([
                'success' => false,
                'message' => "El archivo ya existe: {$this->relativePath($type, $className)}. No se sobreescribe.",
            ], 409);
        }

        $dir = dirname($absolute);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return $this->json(['success' => false, 'message' => 'No se pudo crear la carpeta destino.'], 500);
        }

        $code = $this->buildCode($type, $input, $className);

        if (@file_put_contents($absolute, $code, LOCK_EX) === false) {
            return $this->json(['success' => false, 'message' => 'No se pudo escribir el archivo.'], 500);
        }

        error_log(sprintf(
            'Generator: creado %s (%s) por usuario del panel',
            $this->relativePath($type, $className),
            Session::get('email', 'desconocido')
        ));

        return $this->json([
            'success' => true,
            'message' => "Archivo creado: {$this->relativePath($type, $className)}",
            'file' => $this->relativePath($type, $className),
            'class' => $className,
            'integration' => $this->integrationHint($type, $className, $input),
        ]);
    }

    /* ==================================================================
       PARSEO Y ANALISIS DE FLUJO
       ================================================================== */

    /** Escanea una carpeta de App/ y describe cada clase. */
    private function scanDir(string $type): array
    {
        $dir = BASE_PATH . '/' . self::TARGET_DIR[$type];

        if (!is_dir($dir)) {
            return [];
        }

        $items = [];

        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $class = basename($file, '.php');

            // El controlador base es abstracto: se lista aparte, no como modulo.
            $code = (string) file_get_contents($file);
            $methods = $this->extractMethods($code);

            foreach ($methods as $i => $method) {
                $body = $this->extractMethodBody($code, $method['name']);
                $flow = $this->analyzeFlow((string) $body);
                $methods[$i]['summary'] = $this->shortSummary($flow, $type, $method['name']);
            }

            $items[] = [
                'class' => $class,
                'file' => self::TARGET_DIR[$type] . '/' . $class . '.php',
                'namespace' => $this->extractNamespace($code),
                'extends' => $this->extractExtends($code),
                'abstract' => (bool) preg_match('/abstract\s+class\b/', $code),
                'docblock' => $this->extractClassDocblock($code),
                'methods' => $methods,
            ];
        }

        // Los abstractos/base al final (no son modulos accionables).
        usort($items, static fn (array $a, array $b): int => ($a['abstract'] <=> $b['abstract']) ?: strcmp($a['class'], $b['class']));

        return $items;
    }

    /** Extrae namespace de un archivo PHP. */
    private function extractNamespace(string $code): string
    {
        return preg_match('/namespace\s+([^;]+);/', $code, $m) ? trim($m[1]) : '';
    }

    /** Extrae la clase de la que hereda (si hereda). */
    private function extractExtends(string $code): string
    {
        return preg_match('/class\s+\w+\s+extends\s+([A-Za-z_\\\\]\w*)/', $code, $m) ? $m[1] : '';
    }

    /** Extrae el docblock de la clase. */
    private function extractClassDocblock(string $code): string
    {
        if (!preg_match('#/\*\*(.*?)\*/\s*(?:abstract\s+|final\s+)?class\b#s', $code, $m)) {
            return '';
        }

        $lines = preg_split('/\R/', trim($m[1])) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t*");
            $line = preg_replace('/^@param\b.*$/', '', $line) ?? $line;
            $line = trim($line);

            if ($line !== '' && !str_starts_with($line, '@')) {
                $clean[] = $line;
            }
        }

        return implode(' ', $clean);
    }

    /** Extrae metodos (nombre, firma, visibilidad, docblock). */
    private function extractMethods(string $code): array
    {
        $methods = [];
        $pattern = '/((?:\/\*\*.*?\*\/\s*)?)((?:public|protected|private)\s+(?:static\s+)?function\s+&?\w+\s*\((?:[^()]|\([^()]*\))*\)\s*(?::\s*[\w\\\\|?]+)?)/s';

        if (preg_match_all($pattern, $code, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($matches as $match) {
            $docblock = isset($match[1]) ? trim($match[1]) : '';
            $signature = isset($match[2]) ? trim(preg_replace('/\s+/', ' ', $match[2]) ?? '') : '';

            if (!preg_match('/function\s+&?(\w+)/', $signature, $nameMatch)) {
                continue;
            }

            $visibility = 'public';

            if (str_contains($signature, 'private ')) {
                $visibility = 'private';
            } elseif (str_contains($signature, 'protected ')) {
                $visibility = 'protected';
            }

            $methods[] = [
                'name' => $nameMatch[1],
                'signature' => $signature,
                'visibility' => $visibility,
                'static' => str_contains($signature, 'static '),
                'docblock' => $this->cleanDocblock($docblock),
            ];
        }

        return $methods;
    }

    /** Limpia un docblock a texto plano (una sola linea). */
    private function cleanDocblock(string $docblock): string
    {
        if ($docblock === '') {
            return '';
        }

        $lines = preg_split('/\R/', $docblock) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line, " \t/*");
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '@')) {
                continue;
            }

            $clean[] = $line;
        }

        return implode(' ', $clean);
    }

    /** Extrae el cuerpo completo de un metodo (balanceando llaves). */
    private function extractMethodBody(string $code, string $name): ?string
    {
        $pos = strpos($code, 'function ' . $name . '(');

        if ($pos === false) {
            // Caso con referencia: function &name(
            $pos = strpos($code, 'function &' . $name . '(');
        }

        if ($pos === false) {
            return null;
        }

        $braceStart = strpos($code, '{', $pos);

        if ($braceStart === false) {
            return null;
        }

        $depth = 0;
        $len = strlen($code);

        for ($i = $braceStart; $i < $len; $i++) {
            if ($code[$i] === '{') {
                $depth++;
            } elseif ($code[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($code, $pos, $i - $pos + 1);
                }
            }
        }

        return null;
    }

    /**
     * Analiza el cuerpo de un metodo y devuelve el flujo: a que llama,
     * que vistas renderiza, que toca de BD y que devuelve.
     */
    private function analyzeFlow(string $body): array
    {
        if ($body === '') {
            return [];
        }

        $flow = [];
        $seen = [];

        // Llamadas internas $this->metodo(...)
        if (preg_match_all('/\$this->(\w+)\s*\(/', $body, $m)) {
            foreach (array_unique($m[1]) as $call) {
                if (in_array($call, ['json', 'view', 'validate'], true)) {
                    continue;
                }

                $key = 'internal:' . $call;

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $flow[] = ['type' => 'internal', 'target' => '$this->' . $call . '()'];
                }
            }
        }

        // Llamadas estaticas Clase::metodo(...)
        if (preg_match_all('/\b([A-Z]\w+)::(\w+)\s*\(/', $body, $m, PREG_SET_ORDER)) {
            foreach ($m as $call) {
                $key = 'static:' . $call[1] . '::' . $call[2];

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $flow[] = ['type' => 'static', 'target' => $call[1] . '::' . $call[2] . '()'];
                }
            }
        }

        // Objetos instanciados o excepciones lanzadas: new \Ns\Clase(...)
        if (preg_match_all('/\bnew\s+\\\\?([A-Za-z_\\\\]\w*)\s*\(/', $body, $m)) {
            foreach ($m[1] as $fqcn) {
                $parts = explode('\\', $fqcn);
                $short = end($parts);
                $key = 'new:' . $short;

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $flow[] = ['type' => 'new', 'target' => $short];
                }
            }
        }

        // Helpers globales del framework usados en el cuerpo.
        if (preg_match_all('/\b(route|csrf_token|config|asset)\s*\(/', $body, $m)) {
            foreach (array_unique($m[1]) as $fn) {
                $key = 'helper:' . $fn;

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $flow[] = ['type' => 'helper', 'target' => $fn . '()'];
                }
            }
        }

        // Vistas renderizadas View::render('x')
        if (preg_match_all('/View::render\(\s*[\'"]([^\'"]+)[\'"]/', $body, $m)) {
            foreach (array_unique($m[1]) as $view) {
                $key = 'view:' . $view;

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $flow[] = ['type' => 'view', 'target' => $view];
                }
            }
        }

        // Base de datos: Database::call('sp_...') o Database::query/table
        if (preg_match_all('/Database::(call|query|table|select)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $body, $m, PREG_SET_ORDER)) {
            foreach ($m as $db) {
                $key = 'db:' . $db[2];

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $flow[] = ['type' => 'db', 'target' => $db[1] . '("' . $db[2] . '")'];
                }
            }
        }

        // Sesion leida/escrita
        if (preg_match('/Session::(get|set|destroy|isLoggedIn)/', $body, $m)) {
            $flow[] = ['type' => 'session', 'target' => 'Session::' . $m[1] . '()'];
        }

        // FormRequest en la firma (ya capturado en signature, pero lo senalamos).
        if (preg_match('/\b(\w+Request)\s+\$/', $body, $m)) {
            $flow[] = ['type' => 'formrequest', 'target' => $m[1]];
        }

        // Retorno
        if (preg_match('/return\s+([^;]+);/', $body, $m)) {
            $return = trim(preg_replace('/\s+/', ' ', $m[1]) ?? '');

            if (mb_strlen($return, 'UTF-8') > 90) {
                // mb_substr: corta por caracteres, no por bytes. Con substr se
                // partian multibyte UTF-8 (ej: 'á' = C3 A1) a la mitad y eso
                // rompia json_encode() de todo el payload ('Malformed UTF-8').
                $return = mb_substr($return, 0, 90, 'UTF-8') . '…';
            }

            $flow[] = ['type' => 'return', 'target' => $return];
        }

        return $flow;
    }

    /** Resumen corto de un metodo (para el listado del explorador). */
    private function shortSummary(array $flow, string $type, string $name): string
    {
        if ($flow === []) {
            return $name === '__construct' ? 'Constructor de la clase.' : 'Sin flujo detectado.';
        }

        $bits = [];

        foreach ($flow as $f) {
            $bits[] = match ($f['type']) {
                'view' => 'vista ' . $f['target'],
                'internal' => 'llama a ' . $f['target'],
                'static' => $f['target'],
                'new' => 'nueva ' . $f['target'],
                'helper' => $f['target'],
                'db' => 'BD ' . $f['target'],
                'session' => 'sesión',
                'formrequest' => 'valida con ' . $f['target'],
                'return' => 'devuelve',
                default => $f['target'],
            };
        }

        $text = implode(' · ', array_slice($bits, 0, 4));

        if (mb_strlen($text, 'UTF-8') > 140) {
            return mb_substr($text, 0, 140, 'UTF-8') . '…';
        }

        return $text;
    }

    /** Explicacion en lenguaje natural del flujo de un metodo. */
    private function explainFlow(array $flow, string $type): string
    {
        if ($flow === []) {
            return 'Método sin lógica detectable en su cuerpo.';
        }

        $parts = [];
        $views = [];
        $internals = [];
        $statics = [];
        $news = [];
        $helpers = [];
        $dbs = [];
        $returns = [];
        $hasSession = false;
        $formRequest = '';

        foreach ($flow as $f) {
            match ($f['type']) {
                'view' => $views[] = $f['target'],
                'internal' => $internals[] = $f['target'],
                'static' => $statics[] = $f['target'],
                'new' => $news[] = $f['target'],
                'helper' => $helpers[] = $f['target'],
                'db' => $dbs[] = $f['target'],
                'session' => $hasSession = true,
                'formrequest' => $formRequest = $f['target'],
                'returns' => $returns[] = $f['target'],
                default => null,
            };

            if ($f['type'] === 'return') {
                $returns[] = $f['target'];
            }
        }

        if ($statics !== []) {
            $parts[] = 'Usa ' . $this->listOf($statics) . ' para obtener datos o ejecutar lógica.';
        }

        if ($dbs !== []) {
            $parts[] = 'Consulta la base de datos mediante ' . $this->listOf($dbs) . '.';
        }

        if ($internals !== []) {
            $parts[] = 'Delega el trabajo en ' . $this->listOf($internals) . ', métodos de esta misma clase.';
        }

        if ($news !== []) {
            $parts[] = 'Lanza o instancia ' . $this->listOf($news) . '.';
        }

        if ($helpers !== []) {
            $parts[] = 'Usa el helper global ' . $this->listOf($helpers) . '.';
        }

        if ($views !== []) {
            $parts[] = 'Renderiza la vista ' . $this->listOf($views) . ' como HTML del portal.';
        }

        if ($formRequest !== '') {
            $parts[] = 'Recibe un ' . $formRequest . ' tipado en su parámetro: el router valida rules()/authorize() antes de entrar y los datos limpios se leen con validated().';
        }

        if ($hasSession) {
            $parts[] = 'Lee o modifica datos de la sesión del usuario.';
        }

        if ($returns !== []) {
            $parts[] = 'Devuelve ' . $this->listOf($returns) . '.';
        }

        $intro = match ($type) {
            'controller' => 'Flujo del controlador: ',
            'middleware' => 'Flujo del filtro: ',
            'request' => 'Flujo de validación: ',
            default => '',
        };

        return $intro . implode(' ', $parts);
    }

    /** Helper: lista legible de elementos ("A, B y C"). */
    private function listOf(array $items): string
    {
        $items = array_values(array_unique($items));
        $last = array_pop($items);

        if ($items === []) {
            return '`' . $last . '`';
        }

        return '`' . implode('`, `', $items) . '` y `' . $last . '`';
    }

    /** Pista de integracion tras crear/visualizar una clase. */
    private function integrationHint(string $type, string $className, array $input): string
    {
        switch ($type) {
            case 'middleware':
                return 'Úsalo en rutas con ->middleware(\'' . $className . '\'). '
                    . 'El router llama a handle(Request $request): devuelve true para dejar pasar, '
                    . 'false o un Response para cortar (por ejemplo tras redirigir al login).';

            case 'request':
                $fields = $input['rules'] !== '' ? ' validated() te da solo los campos con regla.' : '';
                return 'Inyéctalo tipado en el método del controlador: public function store(' . $className . ' $request): Response {' . $fields . ' '
                    . 'El router ejecuta authorize() y rules() antes de tu código; si falla, responde 403/422 automáticamente.';

            case 'controller':
                return 'Registra sus rutas en routes/app.php apuntando a las acciones de ' . $className . '. '
                    . 'Cada método público es un endpoint accesible desde las rutas que definas.';

            default:
                return '';
        }
    }

    /* ==================================================================
       ENTRADA Y VALIDACION
       ================================================================== */

    private function readInput(Request $request): array
    {
        $rulesRaw = (string) $request->input('rules', '');
        $messagesRaw = (string) $request->input('messages', '');

        $methods = $request->input('methods', []);
        $methods = is_array($methods) ? $methods : [];

        return [
            'name' => trim((string) $request->input('name', '')),
            'description' => trim((string) $request->input('description', '')),
            'template' => trim((string) $request->input('template', 'custom')),
            'methods' => array_values(array_intersect($methods, self::CONTROLLER_METHODS)),
            'rules' => $rulesRaw,
            'messages' => $messagesRaw,
            'authorize_post' => (string) $request->input('authorize_post', '1') === '1',
            'authorize_csrf' => (string) $request->input('authorize_csrf', '1') === '1',
        ];
    }

    private function validateInput(string $type, array $input): ?string
    {
        if ($input['name'] === '') {
            return 'Indica el nombre de la clase.';
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $input['name']) !== 1) {
            return 'El nombre debe ser un identificador PHP valido (letras, numeros y guion bajo; sin espacios ni simbolos).';
        }

        if (strlen($input['name']) > 60) {
            return 'El nombre es demasiado largo (maximo 60 caracteres).';
        }

        if ($type === 'middleware' && !in_array($input['template'], self::MIDDLEWARE_TEMPLATES, true)) {
            return 'Plantilla de middleware no valida.';
        }

        if ($type === 'request') {
            if ($input['rules'] !== '' && $this->bracketsBalanced($input['rules']) === false) {
                return 'Las reglas (rules) tienen parentesis/corchetes sin cerrar.';
            }

            if ($input['messages'] !== '' && $this->bracketsBalanced($input['messages']) === false) {
                return 'Los mensajes (messages) tienen parentesis/corchetes sin cerrar.';
            }
        }

        if ($type === 'controller' && $input['methods'] === []) {
            return 'Selecciona al menos un metodo para el controlador.';
        }

        return null;
    }

    private function bracketsBalanced(string $code): bool
    {
        $open = substr_count($code, '[') + substr_count($code, '(') + substr_count($code, '{');
        $close = substr_count($code, ']') + substr_count($code, ')') + substr_count($code, '}');

        return $open === $close;
    }

    /* ==================================================================
       NOMBRES Y RUTAS
       ================================================================== */

    private function className(string $type, string $base): string
    {
        return $base . self::CLASS_SUFFIX[$type];
    }

    private function relativePath(string $type, string $className): string
    {
        return self::TARGET_DIR[$type] . '/' . $className . '.php';
    }

    private function absolutePath(string $type, string $className): string
    {
        return BASE_PATH . '/' . $this->relativePath($type, $className);
    }

    /* ==================================================================
       GENERACION DE CODIGO
       ================================================================== */

    private function buildCode(string $type, array $input, string $className): string
    {
        return match ($type) {
            'middleware' => $this->buildMiddleware($input, $className),
            'request' => $this->buildRequest($input, $className),
            'controller' => $this->buildController($input, $className),
            default => '',
        };
    }

    private function docblock(string $description, string $extra = ''): string
    {
        $lines = [];

        if ($description !== '') {
            $lines[] = ' * ' . $description;
        }

        if ($extra !== '') {
            if ($lines !== []) {
                $lines[] = ' *';
            }

            $lines[] = ' * ' . $extra;
        }

        if ($lines === []) {
            return '';
        }

        return "/**\n" . implode("\n", $lines) . "\n */\n";
    }

    /**
     * Middleware: handle(Request $request) recibe SIEMPRE la peticion actual
     * (hereda de Lib\Core\Request: isAjax(), isPost(), input(), all()...).
     * Devuelve true para continuar, false o Response para cortar.
     */
    private function buildMiddleware(array $input, string $className): string
    {
        $description = $input['description'];
        $uses = "use Lib\\Core\\Request;\nuse Lib\\Core\\Response;\n";
        $body = '';

        switch ($input['template']) {
            case 'auth':
                $description = $description !== '' ? $description : 'Exige sesion iniciada antes de ejecutar la ruta.';
                $uses .= 'use Lib\\Support\\Session;';
                $body = <<<'PHP'
        // 1) Comprueba la sesion (clave 'portal_loged' en el portal).
        if (!Session::isLoggedIn()) {
            // 2a) Si es una peticion AJAX, se corta con JSON 401.
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No autenticado.'], 401);
            }

            // 2b) Si es una pagina normal, se redirige al login y se corta.
            Response::redirect(route('portal_login') ?? '/portal/login')->send();
            return false;
        }

        // 3) Todo OK: true deja pasar la peticion al controlador.
        return true;
PHP;
                break;

            case 'rol':
                $description = $description !== '' ? $description : 'Exige una sesion iniciada con un rol concreto.';
                $uses .= 'use Lib\\Support\\Session;';
                $body = <<<'PHP'
        if (!Session::isLoggedIn()) {
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No autenticado.'], 401);
            }

            Response::redirect(route('portal_login') ?? '/portal/login')->send();
            return false;
        }

        // Clave del rol guardada al hacer login (ej: ADMIN, MEDICO...).
        $rolClave = (string) Session::get('portal_rol_clave', '');

        if ($rolClave !== 'ADMIN') {
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No tienes permiso para esta accion.'], 403);
            }

            return Response::make('<h1>403 - Acceso denegado</h1>', 403);
        }

        return true;
PHP;
                break;

            case 'permiso':
                $description = $description !== '' ? $description : 'Exige un permiso concreto del rol (o ser ADMIN).';
                $uses .= 'use Lib\\Support\\Session;';
                $body = <<<'PHP'
        if (!Session::isLoggedIn()) {
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No autenticado.'], 401);
            }

            Response::redirect(route('portal_login') ?? '/portal/login')->send();
            return false;
        }

        // El rol ADMIN pasa siempre todos los permisos.
        if ((string) Session::get('portal_rol_clave', '') === 'ADMIN') {
            return true;
        }

        // Lista de permisos cargada al login (claves o IDs como string).
        $permisos = (array) Session::get('portal_permisos', []);

        // Ajusta '120' por la clave o id del permiso que necesites (ej: 'pacientes.ver').
        if (!in_array('120', $permisos, true)) {
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'No tienes permiso para esta accion.'], 403);
            }

            return Response::make('<h1>403 - Acceso denegado</h1>', 403);
        }

        return true;
PHP;
                break;

            default: // custom
                $description = $description !== '' ? $description : 'Middleware personalizado (plantilla vacia).';
                $body = <<<'PHP'
        // $request es la peticion actual (Lib\Core\Request). Puedes leerla:
        //   $request->isAjax()  -> ¿es una llamada AJAX?
        //   $request->isPost()  -> ¿es un POST?
        //   $input('clave')     -> dato del formulario o query string
        //
        // Devuelve true  -> deja pasar al controlador.
        // Devuelve false -> corta la peticion.
        // Devuelve Response::json(...) / Response::redirect(...) -> corta con esa respuesta.

        return true;
PHP;
        }

        $doc = $this->docblock(
            $description,
            'handle() SIEMPRE recibe la peticion actual (Request $request) y debe devolver bool|Response. Uso en rutas: ->middleware(\'' . $className . '\').'
        );

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Middleware;

{$uses}

{$doc}class {$className}
{
    /**
     * Filtro que se ejecuta ANTES del controlador de la ruta.
     *
     * @param Request \$request Peticion actual (isAjax, isPost, input...).
     * @return bool|Response   true = continuar | false o Response = cortar.
     */
    public function handle(Request \$request): bool|Response
    {
{$body}
    }
}

PHP;
    }

    /**
     * FormRequest: solo pide nombre y reglas sugeridas. authorize() valida
     * metodo POST y token CSRF por defecto (igual que SavePermisoRequest).
     */
    private function buildRequest(array $input, string $className): string
    {
        $description = $input['description'] !== ''
            ? $input['description']
            : 'Validacion de la peticion (estilo Laravel).';

        $authorizeBody = "        return true;\n";

        if ($input['authorize_post'] || $input['authorize_csrf']) {
            $authorizeBody = "        ";

            if ($input['authorize_post']) {
                $authorizeBody .= "if (!\$this->isPost()) {\n";
                $authorizeBody .= "            throw new \\Lib\\Support\\AuthorizationException('Metodo no permitido.', 405);\n";
                $authorizeBody .= "        }\n\n        ";

                if ($input['authorize_csrf']) {
                    $authorizeBody .= "if (!\\Lib\\Support\\Csrf::validate(\$this->input('_token'))) {\n";
                    $authorizeBody .= "            throw new \\Lib\\Support\\AuthorizationException('Token CSRF invalido.');\n";
                    $authorizeBody .= "        }\n\n        ";
                }

                $authorizeBody .= "return true;\n";
            } elseif ($input['authorize_csrf']) {
                $authorizeBody = "        if (!\\Lib\\Support\\Csrf::validate(\$this->input('_token'))) {\n";
                $authorizeBody .= "            throw new \\Lib\\Support\\AuthorizationException('Token CSRF invalido.');\n";
                $authorizeBody .= "        }\n\n        return true;\n";
            }
        }

        $rules = trim($input['rules']);

        if ($rules === '') {
            $rules = "'campo' => 'required|min:3|max:100',";
        }

        $messages = trim($input['messages']);

        if ($messages === '') {
            $messages = "'campo.required' => 'El campo es obligatorio.',";
        }

        $doc = $this->docblock(
            $description,
            'Inyectalo tipado en el controlador: store(' . $className . ' $request). El router ejecuta authorize() y rules() antes de tu metodo.'
        );

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Requests;

use Lib\\Core\\FormRequest;

{$doc}class {$className} extends FormRequest
{
    /**
     * Si la peticion esta permitida. false = 403; lanzar AuthorizationException = ese status.
     */
    public function authorize(): bool
    {
{$authorizeBody}    }

    /**
     * Reglas por campo ('required', 'min:3', 'email'...).
     */
    public function rules(): array
    {
        return [
            {$rules}
        ];
    }

    /**
     * Mensajes personalizados por campo y regla.
     */
    public function messages(): array
    {
        return [
            {$messages}
        ];
    }
}

PHP;
    }

    /**
     * Controlador: solo nombre y metodos elegidos. Cada metodo explica su
     * proposito en el docblock para que sirva de referencia.
     */
    private function buildController(array $input, string $className): string
    {
        $description = $input['description'] !== ''
            ? $input['description']
            : 'Controlador del modulo.';

        $bodies = [];

        foreach ($input['methods'] as $method) {
            $bodies[] = $this->controllerMethod($method);
        }

        $doc = $this->docblock($description, 'Registra sus rutas en routes/app.php apuntando a las acciones que uses.');

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Controllers;

use App\\Controllers\\Controller;
use Lib\\Core\\Request;
use Lib\\Core\\Response;

{$doc}class {$className} extends Controller
{
{$this->joinMethods($bodies)}
}

PHP;
    }

    /** Cuerpo de cada metodo CRUD estandar, con su referencia en el docblock. */
    private function controllerMethod(string $method): string
    {
        switch ($method) {
            case 'index':
                return <<<'PHP'
    /**
     * Listado / pagina principal del modulo.
     * Corresponde a GET  /recurso
     * Flujo tipico: cargar datos -> $this->vista('ruta', datos) o json().
     */
    public function index(Request $request): Response
    {
        // TODO: cargar datos y devolver la vista.
        return $this->json(['success' => true, 'data' => []]);
    }

PHP;

            case 'store':
                return <<<'PHP'
    /**
     * Alta de un registro.
     * Corresponde a POST /recurso
     * Flujo tipico: el FormRequest tipado ya valido; validated() da los datos limpios -> guardar -> json().
     */
    public function store(Request $request): Response
    {
        // TODO: validar (ideal con un FormRequest) y guardar.
        return $this->json(['success' => true, 'message' => 'Creado correctamente.']);
    }

PHP;

            case 'show':
                return <<<'PHP'
    /**
     * Detalle de un registro.
     * Corresponde a GET  /recurso/{id}
     * Flujo tipico: buscar por $id -> si no existe 404 -> devolver datos.
     */
    public function show(int $id): Response
    {
        // TODO: cargar el registro por $id.
        return $this->json(['success' => true, 'id' => $id]);
    }

PHP;

            case 'update':
                return <<<'PHP'
    /**
     * Actualizacion de un registro.
     * Corresponde a POST /recurso/{id} (o PUT/PATCH)
     * Flujo tipico: FormRequest valida -> actualizar por $id -> json().
     */
    public function update(Request $request, int $id): Response
    {
        // TODO: validar y actualizar el registro $id.
        return $this->json(['success' => true, 'message' => 'Actualizado correctamente.']);
    }

PHP;

            case 'destroy':
            default:
                return <<<'PHP'
    /**
     * Baja de un registro.
     * Corresponde a DELETE /recurso/{id}
     * Flujo tipico: comprobar permiso -> eliminar por $id (o baja logica) -> json().
     */
    public function destroy(int $id): Response
    {
        // TODO: eliminar el registro $id.
        return $this->json(['success' => true, 'message' => 'Eliminado correctamente.']);
    }

PHP;
        }
    }

    private function joinMethods(array $bodies): string
    {
        return rtrim(implode("\n", $bodies), "\n");
    }

    /* ==================================================================
       AUTENTICACION, CSRF Y RENDER
       ================================================================== */

    private function validatePost(Request $request): ?Response
    {
        if (!$request->isPost()) {
            return $this->json(['success' => false, 'message' => 'Metodo no permitido.'], 405);
        }

        if (!Csrf::validate($request->input('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token invalido.'], 403);
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

    /**
     * Limpia bytes UTF-8 invalidos de todos los strings de un array (recursivo).
     * Si un extractor dejara un multibyte cortado por la mitad, json_encode()
     * fallaria ('Malformed UTF-8') y Response guardaria false -> body vacio con
     * status 200, que jQuery reporta como error de parse sin codigo HTTP.
     */
    private function utf8Safe(mixed $value): mixed
    {
        if (is_string($value)) {
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $k => $v) {
                $clean[$k] = $this->utf8Safe($v);
            }
            return $clean;
        }

        return $value;
    }
}
