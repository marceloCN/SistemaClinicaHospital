# 08. Crear un módulo (tutorial completo)

Ejemplo: un módulo **Productos** con listado y alta, integrado al panel.
Sigue los 8 pasos; al final tendrás CRUD funcional y visible en el panel Sistema.

## Paso 1 — Crear la tabla

En `config/setup.php` (después de los bloques existentes) o directamente en la base:

```php
$productosExists = $db->query("SHOW TABLES LIKE 'productos'")->fetchColumn();

if ($productosExists === false) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS productos (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(190) NOT NULL,
            precio DECIMAL(10,2) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )
    ");
}
```

## Paso 2 — Crear el modelo

`App/Models/Productos.php`:

```php
<?php

namespace App\Models;

use Lib\Core\Model;

class Productos extends Model
{
    protected string $table = 'productos';
    protected array $fillable = ['nombre', 'precio', 'is_active'];
    protected array $casts = ['precio' => 'float', 'is_active' => 'boolean'];
}
```

## Paso 3 — Registrar las rutas

`routes/app.php` (dentro del bloque existente, agregando otro `Route::controller(...)`):

```php
use App\Controllers\ProductosController;

Route::prefix('join/access/dashboard', function () {
    // ... rutas existentes ...

    Route::controller(ProductosController::class, function () {
        Route::middleware(['Auth'], function () {
            Route::prefix('/Area-of-work/productos', function () {
                Route::get('/', 'index')->name('productos');
                Route::post('/guardar', 'guardar')->name('productos_guardar');
            });
        });
    });
});
```

## Paso 4 — Crear el FormRequest y el controlador

`App/Requests/StoreProductoRequest.php` (la validación vive aquí, estilo Laravel):

```php
<?php

namespace App\Requests;

use Lib\Core\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre' => 'required|min:3',
            'precio' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
        ];
    }
}
```

`App/Controllers/ProductosController.php` (patrón del núcleo: `adminPage()` para páginas y
`FormRequest` para POST — el router valida antes de entrar al método):

```php
<?php

namespace App\Controllers;

use App\Models\Productos;
use App\Requests\StoreProductoRequest;
use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;

class ProductosController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this->isAdmin()) {
            return $this->redirect('login');
        }

        return $this->view('dashboard.base.dashboard', [
            'head'    => View::render('dashboard.base.head', ['title' => 'Productos']),
            'title'   => 'Productos',
            'session' => 'Productos',
            'content' => View::component('dashboard.productos.index', [
                'productos' => Productos::query()->orderBy('id', 'desc')->get(),
            ]),
        ]);
    }

    public function guardar(StoreProductoRequest $request): Response
    {
        if (!$this->isAdmin()) {
            return $this->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $data = $request->validated();

        Productos::query()->insert($data);

        return $this->json(['success' => true]);
    }
}
```

> Si prefieres no crear la clase, el helper `$this->validate(['nombre' => 'required|min:3'])`
> sigue disponible y hace lo mismo (validar, flash de errores y 422 JSON).
> Si el POST debe validar CSRF en el request, hazlo en `authorize()` (ver
> [03-validacion-y-form-requests.md](03-validacion-y-form-requests.md#authorize)).

## Paso 5 — Crear la vista

`App/views/dashboard/productos/index.php` (tu vista, con el layout del panel ya aplicado):

```php
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Listado de productos</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" id="btn-nuevo">Nuevo</button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-sm table-striped">
            <thead>
                <tr><th>#</th><th>Nombre</th><th>Precio</th><th>Activo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= e((string) $producto['id']) ?></td>
                        <td><?= e((string) $producto['nombre']) ?></td>
                        <td><?= e(number_format((float) $producto['precio'], 2)) ?></td>
                        <td><?= $producto['is_active'] ? 'Sí' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $('#btn-nuevo').on('click', function () {
        let nombre = prompt('Nombre del producto');
        if (!nombre) return;

        $.post("<?= route('productos_guardar') ?>", {
            nombre: nombre,
            precio: '1.00',
            _token: "<?= csrf_token() ?>"
        }, function (res) {
            if (res.success) {
                showNotification('success', 'Producto guardado.');
                setTimeout(() => window.location.reload(), 800);
            } else {
                showNotification('error', res.message);
            }
        }, 'json').fail(function (xhr) {
            showNotification('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Error al guardar.');
        });
    });
</script>
```

## Paso 6 — Agregar el enlace al menú

En `System/views/dashboard/base/dashboard.php` (única edición permitida del núcleo),
junto a los ítems del bloque "Sistema":

```php
<li class="nav-item">
    <a href="<?= \lib\Route::route('productos') ?>" class="nav-link">
        <i class="fas fa-box nav-icon"></i>
        <p>Productos</p>
    </a>
</li>
```

## Paso 7 — Probar

Checklist:

1. `composer dump-autoload` (solo si el autoload no encuentra las clases nuevas).
2. Entra al panel → **Productos**: debe listar la tabla vacía.
3. "Nuevo" → guarda y verifica la notificación y el registro en la tabla.
4. **Sistema → Rutas y enlaces**: `productos` aparece y se puede deshabilitar
   (deshabilitada queda como 404 y `Route::isEnabled('productos')` devuelve `false`).
5. **Sistema → Recursos**: `Productos` aparece con su tabla y contador.
6. **Sistema → Base de datos**: confirma la tabla y sus columnas.

## Paso 8 — Buenas prácticas

- Valida **siempre** en el servidor: usa un `FormRequest` en `App/Requests/` (o
  `$this->validate()` para casos simples).
- Usa `Csrf::validate()` en todo POST; responde `{"success": false, "message": ...}` con el
  código adecuado (403/405/422) para que el JS del panel lo muestre con `showNotification`.
- Envuelve las páginas con `$this->isAdmin()` → redirect a `login` (páginas) o JSON 403 (AJAX).
- SQL solo a través de `Model`/PDO con parámetros (nunca concatenes input del usuario).
- Nombra rutas de forma única; serán las claves de los toggles y de `route()`.
- No toques archivos del núcleo (`System/`, `lib/`): todo lo tuyo vive en `App/` y `routes/`.
