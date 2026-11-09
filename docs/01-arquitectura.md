# 01. Arquitectura del sistema

## Ciclo de una petición

```
Petición HTTP
  → public/index.php        punto de entrada único (gate de mantenimiento + arranque)
  → bootstrap.php           autoload, .env, zona horaria, sesión, base de datos, ajustes
  → routes/web.php          rutas públicas
  → routes/app.php          rutas del panel (protegidas)
  → Route::dispatch()       busca la ruta, verifica toggles, ejecuta middlewares
  → Controlador             lógica + validación
  → View::render()          vistas PHP (System/views → App/views)
  → Response                HTML o JSON (->send())
```

### 1. `public/index.php`

Es el único archivo expuesto por Apache (`.htaccess` redirige todo a él).
Hace dos cosas:

1. **Gate de mantenimiento global**: si `Session::MAINTENANCE` está activo y la URL no es del
   panel (`join/access/dashboard/...`), responde la vista `error.maintenance` con HTTP **503**.
   El panel nunca se bloquea.
2. Carga `bootstrap.php`, registra las rutas y despacha la petición.

### 2. `bootstrap.php`

En orden:

1. Define `BASE_PATH` y carga el autoload de Composer (`vendor/autoload.php`).
2. `Env::load()` lee el archivo `.env`.
3. Configura zona horaria y errores (`APP_DEBUG`).
4. Inicia la sesión (`Lib\Support\Session`).
5. Configura la conexión PDO (`Database::configure(config('database'))`).
6. Ejecuta `config/setup.php` (crea `settings`, `visits` y `users` si faltan).
7. Carga los ajustes del sitio en sesión (`SITE_NAME`, `TIME_ZONE`, `MAINTENANCE`).
8. Define constantes de compatibilidad (`URL`, `APP`, `CHARSET`, `TIMEZONE`, `LANGUAGE`) y
   espeja la sesión al formato legado `$_SESSION[constant('APP')]` que usan las vistas del panel.

### 3. Rutas y despacho

Ver [02-rutas-y-middleware.md](02-rutas-y-middleware.md).

### 4. Respuesta

`Lib\Core\Response` encapsula contenido + código HTTP + cabeceras. `->send()` hace
`http_response_code()`, imprime las cabeceras, el contenido y termina (`exit`).

## Mapa de carpetas

| Ruta | Rol |
| --- | --- |
| `public/index.php` | Punto de entrada único |
| `public/.htaccess` | Rewrite a `index.php` (mod_rewrite) |
| `public/code/` | Assets AdminLTE: `dist/` (CSS/JS), `plugins/` (jQuery, Bootstrap, Font Awesome) y `code/js/notification.js` |
| `bootstrap.php` | Arranque de la aplicación |
| `routes/web.php` | Rutas públicas (`App\Controllers\...`) |
| `routes/app.php` | Rutas del panel (`System\Controllers\...`) |
| `lib/Core/` | Núcleo: `Route`, `RouteRegistrar`, `Request`, `FormRequest`, `Response`, `View`, `Controller`, `Model`, `Database` |
| `lib/Support/` | Servicios: `Session`, `Csrf`, `Validator`, `ValidationException`, `AuthorizationException`, `System`, `Env`, `Hash`, `Analytics` |
| `lib/Helpers.php` | Funciones globales: `config()`, `env()`, `session()`, `csrf_token()`, `route()`, `view()`, `asset()`, `old()`, `errors()`, `e()`, `dd()`, `now()` |
| `System/Controllers/` | `AuthController` (login/logout), `SystemController` (panel), `DocsController` (documentación) y `UsuariosController` — **no modificar** |
| `System/Requests/` | FormRequest del núcleo (`LoginRequest`) — **no modificar** |
| `System/Middleware/` | `Auth` (protegido, no desactivable) |
| `System/Models/` | Modelos base: `Settings`, `Users`, `Visits` |
| `System/views/` | Vistas del núcleo: `dashboard/` y `error/` — **no modificar** |
| `App/Controllers/` | Tus controladores |
| `App/Middleware/` | Tus middlewares |
| `App/Models/` | Tus modelos (aparecen automáticamente en el panel → Recursos) |
| `App/Requests/` | Tus FormRequest (validación por request, estilo Laravel) |
| `App/views/` | Tus vistas (`dashboard/` para el panel, `public/` para el sitio) |
| `config/app.php` | Nombre, entorno, URL, debug, zona horaria, idioma, sesión |
| `config/database.php` | Conexión PDO (driver, host, puerto, base, credenciales, charset) |
| `config/setup.php` | Creación automática de tablas base |
| `resources/` | Librerías de terceros embebidas (p. ej. `dompdf/`) |
| `vendor/` | Dependencias de Composer |

## Autoload (Composer)

`composer.json` define PSR-4:

```json
"autoload": {
    "psr-4": {
        "App\\": "App/",
        "Lib\\": "lib/",
        "System\\": "System/"
    },
    "files": ["lib/Helpers.php"]
}
```

Por eso `Lib\Support\Validator` vive en `lib/Support/Validator.php`, `System\Models\Users`
en `System/Models/Users.php`, etc. Si agregas carpetas nuevas, respeta el namespace.
Tras crear clases nuevas, en desarrollo alcanza con el autoload normal; si algo no se
encuentra: `composer dump-autoload`.

## Sistema vs App

- **`System/`** es el núcleo del CMS: garantiza que el login, el panel y las páginas de error
  funcionen aunque tus módulos tengan errores. No lo edites (única excepción permitida:
  agregar ítems al menú lateral de `System/views/dashboard/base/dashboard.php`).
- **`App/`** es tu espacio de trabajo. Controladores, middlewares y vistas se resuelven
  primero en `System/` y luego en `App/`: **no reutilices nombres del núcleo**.
- Si necesitas un layout distinto, **duplica** `System/views/dashboard/base/dashboard.php`
  dentro de `App/views/` con otro nombre y renderiza con él.

## Tablas base

Al primer arranque, `config/setup.php` crea únicamente:

- `settings` — nombre del sitio, zona horaria, `maintenance_mode`.
- `visits` — registro de visitas (IP, país, ciudad, navegador, SO, dispositivo, idioma, URLs, fecha).
- `users` — usuarios del panel (nombre, email, contraseña hasheada y estado).
- `system_settings` — la crea automáticamente `Lib\Support\System` (toggles del panel).

Cualquier otra tabla la define el programador (ver [04-modelos-y-base-de-datos.md](04-modelos-y-base-de-datos.md)).

## Piezas disponibles pero opcionales

- `Lib\Support\Analytics` — helper de registro de visitas (`Analytics::track($request)`),
  listo para conectar en los controladores públicos.
- `resources/dompdf/` — DomPDF embebido para exportar PDFs.
- `View::section()/endSection()/yield()` — sistema de secciones en las vistas (hoy no se usa;
  el layout del panel trabaja con placeholders, ver [05-vistas-y-layout.md](05-vistas-y-layout.md)).
