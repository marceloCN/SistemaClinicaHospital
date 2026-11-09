# 07. Despliegue y entorno

## Requisitos

- Docker + Docker Compose (este proyecto se ejecuta en contenedores).
- Puerto 8095 libre en el host.
- Red Docker externa `bases_de_datos_db_network` (la comparten los contenedores de bases de datos).

## Contenedores

`docker-compose.yml` (en la carpeta padre `proyectoGupadis/`):

```yaml
services:
  gupadis:
    build:
      context: .
      dockerfile: ./sistema/Dockerfile
    container_name: php_apache_gupadis
    ports:
      - "8095:80"
    volumes:
      - ./sistema:/var/www/html
    networks:
      - db_network

networks:
  db_network:
    external: true
    name: bases_de_datos_db_network
```

- La imagen (`sistema/Dockerfile`) parte de `php:8.2-apache`, instala `zip`, `mysqli`,
  `pdo_mysql`, habilita `mod_rewrite`, define el `DocumentRoot` en `/var/www/html/public`
  y copia `php.ini` (vive en la raíz de `proyectoGupadis/`).
- El código se monta por volumen: **los cambios de archivos no requieren reconstruir**;
  solo hace falta rebuild si cambias el `Dockerfile` o `php.ini`.
- MariaDB corre en otro contenedor (`mariadb`) accesible por la red interna con el host
  `mariadb`.

## Comandos habituales

```bash
# Desde proyectoGupadis/
docker compose up -d --build       # construir y levantar
docker compose logs -f gupadis     # logs de Apache/PHP
docker compose restart gupadis     # reiniciar
docker compose down                # detener

# Verificar PHP dentro del contenedor
docker exec php_apache_gupadis php -v
docker exec php_apache_gupadis php -l /var/www/html/lib/Core/Route.php

# Consola de MariaDB
docker exec -it mariadb mariadb -uroot -proot123 gupadis
```

## Variables de entorno (`.env`)

Copia `.env.example` a `.env` y ajusta:

| Variable | Descripción | Ejemplo |
| --- | --- | --- |
| `APP_NAME` | Nombre por defecto del sitio | `Sistema CMS` |
| `APP_ENV` | Entorno (`local`, `production`) | `local` |
| `APP_URL` | URL base para assets y enlaces | `http://localhost:8095` |
| `APP_DEBUG` | Muestra errores PHP (`true` solo en desarrollo) | `true` |
| `APP_KEY` | Reservado para futuras funciones de cifrado (hoy no se usa) | — |
| `DB_CONNECTION` | Driver: `mysql` o `pgsql` | `mysql` |
| `DB_HOST` | Host de base de datos | `mariadb` |
| `DB_PORT` | Puerto | `3306` |
| `DB_DATABASE` | Base de datos | `gupadis` |
| `DB_USERNAME` / `DB_PASSWORD` | Credenciales | `root` / `root123` |
| `DB_CHARSET` | Codificación | `utf8mb4` |
| `TIMEZONE` | Zona horaria | `America/La_Paz` |
| `LANGUAGE` | Idioma | `es` |
| `SESSION_LIFETIME` | Minutos de sesión | `120` |

> En producción: `APP_DEBUG=false`, credenciales reales y `APP_URL` con el dominio final.

## Composer

```bash
# En sistema/
composer install                 # dependencias de producción + dev (phpunit, no usado por el runner)
composer install --no-dev        # solo producción
composer test                    # ejecuta tests/run.php (runner propio, sin PHPUnit)
composer dump-autoload           # regenerar autoload tras crear clases/carpetas
```

`vendor/` se genera en el host (está montado al contenedor). Si el navegador muestra
`Run "composer install" first.`, ejecuta `composer install` en `sistema/`.

## Primer arranque

1. `composer install` en `sistema/`.
2. Copia `.env.example` → `.env`.
3. `docker compose up -d --build`.
4. Abre <http://localhost:8095/join/access/dashboard>.
5. Si la tabla `users` está vacía, se crea el admin inicial `admin / admin123`.
   **Cambia la contraseña** en cuanto entres.

## Solución de problemas

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| `Run "composer install" first` | Falta `vendor/` | `composer install` en `sistema/` |
| Página sin estilos/JS | `APP_URL` incorrecto | Ajusta `APP_URL` en `.env` (incluye puerto) |
| `Database connection failed` | Host/credenciales o red Docker | Revisa `.env`, la red `bases_de_datos_db_network` y que `mariadb` esté arriba |
| 404 en todas las rutas | `mod_rewrite` / `.htaccess` | Reconstruye la imagen (`--build`); verifica `public/.htaccess` |
| Cambios no visibles | Caché del navegador | Hard reload; el volumen monta el código en vivo |
| Error tras crear clases | Autoload desactualizado | `composer dump-autoload` |

## Notas

- `resources/` (por ejemplo `resources/dompdf/`) contiene librerías embebidas que no pasan
  por Composer; se incluyen con `require` cuando se necesitan.
- Los assets del panel viven en `public/code/` (AdminLTE, jQuery, Bootstrap, Font Awesome,
  `notification.js`) — es parte del núcleo, no los muevas.
- Nunca subas `.env` a un repositorio.
