# System/ — Núcleo del CMS (no modificar)

Todo lo que hay aquí **mantiene el sistema en pie**: login, panel de administración,
páginas de error y modelos base. Está pensado para que tus módulos (`App/`) puedan
romperse sin dejar el panel inaccesible.

## Contenido

| Ruta | Rol |
| --- | --- |
| `Controllers/AuthController.php` | Login (`index`, `verificar_credenciales`) y logout (`POST` + CSRF) |
| `Controllers/DocsController.php` | Documentación integrada (12 capítulos) y PDF |
| `Controllers/SystemController.php` | Módulo Sistema: resumen, rutas, middlewares, inicio, base de datos, recursos, APIs, docs |
| `Controllers/UsuariosController.php` | CRUD de usuarios del panel (listado, alta, edición, estado) |
| `Requests/LoginRequest.php` | FormRequest del login: `authorize()` (método + CSRF), `rules()` y `messages()` |
| `Middleware/Auth.php` | Exige sesión y revalida que el usuario siga existiendo y activo; responde 401 JSON (AJAX) o redirect al login. **Protegido**: no se puede deshabilitar desde el panel |
| `Models/Settings.php` | Tabla `settings` (nombre del sitio, zona horaria, mantenimiento) |
| `Models/Users.php` | Tabla `users` (nombre, email, contraseña hasheada, estado) |
| `Models/Visits.php` | Tabla `visits` (registro de visitas) |
| `views/dashboard/base/` | Layout AdminLTE (`dashboard.php`), `login.php`, parciales `head/styles/scripts` |
| `views/dashboard/system/` | Páginas del módulo Sistema |
| `views/dashboard/usuarios/` | Páginas del módulo Usuarios |
| `views/docs/` | Capítulos de la documentación integrada y generador de PDF |
| `views/error/` | `maintenance.php` (503), `system_disabled.php`, `error404.php`, `error500.php` |

## Qué puedes tocar

- **Único punto permitido**: agregar ítems al menú lateral en
  `views/dashboard/base/dashboard.php` para enlazar tus módulos
  (sin alterar la estructura existente).
- Si necesitas otro layout, **duplícalo** dentro de `App/views/` con otro nombre.

## Cómo se conecta con el resto

- Sus rutas se registran en `routes/app.php` bajo `/join/access/dashboard`.
- Los toggles de rutas, middlewares y página de inicio usan `Lib\Support\System`
  (tabla `system_settings`).
- El modo mantenimiento se controla desde aquí (`settings.maintenance_mode`) y lo aplica
  el gate de `public/index.php` (HTTP 503 para el sitio público).
- Los modelos base aparecen en **Sistema → Recursos** junto con los tuyos.

## Documentación

- `docs/06-panel-del-sistema.md` — flujo de login, rutas, toggles y mantenimiento.
- `docs/01-arquitectura.md` — ciclo de la petición y mapa completo.
