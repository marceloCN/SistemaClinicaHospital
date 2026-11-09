# lib/ — Núcleo del framework

Código del framework MVC. **No contiene lógica de negocio** y no debería modificarse
para construir módulos; si algo falta, se agrega aquí de forma genérica y documentada.

## `lib/Core/` — motor

| Clase | Rol |
| --- | --- |
| `Route` | Registro y despacho de rutas (grupos, prefijos, middlewares, toggles) |
| `RouteRegistrar` | Objeto fluido devuelto por `Route::get/post/...` (`->name()`, `->middleware()`) |
| `Request` | Petición HTTP: `input()`, `all()`, `isPost()`, `isAjax()`, `header()`, `file()`, `json()`, params de query/body |
| `FormRequest` | Validación por request estilo Laravel: `authorize()`, `rules()`, `messages()`, `validated()`; la resuelve `Route::dispatch()` por reflexión |
| `Response` | Respuesta: `make()`, `json()`, `redirect()`, `back()`, `error()`, `->send()` |
| `View` | Motor de vistas (`render`, `component`, `share`, resolución `System/` → `App/`) |
| `Controller` | Base de controladores: `view()`, `json()`, `redirect()`, `back()`, `validate()`, `isAuthenticated()`, `isAdmin()` (alias de la anterior) |
| `Model` | Query builder + persistencia (where, orderBy, insert, update, delete, casts, timestamps) |
| `Database` | Conexión PDO, `transaction()`, `raw()`, `reconnect()` |

## `lib/Support/` — servicios

| Clase | Rol |
| --- | --- |
| `Session` | Sesión con namespace por app; `get/set/pull/flash`, `isLoggedIn`, `regenerate`, `destroy` |
| `Csrf` | Token CSRF: `token()`, `field()`, `validate()`, `regenerate()` |
| `Validator` | Validación (`required`, `email`, `min`, `max`, `numeric`, `integer`, `in`, `confirmed`) + mensajes `campo.regla`; una regla desconocida lanza excepción |
| `ValidationException` | Excepción de validación → el router responde 422 JSON; solo en peticiones no-AJAX guarda `_errors`/`_old_*` (nunca contraseñas ni tokens) |
| `AuthorizationException` | Petición no autorizada (`authorize()`) → el router responde 403 JSON (status configurable) |
| `System` | Toggles del panel (`route.enabled.*`, `middleware.enabled.*`, `home.route`) sobre `system_settings` |
| `Env` | Lectura tipada del `.env` (`get`, `getBool`, `getInt`) |
| `Hash` | `make()` / `check()` con `password_hash` |
| `Analytics` | Registro de visitas (`track($request)`), disponible para conectar |

## `lib/Helpers.php`

Funciones globales cargadas por Composer (`"files"`):
`config()`, `env()`, `session()`, `csrf_token()`, `csrf_field()`, `asset()`, `url()`,
`route()`, `redirect()`, `back()`, `json()`, `view()`, `old()`, `errors()`, `hasError()`,
`e()`, `dd()`, `now()`.

## Archivos de compatibilidad

En la raíz de `lib/` quedan clases legadas (`lib\Route`, `lib\Request`, `lib\View`,
`lib\Database`) que usaban las vistas antiguas (`\lib\Route::route(...)`).
No se deben usar en código nuevo; se mantienen solo para no romper esas vistas.

## Documentación

- `docs/01-arquitectura.md` — cómo encajan todas las piezas.
- `docs/02-rutas-y-middleware.md` — API de `Route`.
- `docs/04-modelos-y-base-de-datos.md` — API de `Model`.
- `docs/05-vistas-y-layout.md` — API de `View`.
