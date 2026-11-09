<section class="doc-chapter" id="cap4" data-doc-chapter data-number="4" data-title="Middlewares: usos, situaciones y métodos">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 4</span>
        <h2 class="doc-chapter-title">Middlewares: usos, situaciones y métodos</h2>
        <p class="doc-chapter-lead">
            Un middleware es un portero de la cadena: revisa la petición antes de que llegue a tu
            controlador y puede dejarla pasar, cortarla con un error o desviarla a otro sitio.
            Aquí verás el contrato exacto de <code>handle()</code>, los middlewares que ya trae el
            sistema, cómo crear los tuyos y en qué situaciones usarlos.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap4-que-es">¿Qué es?</a>
            <a class="doc-chip" href="#cap4-incluidos">Incluidos</a>
            <a class="doc-chip" href="#cap4-ejemplos">Ejemplos para copiar</a>
            <a class="doc-chip" href="#cap4-crear">Crear uno propio</a>
        </div>
    </header>

    <section class="doc-section" id="cap4-que-es" data-doc-section data-title="¿Qué es un middleware en este sistema?">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-que-es">#</a>¿Qué es un middleware en este sistema?</h3>
        <p>
            Es una clase con un método <code>handle(Request $request)</code> que se ejecuta
            <strong>en cadena</strong> después de encontrar la ruta y <strong>antes</strong> del
            controlador. Cada middleware decide si la petición continúa o se detiene ahí mismo.
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 260" width="940" height="260" role="img"
                aria-label="Cadena de middlewares entre la peticion y el controlador">
                <rect x="20" y="100" width="130" height="52" rx="9" fill="#0f172a"></rect>
                <text x="85" y="131" font-size="12.5" text-anchor="middle" fill="#e2e8f0">Request</text>

                <line x1="150" y1="126" x2="192" y2="126" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="200,126 188,120 188,132" fill="#94a3b8"></polygon>

                <rect x="205" y="96" width="140" height="60" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="275" y="120" font-size="12" font-weight="700" text-anchor="middle" fill="#0b4ea8">Auth</text>
                <text x="275" y="140" font-size="10.5" text-anchor="middle" fill="#1e3a8a">¿hay sesión?</text>

                <line x1="345" y1="126" x2="387" y2="126" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="395,126 383,120 383,132" fill="#94a3b8"></polygon>

                <rect x="400" y="96" width="140" height="60" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="470" y="120" font-size="12" font-weight="700" text-anchor="middle" fill="#0b4ea8">SoloAjax</text>
                <text x="470" y="140" font-size="10.5" text-anchor="middle" fill="#1e3a8a">¿es AJAX?</text>

                <line x1="540" y1="126" x2="582" y2="126" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="590,126 578,120 578,132" fill="#94a3b8"></polygon>

                <rect x="595" y="96" width="150" height="60" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="670" y="120" font-size="12" font-weight="700" text-anchor="middle" fill="#166534">Controlador</text>
                <text x="670" y="140" font-size="10.5" text-anchor="middle" fill="#14532d">tu método</text>

                <line x1="745" y1="126" x2="787" y2="126" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="795,126 783,120 783,132" fill="#94a3b8"></polygon>

                <rect x="800" y="100" width="120" height="52" rx="9" fill="#0f172a"></rect>
                <text x="860" y="131" font-size="12.5" text-anchor="middle" fill="#e2e8f0">Response</text>

                <line x1="275" y1="156" x2="275" y2="196" stroke="#dc2626" stroke-width="2" stroke-dasharray="6 4"></line>
                <polygon points="275,204 269,192 281,192" fill="#dc2626"></polygon>
                <rect x="180" y="204" width="190" height="40" rx="8" fill="#fee2e2" stroke="#dc2626"></rect>
                <text x="275" y="229" font-size="11" text-anchor="middle" fill="#991b1b">corta: redirige, 401 o 403 JSON</text>

                <line x1="470" y1="156" x2="470" y2="196" stroke="#dc2626" stroke-width="2" stroke-dasharray="6 4"></line>
                <polygon points="470,204 464,192 476,192" fill="#dc2626"></polygon>
                <rect x="390" y="204" width="160" height="40" rx="8" fill="#fee2e2" stroke="#dc2626"></rect>
                <text x="470" y="229" font-size="11" text-anchor="middle" fill="#991b1b">corta: 403 JSON</text>

                <text x="470" y="46" font-size="12.5" text-anchor="middle" fill="#64748b">Cada middleware puede dejar pasar (true), cortar (false) o responder él mismo (Response).</text>
                <text x="470" y="68" font-size="12.5" text-anchor="middle" fill="#64748b">Se ejecutan en el orden en que los declaraste, del primero al último.</text>
            </svg>
            <span class="doc-caption">Figura 4.1 — La cadena de middlewares: porteros en fila antes del controlador.</span>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Lo que devuelve <code>handle()</code></th>
                        <th>Qué pasa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>true</code></td>
                        <td>La petición continúa con el siguiente middleware o con el controlador.</td>
                    </tr>
                    <tr>
                        <td><code>false</code></td>
                        <td>Se corta al instante con una respuesta <strong>403 vacía</strong>. Úsalo como último recurso;
                            normalmente es mejor devolver un <code>Response</code>.</td>
                    </tr>
                    <tr>
                        <td>Un <code>Response</code> (JSON, redirect…)</td>
                        <td>Se envía esa respuesta tal cual y el controlador <strong>nunca se ejecuta</strong>.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="doc-section" id="cap4-incluidos" data-doc-section data-title="Qué middlewares vienen incluidos">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-incluidos">#</a>Qué middlewares vienen incluidos</h3>
        <p>
            El sistema trae <strong>uno solo</strong>: <code>Auth</code>, que protege el panel. La carpeta
            <code>App/Middleware/</code> viene <strong>vacía</strong> (solo con su <code>README.md</code>),
            lista para tus propios middlewares.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Nombre en la ruta</th>
                        <th>Clase</th>
                        <th>Situación de uso</th>
                        <th>Si falla…</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>'Auth'</code></td>
                        <td><code>System\Middleware\Auth</code></td>
                        <td>Proteger todo el panel: exige sesión iniciada y que el usuario siga activo.</td>
                        <td>AJAX → 401 JSON · navegador → redirige al login.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Navegador o AJAX: dos respuestas distintas</span>
            <p>Fíjate en el patrón de <code>Auth</code>: detecta si la petición es AJAX
                (<code>$request-&gt;isAjax()</code>, cabecera <code>X-Requested-With</code>) y responde JSON;
                si es una visita normal del navegador, redirige. Copia ese patrón en tus propios middlewares.</p>
            <p class="mb-0">Los nombres <code>SoloAjax</code>, <code>Invitado</code>, <code>EsAdmin</code> y
                <code>Permiso</code> que verás en el resto de la documentación <strong>no vienen incluidos</strong>:
                son ejemplos que creas tú copiando el código de la sección siguiente. Hasta entonces, en
                <strong>Sistema → Middlewares</strong> solo aparece <code>Auth</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap4-ejemplos" data-doc-section data-title="Ejemplos listos para copiar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-ejemplos">#</a>Ejemplos listos para copiar</h3>
        <p>
            Crea cada archivo en <code>App/Middleware/</code> con el mismo nombre de la clase y aplícalo
            en tus rutas. Son ejemplos opcionales: el scaffold no los incluye.
        </p>

        <h4 class="h6 font-weight-bold mt-3">1. <code>SoloAjax</code> — solo peticiones AJAX</h4>
        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Middleware/SoloAjax.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;

class SoloAjax
{
    public function handle(Request $request): bool|Response
    {
        if (!$request-&gt;isAjax()) {
            return Response::json([
                'success' =&gt; false,
                'message' =&gt; 'Esta ruta solo acepta peticiones AJAX.',
            ], 403);
        }

        return true;
    }
}</code></pre>
        </div>

        <h4 class="h6 font-weight-bold mt-3">2. <code>Invitado</code> — solo visitantes sin sesión</h4>
        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Middleware/Invitado.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

class Invitado
{
    public function handle(Request $request): bool|Response
    {
        if (!Session::isLoggedIn()) {
            return true;
        }

        if ($request-&gt;isAjax()) {
            return Response::json([
                'success' =&gt; false,
                'message' =&gt; 'Ya tienes una sesión iniciada.',
            ], 409);
        }

        Response::redirect(route('system') ?? '/')-&gt;send();
        return false;
    }
}</code></pre>
        </div>

        <h4 class="h6 font-weight-bold mt-3">3. <code>EsAdmin</code> — guardia de sesión</h4>
        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Middleware/EsAdmin.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

/**
 * Equivalente a Auth. El sistema no maneja roles: cualquier sesión
 * iniciada tiene acceso completo. El nombre se conserva por compatibilidad.
 */
class EsAdmin
{
    public function handle(Request $request): bool|Response
    {
        if (Session::isLoggedIn()) {
            return true;
        }

        return $request-&gt;isAjax()
            ? Response::json(['success' =&gt; false, 'message' =&gt; 'No autenticado.'], 401)
            : $this-&gt;aLogin();
    }

    private function aLogin(): bool
    {
        Response::redirect(route('login') ?? '/join/access/dashboard')-&gt;send();
        return false;
    }
}</code></pre>
        </div>

        <h4 class="h6 font-weight-bold mt-3">4. <code>Permiso</code> — middleware con parámetros</h4>
        <p class="small text-muted">
            El sistema no trae roles ni permisos; esto solo muestra cómo recibir el valor que escribes
            tras <code>:</code> en la ruta.
        </p>
        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Middleware/Permiso.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

class Permiso
{
    public function handle(Request $request, string $permiso): bool|Response
    {
        $permisos = Session::get('permisos', []);

        if (is_array($permisos) && in_array($permiso, $permisos, true)) {
            return true;
        }

        return Response::json([
            'success' =&gt; false,
            'message' =&gt; 'No tienes el permiso requerido.',
        ], 403);
    }
}</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Actívalos en el panel</span>
            <p>En cuanto guardes el archivo, aparecerá en <strong>Sistema → Middlewares</strong> con su
                interruptor. Si lo desactivas, el router lo salta por completo (aunque la ruta lo declare).</p>
        </div>
    </section>

    <section class="doc-section" id="cap4-resolucion" data-doc-section data-title="Cómo se resuelve un nombre de middleware">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-resolucion">#</a>Cómo se resuelve un nombre de middleware</h3>
        <p>
            Cuando escribes <code>-&gt;middleware('SoloAjax')</code>, el router no busca un archivo: busca una
            clase. Este es el algoritmo exacto, con una parada previa: el interruptor del panel.
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 400" width="940" height="400" role="img"
                aria-label="Algoritmo de resolucion de un middleware por nombre">
                <rect x="300" y="24" width="340" height="46" rx="9" fill="#0f172a"></rect>
                <text x="470" y="53" font-size="12.5" text-anchor="middle" fill="#e2e8f0">Escrito en la ruta: 'Permiso:43,editar'</text>

                <line x1="470" y1="70" x2="470" y2="98" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="470,106 464,94 476,94" fill="#94a3b8"></polygon>

                <rect x="310" y="106" width="320" height="48" rx="9" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="470" y="126" font-size="12" text-anchor="middle" fill="#92400e">1. Separa por ':' → nombre = Permiso</text>
                <text x="470" y="144" font-size="12" text-anchor="middle" fill="#92400e">2. Separa por ',' → args = ['43', 'editar']</text>

                <line x1="470" y1="154" x2="470" y2="182" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="470,190 464,178 476,178" fill="#94a3b8"></polygon>

                <rect x="290" y="190" width="360" height="46" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="470" y="219" font-size="12" text-anchor="middle" fill="#0b4ea8">¿Interruptor activo? (middleware.enabled.Permiso)</text>

                <line x1="650" y1="213" x2="700" y2="213" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="708,213 696,207 696,219" fill="#94a3b8"></polygon>
                <text x="740" y="205" font-size="11.5" text-anchor="middle" fill="#334155">No → se salta</text>
                <text x="740" y="221" font-size="11.5" text-anchor="middle" fill="#64748b">(no se ejecuta)</text>

                <line x1="470" y1="236" x2="470" y2="264" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="470,272 464,260 476,260" fill="#94a3b8"></polygon>
                <text x="500" y="257" font-size="11.5" fill="#334155">Sí</text>

                <rect x="230" y="272" width="480" height="64" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="470" y="296" font-size="12" text-anchor="middle" fill="#166534">3. ¿Existe System\Middleware\Permiso? → se usa esa</text>
                <text x="470" y="316" font-size="12" text-anchor="middle" fill="#166534">4. Si no, ¿existe App\Middleware\Permiso? → se usa esa</text>
                <text x="470" y="329" font-size="11" text-anchor="middle" fill="#14532d">Si ninguna existe: RuntimeException «Middleware [Permiso] not found»</text>

                <line x1="470" y1="336" x2="470" y2="362" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="470,370 464,358 476,358" fill="#94a3b8"></polygon>

                <rect x="220" y="356" width="500" height="36" rx="8" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="470" y="379" font-size="12" text-anchor="middle" fill="#334155">5. Ejecuta handle($request, '43', 'editar')</text>
            </svg>
            <span class="doc-caption">Figura 4.2 — Del texto en la ruta a la instancia que se ejecuta.</span>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Por qué CamelCase</span>
            <p>El sistema aplica <code>ucfirst()</code> al nombre: <code>'soloAjax'</code> buscaría la clase
                <code>SoloAjax</code>… pero el archivo se llama igual, así que evita líos y escribe siempre el
                nombre tal como se llama la clase: <code>'SoloAjax'</code>, <code>'EsAdmin'</code>.</p>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Prioridad de System</span>
            <p>Si creas un <code>App\Middleware\Auth</code>, <strong>no</strong> sobrescribirá al del sistema:
                primero se busca en <code>System\Middleware</code>. Para tu propio middleware usa un nombre
                nuevo (por ejemplo <code>ComprobarApiKey</code>).</p>
        </div>
    </section>

    <section class="doc-section" id="cap4-usar" data-doc-section data-title="Cómo se aplican en las rutas">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-usar">#</a>Cómo se aplican en las rutas</h3>
        <p>
            En los ejemplos aparecen <code>Invitado</code>, <code>SoloAjax</code> y <code>Permiso</code>:
            son los middlewares de la sección «Ejemplos listos para copiar». Si no los has creado todavía,
            cámbialos por <code>Auth</code> o por los tuyos.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">routes/app.php — las cuatro formas de aplicar middlewares</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// 1. Uno solo, en su propia ruta (accion en array)
Route::get('/login', [\System\Controllers\AuthController::class, 'index'])-&gt;middleware('Invitado');

// 2. Grupo completo (se pueden anidar: se fusionan sin duplicados)
Route::middleware(['Auth'], function () {
    Route::controller(PedidosController::class, function () {
        Route::get('/', 'index')-&gt;name('pedidos');
        Route::post('/guardar', 'guardar')-&gt;middleware('SoloAjax')-&gt;name('pedidos_guardar');
    });
});

// 3. Grupo estilo Laravel (prefijo + middlewares juntos)
Route::group(['prefix' =&gt; 'api/v1', 'middleware' =&gt; ['SoloAjax']], function () {
    Route::controller(\App\Controllers\ApiController::class, function () {
        Route::get('/productos', 'productos')-&gt;name('api_productos');
    });
});

// 4. Parametrizado: lo que va tras ':' llega como argumento de handle()
Route::post('/publicar/{id}', [\App\Controllers\PublicacionesController::class, 'publicar'])
    -&gt;middleware('Permiso:43,publicar');</code></pre>
        </div>

        <ul class="doc-check">
            <li>El <strong>orden importa</strong>: se ejecutan en el orden declarado. Pon primero los
                porteros generales (<code>Auth</code>) y después los específicos (<code>SoloAjax</code>).</li>
            <li>Los middlewares de grupos anidados se <strong>fusionan sin repetirse</strong>: puedes declarar
                <code>Auth</code> en el grupo externo y no volver a escribirlo dentro.</li>
            <li>Al aplicar <code>-&gt;middleware()</code> después de <code>Route::get(...)</code>, recuerda que
                <code>-&gt;name()</code> también encadena; el orden entre ambos es indiferente.</li>
        </ul>
    </section>

    <section class="doc-section" id="cap4-crear" data-doc-section data-title="Crear tu propio middleware">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-crear">#</a>Crear tu propio middleware</h3>
        <p>
            Tres pasos: crea la clase en <code>App/Middleware/</code>, escribe <code>handle()</code> y
            aplícala por su nombre en las rutas. Este ejemplo protege una API pública que espera la cabecera
            <code>X-Api-Key</code>:
        </p>

        <div class="doc-steps">
            <div class="doc-step">
                <span class="doc-step-num">1</span>
                <div class="doc-step-body">
                    <strong>Crea el archivo</strong>
                    <p><code>App/Middleware/ComprobarApiKey.php</code> (el namespace debe coincidir con la carpeta).</p>
                </div>
            </div>
            <div class="doc-step">
                <span class="doc-step-num">2</span>
                <div class="doc-step-body">
                    <strong>Escribe `handle()`</strong>
                    <p>Devuelve <code>true</code> para dejar pasar o un <code>Response</code> para cortar.</p>
                </div>
            </div>
            <div class="doc-step">
                <span class="doc-step-num">3</span>
                <div class="doc-step-body">
                    <strong>Aplícalo en la ruta</strong>
                    <p><code>-&gt;middleware('ComprobarApiKey')</code>. Aparecerá automáticamente en
                        <strong>Sistema → Middlewares</strong> con su interruptor.</p>
                </div>
            </div>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Middleware/ComprobarApiKey.php — completo</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;

class ComprobarApiKey
{
    public function handle(Request $request): bool|Response
    {
        $esperada = env('API_KEY', '');
        $recibida = $request-&gt;header('X-Api-Key', '');

        if ($esperada === '' || !hash_equals($esperada, $recibida)) {
            return Response::json([
                'success' =&gt; false,
                'message' =&gt; 'API key invalida o ausente.',
            ], 401);
        }

        return true;
    }
}</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Middleware con parámetros</span>
            <p>Para recibir argumentos añade la firma <code>handle(Request $request, string $arg1, string $arg2 = '')</code>
                y aplícalo como <code>'MiMiddleware:valor1,valor2'</code>. El router reparte los valores por coma.</p>
        </div>
    </section>

    <section class="doc-section" id="cap4-situaciones" data-doc-section data-title="Situaciones típicas: ¿qué middleware me toca?">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-situaciones">#</a>Situaciones típicas: ¿qué middleware me toca?</h3>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Quiero…</th>
                        <th>Middleware</th>
                        <th>Ejemplo de ruta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Que solo entren usuarios con sesión</td>
                        <td><code>Auth</code></td>
                        <td>Todo el panel bajo <code>/join/access/dashboard</code>.</td>
                    </tr>
                    <tr>
                        <td>Que la página de login no la vea un usuario ya conectado</td>
                        <td>Tu <code>Invitado</code></td>
                        <td><code>Route::get('/login', [\System\Controllers\AuthController::class, 'index'])-&gt;middleware('Invitado')</code></td>
                    </tr>
                    <tr>
                        <td>Que un endpoint solo responda a peticiones AJAX</td>
                        <td>Tu <code>SoloAjax</code></td>
                        <td>Guardar, actualizar y eliminar de cada CRUD.</td>
                    </tr>
                    <tr>
                        <td>Proteger una API con clave o token</td>
                        <td>Tu <code>ComprobarApiKey</code></td>
                        <td><code>Route::get('/api/datos', ...)-&gt;middleware('ComprobarApiKey')</code></td>
                    </tr>
                    <tr>
                        <td>Registrar quién visita ciertas rutas</td>
                        <td>Tu <code>Auditoria</code></td>
                        <td>Escribe en una tabla <code>logs</code> y devuelve <code>true</code> siempre.</td>
                    </tr>
                    <tr>
                        <td>Responder 404 si el módulo está en mantenimiento</td>
                        <td>Tu <code>Mantenimiento</code></td>
                        <td>Consulta <code>System::get(...)</code> y corta con una vista 503.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Solo Auth viene incluido</span>
            <p class="mb-0">De todos los nombres de la tabla, el único que existe de fábrica es <code>Auth</code>.
                Los demás (marcados como <em>Tu…</em>) los creas tú; el código para <code>Invitado</code> y
                <code>SoloAjax</code> está en la sección «Ejemplos listos para copiar» de este capítulo.</p>
        </div>
    </section>

    <section class="doc-section" id="cap4-comprobar" data-doc-section data-title="Comprobar y depurar middlewares">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap4-comprobar">#</a>Comprobar y depurar middlewares</h3>
        <ul class="doc-check">
            <li>Abre <strong>Sistema → Middlewares</strong> (<code>/Area-of-work/system/middleware</code>):
                lista cada middleware <em>en uso</em>, las rutas que lo aplican y su interruptor. Los
                protegidos (<code>Auth</code>) llevan candado: no se pueden apagar.</li>
            <li>Prueba tu middleware con <code>curl</code> o un cliente HTTP enviando y omitiendo la cabecera
                esperada: debes ver el código correcto (401, 403, 409…).</li>
            <li>Si aparece <code>RuntimeException: Middleware [X] not found</code>, revisa namespace, carpeta
                (<code>App/Middleware/</code>) y que el nombre en la ruta sea exactamente el de la clase.</li>
            <li>Si un middleware deshabilitado debería bloquear y no lo hace: ese es el diseño — al apagar el
                interruptor, el router lo salta por completo.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap5"><i class="fas fa-arrow-right mr-1"></i>Siguiente: controladores y FormRequest</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap4') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
