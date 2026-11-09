# 03. Validación y FormRequest

## Validator

`Lib\Support\Validator` es el validador del framework. Se crea con `make()`:

```php
use Lib\Support\Validator;

$validator = Validator::make($request->all(), [
    'nombre' => 'required|min:3',
    'precio' => 'required|numeric',
    'email'  => 'email',
]);

if ($validator->fails()) {
    return $this->json(['success' => false, 'message' => $validator->first()], 422);
}

$data = $validator->validated(); // solo las claves con regla
```

El tercer argumento son **mensajes personalizados** con la clave `campo.regla`:

```php
$validator = Validator::make($request->all(), [
    'email' => 'required|min:3',
], [
    'email.required' => 'Ingresa tu usuario o correo.',
    'email.min'      => 'El correo es demasiado corto.',
]);
```

### Reglas disponibles

| Regla | Ejemplo | Comportamiento |
| --- | --- | --- |
| `required` | `required` | Falla si el valor es `null` o `''` |
| `email` | `email` | `FILTER_VALIDATE_EMAIL` |
| `min` | `min:3` | Longitud mínima (`mb_strlen`) |
| `max` | `max:190` | Longitud máxima |
| `numeric` | `numeric` | `is_numeric` |
| `integer` | `integer` | `FILTER_VALIDATE_INT` |
| `in` | `in:0,1` | Valor dentro de la lista |
| `confirmed` | `confirmed` | Compara con `<campo>_confirmation` |

Las reglas se separan con `|`. Un valor vacío solo falla con `required` (las demás reglas
se saltan si el campo está vacío, igual que en Laravel). Una regla con nombre desconocido
lanza `InvalidArgumentException` (no se ignora en silencio).

### Métodos del validator

```php
$validator->passes();     // bool
$validator->fails();      // bool
$validator->errors();     // ['campo' => ['mensaje', ...], ...]
$validator->first();      // primer mensaje de todo el set
$validator->first('email'); // primer mensaje de un campo
$validator->validated();  // datos filtrados a las claves con regla
```

## Patrones de uso en controladores

### 1. Helper del controlador (el más común)

```php
public function guardar(Request $request): Response
{
    $data = $this->validate([
        'nombre' => 'required|min:3',
        'precio' => 'required|numeric',
    ]);

    Productos::query()->insert($data);

    return $this->json(['success' => true]);
}
```

`Controller::validate()`:

1. Valida `$request->all()`.
2. Si falla y la petición **no es AJAX**, guarda en sesión los errores (`_errors`) y los
   valores previos (`_old_<campo>`), excluyendo `password`, `password_confirmation`,
   `_token` y `_method`.
3. Lanza `Lib\Support\ValidationException`.
4. `Route::dispatch()` la captura y responde JSON `422` con el primer mensaje en
   `message` y **todos los errores** en `errors` (ideal para los endpoints AJAX del
   panel, que abren el modal de errores automáticamente con ese JSON):

```json
{
    "success": false,
    "message": "Ingresa tu usuario o correo.",
    "errors": {
        "email": ["Ingresa tu usuario o correo."],
        "password": ["Ingresa tu contraseña."]
    }
}
```

Para vistas web con formulario, los helpers `errors()` y `old()` leen esos datos flash:

```php
<?php if (hasError('nombre')): ?>
    <div class="text-danger"><?= e(errors('nombre')) ?></div>
<?php endif; ?>

<input name="nombre" value="<?= e(old('nombre')) ?>">
```

### 2. Validación manual con respuesta propia

Cuando necesitas un mensaje o formato distinto (como hacen los controladores del núcleo):

```php
$validator = Validator::make($request->all(), [
    'email'    => 'required|min:3',
    'password' => 'required|min:4',
]);

if ($validator->fails()) {
    return $this->json(['success' => false, 'message' => $validator->first()], 422);
}
```

## CSRF

Todos los `POST` del panel llevan el token:

```php
// En la vista
<meta name="csrf" content="<?= csrf_token() ?>">
<?= csrf_field() ?>           <!-- input hidden listo para formularios -->

// En el controlador
if (!Csrf::validate($request->input('_token'))) {
    return $this->json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
}
```

- El token vive en `$_SESSION['_csrf_token']` (`Lib\Support\Csrf`).
- Se regenera al iniciar sesión (`Csrf::regenerate()`).
- En JavaScript se envía como `_token` (ver `System/views/dashboard/system/*.php`).

## FormRequest (estilo Laravel, implementado)

La validación puede vivir en una clase por petición. El router (`Route::dispatch()`)
inspecciona por **reflexión** los parámetros del método o closure:

1. Si el parámetro es una subclase de `Lib\Core\FormRequest`, la instancia con la petición
   real y ejecuta `validateResolved()` **antes** de llamar al método.
2. `validateResolved()` ejecuta `authorize()` y luego el `Validator` con `rules()` y
   `messages()`.
3. Si `authorize()` devuelve `false` → `AuthorizationException` → **403 JSON**. También
   puedes lanzar `AuthorizationException` dentro de `authorize()` con mensaje y status
   propios (por ejemplo `405`).
4. Si la validación falla → `ValidationException`: en peticiones no-AJAX guarda `_errors`
   y `_old_*` en sesión (para `errors()`/`old()`), sin datos sensibles; el router responde
   **422 JSON** `{"success": false, "message": "<primer error>", "errors": {"<campo>": ["<mensajes>"]}}`.
   El panel abre el modal de errores automáticamente con ese JSON (ver `docs/05`).
5. Dentro del método usas `$request->validated()` (solo las claves con regla) y todos los
   métodos de `Request` (`input()`, `all()`, `isPost()`, `isAjax()`, `file()`, ...).

### Crear tu propio FormRequest (paso a paso)

**Paso 1.** Crea la clase en `App/Requests/` (tus módulos) o `System/Requests/` (núcleo del
CMS). Extiende `Lib\Core\FormRequest` e implementa `rules()`; `authorize()` y `messages()`
son opcionales:

```php
<?php
// App/Requests/StoreProductoRequest.php

namespace App\Requests;

use Lib\Core\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

**Paso 2.** Tipa el parámetro del controlador con tu clase (en lugar de `Request`):

```php
use App\Requests\StoreProductoRequest;

public function guardar(StoreProductoRequest $request): Response
{
    $data = $request->validated();

    Productos::query()->insert($data);

    return $this->json(['success' => true]);
}
```

Eso es todo: ya no escribes `Validator::make()` ni flashes manuales; el router valida antes
de entrar al método. Como extiende `Request`, dentro tienes `input()`, `all()`, `isPost()`,
`isAjax()`, `file()`, `header()`, etc.

### authorize()

- `authorize(): bool` → `false` produce `403` con «Esta acción no está autorizada.».
- Para mensajes y status propios, lanza `AuthorizationException`:

```php
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

public function authorize(): bool
{
    if (!Csrf::validate($this->input('_token'))) {
        throw new AuthorizationException('Token CSRF inválido.');      // 403
    }

    if (!$this->isPost()) {
        throw new AuthorizationException('Método no permitido.', 405); // status propio
    }

    return true;
}
```

### Reglas de resolución de parámetros

| Parámetro del método | Qué recibe |
| --- | --- |
| Subclase de `FormRequest` | Se instancia, se valida (`authorize` + `rules`) y se inyecta |
| `Request` (o subclase que no sea FormRequest) | La petición real |
| Sin tipo y llamado `$request` | La petición real |
| Cualquier otro | Parámetros de ruta en orden (`{id}`) con cast a `int`/`float`/`bool` |

```php
public function ficha(StoreProductoRequest $request, string $id): Response
```

### Ejemplo real del núcleo

`System/Requests/LoginRequest.php` ya usa el patrón (login del panel):

```php
final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->isPost()) {
            throw new AuthorizationException('Método no permitido.', 405);
        }

        if (!Csrf::validate($this->input('_token'))) {
            throw new AuthorizationException('Token CSRF inválido.');
        }

        return true;
    }

    public function rules(): array
    {
        return ['email' => 'required|min:3', 'password' => 'required|min:4'];
    }

    public function messages(): array
    {
        return ['email.required' => 'Ingresa tu usuario o correo.'];
    }
}
```

> `$this->validate()` y `Validator::make()` siguen funcionando igual: puedes migrar los
> endpoints existentes a FormRequest poco a poco, sin romper nada.
