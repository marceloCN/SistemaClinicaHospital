<section class="doc-chapter" id="cap5" data-doc-chapter data-number="5" data-title="Controladores y FormRequest">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 5</span>
        <h2 class="doc-chapter-title">Controladores y FormRequest</h2>
        <p class="doc-chapter-lead">
            El controlador es donde vive la lógica de cada ruta; el FormRequest es su portero de datos:
            autoriza, valida y entrega solo lo limpio. En este capítulo verás la clase base
            <code>Controller</code>, el flujo completo del FormRequest y las reglas de validación disponibles.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap5-controlador">El controlador</a>
            <a class="doc-chip" href="#cap5-formrequest">FormRequest paso a paso</a>
            <a class="doc-chip" href="#cap5-reglas">Reglas disponibles</a>
        </div>
    </header>

    <section class="doc-section" id="cap5-controlador" data-doc-section data-title="El controlador: dónde vive la lógica">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap5-controlador">#</a>El controlador: dónde vive la lógica</h3>
        <p>
            Un controlador es una clase en <code>App/Controllers/</code> que extiende <code>Lib\Core\Controller</code>.
            Cada método público es una acción: a él apunta una ruta del <a href="#cap3">Capítulo 3</a>.
            El router además inyecta la petición real en la propiedad <code>$this-&gt;request</code>.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Controllers/ProductosController.php — esqueleto completo</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Producto;
use App\Requests\StoreProductoRequest;
use App\Requests\UpdateProductoRequest;
use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;

class ProductosController extends Controller
{
    public function index(Request $request): Response
    {
        $productos = Producto::query()-&gt;orderBy('id', 'DESC')-&gt;get();

        return $this-&gt;view('dashboard.productos.index', [
            'productos' =&gt; $productos,
            'titulo'    =&gt; 'Productos',
        ]);
    }

    public function guardar(StoreProductoRequest $request): Response
    {
        // $request ya paso authorize() + rules(); aqui solo datos validados:
        $datos = $request-&gt;validated();

        Producto::query()-&gt;insert($datos);

        return $this-&gt;json(['success' =&gt; true, 'message' =&gt; 'Producto creado.']);
    }
}</code></pre>
        </div>

        <p>Todo lo que ofrece la clase base, sin escribir una sola línea:</p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Helper</th>
                        <th>Qué devuelve</th>
                        <th>Cuándo usarlo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>$this-&gt;view('ruta.vista', $data)</code></td>
                        <td>Respuesta HTML con la vista renderizada.</td>
                        <td>Páginas del panel.</td>
                    </tr>
                    <tr>
                        <td><code>$this-&gt;json($data, $status = 200)</code></td>
                        <td>Respuesta JSON.</td>
                        <td>Endpoints AJAX de tu CRUD.</td>
                    </tr>
                    <tr>
                        <td><code>$this-&gt;redirect('nombre_ruta', $params)</code></td>
                        <td>Redirección 302 a una ruta por nombre.</td>
                        <td>Tras un guardado con formulario clásico.</td>
                    </tr>
                    <tr>
                        <td><code>$this-&gt;back()</code></td>
                        <td>Redirección 302 a la página anterior (<code>Referer</code>).</td>
                        <td>Volver al listado tras una acción.</td>
                    </tr>
                    <tr>
                        <td><code>$this-&gt;validate(['campo' =&gt; 'required'])</code></td>
                        <td>Array validado o <code>ValidationException</code> (422 automático).</td>
                        <td>Validación rápida sin crear un FormRequest.</td>
                    </tr>
                    <tr>
                        <td><code>$this-&gt;isAuthenticated()</code></td>
                        <td><code>true</code> si hay sesión iniciada.</td>
                        <td>Comprobaciones puntuales dentro del método.</td>
                    </tr>
                    <tr>
                        <td><code>$this-&gt;request</code></td>
                        <td>La petición real (método, inputs, archivos, headers).</td>
                        <td>Cuando necesitas algo que no llegó como parámetro.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>También puedes devolver valores sueltos</span>
            <p>Si un método devuelve un <code>array</code> u objeto, el router lo convierte a JSON;
                si devuelve texto, lo envía como HTML. Aun así, sé explícito con <code>Response</code>:
                se lee mejor y evita sorpresas.</p>
        </div>
    </section>

    <section class="doc-section" id="cap5-formrequest" data-doc-section data-title="FormRequest: autoriza, valida y limpia">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap5-formrequest">#</a>FormRequest: autoriza, valida y limpia</h3>
        <p>
            Un <strong>FormRequest</strong> es una clase que extiende <code>Lib\Core\FormRequest</code>
            (que a su vez extiende <code>Request</code>). Si tu método la pide como parámetro, el router
            <strong>la ejecuta antes</strong> de entrar al método. Este es el flujo exacto:
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 360" width="940" height="360" role="img"
                aria-label="Flujo de un FormRequest desde el type-hint hasta el metodo del controlador">
                <rect x="20" y="30" width="250" height="56" rx="9" fill="#0f172a"></rect>
                <text x="145" y="54" font-size="12" text-anchor="middle" fill="#e2e8f0">Peticion POST del formulario</text>
                <text x="145" y="73" font-size="11" text-anchor="middle" fill="#94a3b8">(jQuery + token CSRF)</text>

                <line x1="270" y1="58" x2="312" y2="58" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="320,58 308,52 308,64" fill="#94a3b8"></polygon>

                <rect x="320" y="30" width="270" height="56" rx="9" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="455" y="54" font-size="12" text-anchor="middle" fill="#0b4ea8">Router ve el type-hint:</text>
                <text x="455" y="73" font-size="11.5" text-anchor="middle" fill="#1e3a8a">StoreProductoRequest $request</text>

                <line x1="590" y1="58" x2="632" y2="58" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="640,58 628,52 628,64" fill="#94a3b8"></polygon>

                <rect x="640" y="30" width="280" height="56" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="780" y="54" font-size="12" text-anchor="middle" fill="#166534">createFrom($request)</text>
                <text x="780" y="73" font-size="11" text-anchor="middle" fill="#14532d">copia query, body, cookies, server, files y json</text>

                <line x1="780" y1="86" x2="780" y2="114" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="780,122 774,110 786,110" fill="#94a3b8"></polygon>

                <rect x="640" y="122" width="280" height="50" rx="9" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="780" y="143" font-size="12" text-anchor="middle" fill="#92400e">authorize()</text>
                <text x="780" y="161" font-size="11" text-anchor="middle" fill="#78350f">false → 403 · AuthorizationException → su status</text>

                <line x1="780" y1="172" x2="780" y2="200" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="780,208 774,196 786,196" fill="#94a3b8"></polygon>

                <rect x="640" y="208" width="280" height="50" rx="9" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="780" y="229" font-size="12" text-anchor="middle" fill="#92400e">rules() + messages()</text>
                <text x="780" y="247" font-size="11" text-anchor="middle" fill="#78350f">Validator → ¿falla? → ValidationException</text>

                <line x1="640" y1="233" x2="598" y2="233" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="590,233 602,227 602,239" fill="#94a3b8"></polygon>

                <rect x="320" y="208" width="270" height="50" rx="9" fill="#fee2e2" stroke="#dc2626"></rect>
                <text x="455" y="229" font-size="12" text-anchor="middle" fill="#991b1b">422 JSON automatico</text>
                <text x="455" y="247" font-size="11" text-anchor="middle" fill="#7f1d1d">message + errors → modal rojo</text>

                <line x1="780" y1="258" x2="780" y2="286" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="780,294 774,282 786,282" fill="#94a3b8"></polygon>

                <rect x="615" y="286" width="305" height="56" rx="9" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="767" y="310" font-size="12" text-anchor="middle" fill="#166534">Tu metodo: $request-&gt;validated()</text>
                <text x="767" y="329" font-size="11" text-anchor="middle" fill="#14532d">solo claves con regla, ya limpias</text>

                <text x="200" y="330" font-size="11.5" fill="#64748b">El formulario recibe el 422 y muestra los errores sin recargar.</text>
            </svg>
            <span class="doc-caption">Figura 5.1 — El router orquesta authorize() y rules() antes de tu método.</span>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Requests/StoreProductoRequest.php — patrón real del sistema</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this-&gt;isPost()) {
            throw new AuthorizationException('Metodo no permitido.', 405);
        }

        if (!Csrf::validate($this-&gt;input('_token'))) {
            throw new AuthorizationException('Token CSRF invalido.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'   =&gt; 'required|min:3|max:120',
            'precio'   =&gt; 'required|numeric',
            'stock'    =&gt; 'required|integer',
            'categoria'=&gt; 'required|in:ropa,hogar,tecnologia',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' =&gt; 'El nombre del producto es obligatorio.',
            'precio.numeric'  =&gt; 'El precio debe ser un numero.',
            'categoria.in'    =&gt; 'Categoria no valida.',
        ];
    }
}</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Los tres métodos que puedes sobrescribir</span>
            <p><code>authorize()</code> (¿puede hacerlo?), <code>rules()</code> (¿los datos cumplen?) y
                <code>messages()</code> (mensajes propios). Si no necesitas personalizar <code>authorize()</code>,
                puedes omitirlo: por defecto autoriza todo.</p>
        </div>
    </section>

    <section class="doc-section" id="cap5-reglas" data-doc-section data-title="Reglas de validación disponibles">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap5-reglas">#</a>Reglas de validación disponibles</h3>
        <p>
            Las reglas se combinan con <code>|</code> dentro de un string. Las que reciben valor usan
            <code>:</code> (por ejemplo <code>min:3</code>; en listas, <code>in:rojo,verde,azul</code>).
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Regla</th>
                        <th>Qué comprueba</th>
                        <th>Ejemplo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>required</code></td><td>El campo existe y no está vacío.</td><td><code>'nombre' =&gt; 'required'</code></td></tr>
                    <tr><td><code>email</code></td><td>Formato de correo válido.</td><td><code>'correo' =&gt; 'required|email'</code></td></tr>
                    <tr><td><code>min:n</code> / <code>max:n</code></td><td>Longitud mínima/máxima del texto (cuenta caracteres, no valor numérico).</td><td><code>'nombre' =&gt; 'min:3|max:120'</code></td></tr>
                    <tr><td><code>numeric</code></td><td>Es un número (entero o decimal).</td><td><code>'precio' =&gt; 'numeric'</code></td></tr>
                    <tr><td><code>integer</code></td><td>Es un número entero.</td><td><code>'stock' =&gt; 'integer'</code></td></tr>
                    <tr><td><code>in:a,b,c</code></td><td>El valor está dentro de la lista permitida.</td><td><code>'estado' =&gt; 'in:activo,pausado'</code></td></tr>
                    <tr><td><code>confirmed</code></td><td>Coincide con <code>&lt;campo&gt;_confirmation</code> (p. ej. <code>password_confirmation</code>).</td><td><code>'password' =&gt; 'required|min:6|confirmed'</code></td></tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Mensajes por defecto y personalizados</span>
            <p>Si no defines un mensaje en <code>messages()</code>, el validador genera uno en español.
                La clave del mensaje es <code>'campo.regla'</code>: <code>'precio.numeric'</code>,
                <code>'password.confirmed'</code>, etc.</p>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>validated() solo devuelve lo declarado</span>
            <p>Las claves del formulario que <strong>no tengan regla</strong> se descartan.
                Es una protección contra campos inyectados: si agregas un campo nuevo al formulario,
                decláralo también en <code>rules()</code> o no llegará a tu controlador.</p>
        </div>
    </section>

    <section class="doc-section" id="cap5-errores" data-doc-section data-title="El contrato JSON del panel">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap5-errores">#</a>El contrato JSON del panel</h3>
        <p>
            Todos los endpoints AJAX del panel hablan el mismo idioma. Respétalo y tu CRUD encajará
            con los modales y notificaciones existentes sin tocar JavaScript:
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Respuestas que espera el frontend del panel</span>
                <span class="doc-code-lang">json</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// Exito (cualquier accion) — status 200
{ "success": true, "message": "Producto creado." }

// Error de validacion — status 422 (lo genera el router solo)
{
    "success": false,
    "message": "Datos invalidos.",
    "errors": { "precio": ["El precio debe ser un numero."] }
}

// Error de autorizacion — status 403 (o el que definas: 405, 409...)
{ "success": false, "message": "Token CSRF invalido." }</code></pre>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Origen</th>
                        <th>Qué hace el frontend</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>200</code></td>
                        <td>Tu método responde <code>success: true</code>.</td>
                        <td>Notificación verde (<code>showNotification</code>) y recarga la tabla.</td>
                    </tr>
                    <tr>
                        <td><code>422</code></td>
                        <td>ValidationException lanzada por el router.</td>
                        <td>Modal rojo con la lista de <code>errors</code> por campo.</td>
                    </tr>
                    <tr>
                        <td><code>401 / 403 / 405 / 409</code></td>
                        <td>Middleware o <code>authorize()</code>.</td>
                        <td>Modal de error con el <code>message</code> recibido.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="doc-section" id="cap5-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap5-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Envía el formulario vacío: debe aparecer el modal rojo con un mensaje por campo válido
                (revisa la pestaña Red del navegador: 422 con <code>errors</code>).</li>
            <li>Envía el formulario sin <code>_token</code>: debe responder <code>Token CSRF invalido</code>
                (403).</li>
            <li>Haz <code>GET</code> a la ruta de guardado desde el navegador: <code>405 Método no permitido</code>.</li>
            <li>Envía un campo extra que no esté en <code>rules()</code>: no debe llegar a tu método
                (<code>validated()</code> lo descarta).</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap6"><i class="fas fa-arrow-right mr-1"></i>Siguiente: tokens, formularios y respuestas</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap5') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
