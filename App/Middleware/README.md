# App/Middleware — Tus middlewares

Esta carpeta viene **vacía** a propósito: el scaffold no incluye middlewares de ejemplo.
Aquí crearás los tuyos. El único middleware que trae el sistema es
`System\Middleware\Auth` (protege el panel y no se puede tocar).

## Cómo funciona

Un middleware es una clase con un método `handle()` que se ejecuta **en cadena**,
después de encontrar la ruta y **antes** del controlador:

```php
public function handle(Request $request): bool|Response
```

| Devuelve | Qué pasa |
| --- | --- |
| `true` | La petición continúa (siguiente middleware o controlador). |
| `false` | Se corta con un **403 vacío** (poco útil; mejor devuelve un `Response`). |
| Un `Response` | Se envía esa respuesta y el controlador **no** se ejecuta. |

- La clase debe llamarse **igual** que el nombre usado en la ruta (el router aplica
  `ucfirst()`): `->middleware('SoloAjax')` busca la clase `SoloAjax`.
- Se resuelven primero en `System\Middleware\` y luego en `App\Middleware\`.
  Para tus middlewares usa nombres nuevos que no choquen con el núcleo.
- Cada clase que crees aparece sola en **Sistema → Middlewares**, con su interruptor
  (excepto `Auth`, que está protegido).

## Crear tu primer middleware

1. Crea `App/Middleware/SoloAjax.php` (el namespace debe coincidir con la carpeta).
2. Escribe `handle()`.
3. Aplícalo en la ruta: `->middleware('SoloAjax')`.

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;

class SoloAjax
{
    public function handle(Request $request): bool|Response
    {
        if (!$request->isAjax()) {
            return Response::json([
                'success' => false,
                'message' => 'Esta ruta solo acepta peticiones AJAX.',
            ], 403);
        }

        return true;
    }
}
```

```php
// routes/app.php
Route::post('/productos/guardar', [ProductosController::class, 'guardar'])
    ->middleware('SoloAjax')
    ->name('productos_guardar');
```

## Middlewares con parámetros

Añade argumentos a `handle()` y sepáralos con `:` y `,` en la ruta:

```php
public function handle(Request $request, string $permiso, string $accion = ''): bool|Response

// ->middleware('Permiso:43,publicar')  =>  handle($request, '43', 'publicar')
```

## Patrón navegador vs. AJAX

Lo normal es responder JSON a AJAX y redirigir al navegador:

```php
if ($request->isAjax()) {
    return Response::json(['success' => false, 'message' => 'No autorizado.'], 401);
}

Response::redirect(route('login') ?? '/join/access/dashboard')->send();
return false;
```

## Documentación

- **Sistema → Documentación → Capítulo 4** (`…/system/docs#cap4`): contrato de
  `handle()`, resolución de nombres por el router, ejemplos listos para copiar
  (`SoloAjax`, `Invitado`, `EsAdmin`, `Permiso`) y cómo depurarlos. También disponible
  en PDF.
- [docs/02-rutas-y-middleware.md](../../docs/02-rutas-y-middleware.md): registrar rutas
  y agruparlas con middlewares.
