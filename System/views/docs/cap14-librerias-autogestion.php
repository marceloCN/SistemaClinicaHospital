<section class="doc-chapter" id="cap14" data-doc-chapter data-number="14" data-title="Librerías, límites y autogestión segura">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 14</span>
        <h2 class="doc-chapter-title">Librerías, límites y autogestión segura</h2>
        <p class="doc-chapter-lead">
            Este capítulo es el contrato de convivencia con el CMS: qué librerías trae el sistema y cómo
            usarlas desde tu código, qué archivos nunca debes tocar ni borrar para que la autogestión siga
            funcionando, y cómo aprovechar las guías en <code>docs/</code>. Si solo vas a leer uno de este
            manual antes de empezar a tocar cosas, que sea este.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap14-libs">Librerías incluidas</a>
            <a class="doc-chip" href="#cap14-uso">Cómo usarlas</a>
            <a class="doc-chip" href="#cap14-no-tocar">Qué no se toca</a>
            <a class="doc-chip" href="#cap14-guardas">Autogestión con guardas</a>
            <a class="doc-chip" href="#cap14-docs">Guías de docs/</a>
        </div>
    </header>

    <section class="doc-section" id="cap14-libs" data-doc-section data-title="Las librerías que trae el sistema">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap14-libs">#</a>Las librerías que trae el sistema</h3>
        <p>
            Todo lo que necesitas ya está dentro del proyecto: no hay que instalar nada para empezar a
            construir. Se divide en tres capas.
        </p>

        <div class="doc-h4">Capa PHP (núcleo y utilidades)</div>
        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Librería</th>
                        <th>Dónde vive</th>
                        <th>Para qué sirve</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>Lib\Core\*</code></td>
                        <td><code>lib/Core/</code></td>
                        <td>Motor MVC: <code>Route</code>, <code>Request</code>, <code>Response</code>,
                            <code>View</code>, <code>Controller</code>, <code>Model</code>,
                            <code>FormRequest</code>, <code>Database</code>, <code>RouteRegistrar</code>.</td>
                    </tr>
                    <tr>
                        <td><code>Lib\Support\*</code></td>
                        <td><code>lib/Support/</code></td>
                        <td>Servicios: <code>Session</code>, <code>Csrf</code>, <code>Validator</code>,
                            <code>System</code> (toggles), <code>Env</code>, <code>Hash</code>,
                            <code>Mail</code>, <code>Analytics</code>.</td>
                    </tr>
                    <tr>
                        <td>Helpers globales</td>
                        <td><code>lib/Helpers.php</code></td>
                        <td><code>config()</code>, <code>route()</code>, <code>asset()</code>,
                            <code>e()</code>, <code>csrf_field()</code>, <code>json()</code>,
                            <code>redirect()</code>, <code>view()</code>, <code>old()</code>,
                            <code>errors()</code>, <code>dd()</code>… (Capítulo 7).</td>
                    </tr>
                    <tr>
                        <td>vlucas/phpdotenv</td>
                        <td><code>vendor/</code> + <code>.env</code></td>
                        <td>Variables de entorno tipadas (<code>lib/Support/Env.php</code> las lee).</td>
                    </tr>
                    <tr>
                        <td>dompdf</td>
                        <td><code>resources/dompdf/</code></td>
                        <td>HTML → PDF (manual del Capítulo 16, reportes del Capítulo 9 y la receta 4 del Capítulo 15).</td>
                    </tr>
                    <tr>
                        <td>FPDF</td>
                        <td><code>resources/FPDF/</code></td>
                        <td>PDF celda a celda para tablas personalizadas (Capítulo 9).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-h4">Capa frontend (cargada por el layout del panel)</div>
        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Librería</th>
                        <th>Versión / ruta</th>
                        <th>Para qué sirve</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>AdminLTE</td>
                        <td>3.2 · <code>public/code/dist/</code></td>
                        <td>Tema y componentes del panel: tarjetas, tablas, modales, menú lateral.</td>
                    </tr>
                    <tr>
                        <td>Bootstrap</td>
                        <td>4.x · <code>public/code/plugins/bootstrap/</code></td>
                        <td>Rejilla, botones, formularios, modal y utilidades <code>d-*</code>.</td>
                    </tr>
                    <tr>
                        <td>jQuery</td>
                        <td><code>public/code/plugins/jquery/jquery.min.js</code></td>
                        <td>AJAX del panel (<code>$.post</code>), modales y notificaciones.</td>
                    </tr>
                    <tr>
                        <td>Font Awesome</td>
                        <td><code>public/code/plugins/fontawesome-free/</code></td>
                        <td>Iconos <code>fas fa-*</code>, <code>fab fa-*</code>.</td>
                    </tr>
                    <tr>
                        <td>notification.js</td>
                        <td><code>public/code/js/notification.js</code></td>
                        <td><code>showNotification('success'|'error'|'warning', mensaje)</code> — los toasts del panel.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Versiones fijadas</span>
            <p>Frontend sin CDN: todas las librerías están en <code>public/code/</code>, así que el panel
                funciona sin internet y las versiones no cambian sin que tú lo decidas.</p>
        </div>
    </section>

    <section class="doc-section" id="cap14-uso" data-doc-section data-title="Cómo usarlas tú en tu código">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap14-uso">#</a>Cómo usarlas tú en tu código</h3>
        <p>
            No importas nada: solo llamas. Estos son los patrones que usarás en el 95 % de los casos, con
            código listo para copiar dentro de tus controladores y vistas de <code>App/</code>.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Patrón 1 — Respuestas desde un controlador</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;

class NoticiasController extends Controller
{
    public function index(Request $request): Response
    {
        // HTML: la vista recibe los datos
        return $this-&gt;view('dashboard.noticias.index', [
            'noticias' =&gt; Noticias::query()-&gt;orderBy('id', 'desc')-&gt;get(),
        ]);

        // Alternativas que también devuelve $this-&gt;controller:
        // return $this-&gt;json(['ok' =&gt; true], 200);      → API JSON
        // return $this-&gt;redirect(route('noticias'));     → redirección
        // return $this-&gt;back(302);                       → volver de donde vino
    }
}</code></pre>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Patrón 2 — Validar con Validator o FormRequest</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// Opción A: validación rápida con la excepción del framework
use Lib\Support\ValidationException;

ValidationException::withMessages([
    'email' =&gt; ['email.required' =&gt; 'El correo es obligatorio.'],
])-&gt;validate($request-&gt;all());

// Opción B (recomendada): FormRequest estilo Laravel (Capítulo 5)
// app code: App/Requests/NoticiaRequest con rules() + messages()
// Route::dispatch() lo resuelve por reflexión antes de llamar al método.</code></pre>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Patrón 3 — En una vista: helpers, CSRF y notificaciones</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;form id="form-noticia" action="&lt;?= e(route('noticia_guardar')) ?&gt;" method="POST"&gt;
    &lt;input type="hidden" name="_token" value="&lt;?= csrf_token() ?&gt;"&gt;
    &lt;input name="titulo" value="&lt;?= e(old('titulo')) ?&gt;"&gt;
    &lt;?php if (hasError('titulo')): ?&gt;
        &lt;span class="text-danger"&gt;&lt;?= e(errors('titulo')) ?&gt;&lt;/span&gt;
    &lt;?php endif; ?&gt;
    &lt;button type="submit"&gt;Guardar&lt;/button&gt;
&lt;/form&gt;

&lt;script&gt;
$.post($form.attr('action'), $form.serialize(), function(res) {
    if (res &amp;&amp; res.success) {
        showNotification('success', res.message); // notification.js
    }
}, 'json').fail(function(xhr) {
    showNotification('error', xhr.responseJSON.message);
});
&lt;/script&gt;</code></pre>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Patrón 4 — Datos: modelo, consulta directa y transacción</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use Lib\Core\Database;

// Modelo (Capítulo 8)
$paciente = Pacientes::query()-&gt;where('id', $id)-&gt;first();
$nuevoId  = Pacientes::query()-&gt;insert(['nombre' =&gt; 'Ana']);

// SQL directo cuando el ORM no llega
$total = Database::connection()-&gt;query(
    'SELECT count(*) FROM citas WHERE fecha = :f',
    ['f' =&gt; date('Y-m-d')]
)-&gt;fetchColumn();

// Transacción: todo o nada
Database::transaction(function () use ($cita, $turno) {
    Citas::query()-&gt;insert($cita);
    Turnos::query()-&gt;update(['ocupado' =&gt; 1], ['id' =&gt; $turno['id']]);
});</code></pre>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Patrón 5 — Correo y PDF desde tu módulo</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// Correo SMTP (config en config/mail.php + .env)
$html = Lib\Core\View::render('emails.cita', ['cita' =&gt; $cita]); // tu vista
Lib\Support\Mail::to('destino@correo.com')
    -&gt;subject('Cita confirmada')
    -&gt;send($html);

// PDF con dompdf (detalles en el Capítulo 9)
$html = Lib\Core\View::render('pdf.informe', ['filas' =&gt; $filas]);
require_once BASE_PATH . '/resources/dompdf/autoload.inc.php';
$dompdf = new Dompdf\Dompdf();
$dompdf-&gt;loadHtml($html, 'UTF-8');
$dompdf-&gt;setPaper('A4');
$dompdf-&gt;render();
return Lib\Core\Response::make($dompdf-&gt;output(), 200, [
    'Content-Type' =&gt; 'application/pdf',
]);</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Lista de helpers a memorizar</span>
            <p><code>e()</code> (escapar HTML, úsalo siempre que imprimas datos), <code>route()</code>
                (enlaces por nombre), <code>asset()</code> (rutas a CSS/JS/imagen), <code>csrf_token()</code>,
                <code>config()</code>, <code>json()</code>, <code>redirect()</code>, <code>old()</code>,
                <code>errors()</code>, <code>dd()</code> (morir y mostrar). El resto, con la ayuda del
                editor.</p>
        </div>
    </section>

    <section class="doc-section" id="cap14-no-tocar" data-doc-section data-title="Qué no se toca y qué no se debe borrar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap14-no-tocar">#</a>Qué no se toca y qué no se debe borrar</h3>
        <p>
            La autogestión funciona porque el núcleo está firme. Estas son las líneas rojas, de la más
            dura a la más flexible:
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Zona</th>
                        <th>Regla</th>
                        <th>Si lo rompes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>lib/Core/</code>, <code>lib/Support/</code>, <code>lib/Helpers.php</code></td>
                        <td><span class="doc-badge doc-badge-red">Nunca modificar ni borrar</span></td>
                        <td>Deja de funcionar todo: router, vistas, validación, sesión, modelos. El panel
                            ni siquiera se abriría.</td>
                    </tr>
                    <tr>
                        <td><code>vendor/</code>, <code>composer.json</code>, <code>composer.lock</code></td>
                        <td><span class="doc-badge doc-badge-red">Nunca borrar</span></td>
                        <td>Sin autoload de Composer no carga ninguna clase (ni las tuyas).</td>
                    </tr>
                    <tr>
                        <td><code>resources/dompdf/</code>, <code>resources/FPDF/</code></td>
                        <td><span class="doc-badge doc-badge-red">Nunca borrar</span></td>
                        <td>Se rompe este manual en PDF, los informes y cualquier reporte que generes.</td>
                    </tr>
                    <tr>
                        <td><code>public/index.php</code>, <code>bootstrap.php</code>, <code>.htaccess</code></td>
                        <td><span class="doc-badge doc-badge-red">Nunca borrar</span></td>
                        <td>El sitio devuelve 404 en todas partes: es la única puerta de entrada.</td>
                    </tr>
                    <tr>
                        <td><code>.env</code></td>
                        <td><span class="doc-badge doc-badge-red">Nunca borrar</span></td>
                        <td>Sin credenciales de BD, sesión y correo no arranca (Capítulo 2).</td>
                    </tr>
                    <tr>
                        <td><code>System/Middleware/Auth.php</code> y su registro de rutas</td>
                        <td><span class="doc-badge doc-badge-red">Nunca desproteger</span></td>
                        <td>El panel quedaría abierto. El panel mismo lo impide: el toggle de
                            <code>Auth</code> responde 403 (Capítulo 10).</td>
                    </tr>
                    <tr>
                        <td><code>System/Controllers</code>, <code>System/views</code> (excepto el menú)</td>
                        <td><span class="doc-badge doc-badge-amber">No tocar</span></td>
                        <td>Es el núcleo del CMS: login, usuarios, generadores, base de datos y documentación.
                            Los generadores solo <em>leen</em> de aquí, jamás escriben.</td>
                    </tr>
                    <tr>
                        <td><code>routes/app.php</code> (bloques existentes)</td>
                        <td><span class="doc-badge doc-badge-amber">No editar, solo añadir</span></td>
                        <td>Si borras el grupo <code>Auth</code> pierdes la protección del panel; lo demás
                            es añadir tus bloques de rutas debajo.</td>
                    </tr>
                    <tr>
                        <td><code>routes/web.php</code> (rutas actuales)</td>
                        <td><span class="doc-badge doc-badge-amber">No editar, solo añadir</span></td>
                        <td>Son las rutas del sitio público (portal, especialidades…). Añade las tuyas
                            aparte, sin reescribir las existentes.</td>
                    </tr>
                    <tr>
                        <td><code>App/</code>, <code>public/code/css|js</code> (tuyos), <code>docs/</code></td>
                        <td><span class="doc-badge doc-badge-green">Libre</span></td>
                        <td>Tu espacio: creas, editas y borras lo que generes tú.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-danger">
            <span class="doc-danger-title"><i class="fas fa-exclamation-circle mr-1"></i>La lista mínima que no se borra nunca</span>
            <p>Si algún día limpias el proyecto para publicarlo, conserva siempre:
                <code>lib/</code>, <code>vendor/</code>, <code>resources/dompdf/</code>,
                <code>resources/FPDF/</code>, <code>public/index.php</code>, <code>bootstrap.php</code>,
                <code>.env</code>, <code>composer.json</code>, <code>System/</code>, <code>config/</code>,
                <code>routes/</code>, <code>public/.htaccess</code> y <code>storage/.installed</code>.
                Todo lo demás (especialmente <code>App/</code>) es reemplazable, pero sin esa lista base no
                arranca.</p>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>storage/ y la instalación</span>
            <p><code>storage/.installed</code> marca que el CMS ya fue instalado; si lo borras, el
                instalador puede volver a aparecer y sobreescribir tablas. Borra también
                <code>storage/logs/*</code> con calma: los logs son diagnóstico, no estorban.</p>
        </div>
    </section>

    <section class="doc-section" id="cap14-guardas" data-doc-section data-title="Autogestión con guardas: por qué es seguro">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap14-guardas">#</a>Autogestión con guardas: por qué es seguro</h3>
        <p>
            «Autogestionar» no es «escribir sobre lo que hay». El panel está diseñado para que su uso
            normal nunca dañe el núcleo. Estas son las garantías concretas que puedes verificar:
        </p>

        <div class="doc-cols">
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-lock"></i>Los generadores solo crean</div>
                <p>Solo escriben en <code>App/Middleware</code>, <code>App/Requests</code> y
                    <code>App/Controllers</code>. Si el archivo existe responden <code>409 Conflict</code>
                    y no hay sobrescritura silenciosa.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-eye"></i>El explorador solo lee</div>
                <p><code>scan()</code> y <code>source()</code> abren archivos en modo lectura y no exigen
                    CSRF porque no modifican nada.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-toggle-on"></i>Los toggles son datos</div>
                <p>Deshabilitar rutas/middlewares escribe en la tabla <code>system_settings</code>: no
                    edita código. Se revierte con el mismo interruptor.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-shield-alt"></i>CSRF + Auth en todo lo destructivo</div>
                <p>Toda acción <code>POST</code> del panel exige sesión <em>y</em> token; un tercero sin
                    sesión no puede ni siquiera probar.</p>
            </div>
        </div>

        <div class="doc-h4">Tu checklist de trabajo diario seguro</div>
        <ul class="doc-check">
            <li>Ediciones en <code>App/</code> y <code>routes/</code> (añadiendo, no reemplazando bloques).</li>
            <li>Verificación con <code>php -l</code> + <code>php tests/run.php</code> tras cada cambio.</li>
            <li>Respaldo de la base (dump) antes de tocar datos desde la consola SQL.</li>
            <li><strong>Depuración OFF</strong> y <strong>mantenimiento OFF</strong> al terminar la jornada.</li>
            <li>Nunca borrar carpetas del «no borrar» aunque estén vacías a simple vista.</li>
        </ul>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Y si necesitas tocar el núcleo</span>
            <p>Es una señal de que falta una funcionalidad genérica: repasa los capítulos 3 a 9 antes de
                editar <code>lib/</code>. Casi siempre el comportamiento que buscas se consigue añadiendo
                una ruta, un middleware de <code>App/Middleware</code> o un helper local, sin entrar en el
                motor.</p>
        </div>
    </section>

    <section class="doc-section" id="cap14-docs" data-doc-section data-title="Cómo aprovechar las guías de docs/">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap14-docs">#</a>Cómo aprovechar las guías de <code>docs/</code></h3>
        <p>
            El manual del panel (esta pantalla) es la fuente viva y se actualiza con el código. Además,
            la carpeta <code>docs/</code> del proyecto guarda guías en Markdown para leerlas con cualquier
            visor, llevarlas a otro equipo o versionarlas con el código:
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Contenido</th>
                        <th>Cuándo leerlo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>README.md</code></td>
                        <td>Índice de todas las guías con el orden sugerido.</td>
                        <td>Primera vez que entras a <code>docs/</code>.</td>
                    </tr>
                    <tr>
                        <td><code>01-arquitectura.md</code></td>
                        <td>Ciclo de una petición y rol de cada carpeta.</td>
                        <td>Antes de ubicar dónde va cada archivo nuevo.</td>
                    </tr>
                    <tr>
                        <td><code>02-rutas-y-middleware.md</code></td>
                        <td>Registrar rutas, grupos, toggles y middlewares.</td>
                        <td>Al crear un módulo nuevo.</td>
                    </tr>
                    <tr>
                        <td><code>03-validacion-y-form-requests.md</code></td>
                        <td>Reglas de validación, CSRF y FormRequest.</td>
                        <td>Al escribir formularios.</td>
                    </tr>
                    <tr>
                        <td><code>04-modelos-y-base-de-datos.md</code></td>
                        <td>Consultas, escrituras, casts, creación de tablas.</td>
                        <td>Al modelar datos.</td>
                    </tr>
                    <tr>
                        <td><code>05-vistas-y-layout.md</code></td>
                        <td>Motor de vistas, placeholders, layout, notificaciones.</td>
                        <td>Al crear pantallas.</td>
                    </tr>
                    <tr>
                        <td><code>06-panel-del-sistema.md</code></td>
                        <td>Resumen del panel, toggles y mantenimiento.</td>
                        <td>Como referencia rápida del dashboard.</td>
                    </tr>
                    <tr>
                        <td><code>08-crear-un-modulo.md</code></td>
                        <td>Tutorial de módulo completo: tabla, modelo, rutas, controlador, vista.</td>
                        <td>Reemplaza leer código ajeno cuando vas a clonar un patrón.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-h4">Tres formas de usarlas</div>
        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Como recordatorio rápido</div>
                <p>Abre la guía que toque (por ejemplo <code>03</code> al validar un formulario), copia el
                    fragmento y sigue. Son deliberadamente cortas.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Como fuente para tu propia documentación</div>
                <p>Si entregas el CMS a otro equipo, incluye <code>docs/</code>: es el manual «sin panel».
                    Puedes copiar tablas y ejemplos a tus propios <code>App/README.md</code> o
                    módulo/README.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Como checklist de revisión</div>
                <p>Antes de publicar, recorre <code>08-crear-un-modulo.md</code> y confirma que tu módulo
                    tiene todas las piezas que enumera.</p>
            </div>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Resolución de dudas: ¿dónde me fijo?</span>
            <p>Regla simple: <strong>¿cómo se usa?</strong> → manual del panel (esta pantalla) o la guía
                06. <strong>¿cómo funciona por dentro?</strong> → Capítulo correspondiente de este manual.
                <strong>¿necesito el resumen para copiar y pegar?</strong> → <code>docs/*.md</code>.
                <strong>¿qué no debo tocar?</strong> → este capítulo (14).</p>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Las guías son un resumen</span>
            <p>Si una guía Markdown y este manual discrepan, manda este manual: se genera desde el mismo
                código que se ejecuta. Las guías de <code>docs/</code> no cubren el despliegue ni el
                entorno; para eso está la configuración del proyecto.</p>
        </div>
    </section>

    <section class="doc-section" id="cap14-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap14-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Sabes nombrar las tres capas de librerías (núcleo PHP, utilidades, frontend) y dónde vive cada una.</li>
            <li>Has copiado el patrón de respuesta de controlador y el de helpers en vista en un módulo de práctica.</li>
            <li>Puedes enumerar de memoria la lista «no borrar»: <code>lib/</code>, <code>vendor/</code>,
                <code>resources/</code>, entrada, <code>.env</code>, <code>System/</code>, <code>config/</code>,
                <code>routes/</code>.</li>
            <li>Intentas (en una copia de pruebas, no en producción) generar un archivo que ya existe y
                confirmas que responde 409 sin tocarlo.</li>
            <li>Abres <code>docs/08-crear-un-modulo.md</code> y localizas en él las piezas de tu módulo actual.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap15"><i class="fas fa-arrow-right mr-1"></i>Siguiente: recetas para sistemas complejos</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap14') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
