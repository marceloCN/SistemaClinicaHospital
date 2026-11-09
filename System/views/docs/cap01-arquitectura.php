<section class="doc-chapter" id="cap1" data-doc-chapter data-number="1" data-title="Arquitectura de carpetas del CMS">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 1</span>
        <h2 class="doc-chapter-title">Arquitectura de carpetas del CMS</h2>
        <p class="doc-chapter-lead">
            El sistema es un framework MVC propio inspirado en Laravel. Todo está separado en tres zonas:
            el núcleo del framework, el núcleo del CMS y tu espacio de trabajo. Entender dónde vive cada
            archivo es la base para trabajar sin romper nada.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap1-mapa">Mapa de carpetas</a>
            <a class="doc-chip" href="#cap1-resolucion">Orden de resolución</a>
            <a class="doc-chip" href="#cap1-comprobar">Cómo comprobarlo</a>
        </div>
    </header>

    <section class="doc-section" id="cap1-vision" data-doc-section data-title="Tres zonas bien separadas">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap1-vision">#</a>Tres zonas bien separadas</h3>
        <p>
            Cada carpeta del proyecto cumple un rol claro. La regla de oro es simple:
            <strong>el núcleo no se toca</strong> (salvo el menú lateral) y <strong>todo tu código vive en
            <code>App/</code> y <code>routes/</code></strong>. Gracias a eso puedes actualizar el sistema sin perder
            tus módulos.
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 470" width="940" height="470" role="img"
                aria-label="Mapa de zonas del proyecto: núcleo del framework, núcleo del CMS, tu espacio, entrada, configuración y recursos">
                <text x="20" y="24" font-size="15" font-weight="700" fill="#1f2937">Mapa general del proyecto sistema/</text>

                <rect x="20" y="46" width="290" height="150" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="36" y="72" font-size="13" font-weight="700" fill="#0b4ea8">NÚCLEO DEL FRAMEWORK · lib/</text>
                <text x="36" y="94" font-size="11.5" fill="#1e3a8a">Core/ → Route, Request, Response, View,</text>
                <text x="36" y="111" font-size="11.5" fill="#1e3a8a">Controller, Model, FormRequest, Database</text>
                <text x="36" y="133" font-size="11.5" fill="#1e3a8a">Support/ → Session, Csrf, Validator,</text>
                <text x="36" y="150" font-size="11.5" fill="#1e3a8a">System (toggles), Env, Hash, Mail, Analytics</text>
                <text x="36" y="172" font-size="11.5" fill="#1e3a8a">Helpers.php → config(), route(), csrf_field()…</text>
                <text x="36" y="188" font-size="10.5" font-style="italic" fill="#64748b">Motor del sistema. No se modifica.</text>

                <rect x="325" y="46" width="290" height="150" rx="10" fill="#e8f5e9" stroke="#22c55e" stroke-width="1.5"></rect>
                <text x="341" y="72" font-size="13" font-weight="700" fill="#166534">NÚCLEO DEL CMS · System/</text>
                <text x="341" y="94" font-size="11.5" fill="#14532d">Controllers/ → Auth, System, Usuarios, Docs</text>
                <text x="341" y="116" font-size="11.5" fill="#14532d">Middleware/ → Auth (protegido)</text>
                <text x="341" y="138" font-size="11.5" fill="#14532d">Models/ → Settings, Users, Visits</text>
                <text x="341" y="160" font-size="11.5" fill="#14532d">Requests/ · views/dashboard/ · views/error/</text>
                <text x="341" y="188" font-size="10.5" font-style="italic" fill="#64748b">Panel de administración. Solo se edita el menú.</text>

                <rect x="630" y="46" width="290" height="150" rx="10" fill="#fff8e1" stroke="#f59e0b" stroke-width="1.5"></rect>
                <text x="646" y="72" font-size="13" font-weight="700" fill="#92400e">TU ESPACIO · App/</text>
                <text x="646" y="94" font-size="11.5" fill="#78350f">Controllers/ → tus controladores</text>
                <text x="646" y="116" font-size="11.5" fill="#78350f">Models/ → tus modelos</text>
                <text x="646" y="138" font-size="11.5" fill="#78350f">Requests/ → tus FormRequest</text>
                <text x="646" y="160" font-size="11.5" fill="#78350f">Middleware/ · views/dashboard/ · views/public/</text>
                <text x="646" y="188" font-size="10.5" font-style="italic" fill="#64748b">Aquí escribes el 100% de tu módulo.</text>

                <rect x="20" y="220" width="290" height="132" rx="10" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.5"></rect>
                <text x="36" y="246" font-size="13" font-weight="700" fill="#334155">ENTRADA · public/ + raíz</text>
                <text x="36" y="268" font-size="11.5" fill="#475569">public/index.php → única puerta de entrada</text>
                <text x="36" y="290" font-size="11.5" fill="#475569">public/.htaccess → reescritura de URLs</text>
                <text x="36" y="312" font-size="11.5" fill="#475569">bootstrap.php → arranque del sistema</text>
                <text x="36" y="334" font-size="11.5" fill="#475569">composer.json · .env · Dockerfile</text>

                <rect x="325" y="220" width="290" height="132" rx="10" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.5"></rect>
                <text x="341" y="246" font-size="13" font-weight="700" fill="#334155">CONFIGURACIÓN · config/ y routes/</text>
                <text x="341" y="268" font-size="11.5" fill="#475569">routes/web.php → sitio público</text>
                <text x="341" y="290" font-size="11.5" fill="#475569">routes/app.php → panel de administración</text>
                <text x="341" y="312" font-size="11.5" fill="#475569">config/app.php · database.php · mail.php</text>
                <text x="341" y="334" font-size="11.5" fill="#475569">config/setup.php → tablas base (una vez)</text>

                <rect x="630" y="220" width="290" height="132" rx="10" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.5"></rect>
                <text x="646" y="246" font-size="13" font-weight="700" fill="#334155">RECURSOS · resources/, storage/, docs/</text>
                <text x="646" y="268" font-size="11.5" fill="#475569">resources/dompdf/ → HTML a PDF (incluido)</text>
                <text x="646" y="290" font-size="11.5" fill="#475569">resources/FPDF/ → PDF celda a celda</text>
                <text x="646" y="312" font-size="11.5" fill="#475569">storage/.installed → marca de instalación</text>
                <text x="646" y="334" font-size="11.5" fill="#475569">docs/ → estas guías en Markdown</text>

                <rect x="20" y="372" width="900" height="34" rx="8" fill="#eef2f7" stroke="#d7dee8"></rect>
                <text x="36" y="394" font-size="11.5" fill="#334155">Leyenda:</text>
                <rect x="100" y="382" width="14" height="14" rx="3" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="122" y="394" font-size="11.5" fill="#334155">framework (no tocar)</text>
                <rect x="270" y="382" width="14" height="14" rx="3" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="292" y="394" font-size="11.5" fill="#334155">CMS (solo menú)</text>
                <rect x="420" y="382" width="14" height="14" rx="3" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="442" y="394" font-size="11.5" fill="#334155">tu código</text>
                <rect x="540" y="382" width="14" height="14" rx="3" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="562" y="394" font-size="11.5" fill="#334155">infraestructura y configuración</text>

                <text x="470" y="440" font-size="12" text-anchor="middle" fill="#64748b">public/index.php carga bootstrap.php; bootstrap.php carga config, sesión, base de datos y routes/*.php antes de despachar la petición.</text>
            </svg>
            <span class="doc-caption">Figura 1.1 — Mapa general: quién es quién dentro del proyecto.</span>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Carpeta / archivo</th>
                        <th>Rol</th>
                        <th>¿Se modifica?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>lib/Core/</code></td>
                        <td>Núcleo del framework: <code>Route</code>, <code>Request</code>, <code>FormRequest</code>,
                            <code>Response</code>, <code>View</code>, <code>Controller</code>, <code>Model</code>, <code>Database</code>.</td>
                        <td><span class="doc-badge doc-badge-red">No</span></td>
                    </tr>
                    <tr>
                        <td><code>lib/Support/</code></td>
                        <td>Servicios: <code>Session</code>, <code>Csrf</code>, <code>Validator</code>, <code>System</code>
                            (toggles), <code>Env</code>, <code>Hash</code>, <code>Mail</code>, <code>Analytics</code>.</td>
                        <td><span class="doc-badge doc-badge-red">No</span></td>
                    </tr>
                    <tr>
                        <td><code>System/</code></td>
                        <td>Núcleo del CMS: panel, login, usuarios, documentación, middleware <code>Auth</code> y modelos base.</td>
                        <td><span class="doc-badge doc-badge-amber">Solo el menú</span></td>
                    </tr>
                    <tr>
                        <td><code>App/</code></td>
                        <td>Tu espacio: controladores, modelos, requests, middlewares y vistas de tus módulos.</td>
                        <td><span class="doc-badge doc-badge-green">Sí, siempre</span></td>
                    </tr>
                    <tr>
                        <td><code>routes/web.php</code> · <code>routes/app.php</code></td>
                        <td>Registro de rutas públicas y del panel.</td>
                        <td><span class="doc-badge doc-badge-green">Sí</span></td>
                    </tr>
                    <tr>
                        <td><code>config/</code></td>
                        <td><code>app.php</code>, <code>database.php</code>, <code>mail.php</code> y <code>setup.php</code> (tablas base).</td>
                        <td><span class="doc-badge doc-badge-green">Sí</span></td>
                    </tr>
                    <tr>
                        <td><code>resources/</code></td>
                        <td>Librerías embebidas: <code>dompdf/</code> y <code>FPDF/</code>. Aquí también puedes copiar librerías manuales.</td>
                        <td><span class="doc-badge doc-badge-green">Sí</span></td>
                    </tr>
                    <tr>
                        <td><code>public/</code></td>
                        <td>Única carpeta expuesta al navegador: <code>index.php</code>, <code>.htaccess</code> y los assets
                            (<code>code/dist</code>, <code>code/plugins</code>, <code>code/css</code>, <code>code/js</code>).</td>
                        <td><span class="doc-badge doc-badge-amber">Con cuidado</span></td>
                    </tr>
                    <tr>
                        <td><code>docs/</code></td>
                        <td>Gu&iacute;as Markdown complementarias para leer en el editor. El manual completo y
                            actualizado es esta documentaci&oacute;n integrada.</td>
                        <td><span class="doc-badge doc-badge-green">Sí</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Idea clave</span>
            <p>Tú nunca abres <code>lib/</code> ni <code>System/</code> para programar. Registras rutas,
                escribes clases en <code>App/</code> y creas vistas en <code>App/views/</code>. El sistema hace
                el resto por ti (autoload, validación, sesión, toggles).</p>
        </div>
    </section>

    <section class="doc-section" id="cap1-convenciones" data-doc-section data-title="Convenciones de nombres y namespaces">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap1-convenciones">#</a>Convenciones de nombres y namespaces</h3>
        <p>
            El autoload es PSR-4 (lo configura <code>composer.json</code>): el namespace refleja la carpeta.
            Por eso una clase nueva se encuentra sola, sin registrar nada.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Namespace</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>App/Controllers/ProductosController.php</code></td>
                        <td><code>App\Controllers</code></td>
                        <td>La clase se llama igual que el archivo.</td>
                    </tr>
                    <tr>
                        <td><code>App/Models/Productos.php</code></td>
                        <td><code>App\Models</code></td>
                        <td>Sin sufijo <em>Model</em>: el nombre es el recurso.</td>
                    </tr>
                    <tr>
                        <td><code>App/Requests/StoreProductoRequest.php</code></td>
                        <td><code>App\Requests</code></td>
                        <td>Convención: <code>Store…</code> (crear), <code>Update…</code> (editar).</td>
                    </tr>
                    <tr>
                        <td><code>App/Middleware/SoloAjax.php</code></td>
                        <td><code>App\Middleware</code></td>
                        <td>Middleware propio (lo creas tú en el capítulo 4) y lo aplicas por su nombre.</td>
                    </tr>
                    <tr>
                        <td><code>App/views/dashboard/productos/index.php</code></td>
                        <td>—</td>
                        <td>Vista <code>dashboard.productos.index</code> (los puntos son carpetas).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">composer.json (extracto)</span>
                <span class="doc-code-lang">json</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>"autoload": {
    "psr-4": {
        "App\\": "App/",
        "Lib\\": "lib/",
        "System\\": "System/"
    },
    "files": [
        "lib/Helpers.php"
    ]
}</code></pre>
        </div>

        <p>
            Si agregas una clase y el navegador dice <em>«Class not found»</em>, ejecuta
            <span class="doc-kbd">composer dump-autoload</span> en la carpeta <code>sistema/</code>.
        </p>
    </section>

    <section class="doc-section" id="cap1-resolucion" data-doc-section data-title="Orden de resolución: middleware y vistas">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap1-resolucion">#</a>Orden de resolución: middleware y vistas</h3>
        <p>
            Cuando el sistema busca un <strong>middleware</strong> o una <strong>vista</strong>, recorre las
            carpetas en un orden fijo: <strong>primero <code>System/</code>, después <code>App/</code></strong>.
            Esto hace que el núcleo gane si repites un nombre, y explica por qué <strong>no debes reutilizar
            nombres</strong> de middlewares ni de vistas del sistema. Los <strong>controladores</strong> son la
            excepción: en las rutas se referencian por su clase completa (FQCN), sin búsqueda por carpeta.
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 300" width="940" height="300" role="img"
                aria-label="Diagrama de resolucion de middleware y vistas: primero System, luego App">
                <rect x="20" y="40" width="200" height="70" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="120" y="68" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">Búsqueda de middleware</text>
                <text x="120" y="90" font-size="11.5" text-anchor="middle" fill="#1e3a8a">Ej.: SoloAjax</text>

                <polygon points="232,75 244,69 244,81" fill="#94a3b8"></polygon>
                <line x1="222" y1="75" x2="240" y2="75" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="250" y="40" width="200" height="70" rx="10" fill="#e8f5e9" stroke="#22c55e" stroke-width="1.5"></rect>
                <text x="350" y="64" font-size="12.5" font-weight="700" text-anchor="middle" fill="#166534">1. System\Middleware</text>
                <text x="350" y="84" font-size="11" text-anchor="middle" fill="#14532d">\SoloAjax</text>
                <text x="350" y="100" font-size="11" text-anchor="middle" fill="#14532d">¿existe? → se usa</text>

                <polygon points="462,75 474,69 474,81" fill="#94a3b8"></polygon>
                <line x1="452" y1="75" x2="470" y2="75" stroke="#94a3b8" stroke-width="1.5"></line>
                <text x="465" y="60" font-size="10.5" text-anchor="middle" fill="#64748b">no</text>

                <rect x="480" y="40" width="200" height="70" rx="10" fill="#fff8e1" stroke="#f59e0b" stroke-width="1.5"></rect>
                <text x="580" y="64" font-size="12.5" font-weight="700" text-anchor="middle" fill="#92400e">2. App\Middleware</text>
                <text x="580" y="84" font-size="11" text-anchor="middle" fill="#78350f">\SoloAjax</text>
                <text x="580" y="100" font-size="11" text-anchor="middle" fill="#78350f">¿existe? → se usa</text>

                <polygon points="692,75 704,69 704,81" fill="#94a3b8"></polygon>
                <line x1="682" y1="75" x2="700" y2="75" stroke="#94a3b8" stroke-width="1.5"></line>
                <text x="695" y="60" font-size="10.5" text-anchor="middle" fill="#64748b">no</text>

                <rect x="710" y="40" width="200" height="70" rx="10" fill="#fdecea" stroke="#ef4444" stroke-width="1.5"></rect>
                <text x="810" y="70" font-size="12.5" font-weight="700" text-anchor="middle" fill="#991b1b">RuntimeException</text>
                <text x="810" y="90" font-size="11" text-anchor="middle" fill="#7f1d1d">"Middleware [X] not found"</text>

                <rect x="20" y="170" width="200" height="80" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="120" y="198" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">Búsqueda de vista</text>
                <text x="120" y="220" font-size="11.5" text-anchor="middle" fill="#1e3a8a">Ej.: dashboard.productos.index</text>
                <text x="120" y="238" font-size="11" text-anchor="middle" fill="#1e3a8a">View::component(...)</text>

                <polygon points="232,210 244,204 244,216" fill="#94a3b8"></polygon>
                <line x1="222" y1="210" x2="240" y2="210" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="250" y="170" width="200" height="80" rx="10" fill="#e8f5e9" stroke="#22c55e" stroke-width="1.5"></rect>
                <text x="350" y="198" font-size="12.5" font-weight="700" text-anchor="middle" fill="#166534">1. System/views/</text>
                <text x="350" y="220" font-size="11" text-anchor="middle" fill="#14532d">dashboard/productos/index.php</text>
                <text x="350" y="238" font-size="11" text-anchor="middle" fill="#14532d">¿existe el archivo? → se usa</text>

                <polygon points="462,210 474,204 474,216" fill="#94a3b8"></polygon>
                <line x1="452" y1="210" x2="470" y2="210" stroke="#94a3b8" stroke-width="1.5"></line>
                <text x="465" y="195" font-size="10.5" text-anchor="middle" fill="#64748b">no</text>

                <rect x="480" y="170" width="200" height="80" rx="10" fill="#fff8e1" stroke="#f59e0b" stroke-width="1.5"></rect>
                <text x="580" y="198" font-size="12.5" font-weight="700" text-anchor="middle" fill="#92400e">2. App/views/</text>
                <text x="580" y="220" font-size="11" text-anchor="middle" fill="#78350f">dashboard/productos/index.php</text>
                <text x="580" y="238" font-size="11" text-anchor="middle" fill="#78350f">tu archivo vive aquí</text>

                <polygon points="692,210 704,204 704,216" fill="#94a3b8"></polygon>
                <line x1="682" y1="210" x2="700" y2="210" stroke="#94a3b8" stroke-width="1.5"></line>
                <text x="695" y="195" font-size="10.5" text-anchor="middle" fill="#64748b">no</text>

                <rect x="710" y="170" width="200" height="80" rx="10" fill="#fdecea" stroke="#ef4444" stroke-width="1.5"></rect>
                <text x="810" y="200" font-size="12.5" font-weight="700" text-anchor="middle" fill="#991b1b">Error</text>
                <text x="810" y="222" font-size="11" text-anchor="middle" fill="#7f1d1d">"View [x] not found"</text>

                <text x="470" y="285" font-size="11.5" text-anchor="middle" fill="#64748b">Regla: si llamas tu middleware o vista igual que uno del núcleo, el tuyo nunca se carga. Usa nombres propios del módulo.</text>
            </svg>
            <span class="doc-caption">Figura 1.2 — Middlewares y vistas: el núcleo gana siempre; los controladores se referencian por FQCN.</span>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Evita estos nombres</span>
            <p>No llames a tus middlewares ni vistas <code>Auth</code>, <code>dashboard.base.dashboard</code>,
                etc. Los controladores no colisionan porque se referencian por su clase completa, pero igual
                conviene usar nombres propios del proyecto: <code>VentasController</code>, <code>Ventas</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap1-comprobar" data-doc-section data-title="Cómo comprobar la estructura">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap1-comprobar">#</a>Cómo comprobar la estructura</h3>
        <p>
            No necesitas adivinar nada: el propio panel te muestra el estado real del sistema.
            Usa estas pantallas como «rayos X» del proyecto:
        </p>

        <div class="doc-cols">
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-tachometer-alt"></i>Resumen</div>
                <p>Versión de PHP, entorno, URL, servidor, ruta base, base de datos, número de rutas y usuarios.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-route"></i>Rutas y enlaces</div>
                <p>Todas las rutas registradas con su método, URI, nombre, middlewares y estado (activa/inactiva).</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-magic"></i>Generadores</div>
                <p>Escanea <code>App/</code>, enseña el código fuente de cualquier clase y crea módulos nuevos sin escribirlos a mano.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-database"></i>Base de datos</div>
                <p>Conexión activa, tablas existentes, columnas reales de cada tabla y consola SQL para consultas directas.</p>
            </div>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Truco de verificación rápida</span>
            <p>Crea una tabla de prueba desde la consola de <strong>Sistema → Base de datos</strong>
                (<code>CREATE TABLE tmp_prueba (id serial PRIMARY KEY)</code>) y recarga: si aparece en el
                listado con sus columnas, la conexión y los permisos están bien. Bórrala con
                <code>DROP TABLE</code> al terminar.</p>
        </div>

        <p>
            Para comprobar las carpetas desde el explorador, recuerda la estructura mínima que debes conocer
            de memoria: <code>App/</code> (tu código), <code>routes/</code> (tus rutas), <code>config/</code>
            (ajustes), <code>System/</code> y <code>lib/</code> (núcleo, no tocar).
        </p>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap2"><i class="fas fa-arrow-right mr-1"></i>Siguiente: arranque del sistema</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap1') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
