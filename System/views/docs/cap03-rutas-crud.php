<section class="doc-chapter" id="cap3" data-doc-chapter data-number="3" data-title="Rutas y CRUDs (web.php / app.php)">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 3</span>
        <h2 class="doc-chapter-title">Rutas y CRUDs (web.php / app.php)</h2>
        <p class="doc-chapter-lead">
            Las rutas son el mapa del sistema: conectan cada URL con un método de controlador.
            Aquí aprenderás todos los métodos del router, cómo agrupar, cómo pasar parámetros y el
            mapa completo para construir un CRUD de cuatro operaciones.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap3-donde">¿web.php o app.php?</a>
            <a class="doc-chip" href="#cap3-metodos">Métodos del router</a>
            <a class="doc-chip" href="#cap3-crud">Mapa del CRUD</a>
        </div>
    </header>

    <section class="doc-section" id="cap3-donde" data-doc-section data-title="¿web.php o app.php?">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap3-donde">#</a>¿web.php o app.php?</h3>
        <p>
            El sistema tiene dos archivos de rutas y una regla clara para cada uno:
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Para qué</th>
                        <th>¿Protegido?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>routes/web.php</code></td>
                        <td>Sitio público: páginas abiertas, landings, formularios públicos.</td>
                        <td>Sin sesión (agrega tus middlewares si lo necesitas).</td>
                    </tr>
                    <tr>
                        <td><code>routes/app.php</code></td>
                        <td>Panel de administración: todos tus módulos internos.</td>
                        <td>Bajo <code>/join/access/dashboard</code> con middleware <code>Auth</code>.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>El orden importa</span>
            <p>En <code>public/index.php</code> primero se carga <code>app.php</code> y después <code>web.php</code>.
                Si registras la misma combinación método + URI dos veces, la <strong>última</strong> registrada
                sobrescribe a la anterior (la URI es la clave del arreglo de rutas). Evita duplicar URIs.</p>
        </div>
    </section>

    <section class="doc-section" id="cap3-anatomia" data-doc-section data-title="Anatomía de una ruta">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap3-anatomia">#</a>Anatomía de una ruta</h3>
        <p>
            Toda ruta tiene cuatro piezas: <strong>método HTTP</strong>, <strong>URI</strong>,
            <strong>acción</strong> (controlador y método) y <strong>nombre</strong>. Los middlewares
            y prefijos se añaden como envoltorios.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">routes/app.php — estructura real de un módulo</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

use Lib\Core\Route;
use App\Controllers\ProductosController;

Route::controller(ProductosController::class, function () {      // 1. acción
    Route::middleware(['Auth'], function () {                     // 2. middlewares
        Route::prefix('/Area-of-work/productos', function () {    // 3. prefijo
            Route::get('/', 'index')->name('productos');          // 4. ruta + nombre
            Route::post('/guardar', 'guardar')->name('productos_guardar');
            Route::get('/editar/{id}', 'editar')->name('productos_editar');
            Route::post('/actualizar/{id}', 'actualizar')->name('productos_actualizar');
            Route::post('/eliminar/{id}', 'eliminar')->name('productos_eliminar');
        });
    });
});</code></pre>
        </div>

        <p>Así se descompone la URL final de una de esas rutas:</p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 250" width="940" height="250" role="img"
                aria-label="Anatomia de la URL del panel y como se construye">
                <rect x="20" y="30" width="900" height="52" rx="10" fill="#0f172a"></rect>
                <text x="40" y="62" font-size="14" fill="#e2e8f0">https://misitio.com/join/access/dashboard/Area-of-work/productos/editar/17</text>

                <rect x="238" y="98" width="205" height="40" rx="8" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="340" y="123" font-size="12" text-anchor="middle" fill="#0b4ea8">prefijo del panel</text>
                <line x1="300" y1="84" x2="340" y2="98" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="458" y="98" width="215" height="40" rx="8" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="565" y="123" font-size="12" text-anchor="middle" fill="#166534">Route::prefix(...)</text>
                <line x1="565" y1="84" x2="565" y2="98" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="688" y="98" width="215" height="40" rx="8" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="795" y="123" font-size="12" text-anchor="middle" fill="#92400e">ruta del controlador</text>
                <line x1="795" y1="84" x2="795" y2="98" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="238" y="162" width="205" height="40" rx="8" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="340" y="187" font-size="12" text-anchor="middle" fill="#334155">agregado por el panel</text>

                <rect x="458" y="162" width="215" height="40" rx="8" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="565" y="187" font-size="12" text-anchor="middle" fill="#334155">/Area-of-work/productos</text>

                <rect x="688" y="162" width="215" height="40" rx="8" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="795" y="187" font-size="12" text-anchor="middle" fill="#334155">/editar/{id} → id = 17</text>

                <text x="470" y="230" font-size="12" text-anchor="middle" fill="#64748b">El parámetro {id} se convierte en el argumento $id del método editar(); el resto de la URL nunca cambia.</text>
            </svg>
            <span class="doc-caption">Figura 3.1 — Cómo se forma la URL final del panel.</span>
        </div>
    </section>

    <section class="doc-section" id="cap3-metodos" data-doc-section data-title="Todos los métodos y bloques del router">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap3-metodos">#</a>Todos los métodos y bloques del router</h3>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Bloque</th>
                        <th>Qué hace</th>
                        <th>Ejemplo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="doc-method doc-method-get">GET</span></td>
                        <td>Mostrar una página o consultar datos (nunca modifica nada).</td>
                        <td><code>Route::get('/', 'index')</code></td>
                    </tr>
                    <tr>
                        <td><span class="doc-method doc-method-post">POST</span></td>
                        <td>Crear, actualizar o eliminar datos (siempre con token CSRF).</td>
                        <td><code>Route::post('/guardar', 'guardar')</code></td>
                    </tr>
                    <tr>
                        <td><span class="doc-method doc-method-put">PUT</span> <span class="doc-method doc-method-patch">PATCH</span> <span class="doc-method doc-method-delete">DELETE</span></td>
                        <td>Disponibles para APIs externas que envían esos verbos reales. En el panel se usa POST
                            (los formularios HTML solo envían GET y POST).</td>
                        <td><code>Route::delete('/api/{id}', 'borrar')</code></td>
                    </tr>
                    <tr>
                        <td><code>->name('x')</code></td>
                        <td>Asigna el nombre con el que generarás enlaces y toggles.</td>
                        <td><code>route('productos')</code></td>
                    </tr>
                    <tr>
                        <td><code>->middleware([...])</code></td>
                        <td>Encadena middlewares solo a esa ruta.</td>
                        <td><code>->middleware(['Auth', 'SoloAjax'])</code></td>
                    </tr>
                    <tr>
                        <td><code>Route::controller(Clase::class, fn)</code></td>
                        <td>Declara la clase para todos los <code>Route::get/post</code> internos: el segundo
                            argumento de cada ruta es solo el nombre del método.</td>
                        <td><code>Route::get('/', 'index')</code></td>
                    </tr>
                    <tr>
                        <td><code>Route::middleware([...], fn)</code></td>
                        <td>Grupo de middlewares (se pueden anidar).</td>
                        <td><code>Route::middleware(['Auth'], fn)</code></td>
                    </tr>
                    <tr>
                        <td><code>Route::prefix('...', fn)</code></td>
                        <td>Grupo de prefijo de URL (se pueden anidar).</td>
                        <td><code>Route::prefix('/Area-of-work/productos', fn)</code></td>
                    </tr>
                    <tr>
                        <td><code>Route::group([...], fn)</code></td>
                        <td>Prefijo + middlewares en una sola llamada (estilo Laravel).</td>
                        <td><code>Route::group(['prefix' =&gt; 'admin', 'middleware' =&gt; ['Auth']], fn)</code></td>
                    </tr>
                    <tr>
                        <td>Closures</td>
                        <td>Una ruta también puede resolverse con una función anónima, sin controlador.</td>
                        <td><code>Route::get('/ping', fn() =&gt; 'pong')</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">routes/web.php — los cuatro estilos válidos</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

use Lib\Core\Route;

// 1. Closure: la respuesta directa
Route::get('/ping', function () {
    return 'pong';
})->name('ping');

// 2. Closure que recibe el Request inyectado
Route::get('/quien-soy', function (\Lib\Core\Request $request) {
    return 'Tu IP es ' . $request->ip();
});

// 3. Controlador con metodo (accion en array)
Route::get('/servicios', [\App\Controllers\ServiciosController::class, 'index'])-&gt;name('servicios');

// 4. Grupo de controlador: dentro, la accion es solo el nombre del metodo
Route::controller(\App\Controllers\ApiController::class, function () {
    Route::middleware(['SoloAjax'], function () {
        Route::prefix('api/v1', function () {
            Route::get('/estado', 'estado')-&gt;name('api_estado');
        });
    });
});</code></pre>
        </div>
    </section>

    <section class="doc-section" id="cap3-parametros" data-doc-section data-title="Parámetros de ruta y cómo llegan al método">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap3-parametros">#</a>Parámetros de ruta y cómo llegan al método</h3>
        <p>
            Los segmentos entre llaves (<code>{id}</code>, <code>{slug}</code>…) se capturan y llegan al
            método como argumentos. La resolución es por reflexión: el sistema decide qué inyectar según
            el tipo del parámetro.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Parámetro del método</th>
                        <th>Qué recibe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Subclase de <code>FormRequest</code></td>
                        <td>Se instancia con la petición real, ejecuta <code>authorize()</code> y <code>rules()</code>,
                            y llega <strong>ya validada</strong> (ver <a href="#cap5">Capítulo 5</a>).</td>
                    </tr>
                    <tr>
                        <td><code>Request $request</code></td>
                        <td>La petición real: método, datos, archivos, headers.</td>
                    </tr>
                    <tr>
                        <td><code>string $id</code></td>
                        <td>El parámetro de ruta en orden, sin conversión.</td>
                    </tr>
                    <tr>
                        <td><code>int $id</code> · <code>float $precio</code> · <code>bool $activo</code></td>
                        <td>El parámetro de ruta convertido al tipo (si escribes el tipo, se castea solo).</td>
                    </tr>
                    <tr>
                        <td>Sin tipo llamado <code>$request</code></td>
                        <td>La petición real (compatibilidad).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Ruta con dos parámetros + inyección de Request</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// routes/app.php
Route::controller(PedidosController::class, function () {
    Route::get('/pedidos/{anio}/{mes}', 'pedidosDelMes')->name('pedidos_mes');
});

// App/Controllers/PedidosController.php
use Lib\Core\Request;
use Lib\Core\Response;

public function pedidosDelMes(Request $request, int $anio, int $mes): Response
{
    // $anio y $mes ya son enteros; $request trae query strings, cookies, etc.
    $pedidos = Pedidos::query()
        ->where('anio', $anio)
        ->where('mes', $mes)
        ->get();

    return $this->json(['success' =&gt; true, 'data' =&gt; $pedidos]);
}</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Orden de los argumentos</span>
            <p>Los parámetros de ruta se asignan en el orden en que aparecen en la URL. Si el método tiene
                varios, decláralos en el mismo orden después del <code>Request</code> o <code>FormRequest</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap3-crud" data-doc-section data-title="El mapa de un CRUD completo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap3-crud">#</a>El mapa de un CRUD completo</h3>
        <p>
            Un CRUD (crear, leer, actualizar, eliminar) en este sistema se compone de cuatro rutas,
            cuatro métodos de controlador y dos vistas. Este es el mapa exacto; el
            <a href="#cap12">Capítulo 12</a> lo desarrolla paso a paso:
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 420" width="940" height="420" role="img"
                aria-label="Mapa de un CRUD: rutas, metodos del controlador, modelo y vistas">
                <text x="470" y="26" font-size="14" font-weight="700" text-anchor="middle" fill="#1f2937">Módulo Productos — ruta ↔ método ↔ acción</text>

                <rect x="20" y="48" width="285" height="60" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="36" y="72" font-size="12" font-weight="700" fill="#0b4ea8">GET /productos</text>
                <text x="36" y="92" font-size="11" fill="#1e3a8a">->name('productos')</text>

                <polygon points="317,78 329,72 329,84" fill="#94a3b8"></polygon>
                <line x1="307" y1="78" x2="325" y2="78" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="340" y="48" width="250" height="60" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="356" y="72" font-size="12" font-weight="700" fill="#166534">index(Request)</text>
                <text x="356" y="92" font-size="11" fill="#14532d">listar registros</text>

                <polygon points="602,78 614,72 614,84" fill="#94a3b8"></polygon>
                <line x1="592" y1="78" x2="610" y2="78" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="625" y="48" width="295" height="60" rx="9" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="641" y="72" font-size="12" font-weight="700" fill="#92400e">view → tabla HTML</text>
                <text x="641" y="92" font-size="11" fill="#78350f">App/views/dashboard/productos/index.php</text>

                <rect x="20" y="128" width="285" height="60" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="36" y="152" font-size="12" font-weight="700" fill="#0b4ea8">POST /productos/guardar</text>
                <text x="36" y="172" font-size="11" fill="#1e3a8a">->name('productos_guardar')</text>

                <polygon points="317,158 329,152 329,164" fill="#94a3b8"></polygon>
                <line x1="307" y1="158" x2="325" y2="158" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="340" y="128" width="250" height="60" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="356" y="152" font-size="12" font-weight="700" fill="#166534">guardar(StoreRequest)</text>
                <text x="356" y="172" font-size="11" fill="#14532d">validar → insert() → JSON</text>

                <polygon points="602,158 614,152 614,164" fill="#94a3b8"></polygon>
                <line x1="592" y1="158" x2="610" y2="158" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="625" y="128" width="295" height="60" rx="9" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="641" y="152" font-size="12" font-weight="700" fill="#334155">Productos::query()-&gt;insert()</text>
                <text x="641" y="172" font-size="11" fill="#475569">responde {"success": true}</text>

                <rect x="20" y="208" width="285" height="60" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="36" y="232" font-size="12" font-weight="700" fill="#0b4ea8">GET /productos/editar/{id}</text>
                <text x="36" y="252" font-size="11" fill="#1e3a8a">->name('productos_editar')</text>

                <polygon points="317,238 329,232 329,244" fill="#94a3b8"></polygon>
                <line x1="307" y1="238" x2="325" y2="238" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="340" y="208" width="250" height="60" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="356" y="232" font-size="12" font-weight="700" fill="#166534">editar(Request, int $id)</text>
                <text x="356" y="252" font-size="11" fill="#14532d">find($id) → formulario</text>

                <polygon points="602,238 614,232 614,244" fill="#94a3b8"></polygon>
                <line x1="592" y1="238" x2="610" y2="238" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="625" y="208" width="295" height="60" rx="9" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="641" y="232" font-size="12" font-weight="700" fill="#92400e">view → formulario con datos</text>
                <text x="641" y="252" font-size="11" fill="#78350f">App/views/dashboard/productos/editar.php</text>

                <rect x="20" y="288" width="285" height="60" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="36" y="312" font-size="12" font-weight="700" fill="#0b4ea8">POST /productos/actualizar/{id}</text>

                <polygon points="317,318 329,312 329,324" fill="#94a3b8"></polygon>
                <line x1="307" y1="318" x2="325" y2="318" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="340" y="288" width="250" height="60" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="356" y="312" font-size="12" font-weight="700" fill="#166534">actualizar(UpdateRequest, $id)</text>
                <text x="356" y="332" font-size="11" fill="#14532d">update() → JSON</text>

                <rect x="625" y="288" width="295" height="60" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="641" y="312" font-size="12" font-weight="700" fill="#0b4ea8">DELETE</text>
                <text x="641" y="332" font-size="11" fill="#1e3a8a">POST /productos/eliminar/{id} → delete()</text>

                <rect x="20" y="368" width="900" height="38" rx="8" fill="#fef3c7" stroke="#f59e0b"></rect>
                <text x="36" y="392" font-size="11.5" fill="#78350f">Todos los POST exigen token CSRF y viajan por AJAX; el resultado se muestra con showNotification() (éxito) o el modal rojo de errores (422).</text>
            </svg>
            <span class="doc-caption">Figura 3.2 — Mapa del CRUD: cada botón de la interfaz corresponde a una ruta con nombre.</span>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">routes/app.php — bloque completo listo para copiar</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use App\Controllers\ProductosController;

Route::prefix('join/access/dashboard', function () {
    Route::controller(ProductosController::class, function () {
        Route::middleware(['Auth'], function () {
            Route::prefix('/Area-of-work/productos', function () {
                Route::get('/', 'index')->name('productos');
                Route::post('/guardar', 'guardar')->name('productos_guardar');
                Route::get('/editar/{id}', 'editar')->name('productos_editar');
                Route::post('/actualizar/{id}', 'actualizar')->name('productos_actualizar');
                Route::post('/eliminar/{id}', 'eliminar')->name('productos_eliminar');
            });
        });
    });
});</code></pre>
        </div>
    </section>

    <section class="doc-section" id="cap3-enlaces" data-doc-section data-title="Generar enlaces y respetar los toggles">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap3-enlaces">#</a>Generar enlaces y respetar los toggles</h3>
        <p>
            Nunca escribas una URL a mano: usa el <strong>nombre</strong> de la ruta. Así, si cambia el
            prefijo, todos los enlaces se actualizan solos. Además, el panel permite deshabilitar rutas:
            tu vista puede comprobarlo antes de pintar un botón.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Vista o controlador — enlaces por nombre</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// Enlace simple
&lt;a href="&lt;?= route('productos') ?&gt;"&gt;Ir a Productos&lt;/a&gt;

// Con parámetros (reemplaza {id} de la URI)
$url = route('productos_editar', ['id' =&gt; 17]);
// → /join/access/dashboard/Area-of-work/productos/editar/17

// Comprobar si la ruta esta habilitada (toggle del panel)
&lt;?php if (\Lib\Core\Route::isEnabled('productos')): ?&gt;
    &lt;a class="nav-link" href="&lt;?= route('productos') ?&gt;"&gt;Productos&lt;/a&gt;
&lt;?php endif; ?&gt;

// Desde JavaScript dentro de la vista
$.post("&lt;?= route('productos_guardar') ?&gt;", datos, callback, 'json');</code></pre>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Helper</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>route('productos')</code></td>
                        <td>URI de la ruta nombrada o <code>null</code> si no existe.</td>
                    </tr>
                    <tr>
                        <td><code>route('productos_editar', ['id' =&gt; 3])</code></td>
                        <td>URI con el parámetro <code>{id}</code> sustituido.</td>
                    </tr>
                    <tr>
                        <td><code>\Lib\Core\Route::isEnabled('productos')</code></td>
                        <td><code>true</code>/<code>false</code> según el toggle del panel.</td>
                    </tr>
                    <tr>
                        <td><code>\Lib\Core\Route::all()</code></td>
                        <td>Todas las rutas registradas (así se alimenta la pantalla <strong>Rutas y enlaces</strong>).</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="doc-section" id="cap3-comprobar" data-doc-section data-title="Cómo probar las rutas">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap3-comprobar">#</a>Cómo probar las rutas</h3>
        <ul class="doc-check">
            <li>Abre <strong>Sistema → Rutas y enlaces</strong>: tu ruta nueva debe aparecer con su método,
                URI, nombre, middlewares y el interruptor de estado.</li>
            <li>Visita la ruta en el navegador con la sesión iniciada: debe cargar tu vista o responder JSON.</li>
            <li>Prueba una URL inexistente: debe responder la página <strong>404</strong> del sistema.</li>
            <li>Deshabilita tu ruta desde el panel y recarga: responde 404 aunque el código exista
                (así compruebas el toggle). Vuelve a habilitarla.</li>
            <li>Si esperas parámetro y envías otro tipo, revisa el cast: <code>/editar/abc</code> con
                <code>int $id</code> llega como <code>0</code>; valida siempre el dato dentro del método.</li>
        </ul>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Rutas del panel protegidas</span>
            <p>Las rutas bajo <code>/join/access/dashboard</code> no se pueden deshabilitar desde el panel
                (el sistema las protege para que no te bloquees a ti mismo). Tus módulos viven dentro de ese
                prefijo, así que sus rutas tampoco se pueden apagar… salvo que registres el módulo como ruta
                pública.</p>
        </div>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap4"><i class="fas fa-arrow-right mr-1"></i>Siguiente: middlewares</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap3') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
