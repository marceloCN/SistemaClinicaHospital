# App/views — Tus vistas

Esta carpeta viene **vacía** a propósito: solo contiene este README. Aquí crearás las
vistas de tus módulos, en PHP plano. Las vistas del núcleo viven en `System/views/`
y no se tocan.

## Estructura sugerida

Crea solo las carpetas que necesites; el motor las resuelve por el nombre con puntos:

```
App/views/
├── dashboard/          Vistas de tus módulos dentro del panel AdminLTE
│   └── productos/
│       └── index.php   → se llama como 'dashboard.productos.index'
└── public/             Vistas del sitio público (landing, contacto, etc.)
    └── contacto.php    → se llama como 'public.contacto'
```

## Cómo se resuelve el nombre

- `dashboard.productos.index` busca `dashboard/productos/index.php`.
- Primero busca en `System/views/` y después en `App/views/`. Si repites un nombre
  del núcleo, **tu vista no se carga**; usa nombres nuevos.
- Si no existe ninguna: `RuntimeException: View [...] not found`.

## Cómo usar una vista desde el controlador

`$this->view()` devuelve un `Response` listo para el router:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Lib\Core\Controller;
use Lib\Core\Response;

class ContactoController extends Controller
{
    public function index(): Response
    {
        return $this->view('public.contacto', [
            'titulo' => 'Contacto',
        ]);
    }
}
```

```php
// routes/web.php
Route::get('/contacto', [\App\Controllers\ContactoController::class, 'index'])
    ->name('contacto');
```

`App/views/public/contacto.php`:

```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= e($titulo) ?></title>
</head>
<body>
    <h1><?= e($titulo) ?></h1>
    <p>Escríbenos a <a href="mailto:hola@ejemplo.com">hola@ejemplo.com</a>.</p>
</body>
</html>
```

## Páginas dentro del panel

Para las pantallas del panel, no repitas el HTML del layout: envuelve tu vista con
`dashboard.base.dashboard` desde el controlador.

```php
use Lib\Core\View;

return $this->view('dashboard.base.dashboard', [
    'head'    => View::render('dashboard.base.head', ['title' => 'Productos']),
    'title'   => 'Productos',
    'session' => 'Productos / Listado',
    'content' => View::component('dashboard.productos.index', [
        'productos' => $productos,
    ]),
]);
```

`App/views/dashboard/productos/index.php` empieza por el contenido de la tarjeta,
sin `<html>`, `<head>` ni `<body>`:

```php
<div class="card">
    <div class="card-body">
        <h2 class="h5"><?= e($titulo ?? 'Productos') ?></h2>
        <p>Tienes <?= count($productos) ?> productos.</p>
    </div>
</div>
```

## Pasar datos: variables y placeholders

`View::render($vista, $datos)` convierte cada clave de `$datos` en una variable PHP
y, además, sustituye los placeholders `{clave}` por texto o números.

```php
// En la vista puedes usar la variable...
<h2><?= e($titulo) ?></h2>

// ...o el placeholder (sin escapar; úsalo solo con texto controlado)
<h2>{titulo}</h2>
```

El layout reserva `head`, `link`, `src`, `title`, `session`, `content`, `carousel`,
`about`, `services`, `video`, `noticias`, `main` y `testimonial`. Si un placeholder
reservado no recibe valor, desaparece del HTML. Para escribir una llave literal en
un texto usa `&#123;` y `&#125;`.

## Helpers disponibles en las vistas

| Helper | Para qué |
| --- | --- |
| `e($valor)` | Escapa HTML (siempre que el dato venga de fuera). |
| `route('nombre')` | URL de una ruta con nombre. |
| `asset('code/js/x.js')` | URL de un archivo de `public/` con el dominio correcto. |
| `csrf_field()` / `csrf_token()` | Campo y token CSRF para formularios. |
| `old('campo')` / `errors('campo')` / `hasError('campo')` | Repoblar formularios tras un error de validación. |

## CSS y JavaScript propios

No toques el layout. Pásale los huecos `link` (CSS, dentro del `<head>`) y `src`
(JS, antes de `</body>`):

```php
return $this->view('dashboard.base.dashboard', [
    // ...
    'link' => '<link rel="stylesheet" href="' . asset('code/css/productos.css') . '">',
    'src'  => '<script src="' . asset('code/js/productos.js') . '"></script>',
]);
```

## Buenas prácticas

- Escapa todo dato externo con `e()`.
- Las vistas del panel no llevan `<html>`/`<body>`: el layout ya los pone.
- Nada de bloques gigantes de `<style>`/`<script>`: usa `public/code/css/` y
  `public/code/js/` e inyéctalos con `link`/`src`.
- Usa `asset()` para assets y `route()` para enlaces.

## Documentación

- **Sistema → Documentación → Capítulo 7** (`…/system/docs#cap7`): resolución de
  nombres, `render()`/`component()`, layout del panel, assets y librerías. También
  disponible en PDF.
- [docs/05-vistas-y-layout.md](../../docs/05-vistas-y-layout.md): guía Markdown
  complementaria.
