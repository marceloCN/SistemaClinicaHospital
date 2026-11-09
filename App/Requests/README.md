# App/Requests — Tus FormRequest

Carpeta vacía a propósito: aquí van las clases de validación de **tus formularios**.
El núcleo usa este mismo mecanismo en `System/Requests/` (ej. `LoginRequest`).

## Reglas

- Cada clase extiende `Lib\Core\FormRequest`.
- El namespace es `App\Requests`.
- Implementa `authorize()` (método HTTP + CSRF), `rules()` y, si quieres, `messages()`.
- Tipa el parámetro del controlador con la clase: el router la valida **antes** de entrar.

## Ejemplo mínimo

```php
<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

class StoreProductoRequest extends FormRequest
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
        return [
            'nombre' => 'required|min:3|max:190',
            'precio' => 'required|numeric',
        ];
    }
}
```

Reglas disponibles: `required`, `email`, `min:n`, `max:n`, `numeric`, `integer`, `in:a,b,c`, `confirmed`.
Ver **Sistema → Documentación → Capítulo 5**.
