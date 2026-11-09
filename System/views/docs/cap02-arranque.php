<section class="doc-chapter" id="cap2" data-doc-chapter data-number="2" data-title="Arranque del sistema">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 2</span>
        <h2 class="doc-chapter-title">Arranque del sistema</h2>
        <p class="doc-chapter-lead">
            Antes de que se ejecute tu primera línea de código, el sistema hace una secuencia fija:
            carga dependencias, lee el <code>.env</code>, inicia la sesión, conecta la base de datos,
            crea las tablas base la primera vez y registra las rutas. Este capítulo explica ese viaje completo.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap2-ciclo">Ciclo de una petición</a>
            <a class="doc-chip" href="#cap2-bootstrap">bootstrap.php línea a línea</a>
            <a class="doc-chip" href="#cap2-instalacion">Primer arranque</a>
        </div>
    </header>

    <section class="doc-section" id="cap2-entrada" data-doc-section data-title="La única puerta de entrada: public/index.php">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap2-entrada">#</a>La única puerta de entrada: public/index.php</h3>
        <p>
            Todas las URLs del sistema (páginas, AJAX, descargas PDF) entran por <code>public/index.php</code>.
            El archivo <code>public/.htaccess</code> reescribe las URLs para que todo pase por ahí, y el
            <code>index.php</code> hace solo cuatro cosas:
        </p>

        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Arranca el núcleo</div>
                <p><code>require bootstrap.php</code>: dependencias, entorno, sesión, base de datos y ajustes.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Registra las rutas</div>
                <p><code>routes/app.php</code> (panel) y <code>routes/web.php</code> (sitio público), en ese orden.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Aplica el modo mantenimiento</div>
                <p>Si el mantenimiento está activo y la URL no es del panel, responde la página 503 y termina.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">4. Despacha y responde</div>
                <p><code>Route::dispatch($request)</code> encuentra la ruta, corre middlewares y controlador, y
                    <code>$response->send()</code> envía la respuesta. Si es una página pública visitada con éxito,
                    además registra la visita.</p>
            </div>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">public/index.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

require_once BASE_PATH . '/routes/app.php';
require_once BASE_PATH . '/routes/web.php';

$request = Lib\Core\Request::createFromGlobals();

// Modo mantenimiento: bloquea el sitio público, nunca el panel.
$publicPath = trim($request->uri(), '/');
$isAdminArea = str_starts_with($publicPath, 'join/access/dashboard');

if (!$isAdminArea && Lib\Support\Session::get('MAINTENANCE')) {
    $response = Lib\Core\Response::make(
        Lib\Core\View::render('error.maintenance', [...]),
        503
    );
    $response->send();
}

$response = Lib\Core\Route::dispatch($request);

if (!$isAdminArea && $request->method() === 'GET' && $response->getStatus() === 200) {
    Lib\Support\Analytics::track($request);
}

$response->send();</code></pre>
        </div>
    </section>

    <section class="doc-section" id="cap2-ciclo" data-doc-section data-title="El ciclo completo de una petición">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap2-ciclo">#</a>El ciclo completo de una petición</h3>
        <p>
            Memoriza este diagrama: es el camino que recorre <em>cualquier</em> petición, sea la página de
            inicio del sitio o el botón «Guardar» de tu módulo. Cuando algo falla, siempre puedes ubicar
            el problema en una de estas etapas.
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 340" width="940" height="340" role="img"
                aria-label="Ciclo de una peticion desde el navegador hasta la respuesta">
                <rect x="20" y="30" width="205" height="76" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="122" y="60" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">1. Navegador</text>
                <text x="122" y="82" font-size="11.5" text-anchor="middle" fill="#1e3a8a">URL o petición AJAX</text>
                <text x="122" y="98" font-size="11" text-anchor="middle" fill="#64748b">GET / POST + cookie de sesión</text>

                <polygon points="237,68 249,62 249,74" fill="#94a3b8"></polygon>
                <line x1="227" y1="68" x2="245" y2="68" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="255" y="30" width="205" height="76" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="357" y="60" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">2. public/index.php</text>
                <text x="357" y="82" font-size="11.5" text-anchor="middle" fill="#1e3a8a">.htaccess reescribe la URL</text>
                <text x="357" y="98" font-size="11" text-anchor="middle" fill="#64748b">gate de mantenimiento (503)</text>

                <polygon points="472,68 484,62 484,74" fill="#94a3b8"></polygon>
                <line x1="462" y1="68" x2="480" y2="68" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="490" y="30" width="205" height="76" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="592" y="60" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">3. bootstrap.php</text>
                <text x="592" y="82" font-size="11.5" text-anchor="middle" fill="#1e3a8a">.env · sesión · base de datos</text>
                <text x="592" y="98" font-size="11" text-anchor="middle" fill="#64748b">config/setup.php · ajustes</text>

                <polygon points="707,68 719,62 719,74" fill="#94a3b8"></polygon>
                <line x1="697" y1="68" x2="715" y2="68" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="725" y="30" width="195" height="76" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="822" y="60" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">4. routes/*.php</text>
                <text x="822" y="82" font-size="11.5" text-anchor="middle" fill="#1e3a8a">se registran las rutas</text>
                <text x="822" y="98" font-size="11" text-anchor="middle" fill="#64748b">app.php y web.php</text>

                <path d="M 822 108 L 822 142 L 122 142 L 122 172" fill="none" stroke="#94a3b8" stroke-width="1.5"></path>
                <polygon points="122,184 116,172 128,172" fill="#94a3b8"></polygon>

                <rect x="20" y="190" width="205" height="76" rx="10" fill="#e8f5e9" stroke="#22c55e" stroke-width="1.5"></rect>
                <text x="122" y="220" font-size="13" font-weight="700" text-anchor="middle" fill="#166534">5. Route::dispatch()</text>
                <text x="122" y="242" font-size="11.5" text-anchor="middle" fill="#14532d">busca la URL y aplica toggles</text>
                <text x="122" y="258" font-size="11" text-anchor="middle" fill="#64748b">si no existe → 404</text>

                <polygon points="237,228 249,222 249,234" fill="#94a3b8"></polygon>
                <line x1="227" y1="228" x2="245" y2="228" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="255" y="190" width="205" height="76" rx="10" fill="#e8f5e9" stroke="#22c55e" stroke-width="1.5"></rect>
                <text x="357" y="220" font-size="13" font-weight="700" text-anchor="middle" fill="#166534">6. Middlewares</text>
                <text x="357" y="242" font-size="11.5" text-anchor="middle" fill="#14532d">Auth y los tuyos, en orden</text>
                <text x="357" y="258" font-size="11" text-anchor="middle" fill="#64748b">true | Response | false</text>

                <polygon points="472,228 484,222 484,234" fill="#94a3b8"></polygon>
                <line x1="462" y1="228" x2="480" y2="228" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="490" y="190" width="205" height="76" rx="10" fill="#e8f5e9" stroke="#22c55e" stroke-width="1.5"></rect>
                <text x="592" y="220" font-size="13" font-weight="700" text-anchor="middle" fill="#166534">7. Controlador</text>
                <text x="592" y="242" font-size="11.5" text-anchor="middle" fill="#14532d">FormRequest valida antes</text>
                <text x="592" y="258" font-size="11" text-anchor="middle" fill="#64748b">Modelos y vistas dentro</text>

                <polygon points="707,228 719,222 719,234" fill="#94a3b8"></polygon>
                <line x1="697" y1="228" x2="715" y2="228" stroke="#94a3b8" stroke-width="1.5"></line>

                <rect x="725" y="190" width="195" height="76" rx="10" fill="#e8f5e9" stroke="#22c55e" stroke-width="1.5"></rect>
                <text x="822" y="220" font-size="13" font-weight="700" text-anchor="middle" fill="#166534">8. Response</text>
                <text x="822" y="242" font-size="11.5" text-anchor="middle" fill="#14532d">HTML (vista) o JSON</text>
                <text x="822" y="258" font-size="11" text-anchor="middle" fill="#64748b">$response-&gt;send()</text>

                <rect x="20" y="292" width="900" height="34" rx="8" fill="#eef2f7" stroke="#d7dee8"></rect>
                <text x="36" y="314" font-size="11.5" fill="#334155">Los errores se capturan en el dispatch:</text>
                <text x="230" y="314" font-size="11.5" fill="#991b1b">AuthorizationException → 403 JSON</text>
                <text x="470" y="314" font-size="11.5" fill="#92400e">ValidationException → 422 JSON + errors</text>
                <text x="760" y="314" font-size="11.5" fill="#334155">resto → PHP error</text>
            </svg>
            <span class="doc-caption">Figura 2.1 — De la URL a la respuesta: ocho etapas, siempre en el mismo orden.</span>
        </div>
    </section>

    <section class="doc-section" id="cap2-bootstrap" data-doc-section data-title="bootstrap.php: el arranque paso a paso">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap2-bootstrap">#</a>bootstrap.php: el arranque paso a paso</h3>
        <p>
            <code>bootstrap.php</code> prepara el terreno. Este es el orden exacto de lo que ocurre
            (importante cuando depuras: si la base de datos no responde, nada de lo siguiente se ejecuta):
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 320" width="940" height="320" role="img"
                aria-label="Secuencia de arranque dentro de bootstrap.php">
                <rect x="20" y="20" width="900" height="42" rx="8" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="40" y="47" font-size="12.5" font-weight="700" fill="#0b4ea8">1. BASE_PATH</text>
                <text x="170" y="47" font-size="12" fill="#1e3a8a">define la raíz del proyecto (una sola vez) · si el proyecto se mueve, todo sigue funcionando con rutas absolutas.</text>

                <rect x="20" y="72" width="900" height="42" rx="8" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="40" y="99" font-size="12.5" font-weight="700" fill="#0b4ea8">2. Composer</text>
                <text x="170" y="99" font-size="12" fill="#1e3a8a">carga vendor/autoload.php (PSR-4: Lib, System, App). Si falta, muestra «Run composer install first».</text>

                <rect x="20" y="124" width="900" height="42" rx="8" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="40" y="151" font-size="12.5" font-weight="700" fill="#0b4ea8">3. Entorno</text>
                <text x="170" y="151" font-size="12" fill="#1e3a8a">Env::load() lee .env · zona horaria · modo debug (muestra u oculta errores PHP).</text>

                <rect x="20" y="176" width="900" height="42" rx="8" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="40" y="203" font-size="12.5" font-weight="700" fill="#0b4ea8">4. Sesión</text>
                <text x="170" y="203" font-size="12" fill="#1e3a8a">Session::start() con cookie httponly, samesite Lax y duración SESSION_LIFETIME.</text>

                <rect x="20" y="228" width="900" height="42" rx="8" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="40" y="255" font-size="12.5" font-weight="700" fill="#0b4ea8">5. Base de datos</text>
                <text x="170" y="255" font-size="12" fill="#1e3a8a">Database::configure(config('database')) · config/setup.php crea settings, visits, users y system_settings la primera vez.</text>

                <rect x="20" y="280" width="900" height="34" rx="8" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="40" y="302" font-size="12.5" font-weight="700" fill="#166534">6. Ajustes y compatibilidad</text>
                <text x="330" y="302" font-size="12" fill="#14532d">Settings → SESSION (SITE_NAME, TIME_ZONE, MAINTENANCE) · View::share(appName) · constantes URL/APP/CHARSET.</text>
            </svg>
            <span class="doc-caption">Figura 2.2 — Secuencia de arranque. El marcador <code>storage/.installed</code> evita repetir la instalación.</span>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>¿Qué es config/setup.php?</span>
            <p>Es el instalador de tablas base. La primera vez crea <code>settings</code>, <code>visits</code>,
                <code>users</code> (con el admin inicial) y <code>system_settings</code>, y escribe
                <code>storage/.installed</code> con <code>conexión:base</code>. Si una instalación anterior
                tiene la columna <code>users.role</code>, la elimina (migración de seguridad). Si algún día
                quieres repetir la instalación en una base nueva, borra ese archivo. Tus tablas propias también
                pueden crearse aquí (ver <a href="#cap8">Capítulo 8</a>), con verificación de existencia.</p>
        </div>
    </section>

    <section class="doc-section" id="cap2-env" data-doc-section data-title="El .env: todo lo configurable en un solo lugar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap2-env">#</a>El .env: todo lo configurable en un solo lugar</h3>
        <p>
            El archivo <code>.env</code> vive en la raíz y nunca se comparte ni se sube a un repositorio.
            Se crea copiando <code>.env.example</code>. Los valores se leen con <code>config('archivo.clave')</code>.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Variable</th>
                        <th>Para qué</th>
                        <th>Dónde se usa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>APP_NAME</code></td>
                        <td>Nombre del sitio (título, correos, PDF).</td>
                        <td><code>config('app.name')</code></td>
                    </tr>
                    <tr>
                        <td><code>APP_URL</code></td>
                        <td>URL base para <code>asset()</code>, <code>route()</code> y enlaces.</td>
                        <td><code>config('app.url')</code></td>
                    </tr>
                    <tr>
                        <td><code>APP_DEBUG</code></td>
                        <td>Muestra errores PHP detallados y habilita mensajes de depuración.</td>
                        <td>Arranque y controladores</td>
                    </tr>
                    <tr>
                        <td><code>DB_CONNECTION</code> <code>DB_HOST</code> <code>DB_DATABASE</code> <code>DB_USERNAME</code> <code>DB_PASSWORD</code></td>
                        <td>Conexión de base de datos (MySQL/MariaDB o PostgreSQL).</td>
                        <td><code>config('database')</code></td>
                    </tr>
                    <tr>
                        <td><code>MAIL_HOST</code> <code>MAIL_PORT</code> <code>MAIL_USERNAME</code> <code>MAIL_PASSWORD</code> <code>MAIL_FROM_ADDRESS</code></td>
                        <td>Servidor SMTP para <code>Lib\Support\Mail</code>.</td>
                        <td><code>config('mail')</code></td>
                    </tr>
                    <tr>
                        <td><code>SESSION_LIFETIME</code></td>
                        <td>Minutos de vida de la sesión (por defecto 120).</td>
                        <td><code>config('app.session_lifetime')</code></td>
                    </tr>
                    <tr>
                        <td><code>TIMEZONE</code> <code>LANGUAGE</code></td>
                        <td>Zona horaria y código de idioma del panel.</td>
                        <td><code>config('app.timezone')</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Después de editar .env</span>
            <p>Ejecuta <span class="doc-kbd">php -r "opcache_reset();"</span> o reinicia el contenedor si usas OPcache.
                Si cambias la base de datos, borra también <code>storage/.installed</code> para que
                <code>setup.php</code> vuelva a crear las tablas en la nueva base.</p>
        </div>
    </section>

    <section class="doc-section" id="cap2-instalacion" data-doc-section data-title="Primer arranque (paso a paso)">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap2-instalacion">#</a>Primer arranque (paso a paso)</h3>

        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">Paso 1. Dependencias PHP</div>
                <p>Dentro de la carpeta <code>sistema/</code>: <span class="doc-kbd">composer install</span></p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">Paso 2. Variables de entorno</div>
                <p><span class="doc-kbd">copy .env.example .env</span> (Windows) o
                    <span class="doc-kbd">cp .env.example .env</span>. Revisa <code>APP_URL</code> y las
                    credenciales de la base de datos.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">Paso 3. Levantar el contenedor</div>
                <p>Desde la carpeta padre del proyecto: <span class="doc-kbd">docker compose up -d --build</span>.
                    Sitio público en <code>http://localhost:8095</code> y panel en
                    <code>http://localhost:8095/join/access/dashboard</code>.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">Paso 4. Entrar y cambiar la clave</div>
                <p>El primer arranque crea el administrador <code>admin / admin123</code>. Entra al panel,
                    abre el menú de tu usuario (esquina del sidebar) y cambia la contraseña.</p>
            </div>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Sin Docker</span>
            <p>También funciona con Apache + PHP 8.1 o superior apuntando el DocumentRoot a <code>public/</code>.
                La clave es que <strong>solo</strong> <code>public/</code> quede expuesta: <code>lib/</code>,
                <code>System/</code>, <code>App/</code> y <code>.env</code> deben quedar fuera del navegador.</p>
        </div>
    </section>

    <section class="doc-section" id="cap2-comprobar" data-doc-section data-title="Cómo comprobar que arrancó bien">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap2-comprobar">#</a>Cómo comprobar que arrancó bien</h3>
        <ul class="doc-check">
            <li><strong>Sitio público responde</strong> en <code>http://localhost:8095</code> (sin errores 500).</li>
            <li><strong>Panel carga</strong> en <code>/join/access/dashboard</code> con la pantalla de login.</li>
            <li><strong>Base de datos creada</strong>: entra al panel y abre <strong>Sistema → Base de datos</strong>;
                deben aparecer <code>settings</code>, <code>visits</code>, <code>users</code> y <code>system_settings</code>.</li>
            <li><strong>Archivos marcados</strong>: existe <code>storage/.installed</code> con el texto <code>mysql:gupadis</code> (o tu conexión).</li>
            <li><strong>Entorno correcto</strong>: en <strong>Sistema → Resumen</strong> revisa versión PHP, entorno,
                base de datos y URL base.</li>
        </ul>

        <div class="doc-danger">
            <span class="doc-danger-title"><i class="fas fa-bug mr-1"></i>Si algo falla aquí</span>
            <p>Activa temporalmente <code>APP_DEBUG=true</code> en el <code>.env</code>: el sistema te mostrará el
                error PHP real en pantalla. Los tres problemas más comunes: base de datos sin conexión
                (revisa credenciales/red), falta <code>vendor/</code> (<span class="doc-kbd">composer install</span>)
                y permisos de escritura en <code>storage/</code>.</p>
        </div>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap3"><i class="fas fa-arrow-right mr-1"></i>Siguiente: rutas y CRUDs</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap2') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
