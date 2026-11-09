# App/Controllers — Tus controladores

Carpeta vacía a propósito: aquí van los controladores de **tus módulos**.
El núcleo vive en `System/Controllers/` y no se toca.

## Reglas

- Cada clase extiende `Lib\Core\Controller`.
- El namespace es `App\Controllers`.
- Si repites un nombre que ya existe en `System/Controllers`, el tuyo **nunca** se carga
  (la resolución es siempre `System/` primero, `App/` después).
- Registra sus rutas en `routes/web.php` (sitio público) o `routes/app.php` (panel).

## Ejemplo mínimo

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;

class ProductosController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('dashboard.productos.index', [
            'productos' => [],
        ]);
    }
}
```

```php
// routes/app.php, dentro del prefijo del panel y del grupo Auth
Route::controller(ProductosController::class, function () {
    Route::prefix('/Area-of-work/productos', function () {
        Route::get('/', 'index')->name('productos');
    });
});
```

Ver el tutorial completo en **Sistema → Documentación → Capítulo 12**.
