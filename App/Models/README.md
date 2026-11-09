# App/Models — Tus modelos

Carpeta vacía a propósito: aquí van los modelos de **tus tablas**.
El núcleo vive en `System/Models/` (Settings, Users, Visits) y no se toca.

## Reglas

- Cada clase extiende `Lib\Core\Model`.
- El namespace es `App\Models`.
- Declara `$table`, `$primaryKey`, `$fillable` y `$casts` según tu tabla.
- Los modelos que crees aquí aparecen automáticamente en **Sistema → Recursos**.

## Ejemplo mínimo

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Productos extends Model
{
    protected string $table = 'productos';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'nombre', 'precio', 'is_active', 'created_at', 'updated_at',
    ];

    protected array $casts = [
        'precio' => 'float',
        'is_active' => 'boolean',
    ];
}
```

Consulta la API del `Model` en **Sistema → Documentación → Capítulo 8**.
