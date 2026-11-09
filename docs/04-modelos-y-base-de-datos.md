# 04. Modelos y base de datos

## Conexión

La configuración vive en `config/database.php` y se alimenta del `.env`
(`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_CHARSET`).
Bootstrap configura PDO al arrancar; cualquier modelo obtiene la conexión con
`Database::connection()`.

```php
use Lib\Core\Database;

$rows = Database::connection()->query('SELECT * FROM productos')->fetchAll();

Database::transaction(function ($pdo) {
    $pdo->exec('...');
});

Database::raw('SELECT COUNT(*) FROM productos'); // SELECT/SHOW/DESCRIBE/EXPLAIN → filas; resto → rowCount
```

Opciones PDO: excepciones activadas, fetch asociativo y prepares reales
(sin emulación), lo que protege de inyección SQL al usar `where()` y parámetros.

## Crear un modelo

Un modelo es una clase que extiende `Lib\Core\Model`. En `App/Models/` para tus tablas;
`System/Models/` guarda los del núcleo (`Settings`, `Users`, `Visits`).

```php
<?php
// App/Models/Productos.php

namespace App\Models;

use Lib\Core\Model;

class Productos extends Model
{
    protected string $table = 'productos';
    protected string $primaryKey = 'id';

    protected array $fillable = ['nombre', 'precio', 'stock', 'is_active'];

    protected array $casts = [
        'precio'    => 'float',
        'stock'     => 'integer',
        'is_active' => 'boolean',
    ];
}
```

| Propiedad | Para qué sirve |
| --- | --- |
| `$table` | Nombre real de la tabla (obligatorio) |
| `$primaryKey` | Clave primaria (por defecto `id`) |
| `$fillable` | Whitelist de columnas que `insert()`/`update()` aceptan. Vacío = todas |
| `$casts` | Conversión automática al guardar: `int`/`integer`, `bool`/`boolean`, `float`, `string`, `json` |
| `$timestamps` | `true` (por defecto): agrega `created_at`/`updated_at` automáticamente. `false`: no toca esas columnas |

> Si tu tabla **no** tiene `created_at`/`updated_at`, declara `protected bool $timestamps = false;`
> (así lo hacen `Visits` y tablas legadas) para evitar errores SQL.

## Consultas

```php
use App\Models\Productos;

// Leer
$todos     = Productos::query()->get();
$activos   = Productos::query()->where('is_active', 1)->orderBy('nombre')->get();
$pagina    = Productos::query()->orderBy('id', 'desc')->limit(10)->offset(20)->get();
$uno       = Productos::query()->find(5);            // por primary key
$primero   = Productos::query()->where('nombre', 'Ladrillo')->first(); // ?array
$total     = Productos::query()->where('stock', 0, '>')->count();
$existe    = Productos::query()->where('nombre', 'Ladrillo')->exists();
$nombres   = Productos::query()->pluck('nombre');    // ['Ladrillo', ...]

// Escribir
$id = Productos::query()->insert(['nombre' => 'Ladrillo', 'precio' => 10.5]); // devuelve int
Productos::query()->update(['precio' => 12], ['id' => $id]);   // devuelve filas afectadas
Productos::query()->update(['stock' => 0]);                    // usa el where() acumulado
Productos::query()->delete(['id' => $id]);
Productos::query()->raw('UPDATE productos SET stock = stock + 1 WHERE id = ?', [$id]);
```

Notas del query builder:

- `where($columna, $valor, $operador = '=')` se puede encadenar; las condiciones se unen con `AND`.
- `insert()`/`update()` filtran por `$fillable`, aplican `$casts` y agregan timestamps.
- `update()`/`delete()` sin condiciones usan los `where()` acumulados; si no hay ninguno, lanzan excepción.
- Tras ejecutar una consulta, el builder se reinicia (seguro reutilizar la instancia).

## Crear las tablas

El sistema solo crea automáticamente `settings`, `visits`, `users` y `system_settings`.
Tus tablas se definen de una de estas formas:

### Opción A: en `config/setup.php` (se ejecuta en cada arranque)

```php
$exists = $db->query("SHOW TABLES LIKE 'productos'")->fetchColumn();

if ($exists === false) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS productos (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(190) NOT NULL,
            precio DECIMAL(10,2) DEFAULT 0,
            stock INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )
    ");
}
```

### Opción B: directamente en la base de datos (phpMyAdmin, cliente SQL, migración propia)

## Verlo en el panel

- **Sistema → Base de datos**: lista de tablas con filas estimadas y tamaño; al hacer clic
  muestra columnas, tipos, nulos, claves y valores por defecto (vía `information_schema`).
- **Sistema → Recursos**: detecta automáticamente las clases de `App/Models/*.php` y muestra
  su tabla y contador de registros. No hace falta registrar nada.

## Convenciones recomendadas

- Un modelo por tabla, nombre de clase en plural (`Productos` → `productos`).
- Llaves primarias `BIGINT AUTO_INCREMENT`.
- `created_at`/`updated_at TIMESTAMP NULL` en tablas con timestamps.
- Booleanos como `TINYINT(1)` y decláralos en `$casts` para que el modelo los convierta.
