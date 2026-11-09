<section class="doc-chapter" id="cap7" data-doc-chapter data-number="7" data-title="Vistas, CSS, JS y librerías frontend">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 7</span>
        <h2 class="doc-chapter-title">Vistas, CSS, JS y librerías frontend</h2>
        <p class="doc-chapter-lead">
            Las vistas son PHP puro con placeholders <code>{clave}</code>; el layout del panel es una
            plantilla que rellenas desde el controlador. Aquí verás dónde vive cada archivo, cómo se
            compone una página y cómo inyectar tus propios CSS y JavaScript sin tocar el núcleo.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap7-donde">Dónde vive cada vista</a>
            <a class="doc-chip" href="#cap7-scaffold">Tu primera vista</a>
            <a class="doc-chip" href="#cap7-layout">El layout del panel</a>
            <a class="doc-chip" href="#cap7-assets">Tus CSS y JS</a>
            <a class="doc-chip" href="#cap7-librerias">Librerías incluidas</a>
        </div>
    </header>

    <section class="doc-section" id="cap7-donde" data-doc-section data-title="Dónde vive cada vista y cómo se resuelve el nombre">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap7-donde">#</a>Dónde vive cada vista y cómo se resuelve el nombre</h3>
        <p>
            Las vistas son archivos <code>.php</code> en dos carpetas. El nombre con puntos se convierte
            en ruta: <code>dashboard.productos.index</code> es <code>dashboard/productos/index.php</code>.
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 240" width="940" height="240" role="img"
                aria-label="Resolucion del nombre de una vista a su archivo">
                <rect x="40" y="40" width="330" height="56" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="205" y="64" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">"dashboard.productos.index"</text>
                <text x="205" y="83" font-size="11" text-anchor="middle" fill="#1e3a8a">View::render(...) · View::component(...)</text>

                <line x1="370" y1="68" x2="452" y2="68" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="460,68 448,62 448,74" fill="#94a3b8"></polygon>
                <text x="415" y="56" font-size="11" text-anchor="middle" fill="#64748b">puntos → barras</text>

                <rect x="460" y="40" width="440" height="56" rx="9" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="680" y="64" font-size="12.5" text-anchor="middle" fill="#334155">1º System/views/dashboard/productos/index.php</text>
                <text x="680" y="83" font-size="12.5" text-anchor="middle" fill="#334155">2º App/views/dashboard/productos/index.php</text>

                <line x1="680" y1="96" x2="680" y2="124" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="680,132 674,120 686,120" fill="#94a3b8"></polygon>

                <rect x="440" y="132" width="480" height="50" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="680" y="153" font-size="12" text-anchor="middle" fill="#166534">Se usa la primera que exista: System tiene prioridad</text>
                <text x="680" y="171" font-size="11" text-anchor="middle" fill="#14532d">Tus módulos van en App/views; las vistas del núcleo no se tocan.</text>

                <rect x="40" y="132" width="330" height="50" rx="9" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="205" y="153" font-size="12" text-anchor="middle" fill="#92400e">Si no existe ninguna:</text>
                <text x="205" y="171" font-size="11" text-anchor="middle" fill="#78350f">RuntimeException "View [...] not found"</text>

                <text x="470" y="216" font-size="12" text-anchor="middle" fill="#64748b">Misma regla que las clases: System primero, App después. Si repites un nombre del núcleo, se usa la del sistema.</text>
            </svg>
            <span class="doc-caption">Figura 7.1 — De nombre con puntos a archivo real, y la prioridad System → App.</span>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Carpeta</th>
                        <th>Contenido</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>System/views/</code></td>
                        <td>Vistas del núcleo: <code>dashboard/base/</code> (layout, head, login…),
                            <code>dashboard/system/</code> (pantallas del panel), <code>error/</code> (404…).</td>
                    </tr>
                    <tr>
                        <td><code>App/views/</code></td>
                        <td>Tu espacio. <strong>Empieza vacío</strong>: solo contiene un <code>README.md</code>
                            con la guía. Crea las carpetas que necesites con el mismo esquema:
                            <code>App/views/dashboard/productos/index.php</code>.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="doc-section" id="cap7-scaffold" data-doc-section data-title="Tu primera vista: App/views viene vacío">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap7-scaffold">#</a>Tu primera vista: App/views viene vacío</h3>
        <p>
            El scaffold <strong>no trae ninguna vista</strong> en <code>App/views/</code>: solo el
            <code>README.md</code> con esta misma guía. Tú creas tus carpetas y archivos cuando los
            necesitas. Tres pasos:
        </p>

        <div class="doc-steps">
            <div class="doc-step">
                <span class="doc-step-num">1</span>
                <div class="doc-step-body">
                    <strong>Crea el archivo de la vista</strong>
                    <p>Por ejemplo <code>App/views/public/contacto.php</code> (sitio público) o
                        <code>App/views/dashboard/productos/index.php</code> (dentro del panel). No hace
                        falta registrar nada.</p>
                </div>
            </div>
            <div class="doc-step">
                <span class="doc-step-num">2</span>
                <div class="doc-step-body">
                    <strong>Renderízala desde un controlador</strong>
                    <p><code>return $this-&gt;view('public.contacto', ['titulo' =&gt; 'Contacto']);</code></p>
                </div>
            </div>
            <div class="doc-step">
                <span class="doc-step-num">3</span>
                <div class="doc-step-body">
                    <strong>Dale una URL</strong>
                    <p>Añade la ruta en <code>routes/web.php</code> (pública) o <code>routes/app.php</code>
                        (panel): <code>Route::get('/contacto', [ContactoController::class, 'index'])-&gt;name('contacto');</code></p>
                </div>
            </div>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Vista pública mínima — App/views/public/contacto.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;!DOCTYPE html&gt;
&lt;html lang="es"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;title&gt;&lt;?= e($titulo) ?&gt;&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;&lt;?= e($titulo) ?&gt;&lt;/h1&gt;
    &lt;p&gt;Escríbenos a &lt;a href="mailto:hola@ejemplo.com"&gt;hola@ejemplo.com&lt;/a&gt;.&lt;/p&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Panel o sitio público</span>
            <p>Las vistas del <strong>sitio público</strong> llevan su propio <code>&lt;html&gt;</code>
                completo. Las del <strong>panel</strong> no: se envuelven con el layout
                <code>dashboard.base.dashboard</code> (ver la sección «El layout del panel» de este mismo
                capítulo), que ya aporta la cabecera, el menú lateral y el pie.</p>
        </div>
    </section>

    <section class="doc-section" id="cap7-render" data-doc-section data-title="Cómo funciona render(): datos y placeholders">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap7-render">#</a>Cómo funciona render(): datos y placeholders</h3>
        <p>
            <code>View::render($vista, $datos)</code> extrae las claves de <code>$datos</code> como
            variables PHP dentro de la vista y, después de renderizarla, reemplaza los placeholders
            <code>{clave}</code> por los valores que sean texto o número.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Controlador → vista: dos formas de pasar datos</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// Controlador
return $this-&gt;view('dashboard.productos.index', [
    'titulo'    =&gt; 'Productos',
    'productos' =&gt; $productos,
]);

// App/views/dashboard/productos/index.php
&lt;h2&gt;&lt;?= e($titulo) ?&gt;&lt;/h2&gt;                 &lt;!-- variable: recomendado --&gt;
&lt;p&gt;Tienes &lt;?= count($productos) ?&gt; productos&lt;/p&gt;

&lt;!-- El mismo dato tambien esta disponible como placeholder: --&gt;
&lt;h2&gt;{titulo}&lt;/h2&gt;</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Claves reservadas</span>
            <p>Existen claves del layout (<code>head</code>, <code>link</code>, <code>src</code>,
                <code>title</code>, <code>session</code>, <code>content</code>, <code>carousel</code>,
                <code>about</code>, <code>services</code>, <code>video</code>, <code>noticias</code>,
                <code>main</code>, <code>testimonial</code>): si un dato con ese nombre no llega, el
                placeholder se elimina del HTML. Evita usar <code>{title}</code> literal en tus textos:
                escribe <code>&amp;#123;title&amp;#125;</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap7-layout" data-doc-section data-title="El layout del panel: dashboard.base.dashboard">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap7-layout">#</a>El layout del panel: <code>dashboard.base.dashboard</code></h3>
        <p>
            No hay herencia de plantillas: el layout es una página con placeholders y el controlador lo
            rellena. Este es el patrón exacto que usa <code>SystemController::render()</code>, y el que
            debes copiar en tus módulos:
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Tu controlador — componer una página completa</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use Lib\Core\View;

private function render(string $vista, string $titulo, string $migaPan, array $datos = []): Response
{
    return $this-&gt;view('dashboard.base.dashboard', [
        'head'    =&gt; View::render('dashboard.base.head', [
            'title' =&gt; $titulo,
            'icon'  =&gt; asset('/code/dist/img/AdminLTELogo.png'),
        ]),
        'title'   =&gt; $titulo,
        'session' =&gt; $migaPan,                    // texto de la miga de pan
        'content' =&gt; View::component($vista, $datos), // tu vista del modulo
    ]);
}</code></pre>
        </div>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 250" width="940" height="250" role="img"
                aria-label="Como se ensambla la pagina final a partir del layout y la vista del modulo">
                <rect x="30" y="30" width="200" height="70" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="130" y="58" font-size="12" font-weight="700" text-anchor="middle" fill="#0b4ea8">dashboard.base.head</text>
                <text x="130" y="78" font-size="11" text-anchor="middle" fill="#1e3a8a">→ {head} (título, icono)</text>

                <rect x="30" y="120" width="200" height="70" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="130" y="148" font-size="12" font-weight="700" text-anchor="middle" fill="#166534">Tu vista del modulo</text>
                <text x="130" y="168" font-size="11" text-anchor="middle" fill="#14532d">→ {content}</text>

                <line x1="230" y1="65" x2="330" y2="105" stroke="#94a3b8" stroke-width="1.8"></line>
                <line x1="230" y1="155" x2="330" y2="125" stroke="#94a3b8" stroke-width="1.8"></line>

                <rect x="330" y="60" width="230" height="110" rx="10" fill="#0f172a"></rect>
                <text x="445" y="88" font-size="12.5" font-weight="700" text-anchor="middle" fill="#e2e8f0">dashboard.base.dashboard</text>
                <text x="445" y="110" font-size="11" text-anchor="middle" fill="#94a3b8">{head} {link} {title}</text>
                <text x="445" y="128" font-size="11" text-anchor="middle" fill="#94a3b8">{session} {content} {src}</text>
                <text x="445" y="150" font-size="11" text-anchor="middle" fill="#64748b">sidebar + navbar + footer</text>

                <line x1="560" y1="115" x2="640" y2="115" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="648,115 636,109 636,121" fill="#94a3b8"></polygon>

                <rect x="648" y="60" width="262" height="110" rx="10" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="779" y="90" font-size="12.5" font-weight="700" text-anchor="middle" fill="#92400e">HTML final al navegador</text>
                <text x="779" y="112" font-size="11" text-anchor="middle" fill="#78350f">AdminLTE + tu vista dentro</text>
                <text x="779" y="130" font-size="11" text-anchor="middle" fill="#78350f">tus CSS en {link}</text>
                <text x="779" y="148" font-size="11" text-anchor="middle" fill="#78350f">tus JS en {src}</text>

                <text x="470" y="220" font-size="12" text-anchor="middle" fill="#64748b">View::render reemplaza cada {placeholder} por su valor; si un placeholder del layout no tiene valor, desaparece.</text>
            </svg>
            <span class="doc-caption">Figura 7.2 — Ensamblaje de la página: head + tu vista + layout = HTML final.</span>
        </div>
    </section>

    <section class="doc-section" id="cap7-assets" data-doc-section data-title="Tus propios CSS y JavaScript">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap7-assets">#</a>Tus propios CSS y JavaScript</h3>
        <p>
            El layout reserva dos huecos: <code>{link}</code> (dentro del <code>&lt;head&gt;</code>, para CSS)
            y <code>{src}</code> (antes de <code>&lt;/body&gt;</code>, para JS). Pásalos desde el controlador
            y nunca toques la plantilla base:
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Controlador — inyectar CSS y JS solo en esta pantalla</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>return $this-&gt;view('dashboard.base.dashboard', [
    'head'    =&gt; View::render('dashboard.base.head', ['title' =&gt; 'Productos']),
    'title'   =&gt; 'Productos',
    'session' =&gt; 'Area de trabajo / Productos',
    'content' =&gt; View::component('dashboard.productos.index', $datos),

    // CSS propio (se imprime en el head del layout)
    'link' =&gt; '&lt;link rel="stylesheet" href="' . asset('code/css/productos.css') . '"&gt;',

    // JS propio (se imprime al final del body, despues de jQuery y AdminLTE)
    'src'  =&gt; '&lt;script src="' . asset('code/js/productos.js') . '"&gt;&lt;/script&gt;',
]);</code></pre>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Carpeta</th>
                        <th>Para qué</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>public/code/css/</code></td>
                        <td>Hojas de estilo propias (por ejemplo <code>docs.css</code>).</td>
                    </tr>
                    <tr>
                        <td><code>public/code/js/</code></td>
                        <td>Scripts propios (<code>docs.js</code>, <code>notification.js</code>…).</td>
                    </tr>
                    <tr>
                        <td><code>public/code/plugins/</code></td>
                        <td>Librerías de terceros: <code>jquery</code>, <code>bootstrap</code>, <code>fontawesome-free</code>…</td>
                    </tr>
                    <tr>
                        <td><code>public/code/dist/</code></td>
                        <td>AdminLTE 3 (<code>adminlte.min.js</code>, CSS y el logo del sistema).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Siempre con asset()</span>
            <p>Usa <code>asset('code/js/productos.js')</code> en lugar de rutas relativas: el helper añade
                el dominio de <code>config('app.url')</code> y funciona igual en local y en producción.
                Además, evita el clásico error de la vista cargada desde una URL con más segmentos.</p>
        </div>
    </section>

    <section class="doc-section" id="cap7-librerias" data-doc-section data-title="Qué librerías tienes disponibles">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap7-librerias">#</a>Qué librerías tienes disponibles</h3>
        <p>
            El layout ya carga todo esto en todas las páginas del panel: no necesitas volver a incluirlo.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Librería</th>
                        <th>Dónde</th>
                        <th>Para qué</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>AdminLTE 3</strong></td>
                        <td><code>public/code/dist/</code></td>
                        <td>Estructura visual del panel: sidebar, navbar, tarjetas, clases utilitarias.</td>
                    </tr>
                    <tr>
                        <td><strong>Bootstrap 4</strong></td>
                        <td><code>public/code/plugins/bootstrap/</code></td>
                        <td>Grid, formularios, botones, modales (<code>$('#modal-x').modal('show')</code>).</td>
                    </tr>
                    <tr>
                        <td><strong>jQuery</strong></td>
                        <td><code>public/code/plugins/jquery/</code></td>
                        <td>AJAX (<code>$.post</code>), eventos, <code>serialize()</code>.</td>
                    </tr>
                    <tr>
                        <td><strong>Font Awesome 5</strong></td>
                        <td><code>public/code/plugins/fontawesome-free/</code></td>
                        <td>Iconos: <code>&lt;i class="fas fa-save"&gt;&lt;/i&gt;</code>.</td>
                    </tr>
                    <tr>
                        <td><strong>notification.js</strong></td>
                        <td><code>public/code/js/notification.js</code></td>
                        <td><code>showNotification()</code> y <code>showErrorModal()</code> (ver <a href="#cap6">Capítulo 6</a>).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Consultar el inventario real</span>
            <p>En <strong>Sistema → APIs</strong> hay una lista generada directamente del disco con los
                paquetes del servidor, las librerías de <code>public/code/plugins</code> y las extensiones
                de PHP cargadas.</p>
        </div>
    </section>

    <section class="doc-section" id="cap7-comprobar" data-doc-section data-title="Buenas prácticas y comprobación">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap7-comprobar">#</a>Buenas prácticas y comprobación</h3>
        <ul class="doc-check">
            <li>Todo texto que venga de datos (o de un usuario) se imprime con <code>e()</code>:
                <code>&lt;?= e($producto['nombre']) ?&gt;</code>.</li>
            <li>Tu vista empieza con el contenido de la tarjeta, no con <code>&lt;html&gt;</code>: el layout
                ya lo pone.</li>
            <li>Cero etiquetas <code>&lt;style&gt;</code> y <code>&lt;script&gt;</code> gigantes dentro de la
                vista: muévelas a <code>public/code/css/</code> y <code>public/code/js/</code> e inyéctalas
                con <code>{link}</code>/<code>{src}</code>.</li>
            <li>Comprueba en el navegador: la consola no debe tener errores 404 de assets (revisa las rutas
                con <code>asset()</code>).</li>
            <li>Prueba tu pantalla con el nombre con puntos exacto: si ves
                <code>View [...] not found</code>, revisa la carpeta y las mayúsculas del nombre.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap8"><i class="fas fa-arrow-right mr-1"></i>Siguiente: modelos y base de datos</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap7') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
