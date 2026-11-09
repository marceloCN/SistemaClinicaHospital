<section class="doc-chapter" id="cap13" data-doc-chapter data-number="13" data-title="Dashboard de administración: guía de uso">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 13</span>
        <h2 class="doc-chapter-title">Dashboard de administración: guía de uso</h2>
        <p class="doc-chapter-lead">
            Este capítulo es el manual de usuario del panel: qué significa cada zona de la pantalla, qué
            puedes hacer en cada página y qué flujos de trabajo siguen los administradores para construir
            sistemas completos sin salir del dashboard. Los capítulos anteriores explican el «por qué»;
            aquí importa el «cómo se usa cada día».
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap13-mapa">Anatomía del panel</a>
            <a class="doc-chip" href="#cap13-sesion">Sesión y perfil</a>
            <a class="doc-chip" href="#cap13-resumen">Resumen</a>
            <a class="doc-chip" href="#cap13-usuarios">Usuarios</a>
            <a class="doc-chip" href="#cap13-autogestion">Autogestión</a>
            <a class="doc-chip" href="#cap13-flujos">Flujos de trabajo</a>
        </div>
    </header>

    <section class="doc-section" id="cap13-mapa" data-doc-section data-title="Anatomía del panel: qué ve la pantalla">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-mapa">#</a>Anatomía del panel: qué ve la pantalla</h3>
        <p>
            Todas las páginas del panel comparten el mismo armazón (<code>System/views/dashboard/base/dashboard.php</code>).
            Conocer sus cinco zonas te dice de un vistazo dónde está lo que buscas:
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Zona</th>
                        <th>Dónde está</th>
                        <th>Qué contiene</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Barra superior</td>
                        <td>Arriba, a todo lo ancho</td>
                        <td>Botón del menú lateral, reloj en vivo y el título de la página.</td>
                    </tr>
                    <tr>
                        <td>Menú lateral</td>
                        <td>Izquierda, oscuro</td>
                        <td>Grupos <strong>Sistema</strong> (Resumen, Usuarios, Rutas, Middlewares,
                            Generadores, Página de inicio, Base de datos, Documentación) y
                            <strong>Sesión</strong> (Panel, Ver sitio, Cerrar sesión), más tu nombre
                            arriba con acceso al modal «Mi usuario».</td>
                    </tr>
                    <tr>
                        <td>Migas de pan</td>
                        <td>Esquina superior derecha del contenido</td>
                        <td><code>Sistema / &lt;sección&gt;</code>: confirma siempre dónde estás.</td>
                    </tr>
                    <tr>
                        <td>Contenido</td>
                        <td>Centro</td>
                        <td>La tarjeta de la página actual (tablas, formularios, consola…).</td>
                    </tr>
                    <tr>
                        <td>Notificaciones</td>
                        <td>Esquina</td>
                        <td>Toasts de éxito/error (<code>showNotification()</code>) que desaparecen solos.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 340" width="940" height="340" role="img"
                aria-label="Diagrama de zonas del dashboard: barra superior, menu lateral, contenido y pie">
                <rect x="20" y="20" width="900" height="34" rx="8" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="40" y="42" font-size="12" font-weight="700" fill="#0b4ea8">BARRA SUPERIOR</text>
                <text x="700" y="42" font-size="11" fill="#1e3a8a">reloj · título de página</text>

                <rect x="20" y="66" width="220" height="216" rx="8" fill="#1f2a37" stroke="#111827"></rect>
                <text x="36" y="90" font-size="12" font-weight="700" fill="#93c5fd">MENÚ LATERAL</text>
                <text x="36" y="114" font-size="11" fill="#e5e7eb">▸ Sistema</text>
                <text x="52" y="134" font-size="10.5" fill="#cbd5e1">Resumen · Usuarios · Rutas</text>
                <text x="52" y="152" font-size="10.5" fill="#cbd5e1">Middlewares · Generadores</text>
                <text x="52" y="170" font-size="10.5" fill="#cbd5e1">Página de inicio · Base de datos</text>
                <text x="52" y="188" font-size="10.5" fill="#cbd5e1">Documentación</text>
                <text x="36" y="214" font-size="11" fill="#e5e7eb">▸ Sesión</text>
                <text x="52" y="234" font-size="10.5" fill="#cbd5e1">Panel · Ver sitio · Cerrar sesión</text>
                <text x="36" y="262" font-size="10.5" fill="#fbbf24">tu nombre → modal «Mi usuario»</text>

                <rect x="256" y="66" width="664" height="42" rx="8" fill="#f1f5f9" stroke="#94a3b8"></rect>
                <text x="272" y="86" font-size="11.5" font-weight="700" fill="#334155">MIGAS DE PAN</text>
                <text x="640" y="86" font-size="11" fill="#64748b">Sistema / Base de datos</text>

                <rect x="256" y="120" width="664" height="130" rx="8" fill="#ffffff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="588" y="150" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">CONTENIDO DE LA PÁGINA</text>
                <text x="588" y="176" font-size="11.5" text-anchor="middle" fill="#334155">tarjeta con tablas, formularios, modales, consola SQL…</text>
                <text x="588" y="200" font-size="11.5" text-anchor="middle" fill="#64748b">cada acción AJAX muestra un toast de éxito o error</text>
                <text x="588" y="228" font-size="10.5" text-anchor="middle" fill="#94a3b8">el breadcrumb y el menú resaltan la sección activa</text>

                <rect x="20" y="294" width="900" height="30" rx="8" fill="#eef2f7" stroke="#d7dee8"></rect>
                <text x="40" y="313" font-size="11" fill="#475569">PIE · nombre de la aplicación y año</text>
                <text x="700" y="313" font-size="11" fill="#64748b">scripts: jQuery, Bootstrap, AdminLTE, notification.js</text>
            </svg>
            <span class="doc-caption">Figura 13.1 — Las cinco zonas del dashboard, iguales en todas las páginas.</span>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Atajo de teclado</span>
            <p>En <strong>Sistema → Documentación</strong> pulsa <code>Ctrl + K</code> para abrir el buscador
                del manual sin tocar el ratón; <code>Esc</code> lo cierra.</p>
        </div>
    </section>

    <section class="doc-section" id="cap13-sesion" data-doc-section data-title="Sesión: entrar, tu perfil y salir">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-sesion">#</a>Sesión: entrar, tu perfil y salir</h3>
        <p>
            El acceso se hace por <code>/join/access/dashboard</code> (nombre de ruta <code>login</code>).
            Tras validar credenciales, el sistema guarda tu sesión y todas las páginas del panel exigen
            esa sesión dos veces: con el middleware <code>Auth</code> en la ruta y con
            <code>adminPage()</code>/<code>adminJson()</code> en el controlador (Capítulo 10).
        </p>

        <div class="doc-cols">
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-user-edit"></i>Mi usuario</div>
                <p>Haz clic en tu nombre (menú lateral) para abrir el modal: cambia nombre, correo o
                    contraseña. Deja la contraseña vacía para conservar la actual.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-id-badge"></i>Panel</div>
                <p>Página de bienvenida con tu nombre y un enlace al Resumen. Útil como punto neutro
                    tras iniciar sesión.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-sign-out-alt"></i>Cerrar sesión</div>
                <p>Botón del menú: es un <code>POST</code> con token CSRF (nunca un simple enlace
                    <code>GET</code>), como exige el Capítulo 6.</p>
            </div>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Qué hace el modal «Mi usuario»</span>
                <span class="doc-code-lang">javascript</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// dashboard.php — el modal reutiliza la ruta de edición de Usuarios
$.post(usuarios_actualizar_url, $(this).serialize(), function(res) {
    if (res &amp;&amp; res.success) {
        showNotification('success', res.message);
        setTimeout(function() { window.location.reload(); }, 800); // la sesión ya tiene los datos nuevos
    }
}, 'json');</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Contraseña olvidada</span>
            <p>Si el administrador pierde la contraseña y no queda nadie más, recupérala con SQL desde la
                consola del Capítulo 10 (la tabla es <code>users</code>) o regenerando el hash con
                <code>Lib\Support\Hash::make('nueva')</code> en un script temporal dentro del contenedor.</p>
        </div>
    </section>

    <section class="doc-section" id="cap13-resumen" data-doc-section data-title="Resumen: el tablero de control">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-resumen">#</a>Resumen: el tablero de control</h3>
        <p>
            <strong>Sistema → Resumen</strong> (<code>system</code>) es la primera pantalla tras entrar. Su
            orden responde a «¿en qué entorno estoy y qué está activo?»:
        </p>

        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">Cuatro indicadores</div>
                <p>Versión de PHP, entorno (<code>local</code>/<code>production</code>), número de tablas
                    de la base activa y estado del modo mantenimiento (<code>ON</code>/<code>OFF</code>).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">Tabla «Entorno del sistema»</div>
                <p>URL pública, ruta base, base de datos, nº de rutas y usuarios, y los dos interruptores:
                    <strong>Depuración</strong> (muestra errores en pantalla) y el enlace al
                    mantenimiento. El interruptor de depuración es el más usado al desarrollar.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">Accesos rápidos</div>
                <p>Lista con las páginas más usadas (Rutas, Middlewares, Página de inicio, Base de datos,
                    Documentación) con su dato clave a la derecha: cuántas rutas, cuántas tablas, etc.</p>
            </div>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Regla práctica de depuración</span>
            <p>Con <strong>Depuración</strong> activada, cualquier error PHP se pinta en pantalla con archivo
                y línea: es lo primero que miras ante una pantalla en blanco. Desactívala en cuanto el
                módulo funcione: en producción debe estar apagada (el archivo <code>.env</code> manda).</p>
        </div>
    </section>

    <section class="doc-section" id="cap13-usuarios" data-doc-section data-title="Usuarios: altas, ediciones y bajas">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-usuarios">#</a>Usuarios: altas, ediciones y bajas</h3>
        <p>
            <strong>Usuarios</strong> (<code>usuarios</code>) gestiona las cuentas que entran al panel (tabla
            <code>users</code>). La tabla muestra nombre, correo, estado, fecha de registro y acciones; tu
            propia fila lleva el badge <em>TÚ</em> y su interruptor está deshabilitado: no puedes
            desactivarte a ti mismo.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Acción</th>
                        <th>Cómo</th>
                        <th>Qué vigila</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Crear</td>
                        <td>Botón <strong>Nuevo usuario</strong> → modal con nombre, correo y contraseña.</td>
                        <td>El correo no puede repetirse (se valida en vivo, Capítulo 12).</td>
                    </tr>
                    <tr>
                        <td>Editar</td>
                        <td>Botón <strong>Editar</strong> en la fila (modal). Si dejas vacía la contraseña, no cambia.</td>
                        <td>Solo nombre, correo y contraseña; el rol no se edita aquí.</td>
                    </tr>
                    <tr>
                        <td>Activar/desactivar</td>
                        <td>Interruptor de la fila → <code>POST /usuarios/estado</code>.</td>
                        <td>Un usuario con <code>status = 0</code> no puede iniciar sesión.</td>
                    </tr>
                    <tr>
                        <td>Eliminar</td>
                        <td>Botón <strong>Eliminar</strong> con confirmación → <code>POST /usuarios/eliminar</code>.</td>
                        <td>Borrado físico de la fila; no se puede eliminar a ti mismo.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-danger">
            <span class="doc-danger-title"><i class="fas fa-exclamation-circle mr-1"></i>Alta inicial</span>
            <p>Crea siempre un segundo administrador antes de tocar el primero: si editas tu contraseña mal
                y quedas fuera, solo el otro usuario (o SQL directo) podrá entrar.</p>
        </div>
    </section>

    <section class="doc-section" id="cap13-autogestion" data-doc-section data-title="Autogestión: explorador y generadores">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-autogestion">#</a>Autogestión: explorador y generadores</h3>
        <p>
            <strong>Sistema → Generadores</strong> (<code>system_generators</code>) es la herramienta que
            permite al propio panel crecer sin editor de código externo. Tiene dos caras:
        </p>

        <div class="doc-cols">
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-search"></i>Explorador (solo lectura)</div>
                <p>Escanea <code>App/</code> y lista tus controladores, middlewares y FormRequest con sus
                    métodos. Al elegir uno ves el cuerpo de cada método y su análisis de flujo: a qué
                    llama, qué devuelve y por dónde pasa. No modifica nada.</p>
            </div>
            <div class="doc-mini-card">
                <div class="doc-mini-title"><i class="fas fa-magic"></i>Generador (crea archivos nuevos)</div>
                <p>Crea <strong>middleware</strong>, <strong>FormRequest</strong> o <strong>controlador</strong>
                    a partir de un nombre y unas opciones. Genera una vista previa con todas las piezas
                    (clase, rutas, menú) y las escribe en disco solo si confirmas.</p>
            </div>
        </div>

        <div class="doc-h4">Flujo completo en tres clics</div>
        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Elige tipo y plantilla</div>
                <p>Middleware (plantillas <em>auth</em>, <em>rol</em>, <em>permiso</em>, <em>custom</em>),
                    Request o Controlador (con los métodos <code>index/store/show/update/destroy</code> que
                    marques).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Revisa la vista previa</div>
                <p>El botón <strong>Vista previa</strong> muestra exactamente qué se va a escribir, incluida
                    la línea a pegar en <code>routes/app.php</code> y, si toca, la del menú
                    <code>dashboard.php</code>.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Genera y pega las rutas</div>
                <p><strong>Crear</strong> escribe el archivo en <code>App/</code> (409 si ya existe: nunca
                    sobreescribe) y te recuerda qué rutas registrar manualmente.</p>
            </div>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">La única edición manual tras generar</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// routes/app.php — dentro del grupo Auth, donde te indique el generador
Route::controller(MiModuloController::class, function () {
    Route::prefix('/Area-of-work/mi-modulo', function () {
        Route::get('/', 'index')->name('mi_modulo');
        Route::post('/guardar', 'store')->name('mi_modulo_guardar');
    });
});</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Aprender mirando</span>
            <p>Antes de escribir un módulo parecido a otro que ya funciona, usa el explorador para leer el
                controlador existente: el análisis de flujo te dice qué llamadas hace (validación, modelo,
                respuesta JSON) sin abrir el archivo a mano. Es la mejor plantilla de la que dispones.</p>
        </div>
    </section>

    <section class="doc-section" id="cap13-database" data-doc-section data-title="Base de datos en el día a día">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-database">#</a>Base de datos en el día a día</h3>
        <p>
            <strong>Sistema → Base de datos</strong> (<code>system_database</code>) tiene tres pestañas y se
            usa así en la rutina diaria:
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Pestaña</th>
                        <th>Úsala para</th>
                        <th>Ejemplo típico</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Conexión activa</td>
                        <td>Confirmar a qué base estás conectado.</td>
                        <td>Antes de tocar datos: ¿driver, servidor, BD, usuario?</td>
                    </tr>
                    <tr>
                        <td>Tablas</td>
                        <td>Ver columnas, tipos, motor, filas y tamaño.</td>
                        <td>Comprobar que <code>productos</code> quedó creada como esperabas.</td>
                    </tr>
                    <tr>
                        <td>Modo consola</td>
                        <td>Ejecutar SQL real y ver resultados o errores depurados.</td>
                        <td><code>SELECT * FROM productos ORDER BY id DESC LIMIT 20;</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>
            La consola distingue consultas (devuelven tabla con columnas y filas) de comandos
            <code>INSERT</code>/<code>UPDATE</code>/<code>DELETE</code>/DDL (devuelven filas afectadas) y,
            si algo falla, muestra el SQLSTATE del driver con una explicación en español: sintaxis, objeto
            inexistente, permisos, claves duplicadas y demás. Cada tabla de la pestaña anterior tiene un
            botón <i class="fas fa-terminal"></i> que carga su <code>SELECT * … LIMIT 100</code> listo para
            ejecutar.
        </p>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>La consola ejecuta SQL real</span>
            <p>No hay deshacer: un <code>UPDATE</code> sin <code>WHERE</code> o un <code>DROP</code> afecta
                a los datos de inmediato. Practica con tablas temporales y respalda antes de operaciones
                grandes.</p>
        </div>
    </section>

    <section class="doc-section" id="cap13-flujos" data-doc-section data-title="Flujos de trabajo: el panel como sistema completo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-flujos">#</a>Flujos de trabajo: el panel como sistema completo</h3>
        <p>
            La potencia del dashboard no está en cada página, sino en combinarlas. Estos tres flujos son
            los que usan los administradores para construir y mantener sistemas complejos.
        </p>

        <div class="doc-h4">Flujo A — Levantar un módulo nuevo de principio a fin</div>
        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Diseña la tabla</div>
                <p>En <strong>Base de datos → Modo consola</strong> ejecuta el <code>CREATE TABLE</code> y
                    verifica columnas e índices con un <code>SELECT</code>. Si hay error de sintaxis, la
                    consola te dice exactamente dónde.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Genera las clases base</div>
                <p>En <strong>Generadores</strong> crea el FormRequest (validación) y el controlador
                    (métodos CRUD), revisa la vista previa y genera.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Registra las rutas</div>
                <p>Pega el bloque sugerido en <code>routes/app.php</code> (o <code>web.php</code> si es
                    público) y confirma en <strong>Sistema → Rutas</strong> que aparecen con el origen
                    correcto (<code>app.php</code>/<code>web.php</code> y línea).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">4. Escribe la vista y el menú</div>
                <p>Copia una vista existente similar (o usa el Capítulo 12) y añade el enlace al menú
                    lateral si será una sección permanente.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">5. Prueba desde el panel</div>
                <p><code>php -l</code> y <code>php tests/run.php</code> en verde, luego CRUD completo desde
                    la interfaz y verificación final de datos en <strong>Base de datos</strong>.</p>
            </div>
        </div>

        <div class="doc-h4">Flujo B — Diagnosticar un error sin adivinar</div>
        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. ¿Existe la ruta y está activa?</div>
                <p><strong>Sistema → Rutas</strong>: filtra por <code>web.php</code>/<code>app.php</code> y
                    comprueba que el interruptor está puesto.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. ¿Es un 403/422?</div>
                <p>Respuesta JSON en el navegador (DevTools → Red): el mensaje dice si falló CSRF,
                    validación o permisos.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. ¿Es un 500 o pantalla en blanco?</div>
                <p>Activa <strong>Depuración</strong> en el Resumen y reproduces: el error aparece con
                    archivo y línea. Revisa también el log del contenedor.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">4. ¿Los datos no son los esperados?</div>
                <p><strong>Base de datos → consola</strong>: ejecuta la consulta que el controlador debería
                    estar haciendo y compara. Si la consulta falla ahí, el problema es de datos o SQL, no
                    de PHP.</p>
            </div>
        </div>

        <div class="doc-h4">Flujo C — Operar en producción con seguridad</div>
        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Mantenimiento para cambios sensibles</div>
                <p>Activa el modo mantenimiento desde el Resumen: el público ve 503 y tú sigues
                    trabajando en el panel.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Verifica entorno y depuración</div>
                <p>Entorno <code>production</code> y <strong>Depuración</strong> apagada antes de cerrar.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Documentación como contrato</div>
                <p>Si cambias rutas o comportamiento, actualiza el manual (Capítulo 16) y descarga el PDF
                    recién generado para dejar constancia.</p>
            </div>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Nada de FTP ni SSH</span>
            <p>Estos tres flujos se completan íntegramente dentro del dashboard: crear tablas, generar
                código, registrar rutas, verificar datos y operar en producción. Eso es la autogestión que
                defiende el Capítulo 14.</p>
        </div>
    </section>

    <section class="doc-section" id="cap13-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap13-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Entras por <code>/join/access/dashboard</code> y el menú lateral resalta la sección activa.</li>
            <li>Cambias tu nombre desde «Mi usuario» y al recargar aparece en el menú.</li>
            <li>En el Resumen activas y desactivas la depuración, y el cambio surte efecto al refrescar.</li>
            <li>Creas un usuario, lo desactivas y compruebas que no puede iniciar sesión.</li>
            <li>En Generadores generas un middleware de prueba, ves la vista previa y lo borras o lo dejas
                como ejercicio.</li>
            <li>En Base de datos ejecutas un <code>SELECT</code> y un <code>CREATE</code>/<code>DROP</code>
                temporal para ver ambos tipos de respuesta.</li>
            <li>Recorres el Flujo A completo con una tabla de práctica.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap14"><i class="fas fa-arrow-right mr-1"></i>Siguiente: librerías, límites y autogestión</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap13') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
