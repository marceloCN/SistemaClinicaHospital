# Sistema CMS - Proyecto base

Estructura mínima lista para arrancar un nuevo sistema con el framework MVC
propio del CMS. Incluye el panel de administración (`System/`), el núcleo
(`lib/`), generadores, consola SQL, documentación y autenticación.

## Estructura incluida

```
sistema-cms-base/
├── App/                # Controladores, Middleware, Modelos, Requests y views
├── config/             # app.php, database.php, mail.php, setup.php
├── lib/                # Núcleo del framework (Route, Model, Database, View...)
├── public/             # index.php, .htaccess, assets y frontend (sin medicina/)
├── resources/          # dompdf, FPDF
├── routes/             # app.php (admin) + web.php (vacío, aquí programas)
├── System/             # Panel de administración, generadores y documentación
├── tests/              # Suite de comprobaciones
├── tools/              # Utilidades opcionales
├── vendor/             # Dependencias de Composer
├── bootstrap.php       # Arranque de la aplicación
├── composer.json       # Autoload y dependencias
├── .env                # Configuración de entorno (cambia los valores)
├── .env.example        # Plantilla del .env
├── Dockerfile          # Imagen PHP 8.2 + Apache
├── docker-compose.yml  # PHP + Apache + PostgreSQL listos para usar
└── php.ini             # Configuración de PHP
```

> **Nota:** `storage/` no se incluye porque se genera automáticamente la
> primera vez que el sistema arranca. Debe ser escribible por el servidor web.

## Arranque local con Docker

1. Tener instalados Docker y docker-compose.
2. Copiar el paquete a una carpeta nueva:
   ```bash
   unzip sistema-cms-base.zip
   cd sistema-cms-base
   ```
3. Levantar los contenedores:
   ```bash
   docker-compose up -d
   ```
4. Esperar a que la base de datos esté sana (`docker-compose logs -f db`).
5. Acceder al panel:
   ```
   http://localhost:8095/join/access/dashboard
   ```
   El sistema crea las tablas base en el primer acceso si `storage/.installed`
   no existe.

## Arranque local sin Docker (PHP nativo)

Requisitos: PHP >= 8.1 con extensiones `pdo`, `pdo_pgsql` (o `pdo_mysql`),
`zip`, `mbstring`; PostgreSQL o MySQL; Composer.

1. Copiar `.env.example` a `.env` y ajustar los datos de base de datos.
2. Crear la base de datos vacía.
3. Instalar dependencias (si no trajiste `vendor/`):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Apuntar el virtual host a la carpeta `public/` (equivalente al
   `DocumentRoot` del Dockerfile).
5. Acceder al panel. El sistema creará `storage/` y las tablas base en el
   primer uso.

## Despliegue a producción

1. Configurar `.env` con `APP_ENV=production`, `APP_DEBUG=false` y las
   credenciales reales de base de datos y correo.
2. Asegurar que `storage/` exista y tenga permisos de escritura.
3. Opciones de servidor:
   - **Simple:** usar el mismo Dockerfile y subir la imagen a tu VPS.
   - **Recomendado:** nginx como proxy/reescritura + php-fpm (ver
     `public/.htaccess` para las reglas de rewrite equivalentes).
4. Ejecutar la suite de pruebas antes de publicar:
   ```bash
   php tests/run.php
   ```

## Dónde programar

- **Rutas públicas:** `routes/web.php` (vacío de fábrica).
- **Rutas de administración:** `routes/app.php`.
- **Controladores:** `App/Controllers/`.
- **Modelos:** `App/Models/`.
- **Vistas públicas:** `App/views/`.
- **Vistas del panel:** `System/views/dashboard/`.

## Primer módulo

Sigue el Capítulo 12 del manual (`Sistema → Documentación`) para crear un CRUD
completo paso a paso.