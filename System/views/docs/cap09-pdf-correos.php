<section class="doc-chapter" id="cap9" data-doc-chapter data-number="9" data-title="API interna, PDF y correos">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 9</span>
        <h2 class="doc-chapter-title">API interna, PDF y correos</h2>
        <p class="doc-chapter-lead">
            Un controlador puede responder tres cosas: HTML, JSON o un archivo binario (PDF). Aquí verás
            cómo montar endpoints JSON para tu propia interfaz, cómo generar PDFs con las dos librerías
            embebidas y cómo enviar correo por SMTP con la clase <code>Mail</code>.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap9-api">API interna</a>
            <a class="doc-chip" href="#cap9-dompdf">PDF con dompdf</a>
            <a class="doc-chip" href="#cap9-mail">Correos SMTP</a>
        </div>
    </header>

    <section class="doc-section" id="cap9-api" data-doc-section data-title="API interna: endpoints JSON">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap9-api">#</a>API interna: endpoints JSON</h3>
        <p>
            Ya construiste endpoints JSON en el <a href="#cap3">Capítulo 3</a> sin llamarlos así: cada
            acción AJAX de tus CRUDs es un endpoint de la API interna. Las reglas para uno nuevo:
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Regla</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Método y middleware</td>
                        <td><code>POST</code> para escribir, <code>GET</code> para leer; protégelo con
                            <code>Auth</code> y, si solo lo usa tu JS, con <code>'SoloAjax'</code>.</td>
                    </tr>
                    <tr>
                        <td>Validación</td>
                        <td>Un <code>FormRequest</code>: el router responde el 422 automático (ver <a href="#cap5">Capítulo 5</a>).</td>
                    </tr>
                    <tr>
                        <td>Contrato de salida</td>
                        <td><code>{"success": true, "message": "…"}</code> y datos extra en claves propias
                            (<code>data</code>, <code>rows</code>, <code>total</code>…).</td>
                    </tr>
                    <tr>
                        <td>Estados</td>
                        <td>200 correcto · 422 validación · 401/403 autorización · 404 no encontrado ·
                            500 error inesperado.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Un endpoint de datos para tu interfaz (buscador en vivo)</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// routes/app.php — dentro de Route::controller(ProductosController::class, …) y del grupo Auth
Route::get('/productos/buscar', 'buscar')-&gt;middleware('SoloAjax')-&gt;name('productos_buscar');

// App/Controllers/ProductosController.php
public function buscar(Request $request): Response
{
    $q = trim((string) $request-&gt;input('q', ''));

    $filas = Producto::query()
        -&gt;where('nombre', '%' . $q . '%', 'LIKE')
        -&gt;orderBy('nombre')
        -&gt;limit(10)
        -&gt;get();

    return $this-&gt;json([
        'success' =&gt; true,
        'rows'    =&gt; $filas,
        'total'   =&gt; count($filas),
    ]);
}

// JavaScript de la vista (nota el header que exige SoloAjax)
$.get(urlBuscar, { q: texto }, function(res) {
    res.rows.forEach(function(fila) { /* pintar resultado */ });
}, 'json');</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Probar tus endpoints con curl</span>
            <p><code>curl -H "X-Requested-With: XMLHttpRequest" -H "Accept: application/json"
                "https://tu-dominio/join/access/dashboard/Area-of-work/productos/buscar?q=cam"</code>.
                Con <code>SoloAjax</code> la cabecera <code>X-Requested-With</code> es obligatoria (o
                <code>Accept: application/json</code>); para rutas solo de sesión, pasa además las cookies.</p>
        </div>
    </section>

    <section class="doc-section" id="cap9-dompdf" data-doc-section data-title="Generar PDF con dompdf (HTML → PDF)">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap9-dompdf">#</a>Generar PDF con dompdf (HTML → PDF)</h3>
        <p>
            dompdf está embebido en <code>resources/dompdf/</code> y convierte HTML/CSS en PDF. El flujo
            es: renderizas una <strong>vista especial de PDF</strong> (sin menús ni scripts), la pasas a
            dompdf y devuelves el binario como <code>Response</code>.
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 240" width="940" height="240" role="img"
                aria-label="Flujo de generacion de PDF: vista HTML, dompdf, respuesta binaria">
                <rect x="30" y="60" width="200" height="80" rx="10" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="130" y="92" font-size="12.5" font-weight="700" text-anchor="middle" fill="#0b4ea8">Vista PDF</text>
                <text x="130" y="112" font-size="11" text-anchor="middle" fill="#1e3a8a">App/views/pdf/reporte.php</text>
                <text x="130" y="128" font-size="11" text-anchor="middle" fill="#1e3a8a">View::render(...)</text>

                <line x1="230" y1="100" x2="312" y2="100" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="320,100 308,94 308,106" fill="#94a3b8"></polygon>
                <text x="275" y="88" font-size="10.5" text-anchor="middle" fill="#64748b">HTML string</text>

                <rect x="320" y="60" width="230" height="80" rx="10" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="435" y="92" font-size="12.5" font-weight="700" text-anchor="middle" fill="#166534">dompdf</text>
                <text x="435" y="112" font-size="11" text-anchor="middle" fill="#14532d">loadHtml → render → output</text>
                <text x="435" y="128" font-size="11" text-anchor="middle" fill="#14532d">A4, fuentes DejaVu</text>

                <line x1="550" y1="100" x2="632" y2="100" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="640,100 628,94 628,106" fill="#94a3b8"></polygon>
                <text x="595" y="88" font-size="10.5" text-anchor="middle" fill="#64748b">binario %PDF</text>

                <rect x="640" y="60" width="270" height="80" rx="10" fill="#0f172a"></rect>
                <text x="775" y="92" font-size="12.5" font-weight="700" text-anchor="middle" fill="#e2e8f0">Response 200</text>
                <text x="775" y="112" font-size="11" text-anchor="middle" fill="#94a3b8">Content-Type: application/pdf</text>
                <text x="775" y="128" font-size="11" text-anchor="middle" fill="#94a3b8">inline (ver) o attachment (descargar)</text>

                <rect x="30" y="168" width="880" height="52" rx="9" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="470" y="190" font-size="11.5" text-anchor="middle" fill="#92400e">La vista PDF es un documento completo: &lt;html&gt;&lt;head&gt;&lt;style&gt;…; sin layout del panel.</text>
                <text x="470" y="208" font-size="11.5" text-anchor="middle" fill="#78350f">En dompdf el CSS moderno (grid, flex) no existe: usa bloques, tablas y page-break-before para separar secciones.</text>
            </svg>
            <span class="doc-caption">Figura 9.1 — De una vista HTML a un PDF binario, sin salir del framework.</span>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Controlador — PDF con numeración de páginas (patrón de la documentación)</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>public function reporte(Request $request): Response
{
    $html = View::render('pdf.reporte', [
        'ventas' =&gt; Venta::query()-&gt;orderBy('fecha', 'DESC')-&gt;get(),
        'hoy'    =&gt; date('d/m/Y'),
    ]);

    require_once BASE_PATH . '/resources/dompdf/autoload.inc.php';

    $options = new \Dompdf\Options();
    $options-&gt;set('defaultFont', 'DejaVu Sans');   // acentos y simbolos garantizados
    $options-&gt;set('isRemoteEnabled', true);        // imagenes por URL
    $options-&gt;set('chroot', BASE_PATH);            // limita accesos al proyecto
    $options-&gt;set('dpi', 120);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf-&gt;loadHtml($html, 'UTF-8');
    $dompdf-&gt;setPaper('A4', 'portrait');
    $dompdf-&gt;render();

    // Pie de pagina en cada hoja ({PAGE_NUM} y {PAGE_COUNT} los sustituye dompdf)
    $canvas = $dompdf-&gt;getCanvas();
    $canvas-&gt;page_text(40, 812, 'Reporte de ventas', null, 7, [0.45, 0.45, 0.45]);
    $canvas-&gt;page_text(430, 812, 'Pagina {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.45, 0.45, 0.45]);

    return Response::make($dompdf-&gt;output(), 200, [
        'Content-Type'        =&gt; 'application/pdf',
        'Content-Disposition' =&gt; 'inline; filename="reporte-ventas.pdf"',
        'Cache-Control'       =&gt; 'no-store',
    ]);
}</code></pre>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Necesidad</th>
                        <th>Solución</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Descargar en vez de ver</td>
                        <td><code>Content-Disposition: attachment; filename="…"</code>.</td>
                    </tr>
                    <tr>
                        <td>Separar capítulos/hojas</td>
                        <td><code>page-break-before: always</code> en el CSS de la vista PDF.</td>
                    </tr>
                    <tr>
                        <td>Acentos y símbolos</td>
                        <td>Fuente <code>DejaVu Sans</code> como <code>defaultFont</code>.</td>
                    </tr>
                    <tr>
                        <td>Diagramas SVG</td>
                        <td>dompdf no dibuja <code>&lt;svg&gt;</code> embebido: conviértelo a
                            <code>&lt;img src="data:image/svg+xml;base64,…"&gt;</code> o a PNG.</td>
                    </tr>
                    <tr>
                        <td>Enlaces internos (índice)</td>
                        <td>Los <code>id</code> del HTML se vuelven anclas del PDF: <code>href="#cap3"</code> funciona.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>¿Y FPDF?</span>
            <p>En <code>resources/FPDF/</code> también está embebida FPDF, la librería clásica de celdas
                (<code>Cell()</code>, <code>MultiCell()</code>). Es útil si necesitas dibujar celda a celda
                con control absoluto de coordenadas, pero para informes desde HTML, dompdf te ahorrará
                mucho trabajo. El PDF de esta misma documentación se genera con dompdf.</p>
        </div>
    </section>

    <section class="doc-section" id="cap9-mail" data-doc-section data-title="Enviar correos por SMTP con Mail">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap9-mail">#</a>Enviar correos por SMTP con <code>Mail</code></h3>
        <p>
            La clase <code>Lib\Support\Mail</code> es fluida: encadenas destinatarios, asunto y envías.
            El servidor SMTP se configura en el <code>.env</code> y se lee desde
            <code>config/mail.php</code> — no toques código para cambiar de servidor de correo.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Variables .env</th>
                        <th>Qué es</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>MAIL_HOST</code> · <code>MAIL_PORT</code></td><td>Servidor y puerto SMTP (587 para TLS, 465 para SSL).</td></tr>
                    <tr><td><code>MAIL_USERNAME</code> · <code>MAIL_PASSWORD</code></td><td>Credenciales del buzón.</td></tr>
                    <tr><td><code>MAIL_ENCRYPTION</code></td><td>Seguridad de la conexión (<code>tls</code>, <code>ssl</code>).</td></tr>
                    <tr><td><code>MAIL_FROM_ADDRESS</code> · <code>MAIL_FROM_NAME</code></td><td>Remitente por defecto si no usas <code>-&gt;from()</code>.</td></tr>
                    <tr><td><code>MAIL_VERIFY_PEER</code></td><td><code>false</code> solo si el servidor usa un certificado autofirmado.</td></tr>
                    <tr><td><code>MAIL_TIMEOUT</code></td><td>Segundos de espera para la conexión.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Mail — los cuatro usos típicos</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use Lib\Support\Mail;

// 1. Basico: toma el remitente de config/mail.php
Mail::to('cliente@ejemplo.com')
    -&gt;subject('Tu pedido fue recibido')
    -&gt;send('&lt;h1&gt;Gracias por tu compra&lt;/h1&gt;&lt;p&gt;Pedido #1042&lt;/p&gt;');

// 2. Con copia, respuesta y remitente propio
Mail::to(['cliente@ejemplo.com', 'jefe@ejemplo.com'])
    -&gt;cc('soporte@ejemplo.com')
    -&gt;bcc('auditoria@ejemplo.com')
    -&gt;replyTo('ventas@ejemplo.com', 'Ventas')
    -&gt;from('tienda@ejemplo.com', 'Mi Tienda')
    -&gt;subject('Factura mensual')
    -&gt;send($html, 'Version de texto plano por si el lector no muestra HTML');

// 3. Desde una vista del propio sistema
$html = View::render('pdf.factura', ['pedido' =&gt; $pedido]); // o una vista de correo
Mail::to($cliente['email'])-&gt;subject('Factura #' . $pedido['id'])-&gt;send($html);

// 4. Manejo de errores
try {
    Mail::to($email)-&gt;subject('Aviso')-&gt;send($html);
    return $this-&gt;json(['success' =&gt; true, 'message' =&gt; 'Correo enviado.']);
} catch (\Throwable $e) {
    error_log('Mail failed: ' . $e-&gt;getMessage());
    return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'No se pudo enviar el correo.'], 500);
}</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>El envío es síncrono</span>
            <p><code>send()</code> abre la conexión SMTP y espera: en un hosting lento puede tardar varios
                segundos. No envíes correos dentro del bucle de un formulario sin <code>MAIL_TIMEOUT</code>
                razonable, y nunca dejes que un fallo de correo tumbe un guardado que ya está hecho.</p>
        </div>
    </section>

    <section class="doc-section" id="cap9-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap9-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Llama a tu endpoint con curl: verás el JSON con <code>success</code> y las filas.</li>
            <li>Quita la cabecera <code>X-Requested-With</code>: <code>SoloAjax</code> responde 403 JSON.</li>
            <li>Abre tu ruta de PDF en el navegador: se ve el documento con el pie «Página X de Y».</li>
            <li>Cambia el <code>Content-Disposition</code> a <code>attachment</code>: el PDF se descarga.</li>
            <li>Envía un correo de prueba a tu propia cuenta y revisa la carpeta de spam: confirma
                <code>MAIL_FROM_ADDRESS</code> y credenciales.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap10"><i class="fas fa-arrow-right mr-1"></i>Siguiente: panel del sistema</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap9') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
