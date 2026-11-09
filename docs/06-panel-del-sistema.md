# 06. Panel del sistema

El panel vive bajo el prefijo `/join/access/dashboard` y se compone del **login** y del
**módulo Sistema**. Sus rutas están en `routes/app.php`.

## Login

| Ruta | Nombre | Acción |
| --- | --- | --- |
| `GET /join/access/dashboard` | `login` | Formulario de acceso (si ya hay sesión admin, redirige a `system`) |
| `POST /join/access/dashboard/verify-credentials` | `verify_credentials` | Verifica credenciales (JSON) |
| `POST /join/access/dashboard/logout` | `logout` | Cierra sesión (protegido con `Auth` + CSRF) |

Flujo del login (`System\Controllers\AuthController`):

1. El formulario envía por AJAX `email`, `password` y `_token` (CSRF).
2. Se valida (`required|min:3` / `required|min:4`) y se comprueba CSRF.
3. Si el usuario existe y está **activo** (`status = 1`), inicia sesión:
   `loged`, `user` (sin contraseña), `id_usuario`, `nombre`, `email`.
4. Si la contraseña guardada está en texto plano (sistema legado), se migra
   automáticamente a **bcrypt** (`Hash::make`).
5. No hay roles: cualquier usuario activo entra al panel. El middleware `Auth`
   revalida en cada petición que el usuario siga existiendo y activo; si no,
   destruye la sesión y redirige al login.
6. Al autenticar se regenera el token CSRF y el JS redirige a `route('system')`.

## Rutas del módulo Sistema

Todas protegidas con middleware `Auth`, bajo `/join/access/dashboard/Area-of-work/system`:

| Ruta | Nombre | Página |
| --- | --- | --- |
| `GET /` | `system` | Resumen (versión PHP, entorno, URLs, conteos, toggles) |
| `GET /routes` | `system_routes` | Rutas y enlaces (habilitar/deshabilitar) |
| `POST /routes/toggle` | `system_route_toggle` | Guarda el toggle de una ruta |
| `GET /middleware` | `system_middleware` | Middlewares (uso y habilitar/deshabilitar) |
| `POST /middleware/toggle` | `system_middleware_toggle` | Guarda el toggle de un middleware |
| `GET /home` | `system_home` | Página de inicio del sitio |
| `POST /home` | `system_home_update` | Guarda la ruta de inicio elegida |
| `POST /maintenance` | `system_maintenance_toggle` | Activa/desactiva mantenimiento |
| `GET /database` | `system_database` | Explorador de base de datos + consola SQL |
| `POST /database/query` | `system_database_query` | Ejecuta SQL (SELECT con resultados, DDL/DML con filas afectadas) |
| `GET /docs` | `system_docs` | Documentación integrada |

## Toggles: cómo funcionan

El estado se guarda en la tabla `system_settings` (clave/valor) administrada por
`Lib\Support\System`:

| Clave | Significado |
| --- | --- |
| `route.enabled.<nombre-o-uri>` | `1`/`0` — ¿la ruta se despacha? |
| `middleware.enabled.<Nombre>` | `1`/`0` — ¿el middleware se ejecuta? |
| `home.route` | Nombre de la ruta elegida como página de inicio (por defecto `inicio`) |

- **Rutas deshabilitadas**: `Route::dispatch()` las salta (quedan como 404). Las del panel
  (`join/access/dashboard/...`) no se pueden deshabilitar.
- **Middlewares deshabilitados**: se saltan al despachar. `Auth` es **protegido**:
  `System::setMiddlewareEnabled('Auth', false)` devuelve `false` y el panel muestra el
  candado; nunca puede dejarse el panel sin protección.
- Desde plantillas PHP: `System::routeEnabled($clave)`, `System::middlewareEnabled($nombre)`,
  `System::homeRoute()`, `System::setRouteEnabled(...)`, `System::setMiddlewareEnabled(...)`,
  `System::setHomeRoute(...)`.

> **Pendiente**: el selector de página de inicio ya guarda `home.route`, pero la portada `/`
> todavía no redirige automáticamente a esa ruta. Está en el roadmap (ver README raíz).

## Modo mantenimiento

1. Se activa desde **Sistema → Resumen**.
2. Guarda `settings.maintenance_mode = 1` y actualiza la sesión (`MAINTENANCE`).
3. El gate de `public/index.php` responde **503** con la vista
   `System/views/error/maintenance.php` a toda URL pública.
4. El panel (`join/access/dashboard/...`) **nunca** se bloquea.

## Recursos y APIs

- **Recursos**: recorre `App/Models/*.php`, instancia cada modelo y muestra su tabla y
  `COUNT(*)` (si un modelo apunta a una tabla inexistente, el contador simplemente no aparece).
- **APIs**: muestra versión de PHP, SAPI, límites (`memory_limit`, `upload_max_filesize`,
  `post_max_size`, `max_execution_time`), extensiones cargadas, paquetes Composer
  (`vendor/composer/installed.json`), plugins cliente (`public/code/plugins/`) y todas las
  rutas JSON del proyecto.

## Códigos HTTP usados en el panel

| Código | Uso |
| --- | --- |
| 200 | Éxito (HTML o `{"success": true, ...}`) |
| 302 | Redirecciones (login, logout, acceso denegado a páginas) |
| 401 | AJAX sin sesión (middleware `Auth`) |
| 403 | Sin permisos, token CSRF inválido, toggle prohibido |
| 404 | Ruta o recurso inexistente |
| 405 | Método incorrecto (POST esperado) |
| 422 | Validación fallida (`{"success": false, "message": ...}`) |
| 503 | Mantenimiento |
