# 05. Vistas y layout

## Motor de vistas

`Lib\Core\View` renderiza archivos PHP con notación de puntos: `dashboard.base.head`
se convierte en `dashboard/base/head.php`.

Orden de búsqueda (¡importante!):

1. `System/views/` — vistas del núcleo (no modificar).
2. `App/views/` — tus vistas.

Si una vista tuya se llama igual que una del núcleo, **nunca se carga**. Usa nombres distintos.

```php
use Lib\Core\View;

View::render('dashboard.mi_vista', ['productos' => $todos]); // string HTML
View::component('dashboard.mi_vista', $data);                // alias de render
View::exists('dashboard.mi_vista');                          // bool
View::share('appName', 'Mi Sistema');                        // dato global para todas las vistas
View::getShared('appName');
```

`bootstrap.php` ya comparte `appName` (nombre del sitio configurado en `settings`).

### Placeholders `{clave}`

Además de PHP puro, el motor hace un reemplazo simple de placeholders:

- Cada **dato string o numérico** reemplaza las apariciones de `{clave}` en el HTML.
  Ej.: con `['title' => 'Productos']`, todo `{title}` se convierte en `Productos`.
- Al terminar, los placeholders del layout que quedaron sin dato se limpian
  (`head`, `link`, `src`, `title`, `session`, `content`, `carousel`, `about`, `services`,
  `video`, `noticias`, `main`, `testimonial`).

### Secciones (disponible, hoy sin uso)

```php
// Vista hija
View::section('scripts');
// ... HTML ...
View::endSection();

// Layout
<?= View::yield('scripts') ?>
```

## El layout del panel

Toda página del panel se renderiza con `dashboard.base.dashboard`. Patrón recomendado
(el mismo que usa `SystemController::render()`):

```php
return $this->view('dashboard.base.dashboard', [
    'head'    => View::render('dashboard.base.head', [
        'title' => 'Productos',
        'icon'  => asset('/code/dist/img/AdminLTELogo.png'),
    ]),
    'title'   => 'Productos',
    'session' => 'Productos',                    // breadcrumb (parte activa)
    'content' => View::component('dashboard.mi_vista', ['productos' => $todos]),
]);
```

Placeholders que entiende el layout:

| Placeholder | Contenido |
| --- | --- |
| `{head}` | Resultado de `View::render('dashboard.base.head')` (title + favicon) |
| `{title}` | `<h1>` de la cabecera |
| `{session}` | Breadcrumb activo |
| `{content}` | Cuerpo de la página |
| `{link}` | Opcional: `View::render('dashboard.base.styles', ['links' => [...]])` |
| `{src}` | Opcional: `View::render('dashboard.base.scripts', ['src' => [...]])` |

Ejemplo para inyectar CSS/JS extra:

```php
'link' => View::render('dashboard.base.styles', ['links' => [asset('/code/plugins/daterangepicker/daterangepicker.css')]]),
'src'  => View::render('dashboard.base.scripts', ['src' => [asset('/code/js/mi-modulo.js')]]),
```

El menú lateral vive en `System/views/dashboard/base/dashboard.php`. La **única edición
permitida** es agregar ítems para tus módulos, siguiendo el patrón de los existentes
(`<li class="nav-item">` con un enlace generado con `\lib\Route::route('nombre')`).

## Notificaciones flash

Disponibles en todo el panel (`public/code/js/notification.js`):

```php
// En el controlador
Session::set('success', 'Guardado correctamente.');   // verde
Session::set('mensaje', 'Ocurrió un error.');          // rojo
```

- Las claves clásicas `_flash_success` y `_flash_mensaje`/`_flash_error` también se
  espejan automáticamente a `success`/`mensaje` en `bootstrap.php`.
- El layout las lee de la sesión, las muestra y las limpia por sí solo.

En JavaScript:

```js
showNotification('success', 'Guardado correctamente.');
showNotification('error', res.message);
```

### Modal de errores de validación

`public/code/js/notification.js` incluye un modal automático para formularios con datos
inválidos:

```js
// Manualmente, con la respuesta 422 del servidor
showErrorModal(res.message, res.errors);        // título por defecto
showErrorModal(res.message, res.errors, 'Corrige los datos');
```

- Cualquier petición AJAX que reciba un JSON `422` con la clave `errors`
  (`{"message": "...", "errors": {"campo": ["mensaje", ...]}}`) abre el modal **sin
  escribir nada en la vista** (handler global `jQuery.ajaxError`).
- En formularios clásicos (sin AJAX), el layout del panel lee los errores flash
  (`errors()` / `_errors`) y abre el mismo modal automáticamente al cargar la página.
- El modal lista cada campo con todos sus mensajes, usando los estilos Bootstrap del panel.

## Helpers imprescindibles en vistas

```php
<?= route('mi_modulo') ?>                 // URL de una ruta con nombre
<?= asset('/code/dist/img/logo.png') ?>   // URL absoluta a un asset
<?= e($valor) ?>                          // htmlspecialchars
<?= csrf_token() ?>                       // token CSRF
<?= old('nombre') ?>                      // valor previo de un formulario
<?= errors('nombre') ?>                   // primer error de validación del campo
<?php if (hasError('nombre')): ?> ... <?php endif; ?>
```

> Las vistas del núcleo (`System/views/`) usan además la constante legada `URL` y la clase
> `\lib\Route`. En tus vistas usa siempre los helpers (`route()`, `asset()`).

## Sitio público

El scaffold no trae una portada propia: la ruta `/` (`routes/web.php`) devuelve el texto
`hola mundo` mientras la página de inicio configurada sea `inicio`. En cuanto registres otra
ruta pública con nombre y la elijas en **Sistema → Página de inicio**, `/` redirige a ella.
Crea tus vistas en `App/views/public/` y sírvelas desde controladores en `App/Controllers/`
con rutas en `routes/web.php`.
