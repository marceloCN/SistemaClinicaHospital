<?php

declare(strict_types=1);

namespace System\Controllers;

use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Session;

class DocsController extends Controller
{
    private const CHAPTERS = [
        [
            'id' => 'cap1',
            'number' => 1,
            'title' => 'Arquitectura de carpetas del CMS',
            'icon' => 'fas fa-sitemap',
            'view' => 'docs.cap01-arquitectura',
            'summary' => 'Qué hace cada carpeta y cada archivo, qué se toca y qué no, y en qué orden se resuelven las clases.',
        ],
        [
            'id' => 'cap2',
            'number' => 2,
            'title' => 'Arranque del sistema',
            'icon' => 'fas fa-power-off',
            'view' => 'docs.cap02-arranque',
            'summary' => 'El camino completo de una petición: index.php, bootstrap.php, .env, sesión, base de datos y rutas.',
        ],
        [
            'id' => 'cap3',
            'number' => 3,
            'title' => 'Rutas y CRUDs (web.php / app.php)',
            'icon' => 'fas fa-route',
            'view' => 'docs.cap03-rutas-crud',
            'summary' => 'Todos los métodos del router, grupos, prefijos, parámetros y el mapa para crear un CRUD completo.',
        ],
        [
            'id' => 'cap4',
            'number' => 4,
            'title' => 'Middlewares: usos, situaciones y métodos',
            'icon' => 'fas fa-shield-alt',
            'view' => 'docs.cap04-middlewares',
            'summary' => 'Cómo funciona la cadena de middlewares, los retornos de handle() y los escenarios típicos de protección.',
        ],
        [
            'id' => 'cap5',
            'number' => 5,
            'title' => 'Controladores y FormRequest',
            'icon' => 'fas fa-code',
            'view' => 'docs.cap05-controladores-requests',
            'summary' => 'Anatomía de un controlador, inyección de dependencias, validación por request y buenas prácticas.',
        ],
        [
            'id' => 'cap6',
            'number' => 6,
            'title' => 'Tokens, formularios y respuestas',
            'icon' => 'fas fa-paper-plane',
            'view' => 'docs.cap06-tokens-formularios',
            'summary' => 'El viaje de un formulario: CSRF, AJAX, códigos HTTP, el modal de errores y cómo depurar un POST.',
        ],
        [
            'id' => 'cap7',
            'number' => 7,
            'title' => 'Vistas, CSS, JS y librerías frontend',
            'icon' => 'fas fa-paint-brush',
            'view' => 'docs.cap07-vistas-frontend',
            'summary' => 'Dónde vive cada vista, cómo se compone el layout, dónde alojar tu CSS/JS y cómo añadir librerías.',
        ],
        [
            'id' => 'cap8',
            'number' => 8,
            'title' => 'Modelos y base de datos',
            'icon' => 'fas fa-database',
            'view' => 'docs.cap08-modelos-datos',
            'summary' => 'Model, consultas, casts, timestamps, procedimientos almacenados y el explorador de base de datos.',
        ],
        [
            'id' => 'cap9',
            'number' => 9,
            'title' => 'API interna, PDF y correos',
            'icon' => 'fas fa-plug',
            'view' => 'docs.cap09-pdf-correos',
            'summary' => 'Endpoints JSON, integración de librerías backend (dompdf/FPDF) y envío de correo por SMTP.',
        ],
        [
            'id' => 'cap10',
            'number' => 10,
            'title' => 'Panel del sistema: toggles y mantenimiento',
            'icon' => 'fas fa-tools',
            'view' => 'docs.cap10-panel-sistema',
            'summary' => 'Rutas y enlaces, middlewares, página de inicio, modo mantenimiento y base de datos con consola SQL.',
        ],
        [
            'id' => 'cap11',
            'number' => 11,
            'title' => 'Pruebas, depuración y errores comunes',
            'icon' => 'fas fa-first-aid',
            'view' => 'docs.cap11-pruebas-debug',
            'summary' => 'Cómo probar cada pieza, cómo depurar con el navegador y la tabla de síntomas con su solución.',
        ],
        [
            'id' => 'cap12',
            'number' => 12,
            'title' => 'Tutorial: un CRUD completo paso a paso',
            'icon' => 'fas fa-clipboard-check',
            'view' => 'docs.cap12-tutorial-modulo',
            'summary' => 'El módulo Productos de principio a fin: tabla, modelo, FormRequest, rutas, controlador, vista y menú.',
        ],
        [
            'id' => 'cap13',
            'number' => 13,
            'title' => 'Dashboard de administración: guía de uso',
            'icon' => 'fas fa-tachometer-alt',
            'view' => 'docs.cap13-dashboard',
            'summary' => 'Cómo usar el panel cada día: Resumen, Usuarios, Generadores, Base de datos, Documentación y flujos de trabajo.',
        ],
        [
            'id' => 'cap14',
            'number' => 14,
            'title' => 'Librerías, límites y autogestión segura',
            'icon' => 'fas fa-cubes',
            'view' => 'docs.cap14-librerias-autogestion',
            'summary' => 'Qué librerías incluye el CMS, cómo usarlas, qué archivos no se tocan ni se borran y cómo aprovechar docs/.',
        ],
        [
            'id' => 'cap15',
            'number' => 15,
            'title' => 'Recetas: construir sistemas complejos',
            'icon' => 'fas fa-layer-group',
            'view' => 'docs.cap15-recetas-complejas',
            'summary' => 'Patrones prácticos: roles y permisos, API JSON para SPA, reportes PDF, bitácora, exportación y más.',
        ],
    ];

    public function index(Request $request): Response
    {
        if (!$this->isAuthenticated()) {
            return Response::redirect(route(Session::isLoggedIn() ? 'panel' : 'login') ?? '/');
        }

        return $this->render('dashboard.system.docs', 'Documentación', 'Sistema / Documentación', [
            'chapters' => self::CHAPTERS,
            'stats' => [
                'php' => PHP_VERSION,
                'chapters' => count(self::CHAPTERS),
                'files' => count($this->projectFiles()),
            ],
        ]);
    }

    public function pdf(Request $request): Response
    {
        if (!$this->isAuthenticated()) {
            return Response::redirect(route(Session::isLoggedIn() ? 'panel' : 'login') ?? '/');
        }

        $cap = trim((string) $request->input('cap', ''));
        $chapters = self::CHAPTERS;

        if ($cap !== '') {
            $chapters = array_values(array_filter(
                self::CHAPTERS,
                static fn (array $chapter): bool => $chapter['id'] === $cap
            ));

            if ($chapters === []) {
                return Response::json(['success' => false, 'message' => 'El capítulo solicitado no existe.'], 404);
            }
        }

        try {
            $html = View::render('docs.pdf', [
                'chapters' => $chapters,
                'appName' => (string) Session::get('SITE_NAME', config('app.name')),
                'appUrl' => (string) config('app.url'),
                'php' => PHP_VERSION,
                'generatedAt' => date('d/m/Y H:i'),
                'single' => $cap !== '',
                'logo' => $this->logoDataUri(),
            ]);

            $html = $this->inlineSvgs($html);

            require_once BASE_PATH . '/resources/dompdf/autoload.inc.php';

            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', true);
            $options->set('chroot', BASE_PATH);
            $options->set('dpi', 120);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $canvas = $dompdf->getCanvas();
            $canvas->page_text(40, 812, 'Documentacion ' . config('app.name'), null, 7, [0.45, 0.45, 0.45]);
            $canvas->page_text(420, 812, 'Pagina {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.45, 0.45, 0.45]);

            $filename = $cap !== ''
                ? 'documentacion-' . $cap . '.pdf'
                : 'documentacion-sistema.pdf';

            return Response::make($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'no-store',
            ]);
        } catch (\Throwable $e) {
            error_log('Docs PDF generation failed: ' . $e->getMessage());

            $message = config('app.debug')
                ? 'Error generando el PDF: ' . $e->getMessage()
                : 'No se pudo generar el PDF. Revisa la configuración de dompdf.';

            return Response::make((string) $message, 500);
        }
    }

    /**
     * dompdf no dibuja <svg> incrustado; se convierte cada diagrama en una
     * imagen data URI, que dompdf si renderiza (via php-svg-lib).
     */
    private function inlineSvgs(string $html): string
    {
        return (string) preg_replace_callback(
            '#<svg\b([^>]*)>(.*?)</svg>#is',
            static function (array $match): string {
                $svg = $match[0];
                $inject = [];

                if (stripos($match[1], 'xmlns=') === false) {
                    $inject[] = 'xmlns="http://www.w3.org/2000/svg"';
                }

                if (stripos($match[1], 'font-family=') === false) {
                    $inject[] = 'font-family="DejaVu Sans"';
                }

                if ($inject !== []) {
                    $svg = (string) preg_replace('#<svg\b#i', '<svg ' . implode(' ', $inject), $svg, 1);
                }

                $svg = (string) preg_replace('#<text(?=[\s>])#i', '<text font-family="DejaVu Sans"', $svg);

                return '<img class="doc-pdf-figure" src="data:image/svg+xml;base64,'
                    . base64_encode($svg) . '" alt="Diagrama">';
            },
            $html
        );
    }

    private function logoDataUri(): string
    {
        $path = BASE_PATH . '/public/code/dist/img/AdminLTELogo.png';

        if (!is_file($path)) {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));
    }

    private function projectFiles(): array
    {
        $files = [];

        foreach (['lib', 'System', 'App', 'routes', 'config'] as $folder) {
            $path = BASE_PATH . '/' . $folder;

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Descarga un paquete ZIP con la estructura base del CMS lista para arrancar
     * un proyecto nuevo. Incluye App, config, lib, public (sin medicina),
     * resources, routes (con web.php en blanco), System, tests, tools, vendor,
     * composer, .env comentado, Dockerfile, docker-compose.yml y php.ini.
     */
    public function downloadBase(Request $request): Response
    {
        if (!$this->isAuthenticated()) {
            return Response::redirect(route(Session::isLoggedIn() ? 'panel' : 'login') ?? '/');
        }

        if (!class_exists(\ZipArchive::class)) {
            return Response::error('La extensión Zip de PHP no está disponible.', 500);
        }

        $force = $request->input('force', '') === '1';
        $cacheDir = BASE_PATH . '/storage/downloads';
        $cacheFile = $cacheDir . '/sistema-cms-base.zip';
        $maxAge = 3600; // 1 hora

        // Si existe caché fresco, se sirve directamente.
        if (!$force && is_file($cacheFile) && (time() - filemtime($cacheFile)) < $maxAge) {
            $content = file_get_contents($cacheFile);
            if ($content !== false) {
                return Response::make($content, 200, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="sistema-cms-base.zip"',
                    'Content-Length' => (string) strlen($content),
                    'Cache-Control' => 'no-store',
                ]);
            }
        }

        set_time_limit(0);

        $base = BASE_PATH;
        $tmpDir = sys_get_temp_dir() . '/cms_base_' . uniqid();
        $buildDir = $tmpDir . '/sistema-cms-base';
        $zipPath = $tmpDir . '/sistema-cms-base.zip';

        try {
            if (!@mkdir($buildDir, 0755, true) && !is_dir($buildDir)) {
                throw new \RuntimeException('No se pudo crear el directorio temporal.');
            }

            $this->buildStarterPackage($base, $buildDir);
            $this->zipDirectory($buildDir, $zipPath);

            $content = file_get_contents($zipPath);
            if ($content === false) {
                throw new \RuntimeException('No se pudo leer el ZIP generado.');
            }

            // Guardar en caché para las siguientes descargas.
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            if (is_dir($cacheDir) && is_writable($cacheDir)) {
                @file_put_contents($cacheFile, $content);
            }

            $this->recursiveDelete($tmpDir);

            return Response::make($content, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="sistema-cms-base.zip"',
                'Content-Length' => (string) strlen($content),
                'Cache-Control' => 'no-store',
            ]);
        } catch (\Throwable $e) {
            $this->recursiveDelete($tmpDir);
            error_log('Download base failed: ' . $e->getMessage());

            $message = config('app.debug')
                ? 'Error generando el paquete: ' . $e->getMessage()
                : 'No se pudo generar el paquete base. Revisa los logs del servidor.';

            return Response::error($message, 500);
        }
    }

    /**
     * Arma el contenido del paquete base en $buildDir.
     */
    private function buildStarterPackage(string $base, string $buildDir): void
    {
        // Archivos sueltos de la raíz.
        foreach (['bootstrap.php', 'composer.json', 'composer.lock', '.gitignore'] as $file) {
            $src = $base . '/' . $file;
            if (is_file($src)) {
                $this->copyFile($src, $buildDir . '/' . $file);
            }
        }

        // Directorios que se copian tal cual (con filtros).
        $directories = [
            ['src' => 'App'],
            ['src' => 'config'],
            ['src' => 'lib'],
            ['src' => 'public', 'exclude' => ['medicina']],
            ['src' => 'resources'],
            ['src' => 'System'],
            ['src' => 'tests'],
            ['src' => 'tools'],
            ['src' => 'vendor'],
        ];

        foreach ($directories as $dir) {
            $src = $base . '/' . $dir['src'];
            if (!is_dir($src)) {
                continue;
            }
            $this->copyDirectory($src, $buildDir . '/' . $dir['src'], [
                '.git',
                '.svn',
                '.DS_Store',
                'Thumbs.db',
                ...($dir['exclude'] ?? []),
            ]);
        }

        // routes: copia app.php y genera web.php en blanco con nota.
        if (is_dir($base . '/routes')) {
            @mkdir($buildDir . '/routes', 0755, true);
            if (is_file($base . '/routes/app.php')) {
                $this->copyFile($base . '/routes/app.php', $buildDir . '/routes/app.php');
            }
            $this->writeFile(
                $buildDir . '/routes/web.php',
                "<?php\n\n" .
                "declare(strict_types=1);\n\n" .
                "/**\n" .
                " * Rutas públicas del sitio.\n" .
                " *\n" .
                " * Este archivo está vacío de fábrica. Aquí debes registrar las\n" .
                " * páginas, API y CRUDs de tu nuevo proyecto.\n" .
                " *\n" .
                " * Ejemplo:\n" .
                " *   use App\\Controllers\\HomeController;\n" .
                " *   Route::get('/', [HomeController::class, 'index'])->name('inicio');\n" .
                " *\n" .
                " * Las rutas de administración viven en routes/app.php.\n" .
                " */\n\n" .
                "use Lib\\Core\\Route;\n"
            );
        }

        // Archivos de entorno comentados.
        $this->writeFile($buildDir . '/.env.example', $this->envTemplate());
        $this->writeFile($buildDir . '/.env', $this->envTemplate(false));

        // Docker y PHP.
        $this->writeFile($buildDir . '/Dockerfile', $this->dockerfile());
        $this->writeFile($buildDir . '/docker-compose.yml', $this->dockerCompose());
        $this->writeFile($buildDir . '/php.ini', $this->phpIni($base));

        // README principal.
        $this->writeFile($buildDir . '/README.md', $this->readmeStarter());
    }

    /**
     * Copia un archivo respetando los permisos originales.
     */
    private function copyFile(string $src, string $dst): void
    {
        $dir = dirname($dst);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!@copy($src, $dst)) {
            throw new \RuntimeException('No se pudo copiar: ' . $src);
        }
    }

    /**
     * Copia un directorio recursivamente ignorando ciertos nombres.
     */
    private function copyDirectory(string $src, string $dst, array $exclude = []): void
    {
        if (!is_dir($dst)) {
            @mkdir($dst, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if (in_array($item->getFilename(), $exclude, true)) {
                if ($item->isDir()) {
                    $iterator->setMaxDepth(0);
                }
                continue;
            }

            $relative = substr($item->getPathname(), strlen($src) + 1);
            $target = $dst . '/' . $relative;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    @mkdir($target, 0755, true);
                }
                continue;
            }

            $this->copyFile($item->getPathname(), $target);
        }
    }

    /**
     * Escribe un archivo de texto nuevo.
     */
    private function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException('No se pudo escribir: ' . $path);
        }
    }

    /**
     * Comprime un directorio en un archivo ZIP.
     */
    private function zipDirectory(string $source, string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el ZIP.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relative = substr($file->getPathname(), strlen($source) + 1);
            $zip->addFile($file->getPathname(), 'sistema-cms-base/' . $relative);
        }

        $zip->close();
    }

    /**
     * Elimina un directorio y todo su contenido.
     */
    private function recursiveDelete(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }

    /**
     * Plantilla de entorno con comentarios explicativos.
     */
    private function envTemplate(bool $isExample = true): string
    {
        $suffix = $isExample ? ' (copia este archivo a .env y rellena tus datos)' : ' (configura estos valores para tu entorno)';
        $dbPassword = $isExample ? 'tu_password' : 'cambia_este_password';

        return <<<ENV
APP_NAME="Sistema CMS"{$suffix}
APP_ENV=local
APP_URL=http://localhost:8095
APP_DEBUG=true

# APP_KEY queda reservado para futuras funciones de cifrado. Hoy el CMS no lo usa.

# ---------------------------------------------------------------------------
# Base de datos
# ---------------------------------------------------------------------------
# El CMS soporta PostgreSQL y MySQL. En desarrollo con Docker usa el host
# del contenedor: DB_HOST=db (nombre del servicio en docker-compose.yml).
# Si corres PHP nativo contra Postgres local: DB_HOST=localhost y DB_PORT=5433.
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=medicina
DB_USERNAME=postgres
DB_PASSWORD={$dbPassword}
DB_CHARSET=utf8

# ---------------------------------------------------------------------------
# Zona horaria e idioma
# ---------------------------------------------------------------------------
TIMEZONE=America/La_Paz
LANGUAGE=es

# ---------------------------------------------------------------------------
# Sesión (minutos)
# ---------------------------------------------------------------------------
SESSION_LIFETIME=120

# ---------------------------------------------------------------------------
# Correo SMTP (opcional; se usa en reportes y notificaciones)
# ---------------------------------------------------------------------------
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.tudominio.com
# MAIL_PORT=587
# MAIL_USERNAME=tu@tudominio.com
# MAIL_PASSWORD=tu_password
# MAIL_ENCRYPTION=tls
# MAIL_FROM_ADDRESS=tu@tudominio.com
# MAIL_FROM_NAME="\${APP_NAME}"

# APP_DEBUG=false en producción. No olvides crear storage/ y storage/.installed.
ENV;
    }

    /**
     * Dockerfile para desarrollo con PHP 8.2 + Apache.
     */
    private function dockerfile(): string
    {
        return <<<'DOCKER'
# Imagen base: PHP 8.2 + Apache (desarrollo y pruebas locales).
# En producción se recomienda nginx + php-fpm; este Dockerfile es para
# levantar el proyecto rápidamente con docker-compose.
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install zip mysqli pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# rewrite (equivale al .htaccess) + compresión + caché de estáticos
RUN a2enmod rewrite deflate expires headers

# VirtualHost: docroot = /var/www/html/public
RUN printf '%s\n' \
    '<VirtualHost *:80>' \
    '    ServerName localhost' \
    '    DocumentRoot /var/www/html/public' \
    '    ErrorLog ${APACHE_LOG_DIR}/cms-error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/cms-access.log combined' \
    '' \
    '    <Directory /var/www/html/public>' \
    '        Options -Indexes +FollowSymLinks' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

COPY php.ini /usr/local/etc/php/php.ini

EXPOSE 80
DOCKER;
    }

    /**
     * docker-compose.yml para levantar PHP + Apache + PostgreSQL.
     */
    private function dockerCompose(): string
    {
        return <<<'YAML'
version: "3.9"

services:
  db:
    image: postgres:15-alpine
    container_name: cms_db
    environment:
      POSTGRES_DB: medicina
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: cms_local_password
    ports:
      - "5433:5432"
    volumes:
      - cms_db_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres -d medicina"]
      interval: 5s
      timeout: 5s
      retries: 5

  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: cms_app
    ports:
      - "8095:80"
    volumes:
      - .:/var/www/html
    depends_on:
      db:
        condition: service_healthy
    environment:
      DB_HOST: db
      DB_PORT: 5432
      DB_DATABASE: medicina
      DB_USERNAME: postgres
      DB_PASSWORD: cms_local_password

volumes:
  cms_db_data:
YAML;
    }

    /**
     * php.ini basado en la configuración de producción pero apto para local.
     */
    private function phpIni(string $base): string
    {
        $source = $base . '/desplegar/php/php.ini';
        if (is_file($source)) {
            return str_replace(
                ['display_errors = Off', 'display_startup_errors = Off'],
                ['display_errors = On', 'display_startup_errors = On'],
                (string) file_get_contents($source)
            );
        }

        return <<<'INI'
; PHP 8.2 - configuración base para desarrollo local
date.timezone = America/La_Paz

memory_limit = 512M
max_execution_time = 120
max_input_time = 120
max_input_vars = 3000

upload_max_filesize = 8M
post_max_size = 10M
max_file_uploads = 20

expose_php = Off
display_errors = On
display_startup_errors = On
log_errors = On
error_log = /proc/self/fd/2
default_charset = "UTF-8"

session.cookie_httponly = 1
session.cookie_samesite = Lax
session.gc_maxlifetime = 7200

realpath_cache_size = 4096K
realpath_cache_ttl = 600

opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 1
INI;
    }

    /**
     * README del paquete base: instrucciones de local y producción.
     */
    private function readmeStarter(): string
    {
        return <<<'MD'
# Sistema CMS - Proyecto base

Estructura mínima lista para arrancar un nuevo sistema con el framework MVC
propio del CMS. Incluye el panel de administración (`System/`), el núcleo
(`lib/`), generadores, consola SQL, documentación y autenticación.

## Estructura incluida

```
sistema-cms-base/
├── App/                # Controladores, Middleware, Modelos, Requests y views
├── config/             # app.php, database.php, mail.php, setup.php
├── lib/                # Núcleo del framework (Route, Model, Database, View...)
├── public/             # index.php, .htaccess, assets y frontend (sin medicina/)
├── resources/          # dompdf, FPDF
├── routes/             # app.php (admin) + web.php (vacío, aquí programas)
├── System/             # Panel de administración, generadores y documentación
├── tests/              # Suite de comprobaciones
├── tools/              # Utilidades opcionales
├── vendor/             # Dependencias de Composer
├── bootstrap.php       # Arranque de la aplicación
├── composer.json       # Autoload y dependencias
├── .env                # Configuración de entorno (cambia los valores)
├── .env.example        # Plantilla del .env
├── Dockerfile          # Imagen PHP 8.2 + Apache
├── docker-compose.yml  # PHP + Apache + PostgreSQL listos para usar
└── php.ini             # Configuración de PHP
```

> **Nota:** `storage/` no se incluye porque se genera automáticamente la
> primera vez que el sistema arranca. Debe ser escribible por el servidor web.

## Arranque local con Docker

1. Tener instalados Docker y docker-compose.
2. Copiar el paquete a una carpeta nueva:
   ```bash
   unzip sistema-cms-base.zip
   cd sistema-cms-base
   ```
3. Levantar los contenedores:
   ```bash
   docker-compose up -d
   ```
4. Esperar a que la base de datos esté sana (`docker-compose logs -f db`).
5. Acceder al panel:
   ```
   http://localhost:8095/join/access/dashboard
   ```
   El sistema crea las tablas base en el primer acceso si `storage/.installed`
   no existe.

## Arranque local sin Docker (PHP nativo)

Requisitos: PHP >= 8.1 con extensiones `pdo`, `pdo_pgsql` (o `pdo_mysql`),
`zip`, `mbstring`; PostgreSQL o MySQL; Composer.

1. Copiar `.env.example` a `.env` y ajustar los datos de base de datos.
2. Crear la base de datos vacía.
3. Instalar dependencias (si no trajiste `vendor/`):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Apuntar el virtual host a la carpeta `public/` (equivalente al
   `DocumentRoot` del Dockerfile).
5. Acceder al panel. El sistema creará `storage/` y las tablas base en el
   primer uso.

## Despliegue a producción

1. Configurar `.env` con `APP_ENV=production`, `APP_DEBUG=false` y las
   credenciales reales de base de datos y correo.
2. Asegurar que `storage/` exista y tenga permisos de escritura.
3. Opciones de servidor:
   - **Simple:** usar el mismo Dockerfile y subir la imagen a tu VPS.
   - **Recomendado:** nginx como proxy/reescritura + php-fpm (ver
     `public/.htaccess` para las reglas de rewrite equivalentes).
4. Ejecutar la suite de pruebas antes de publicar:
   ```bash
   php tests/run.php
   ```

## Dónde programar

- **Rutas públicas:** `routes/web.php` (vacío de fábrica).
- **Rutas de administración:** `routes/app.php`.
- **Controladores:** `App/Controllers/`.
- **Modelos:** `App/Models/`.
- **Vistas públicas:** `App/views/`.
- **Vistas del panel:** `System/views/dashboard/`.

## Primer módulo

Sigue el Capítulo 12 del manual (`Sistema → Documentación`) para crear un CRUD
completo paso a paso.
MD;
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
            'link' => '<link rel="stylesheet" href="' . asset('code/css/docs.css') . '">',
            'content' => View::component($view, $data),
            'src' => '<script src="' . asset('code/js/docs.js') . '" defer></script>',
        ]);
    }
}
