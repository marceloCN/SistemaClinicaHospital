# App/ — Tu espacio de trabajo

Aquí escribes **todo el código de tus módulos**. El núcleo (`System/` y `lib/`) no se toca.

## Estructura

```
App/
├── Controllers/    Tus controladores (extienden Lib\Core\Controller)
├── Middleware/     Tus middlewares (se resuelven después de los de System\)
├── Models/         Tus modelos (extienden Lib\Core\Model)
├── Requests/       Tus FormRequest (validación por request, estilo Laravel)
└── views/          Tus vistas (dashboard/ para el panel, public/ para el sitio público)
```

## Validación con FormRequest

Crea una clase por request en `App/Requests/` y tipa el parámetro del controlador con ella;
el router la valida sola antes de entrar al método (ver
`docs/03-validacion-y-form-requests.md`):

```php
// App/Requests/StoreProductoRequest.php
namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->isPost() || !Csrf::validate($this->input('_token'))) {
            throw new AuthorizationException('Solicitud no autorizada.');
        }

        return true;
    }

    public function rules(): array
    {
        return ['nombre' => 'required|min:3'];
    }
}
```

```php
// En el controlador
public function guardar(StoreProductoRequest $request): Response
{
    $data = $request->validated();
    // ...
}
```

## Estado inicial (scaffold)

Este proyecto se entrega **listo para empezar**: las carpetas de `App/` vienen sin
módulos, solo con un `README.md` en cada una que explica cómo crear el tuyo.
`App/Middleware/` y `App/views/` están **vacías**: el sistema solo incluye el
middleware `Auth` y sus propias vistas en `System/`. Crea tus archivos cuando los
necesites (tienes ejemplos listos para copiar en el capítulo 4 y 7 de la
documentación integrada).

## Reglas

1. **Nombres únicos**: controladores, middlewares y vistas se resuelven primero en
   `System/` y luego en `App/`. Si repites un nombre del núcleo, el tuyo nunca se carga.
2. Usa los helpers y el patrón de los controladores del núcleo:
   `$this->view()`, `$this->json()`, `$this->redirect()`, `$this->validate()`,
   `$this->isAdmin()`.
3. Todo POST: verifica método + CSRF y valida antes de tocar la base de datos.
   Lo más limpio es un `FormRequest` en `App/Requests/` (CSRF en `authorize()`).
4. Envuelve las páginas del panel con el layout `dashboard.base.dashboard`.

## Ejemplo mínimo de controlador

```php
<?php

namespace App\Controllers;

use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;

class MiController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('public.mi-pagina');
    }
}
```

Regístralo en `routes/web.php` o `routes/app.php` según corresponda.

## Documentación

La referencia principal es la documentación integrada en el panel
(**Sistema → Documentación**, 12 capítulos con el detalle real del código).

Guías Markdown complementarias en `docs/`:
`02-rutas-y-middleware.md`, `03-validacion-y-form-requests.md`,
`04-modelos-y-base-de-datos.md`, `05-vistas-y-layout.md`, `08-crear-un-modulo.md`.
