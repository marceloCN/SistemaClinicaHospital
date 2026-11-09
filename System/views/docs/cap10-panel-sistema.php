<section class="doc-chapter" id="cap10" data-doc-chapter data-number="10" data-title="Panel del sistema: toggles y mantenimiento">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 10</span>
        <h2 class="doc-chapter-title">Panel del sistema: toggles y mantenimiento</h2>
        <p class="doc-chapter-lead">
            El bloque <strong>Sistema</strong> del panel es el centro de control del CMS: muestra el estado
            del servidor, permite deshabilitar rutas y middlewares sin tocar código, elegir la página de
            inicio, activar el modo mantenimiento y explorar la base de datos con su consola SQL. Todo lo
            que cambias aquí se guarda en la tabla <code>system_settings</code> y se aplica en la
            siguiente petición.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap10-mapa">Mapa del bloque</a>
            <a class="doc-chip" href="#cap10-rutas">Rutas y enlaces</a>
            <a class="doc-chip" href="#cap10-middleware">Middlewares</a>
            <a class="doc-chip" href="#cap10-mantenimiento">Mantenimiento</a>
            <a class="doc-chip" href="#cap10-datos">Base de datos</a>
        </div>
    </header>

    <section class="doc-section" id="cap10-mapa" data-doc-section data-title="Mapa del bloque Sistema">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap10-mapa">#</a>Mapa del bloque Sistema</h3>
        <p>
            Todas las páginas y acciones viven en <code>SystemController</code> y comparten el prefijo
            <code>join/access/dashboard/Area-of-work/system</code>. Las rutas están registradas en
            <code>routes/app.php</code> dentro de un grupo <code>Route::controller(SystemController::class, …)</code>
            protegido por el middleware <code>Auth</code>.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Página</th>
                        <th>Ruta (nombre)</th>
                        <th>Qué muestra o hace</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Resumen</td>
                        <td><code>GET /Area-of-work/system</code> (<code>system</code>)</td>
                        <td>Versión de PHP, entorno, servidor, ruta base, conexión, nº de tablas, rutas,
                            usuarios y el interruptor de mantenimiento.</td>
                    </tr>
                    <tr>
                        <td>Rutas y enlaces</td>
                        <td><code>GET /routes</code> (<code>system_routes</code>)</td>
                        <td>Inventario de rutas con su método, URI, nombre, archivo de origen
                            (<code>web.php</code>/<code>app.php</code>), middlewares y toggle.</td>
                    </tr>
                    <tr>
                        <td>Middlewares</td>
                        <td><code>GET /middleware</code> (<code>system_middleware</code>)</td>
                        <td>Middlewares en uso, en cuántas rutas y su estado; permite desactivarlos.</td>
                    </tr>
                    <tr>
                        <td>Página de inicio</td>
                        <td><code>GET|POST /home</code> (<code>system_home</code>)</td>
                        <td>Elige qué ruta pública se abre en <code>/</code>.</td>
                    </tr>
                    <tr>
                        <td>Base de datos</td>
                        <td><code>GET /database</code> (<code>system_database</code>)</td>
                        <td>Pestañas de conexión activa, tablas con motor/filas/tamaño y consola SQL
                            (<code>POST /database/query</code>).</td>
                    </tr>
                    <tr>
                        <td>Generadores</td>
                        <td><code>GET /Area-of-work/system/generators</code> (<code>system_generators</code>)</td>
                        <td>Escanea <code>App/</code>, enseña fuente y crea controladores, vistas, modelos,
                            rutas y FormRequest (Capítulo 13).</td>
                    </tr>
                    <tr>
                        <td>Documentación</td>
                        <td><code>GET /docs</code> (<code>system_docs</code>)</td>
                        <td>Este manual, con búsqueda y descarga en PDF.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 250" width="940" height="250" role="img"
                aria-label="El panel guarda toggles en system_settings y el router los aplica en cada peticion">
                <rect x="30" y="40" width="240" height="90" rx="10" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="150" y="70" font-size="12.5" font-weight="700" text-anchor="middle" fill="#0b4ea8">Panel Sistema</text>
                <text x="150" y="90" font-size="11" text-anchor="middle" fill="#1e3a8a">POST con _token CSRF</text>
                <text x="150" y="108" font-size="11" text-anchor="middle" fill="#1e3a8a">routeToggle / middlewareToggle</text>

                <line x1="270" y1="85" x2="348" y2="85" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="356,85 344,79 344,91" fill="#94a3b8"></polygon>

                <rect x="360" y="40" width="240" height="90" rx="10" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="480" y="70" font-size="12.5" font-weight="700" text-anchor="middle" fill="#166534">Lib\Support\System</text>
                <text x="480" y="90" font-size="11" text-anchor="middle" fill="#14532d">set('route.enabled.…', '0')</text>
                <text x="480" y="108" font-size="11" text-anchor="middle" fill="#14532d">set('middleware.enabled.…', '0')</text>

                <line x1="600" y1="85" x2="658" y2="85" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="666,85 654,79 654,91" fill="#94a3b8"></polygon>

                <rect x="670" y="40" width="240" height="90" rx="10" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="790" y="70" font-size="12.5" font-weight="700" text-anchor="middle" fill="#92400e">system_settings</text>
                <text x="790" y="90" font-size="11" text-anchor="middle" fill="#78350f">clave / valor</text>
                <text x="790" y="108" font-size="11" text-anchor="middle" fill="#78350f">una fila por toggle</text>

                <rect x="30" y="160" width="880" height="66" rx="9" fill="#f1f5f9" stroke="#94a3b8"></rect>
                <text x="470" y="184" font-size="11.5" text-anchor="middle" fill="#334155">En la siguiente petición, Route::dispatch() consulta System::routeEnabled() y System::middlewareEnabled() antes de ejecutar.</text>
                <text x="470" y="204" font-size="11.5" text-anchor="middle" fill="#475569">Si una ruta está deshabilitada se salta y la petición termina en 404; si un middleware no es Auth, se omite de la cadena.</text>
            </svg>
            <span class="doc-caption">Figura 10.1 — El panel no cambia archivos: escribe toggles y el router los lee en cada petición.</span>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Doble protección</span>
            <p>Además del middleware <code>Auth</code>, cada acción del controlador vuelve a comprobar la sesión:
                las páginas usan <code>adminPage()</code> (redirige al login) y las acciones AJAX usan
                <code>adminJson()</code> (responde 403 JSON). Si añades una página nueva, copia ese patrón.</p>
        </div>
    </section>

    <section class="doc-section" id="cap10-rutas" data-doc-section data-title="Rutas y enlaces: deshabilitar sin borrar código">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap10-rutas">#</a>Rutas y enlaces: deshabilitar sin borrar código</h3>
        <p>
            La página <strong>Sistema → Rutas y enlaces</strong> lista todo lo registrado en
            <code>Route::all()</code>. Cada fila muestra método, URI, nombre, middlewares y un interruptor.
            El estado se guarda en <code>system_settings</code> con la clave
            <code>route.enabled.&lt;nombre&gt;</code> (o la URI si la ruta no tiene nombre).
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Acción</th>
                        <th>Endpoint</th>
                        <th>Respuesta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Deshabilitar / habilitar una ruta</td>
                        <td><code>POST /Area-of-work/system/routes/toggle</code>
                            (<code>system_route_toggle</code>)</td>
                        <td><code>{"success":true,"enabled":false}</code></td>
                    </tr>
                    <tr>
                        <td>Deshabilitar / habilitar un middleware</td>
                        <td><code>POST /Area-of-work/system/middleware/toggle</code>
                            (<code>system_middleware_toggle</code>)</td>
                        <td><code>{"success":true,"enabled":true}</code></td>
                    </tr>
                    <tr>
                        <td>Cambiar la página de inicio</td>
                        <td><code>POST /Area-of-work/system/home</code>
                            (<code>system_home_update</code>)</td>
                        <td><code>{"success":true,"route":"inicio"}</code></td>
                    </tr>
                    <tr>
                        <td>Activar / desactivar mantenimiento</td>
                        <td><code>POST /Area-of-work/system/maintenance</code>
                            (<code>system_maintenance_toggle</code>)</td>
                        <td><code>{"success":true,"enabled":true}</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>
            Los tres primeros endpoints comprueban método y CSRF con <code>validatePost()</code>; el de
            mantenimiento usa el mismo patrón. Todos responden JSON porque los consume el JavaScript del
            panel, no un formulario HTML.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Cómo se comprueba una ruta en el router</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// lib/Core/Route.php — dentro de dispatch()
if (!System::routeEnabled($routeData['name'] ?? $route)) {
    continue; // la ruta se ignora: si no hay otra que coincida, termina en 404
}

// El estado vive en system_settings: route.enabled.&lt;clave&gt;
System::routeEnabled('productos');        // true por defecto
System::setRouteEnabled('productos', false);
Route::isEnabled('productos');             // false</code></pre>
        </div>

        <ul class="doc-check">
            <li>Deshabilita una ruta pública: deja de responder y el log no muestra errores (404 esperado).</li>
            <li>Vuelve a habilitarla desde el mismo interruptor: responde de nuevo sin reiniciar nada.</li>
            <li>Intenta deshabilitar una ruta del panel: el controlador responde 403; las rutas bajo
                <code>join/access/dashboard</code> están blindadas.</li>
            <li>Borra la fila de <code>system_settings</code> y recarga: el valor por defecto es habilitado.</li>
        </ul>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>El nombre es la clave</span>
            <p>Si dos rutas comparten nombre, comparten interruptor. Usa nombres únicos (los del panel ya lo
                son) y recuerda que renombrar una ruta deja huérfana su fila antigua en
                <code>system_settings</code>; puedes borrarla con <code>System::remove('route.enabled.viejo')</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap10-middleware" data-doc-section data-title="Middlewares: ver uso y desactivar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap10-middleware">#</a>Middlewares: ver uso y desactivar</h3>
        <p>
            La página <strong>Sistema → Middlewares</strong> recorre <code>Route::all()</code>, agrupa por
            nombre y muestra en cuántas rutas se usa cada uno. El estado se guarda con la clave
            <code>middleware.enabled.&lt;Nombre&gt;</code>.
        </p>

        <div class="doc-cols">
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-shield-alt"></i>Auth está protegido</div>
                <p><code>System::isProtectedMiddleware('Auth')</code> devuelve <code>true</code>:
                    no aparece desactivable y el endpoint responde 403 si lo intentas. Desactivar la
                    autenticación dejaría el panel abierto.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-plug"></i>Resolución del nombre</div>
                <p>El router busca <code>System\Middleware\Nombre</code> y, si no existe,
                    <code>App\Middleware\Nombre</code>. El nombre visible es el que usas en
                    <code>Route::middleware('Nombre')</code>.</p>
            </div>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Desactivar un middleware desde código</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use Lib\Support\System;

System::middlewareEnabled('SoloAjax');            // true por defecto
System::setMiddlewareEnabled('SoloAjax', false);  // devuelve true si se guardo
System::setMiddlewareEnabled('Auth', false);      // devuelve false: protegido

// En Route::dispatch(), un middleware deshabilitado se omite:
if (!System::middlewareEnabled($name)) {
    continue;
}</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Úsalo para diagnosticar</span>
            <p>Si un 403 aparece solo en producción, desactiva temporalmente el middleware sospechoso desde
                esta página, reproduce la petición y vuelve a activarlo. Es más rápido que editar rutas y
                desplegar, y el cambio queda registrado en <code>system_settings</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap10-mantenimiento" data-doc-section data-title="Modo mantenimiento y página de inicio">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap10-mantenimiento">#</a>Modo mantenimiento y página de inicio</h3>
        <p>
            El mantenimiento no es un middleware: se aplica en <code>public/index.php</code> antes de
            despachar rutas. El valor se carga en <code>bootstrap.php</code> desde
            <code>settings.maintenance_mode</code> y se copia a la sesión como <code>MAINTENANCE</code>.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">public/index.php — bloqueo del sitio público</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>$publicPath = trim($request-&gt;uri(), '/');
$isAdminArea = str_starts_with($publicPath, 'join/access/dashboard');

if (!$isAdminArea &amp;&amp; Session::get('MAINTENANCE')) {
    $response = Response::make(
        View::render('error.maintenance', [
            'siteName' =&gt; Session::get('SITE_NAME', config('app.name')),
        ]),
        503
    );

    $response-&gt;send();
}</code></pre>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Situación</th>
                        <th>Qué ocurre</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Mantenimiento activo + ruta pública</td>
                        <td>Responde <code>503</code> con la vista <code>error.maintenance</code>.
                            No se ejecuta el controlador ni se registra la visita.</td>
                    </tr>
                    <tr>
                        <td>Mantenimiento activo + panel</td>
                        <td>El panel sigue funcionando: la comprobación excluye todo lo que empieza por
                            <code>join/access/dashboard</code>.</td>
                    </tr>
                    <tr>
                        <td>Activar desde el panel</td>
                        <td><code>Settings::update(['maintenance_mode' =&gt; 1], ['id' =&gt; 1])</code> y
                            actualiza la sesión al instante.</td>
                    </tr>
                    <tr>
                        <td>Cambiar la p&aacute;gina de inicio</td>
                        <td><code>System::setHomeRoute('nombre')</code> con una ruta <code>GET</code>
                            con nombre fuera del panel. El valor por defecto es <code>inicio</code>.
                            La ruta <code>/</code> de <code>routes/web.php</code> redirige a la ruta elegida
                            cuando es distinta de <code>inicio</code>.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Mantenimiento y sesión</span>
            <p>El valor se lee de la sesión, no de la base de datos en cada petición. Si cambias
                <code>maintenance_mode</code> directamente en la base, un usuario con sesión abierta no lo
                verá hasta que vuelva a iniciar sesión (o hasta que el panel lo cambie, que actualiza la
                sesión).</p>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Probar sin dejar el sitio caído</span>
            <p>Activa el mantenimiento, abre una ventana de incógnito (sin sesión) para ver el 503 y otra
                con tu sesión de administrador para comprobar que el panel sigue disponible. Desactívalo al
                terminar.</p>
        </div>
    </section>

    <section class="doc-section" id="cap10-datos" data-doc-section data-title="Base de datos y consola SQL">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap10-datos">#</a>Base de datos</h3>

        <div class="doc-h4">Explorador y consola SQL</div>
        <p>
            La página de base de datos lista las tablas de la conexión activa y, al elegir una, sus
            columnas. Usa <code>information_schema</code> en MySQL y el catálogo de PostgreSQL cuando el
            driver es <code>pgsql</code>. La contraseña de la conexión se enmascara antes de pasarla a la
            vista; nunca se imprime la real.
        </p>
        <p>
            La pestaña <strong>Modo consola</strong> ejecuta SQL contra la conexión activa: los
            <code>SELECT</code>/<code>SHOW</code>/<code>EXPLAIN</code> devuelven una tabla con columnas y
            filas, y los <code>INSERT</code>/<code>UPDATE</code>/<code>DELETE</code>/DDL devuelven las
            filas afectadas. Los errores llegan depurados con su SQLSTATE, el detalle del driver y una
            sugerencia en español.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Página</th>
                        <th>Fuente de los datos</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Base de datos</td>
                        <td><code>information_schema.TABLES</code> y <code>information_schema.COLUMNS</code>
                            (MySQL) o <code>pg_class</code> / <code>information_schema.columns</code> (PostgreSQL).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Verifica la conexión de un vistazo</span>
            <p>La pestaña <strong>Conexión activa</strong> muestra driver, servidor, base de datos y
                usuario sin exponer la contraseña real: es la forma más rápida de comprobar a qué BD
                está conectado el sistema.</p>
        </div>
    </section>

    <section class="doc-section" id="cap10-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap10-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Entra a <strong>Sistema → Rutas y enlaces</strong>: ves todas las rutas y puedes deshabilitar
                una pública.</li>
            <li>Aplica un middleware de ejemplo (por ejemplo <code>SoloAjax</code>) a una de tus rutas,
                deshabil&iacute;talo desde aqu&iacute; y comprueba que deja de aplicarse.</li>
            <li>Intenta deshabilitar <code>Auth</code> o una ruta del panel: recibes 403.</li>
            <li>Activa el mantenimiento, abre el sitio sin sesión: 503 con la vista de mantenimiento.</li>
            <li>Agrega una ruta p&uacute;blica con nombre distinta de <code>inicio</code>, el&iacute;gela en
                <strong>P&aacute;gina de inicio</strong> y abre <code>/</code>: te redirige a ella.</li>
            <li>En <strong>Base de datos</strong> ves las tablas reales y puedes ejecutar un
                <code>SELECT</code> de prueba desde la consola, sin exponer la contraseña.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap11"><i class="fas fa-arrow-right mr-1"></i>Siguiente: pruebas y depuración</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap10') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
