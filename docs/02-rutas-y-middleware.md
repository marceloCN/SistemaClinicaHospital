# 02. Rutas y middleware

## Dónde se registran

| Archivo | Uso |
| --- | --- |
| `routes/web.php` | Rutas públicas (sin sesión): el sitio que ve el visitante |
| `routes/app.php` | Rutas del panel de administración, protegidas con middleware `Auth` |

Ambos se cargan en `public/index.php` **después** de `bootstrap.php` y antes del dispatch.

## Registrar rutas

```php
use Lib\Core\Route;
use App\Controllers\ServiciosController;

// Ruta suelta (closure)
Route::get('/estado', fn ($request) => Response::json(['ok' => true]))->name('estado');

// Ruta a un controlador
Route::get('/servicios', [ServiciosController::class, 'index'])->name('frontend.servicios');

// Grupo de controlador: el segundo argumento es el método del controlador
Route::controller(ServiciosController::class, function () {
    Route::get('/', 'index')->name('servicios');
    Route::post('/guardar', 'guardar')->name('servicios_guardar');
});
```

Métodos disponibles: `Route::get()`, `post()`, `put()`, `patch()`, `delete()`.
Cada uno devuelve un `RouteRegistrar` con:

- `->name('nombre')` — nombre único de la ruta (clave para enlaces y toggles).
- `->middleware('Auth')` o `->middleware(['Auth', 'SoloAdmin:18'])`.

### Parámetros de ruta

```php
Route::get('/socios/{id}/ficha', [SociosController::class, 'ficha'])->name('socios_ficha');
```

`{id}` captura un segmento (sin `/`) y llega **como argumento posicional** al método,
después del `Request`:

```php
public function ficha(Request $request, string $id): Response { ... }
```

### Prefijos y grupos

```php
Route::prefix('join/access/dashboard', function () {
    Route::middleware(['Auth'], function () {
        Route::prefix('/Area-of-work/mi-modulo', function () {
            Route::get('/', 'index')->name('mi_modulo');
            Route::post('/guardar', 'guardar')->name('mi_modulo_guardar');
        });
    });
});

// Forma compacta
Route::group(['prefix' => '/Area-of-work/otro', 'middleware' => ['Auth']], function () {
    Route::get('/', 'index')->name('otro');
});
```

Los grupos se anidan; prefijos y middlewares se acumulan en orden.

## Generar enlaces

```php
route('mi_modulo')            // devuelve la URL o null si la ruta no existe
route('socios_ficha', ['id' => 5])
```

En las vistas se usa la función `route()`. Las vistas del núcleo conservan además
`\lib\Route::route()` (clase de compatibilidad); en código nuevo usa siempre `route()`.

Consultas útiles:

```php
Route::all();                 // lista de todas las rutas (uri, método, nombre, middleware)
Route::findByName('mi_modulo');
Route::isEnabled('mi_modulo'); // ¿el toggle del panel la tiene habilitada?
```

## Middleware

### Cómo se resuelven

1. Por nombre, **sin** la palabra `Middleware`: `'Auth'` busca la clase `Auth`.
2. Primero en `System\Middleware\Auth` y luego en `App\Middleware\Auth`.
3. Si no existe ninguna: `RuntimeException`.

Llevar un middleware en la ruta:

```php
Route::get('/solo-admins', 'index')->name('zona_privada')->middleware('SoloAdmin');

Route::middleware(['Auth', 'SoloAdmin'], function () { ... });
```

Parámetros con `Nombre:param1,param2`:

```php
Route::middleware(['Age:18'], function () { ... });
```

### Contrato actual de `handle()`

```php
namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

class SoloAdmin
{
    public function handle(Request $request): bool|Response
    {
        if (!Session::isLoggedIn()) {
            return Response::json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        return true; // continúa al siguiente middleware o al controlador
    }
}
```

Reglas del despachador:

| Retorno | Efecto |
| --- | --- |
| `true` (o cualquier valor distinto de `false`) | Continúa la cadena |
| `Response` | Se corta y se envía esa respuesta |
| `false` | Se responde `403` vacío |

Los parámetros (`Age:18` → `18`) llegan como argumentos extra: `handle($request, '18')`.

### Toggles del panel

- Cada middleware aparece en **Sistema → Middlewares** con su uso y un interruptor.
- Si un middleware está deshabilitado, `Route::dispatch()` **lo salta** (la petición sigue
  sin él). El middleware `Auth` está **protegido**: no puede deshabilitarse.
- El estado se guarda en `system_settings` con la clave `middleware.enabled.<Nombre>`.

> **Roadmap**: se planifica un pipeline estilo Laravel con `handle($request, $next)` y
> aliases (`Route::aliasMiddleware(...)`), manteniendo compatibilidad con el contrato actual
> `bool|Response`. Ver [03-validacion-y-form-requests.md](03-validacion-y-form-requests.md#plan-de-migración-framework).

## Despacho: qué hace `Route::dispatch()`

1. Normaliza método y URI (sin `/` inicial y final).
2. Compara contra las rutas del método con un patrón regex (`{param}` → `([^/]+)`).
3. Si la ruta tiene nombre (o URI) **deshabilitado** en `system_settings`
   (`route.enabled.*`), la ignora y sigue buscando (termina en 404).
4. Ejecuta los middlewares en orden (saltando los deshabilitados).
5. Llama al controlador (`setRequest($request)` primero) o al closure, resolviendo los
   argumentos por **reflexión**: `Request` → petición real; subclase de `FormRequest` →
   se instancia, se valida (`authorize()` + `rules()`) y se inyecta; el resto → parámetros
   de ruta en orden (ver [03-validacion-y-form-requests.md](03-validacion-y-form-requests.md)).
6. Si `authorize()` falla lanza `AuthorizationException` → JSON `403`
   `{"success": false, "message": "<mensaje>"}` (status configurable).
7. Si la validación lanza `ValidationException`, responde JSON `422`
   `{"success": false, "message": "<primer error>"}` y deja `_errors`/`_old_*` en sesión.
8. Convierte el resultado a `Response` (array/objeto → JSON automático).
9. Si nada coincide: 404 (JSON para AJAX, o la vista `error.error404`).

## Toggle de rutas desde el panel

- **Sistema → Rutas y enlaces** lista las rutas con su nombre, método, middleware y área.
- El interruptor guarda `route.enabled.<nombre|uri>` en `system_settings`.
- Las rutas del panel (`join/access/dashboard/...`) no se pueden deshabilitar desde el panel.
- En una vista puedes ocultar enlaces de rutas deshabilitadas:

```php
<?php if (\Lib\Core\Route::isEnabled('mi_modulo')): ?>
    <a href="<?= route('mi_modulo') ?>">Mi módulo</a>
<?php endif; ?>
```
