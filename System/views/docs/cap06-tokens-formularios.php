<section class="doc-chapter" id="cap6" data-doc-chapter data-number="6" data-title="Tokens, formularios y respuestas">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 6</span>
        <h2 class="doc-chapter-title">Tokens, formularios y respuestas</h2>
        <p class="doc-chapter-lead">
            Todo formulario del panel sigue el mismo ritual: token CSRF oculto, envío AJAX con jQuery,
            y una respuesta JSON que se convierte en notificación verde o modal rojo. Aprende el ritual
            una vez y todos tus módulos se comportarán igual que los del sistema.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap6-csrf">El token CSRF</a>
            <a class="doc-chip" href="#cap6-formulario">Formulario AJAX completo</a>
            <a class="doc-chip" href="#cap6-respuestas">Notificaciones y modales</a>
        </div>
    </header>

    <section class="doc-section" id="cap6-csrf" data-doc-section data-title="El token CSRF: qué es y cómo se usa">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap6-csrf">#</a>El token CSRF: qué es y cómo se usa</h3>
        <p>
            El token CSRF es una cadena secreta que el servidor guarda en la sesión y entrega al formulario.
            Al enviar, el servidor comprueba que ambas coinciden: así, una web externa no puede enviar
            peticiones en tu nombre (no conoce el token).
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Método / Helper</th>
                        <th>Qué hace</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>Csrf::token()</code> · <code>csrf_token()</code></td>
                        <td>Devuelve el token actual; lo genera la primera vez (32 bytes aleatorios en hex).</td>
                    </tr>
                    <tr>
                        <td><code>Csrf::field()</code> · <code>csrf_field()</code></td>
                        <td>Devuelve el HTML <code>&lt;input type="hidden" name="_token" value="…"&gt;</code> listo para pegar.</td>
                    </tr>
                    <tr>
                        <td><code>Csrf::validate($token)</code></td>
                        <td>Compara con <code>hash_equals()</code> contra el de la sesión. <code>true</code>/<code>false</code>.</td>
                    </tr>
                    <tr>
                        <td><code>Csrf::regenerate()</code></td>
                        <td>Genera un token nuevo. El sistema lo llama al iniciar sesión (rotación tras login).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Las dos formas de poner el token en un formulario</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;!-- 1. Manual: la que usan las vistas del panel --&gt;
&lt;input type="hidden" name="_token" value="&lt;?= csrf_token() ?&gt;"&gt;

&lt;!-- 2. Helper: imprime el input completo --&gt;
&lt;?= csrf_field() ?&gt;</code></pre>
        </div>

        <p>
            En el servidor no hay que acordarse de nada: tus <code>StoreXxxRequest</code> ya comprueban el
            token dentro de <code>authorize()</code> con el patrón del
            <a href="#cap5">Capítulo 5</a>. Si el token falta o no coincide, la respuesta es
            <code>403 Token CSRF invalido</code>.
        </p>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Reglas de oro del token</span>
            <p>1) Todo <code>POST</code> lo lleva, sin excepción. 2) Se imprime en el formulario con
                <code>csrf_token()</code>, nunca se escribe a mano. 3) Si usas caché de página o la pestaña
                lleva horas abierta y recibes 403, recarga la página: el token rotó al iniciar sesión.</p>
        </div>
    </section>

    <section class="doc-section" id="cap6-formulario" data-doc-section data-title="Anatomía de un formulario AJAX del panel">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap6-formulario">#</a>Anatomía de un formulario AJAX del panel</h3>
        <p>
            Este es el patrón exacto que usan las vistas del sistema (por ejemplo
            <code>System/views/dashboard/usuarios/index.php</code>): un modal, un formulario serializado y
            una respuesta manejada con callbacks. La página nunca se recarga a medias:
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 300" width="940" height="300" role="img"
                aria-label="Viaje de ida y vuelta de un formulario AJAX con token CSRF">
                <rect x="20" y="40" width="270" height="150" rx="10" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="155" y="66" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">Formulario en el modal</text>
                <text x="155" y="90" font-size="11.5" text-anchor="middle" fill="#1e3a8a">&lt;input name="_token"&gt;</text>
                <text x="155" y="108" font-size="11.5" text-anchor="middle" fill="#1e3a8a">&lt;input name="nombre"&gt;</text>
                <text x="155" y="126" font-size="11.5" text-anchor="middle" fill="#1e3a8a">$(this).serialize()</text>
                <text x="155" y="152" font-size="11" text-anchor="middle" fill="#64748b">incluye el token automaticamente</text>
                <text x="155" y="170" font-size="11" text-anchor="middle" fill="#64748b">$.post(url, data, callback, 'json')</text>

                <line x1="290" y1="100" x2="392" y2="100" stroke="#0d6efd" stroke-width="2"></line>
                <polygon points="400,100 388,94 388,106" fill="#0d6efd"></polygon>
                <text x="345" y="88" font-size="11" text-anchor="middle" fill="#0b4ea8">POST</text>

                <rect x="400" y="40" width="200" height="150" rx="10" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="500" y="66" font-size="13" font-weight="700" text-anchor="middle" fill="#166534">FormRequest</text>
                <text x="500" y="90" font-size="11.5" text-anchor="middle" fill="#14532d">authorize(): ¿POST? ¿token?</text>
                <text x="500" y="108" font-size="11.5" text-anchor="middle" fill="#14532d">rules(): campos validos</text>
                <text x="500" y="134" font-size="11" text-anchor="middle" fill="#64748b">datos limpios →</text>
                <text x="500" y="152" font-size="11" text-anchor="middle" fill="#64748b">$request-&gt;validated()</text>

                <line x1="600" y1="100" x2="702" y2="100" stroke="#22c55e" stroke-width="2"></line>
                <polygon points="710,100 698,94 698,106" fill="#22c55e"></polygon>

                <rect x="710" y="40" width="210" height="150" rx="10" fill="#0f172a"></rect>
                <text x="815" y="66" font-size="13" font-weight="700" text-anchor="middle" fill="#e2e8f0">200 JSON</text>
                <text x="815" y="90" font-size="11.5" text-anchor="middle" fill="#94a3b8">{"success": true,</text>
                <text x="815" y="108" font-size="11.5" text-anchor="middle" fill="#94a3b8">"message": "Usuario guardado."}</text>
                <text x="815" y="140" font-size="11" text-anchor="middle" fill="#64748b">cierra modal + notificacion verde</text>
                <text x="815" y="158" font-size="11" text-anchor="middle" fill="#64748b">y recarga la tabla</text>

                <line x1="815" y1="190" x2="155" y2="230" stroke="#dc2626" stroke-width="2" stroke-dasharray="7 5"></line>
                <polygon points="147,233 159,225 162,237" fill="#dc2626"></polygon>
                <rect x="240" y="216" width="410" height="46" rx="9" fill="#fee2e2" stroke="#dc2626"></rect>
                <text x="445" y="236" font-size="11.5" text-anchor="middle" fill="#991b1b">422 con "errors" → modal rojo campo por campo · 403 → notificacion roja con el message</text>
                <text x="445" y="253" font-size="11" text-anchor="middle" fill="#7f1d1d">el modal sigue abierto con lo que el usuario escribio</text>

                <text x="470" y="24" font-size="12" text-anchor="middle" fill="#64748b">El viaje completo de un guardado AJAX en el panel</text>
            </svg>
            <span class="doc-caption">Figura 6.1 — El viaje del formulario: token → validación → JSON → feedback visual.</span>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Vista de un módulo — formulario + JavaScript (patrón real del panel)</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;form id="form-producto" autocomplete="off"&gt;
    &lt;input type="hidden" name="_token" value="&lt;?= csrf_token() ?&gt;"&gt;
    &lt;input type="hidden" name="id" id="producto-id" value=""&gt;

    &lt;div class="form-group"&gt;
        &lt;label for="producto-nombre"&gt;Nombre&lt;/label&gt;
        &lt;input type="text" class="form-control" id="producto-nombre" name="nombre" maxlength="120" required&gt;
    &lt;/div&gt;
    ...
&lt;/form&gt;

&lt;script&gt;
document.addEventListener('DOMContentLoaded', function() {
    var urlGuardar    = "&lt;?= route('productos_guardar') ?&gt;";
    var urlActualizar = "&lt;?= route('productos_actualizar') ?&gt;";
    var editarId = null;

    function manejarError(xhr) {
        var res = xhr.responseJSON || {};
        // Si el servidor mando errores por campo (422): modal completo.
        if (res.errors &amp;&amp; typeof showErrorModal === 'function') {
            showErrorModal(res.message, res.errors);
            return;
        }
        showNotification('error', res.message || 'Error al procesar la solicitud.');
    }

    $('#form-producto').on('submit', function(e) {
        e.preventDefault();

        var data = $(this).serialize();                        // incluye _token
        var url  = editarId === null
            ? urlGuardar
            : urlActualizar.replace('{id}', editarId);

        $.post(url, data, function(res) {
            if (res &amp;&amp; res.success) {
                $('#modal-producto').modal('hide');
                showNotification('success', res.message || 'Guardado correctamente.');
                setTimeout(function() { window.location.reload(); }, 800);
            }
        }, 'json').fail(manejarError);
    });
});
&lt;/script&gt;</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>¿Por qué serialize() es suficiente?</span>
            <p><code>serialize()</code> convierte todos los campos del formulario en
                <code>nombre=valor&amp;…</code>, incluido el <code>_token</code> oculto. No hay que
                construir el objeto a mano ni preocuparse por el CSRF en JavaScript.</p>
        </div>
    </section>

    <section class="doc-section" id="cap6-respuestas" data-doc-section data-title="Notificaciones, modales y mensajes flash">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap6-respuestas">#</a>Notificaciones, modales y mensajes flash</h3>
        <p>
            El panel incluye <code>public/code/js/notification.js</code> con dos funciones globales.
            La firma exacta es <code>showNotification(icon, message)</code>:
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Llamada</th>
                        <th>Resultado visual</th>
                        <th>Cuándo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>showNotification('success', 'Producto creado.')</code></td>
                        <td>Alerta verde arriba a la derecha (se oculta sola a los 5 s).</td>
                        <td>Guardado u operación correcta.</td>
                    </tr>
                    <tr>
                        <td><code>showNotification('warning', 'Revisa los datos.')</code></td>
                        <td>Alerta amarilla.</td>
                        <td>Avisos no bloqueantes.</td>
                    </tr>
                    <tr>
                        <td><code>showNotification('error', mensaje)</code></td>
                        <td>Alerta roja.</td>
                        <td>Errores de autorización, red o servidor.</td>
                    </tr>
                    <tr>
                        <td><code>showErrorModal(mensaje, errors)</code></td>
                        <td>Modal rojo con la lista de campos inválidos.</td>
                        <td>Cuando la respuesta 422 trae <code>errors</code>.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p>
            Además, el layout <code>dashboard.php</code> muestra automáticamente los mensajes guardados en
            sesión (útiles cuando haces un guardado clásico con recarga de página):
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Controlador con flujo clásico (redirect) — mensajes flash</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use Lib\Support\Session;

// Tras guardar, redirige y deja el aviso en sesion:
Session::flash('success', 'Producto guardado correctamente.');
return $this-&gt;redirect('productos');

// En la vista, para repoblar un formulario tras un error de validacion:
&lt;input type="text" name="nombre" value="&lt;?= e(old('nombre')) ?&gt;"&gt;

&lt;?php if (hasError('nombre')): ?&gt;
    &lt;div class="text-danger small"&gt;&lt;?= e(errors('nombre')) ?&gt;&lt;/div&gt;
&lt;?php endif; ?&gt;</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Las claves de flash que el layout ya conoce</span>
            <p><code>$_SESSION[APP]['mensaje']</code> → notificación roja ·
                <code>$_SESSION[APP]['success']</code> → notificación verde. Los errores de validación
                <strong>no</strong> se muestran al cargar otra página: el 422 JSON dispara el modal rojo
                desde <code>notification.js</code>. En peticiones no-AJAX, <code>ValidationException</code>
                además guarda <code>_errors</code>/<code>_old_*</code> (sin contraseñas ni tokens) para
                repoblar formularios con <code>errors()</code> y <code>old()</code>.</p>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>No mezcles dos veces el mismo aviso</span>
            <p>Si ya informas del 422 con <code>showErrorModal()</code> en el <code>.fail()</code>, no repitas
                el mensaje con <code>alert()</code> ni con otra notificación: el usuario vería el error dos veces.</p>
        </div>
    </section>

    <section class="doc-section" id="cap6-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap6-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Inspecciona el formulario: el input <code>_token</code> existe y su valor cambia tras iniciar sesión.</li>
            <li>Borra el token con las herramientas del navegador y envía: <code>403 Token CSRF invalido</code>.</li>
            <li>Envía datos inválidos: modal rojo con la lista por campo y el formulario conserva lo escrito.</li>
            <li>Envía datos válidos: notificación verde, modal cerrado y tabla actualizada.</li>
            <li>Prueba el flujo clásico (guardar + recargar): el aviso verde debe aparecer una sola vez.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap7"><i class="fas fa-arrow-right mr-1"></i>Siguiente: vistas, CSS, JS y librerías</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap6') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
