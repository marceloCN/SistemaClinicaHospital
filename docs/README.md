# Documentación del Sistema CMS

Índice de guías. Cada archivo puede leerse por separado; el orden sugerido es:

| # | Guía | Para qué sirve |
| --- | --- | --- |
| 01 | [Arquitectura](01-arquitectura.md) | Entender el ciclo de una petición y el rol de cada carpeta (`lib/`, `System/`, `App/`) |
| 02 | [Rutas y middleware](02-rutas-y-middleware.md) | Registrar rutas, agruparlas, protegerlas con middlewares y usar los toggles del panel |
| 03 | [Validación y FormRequest](03-validacion-y-form-requests.md) | Reglas del `Validator`, CSRF y cómo crear tus propios FormRequest (estilo Laravel) |
| 04 | [Modelos y base de datos](04-modelos-y-base-de-datos.md) | Consultas, insert/update/delete, casts, timestamps y creación de tablas |
| 05 | [Vistas y layout](05-vistas-y-layout.md) | Motor de vistas, placeholders, layout AdminLTE y notificaciones flash |
| 06 | [Panel del sistema](06-panel-del-sistema.md) | Login, módulo Sistema, toggles de rutas/middlewares/inicio y mantenimiento |
| 07 | [Despliegue y entorno](07-despliegue-y-entorno.md) | Docker, `.env`, Composer y solución de problemas |
| 08 | [Crear un módulo](08-crear-un-modulo.md) | Tutorial completo: tabla + modelo + rutas + controlador + vista |

Documentación rápida adicional:

- `README.md` en la raíz del proyecto (resumen y arranque rápido).
- `App/README.md`, `System/README.md` y `lib/README.md` (rol de cada carpeta).
- Panel de administración → **Sistema → Documentación** (manual de referencia, 15 capítulos).
  Es la fuente principal y se actualiza junto con el código; estas guías Markdown son un
  resumen complementario.
