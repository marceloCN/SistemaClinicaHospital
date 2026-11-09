<section class="doc-chapter" id="cap15" data-doc-chapter data-number="15" data-title="Recetas: construir sistemas complejos">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 15</span>
        <h2 class="doc-chapter-title">Recetas: construir sistemas complejos</h2>
        <p class="doc-chapter-lead">
            Un sistema complejo no es un CRUD gigante: es la suma de piezas pequeñas bien conectadas —
            permisos, API, reportes, bitácora, exportaciones, agenda. Cada receta de este capítulo es
            autónoma: elige las que necesites, cópialas en <code>App/</code> y verifícalas con el Flujo A
            del Capítulo 13. Todas usan solo las librerías del Capítulo 14.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap15-r1">1 · Roles y permisos</a>
            <a class="doc-chip" href="#cap15-r2">2 · API JSON</a>
            <a class="doc-chip" href="#cap15-r3">3 · Reportes PDF</a>
            <a class="doc-chip" href="#cap15-r4">4 · Bitácora</a>
            <a class="doc-chip" href="#cap15-r5">5 · Exportación</a>
            <a class="doc-chip" href="#cap15-r6">6 · Transacciones</a>
            <a class="doc-chip" href="#cap15-componer">Componer</a>
        </div>
    </header>

    <section class="doc-section" id="cap15-r1" data-doc-section data-title="Receta 1 — Roles y permisos por middleware">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-r1">#</a>Receta 1 — Roles y permisos por middleware</h3>
        <p>
            El patrón de este CMS aplica permisos en la <strong>ruta</strong>, no en la vista: quien llega
            al controlador ya está autorizado. El proyecto trae el middleware <code>Permiso</code>
            (<code>App/Middleware/Permiso.php</code>) que compara permisos guardados en sesión contra la
            tabla <code>rol_permiso</code>, con el rol <code>ADMIN</code> siempre libre.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">routes/web.php — aplicación en rutas (estilo real del portal)</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// Un permiso
Route::get('/expedientes', [ExpedienteController::class, 'index'])
    -&gt;name('expediente_index')
    -&gt;middleware(['PortalAuth', 'Permiso:120']);

// Cualquiera de varios permisos (lógica OR)
Route::get('/reportes', [ReporteController::class, 'index'])
    -&gt;name('reporte_index')
    -&gt;middleware(['PortalAuth', 'Permiso:130,131']);</code></pre>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Middleware/Rol.php — middleware propio con generador</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Middleware;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\Session;

class Rol
{
    public function handle(Request $request, string ...$rolesPermitidos): bool|Response
    {
        $rol = (string) Session::get('portal_rol_clave', '');

        if ($rol === 'ADMIN' || in_array($rol, $rolesPermitidos, true)) {
            return true;
        }

        return $request-&gt;isAjax()
            ? Response::json(['success' =&gt; false, 'message' =&gt; 'Rol insuficiente.'], 403)
            : Response::redirect('/', 403);
    }
}</code></pre>
        </div>

        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Crear</div>
                <p>Generadores → tipo <em>middleware</em> → plantilla <em>rol</em> (o copia el anterior).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Registrar</div>
                <p>Añade <code>-&gt;middleware('Rol:CAJERO')</code> en las rutas que proteja.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Verificar</div>
                <p>Entrando con un usuario sin ese rol esperas 403; con él, 200. Comprueba en
                    <strong>Sistema → Middlewares</strong> que el nombre resuelve y en cuántas rutas se usa.</p>
            </div>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Siempre doble llave</span>
            <p>Combina autenticación + autorización: <code>['PortalAuth', 'Permiso:120']</code>. Un
                middleware de rol/permiso sin autenticación deja pasar a cualquiera con la sesión vacía en
                algunos flujos; la cadena completa evita ese hueco.</p>
        </div>
    </section>

    <section class="doc-section" id="cap15-r2" data-doc-section data-title="Receta 2 — API JSON para una interfaz rica">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-r2">#</a>Receta 2 — API JSON para una interfaz rica</h3>
        <p>
            Cuando la interfaz necesita datos sin recargar (búsqueda en vivo, autocompletado, dashboards),
            crea endpoints JSON con la misma ruta base que el módulo. Regla de contrato: siempre
            <code>success</code>, <code>message</code> y datos en raíz.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Controllers/EspecialidadApiController.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Controllers;

use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use App\Models\Especialidad;

class EspecialidadApiController extends Controller
{
    // GET /api/especialidades?q=cardio&amp;page=2
    public function search(Request $request): Response
    {
        $q    = trim((string) $request-&gt;input('q', ''));
        $page = (int) $request-&gt;input('page', 1);

        $query = Especialidad::query()-&gt;orderBy('nombre', 'asc');

        if ($q !== '') {
            // firma: where(columna, valor, operador)
            $query-&gt;where('nombre', '%' . $q . '%', 'ILIKE');
        }

        $result = $query-&gt;paginate(10, $page); // objeto con items, total, lastPage…

        return $this-&gt;json([
            'success' =&gt; true,
            'data'    =&gt; $result-&gt;items,
            'meta'    =&gt; [
                'page'     =&gt; $result-&gt;currentPage,
                'total'    =&gt; $result-&gt;total,
                'lastPage' =&gt; $result-&gt;lastPage,
            ],
        ]);
    }
}</code></pre>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">routes/web.php + consumo desde cualquier vista</span>
                <span class="doc-code-lang">javascript</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>Route::get('/api/especialidades', [EspecialidadApiController::class, 'search'])
    -&gt;name('api_especialidades')
    -&gt;middleware(['PortalAuth']);

// En tu vista (jQuery ya está cargado):
var timer;
$('#buscador').on('keyup', function() {
    clearTimeout(timer);
    var q = $(this).val();
    timer = setTimeout(function() {
        $.get('&lt;?= e(route('api_especialidades')) ?&gt;', { q: q }, function(res) {
            if (res.success) {
                pintarLista(res.data, res.meta);
            }
        }, 'json');
    }, 300); // debounce: evita una petición por tecla
});</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>JSON y CSRF</span>
            <p>Los <code>GET</code> de lectura no llevan token. Si tu endpoint escribe, exige
                <code>_token</code> en el <code>POST</code> y valida con <code>Csrf::validate()</code>
                (o reutiliza <code>validatePost()</code> del patrón del panel, Capítulo 10).</p>
        </div>
    </section>

    <section class="doc-section" id="cap15-r3" data-doc-section data-title="Receta 3 — Reportes PDF con dompdf">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-r3">#</a>Receta 3 — Reportes PDF con dompdf</h3>
        <p>
            El mismo motor que genera este manual (dompdf, en <code>resources/dompdf/</code>) sirve para
            tus reportes: una vista HTML normal, un controlador que la convierte y una ruta con nombre
            para enlazarla.
        </p>

        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Vista del reporte</div>
                <p><code>App/views/pdf/citas.php</code>: HTML semántico con tabla y estilos inline (dompdf
                    no entiende CSS externo ni <code>&lt;svg&gt;</code> complejo).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Controlador</div>
                <p>Renderiza, carga dompdf y devuelve el binario con <code>Content-Type: application/pdf</code>.</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Ruta + botón</div>
                <p>Ruta <code>GET</code> con nombre y un enlace con <code>target="_blank"</code>.</p>
            </div>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Controllers/ReporteCitasController.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>public function pdf(Request $request): Response
{
    $desde = (string) $request-&gt;input('desde', date('Y-m-01'));
    $hasta = (string) $request-&gt;input('hasta', date('Y-m-d'));

    $html = \Lib\Core\View::render('pdf.citas', [
        'citas'  =&gt; $this-&gt;buscarCitas($desde, $hasta),
        'desde'  =&gt; $desde,
        'hasta'  =&gt; $hasta,
    ]);

    require_once BASE_PATH . '/resources/dompdf/autoload.inc.php';

    $options = new \Dompdf\Options();
    $options-&gt;set('defaultFont', 'DejaVu Sans'); // acentos y ñ

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf-&gt;loadHtml($html, 'UTF-8');
    $dompdf-&gt;setPaper('A4', 'landscape');
    $dompdf-&gt;render();

    return Response::make($dompdf-&gt;output(), 200, [
        'Content-Type'        =&gt; 'application/pdf',
        'Content-Disposition' =&gt; 'inline; filename="citas.pdf"',
    ]);
}</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>¿dompdf o FPDF?</span>
            <p><strong>dompdf</strong>: HTML y CSS → rápido de maquetar (informes con logotipo, texto
                largo). <strong>FPDF</strong> (<code>resources/FPDF/</code>): celda a celda, para tablas
                densas con totales y control exacto del ancho de columnas. El Capítulo 9 muestra ambos.</p>
        </div>
    </section>

    <section class="doc-section" id="cap15-r4" data-doc-section data-title="Receta 4 — Bitácora de auditoría">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-r4">#</a>Receta 4 — Bitácora de auditoría</h3>
        <p>
            «Quién hizo qué y cuándo» es lo primero que pide un sistema de producción. Este CMS ya lo
            resuelve con las tablas y modelos <code>BitacoraAcceso</code> y <code>BitacoraAccion</code> y
            sus pantallas en el portal; para tus módulos basta con registrar en la misma línea.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Registro de una acción (modelo BitacoraAccion)</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use App\Models\BitacoraAccion;
use Lib\Support\Session;

// Columnas reales de la tabla bitacora_acciones
BitacoraAccion::query()-&gt;insert([
    'id_persona'    =&gt; (int) Session::get('id_usuario', 0),
    'tabla_afectada' =&gt; 'CITAS',
    'accion'        =&gt; 'CREAR',
    'registro_id'   =&gt; (string) $idCita,
    'datos_nuevos'  =&gt; json_encode(['paciente' =&gt; $nombre], JSON_UNESCAPED_UNICODE),
    'ip_address'    =&gt; $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent'    =&gt; $_SERVER['HTTP_USER_AGENT'] ?? null,
    'fecha_hora'    =&gt; now(),
    'resultado'     =&gt; 'EXITOSO',
]);</code></pre>
        </div>

        <div class="doc-h4">Variante sin escribir código en cada método</div>
        <p>
            Si la acción es repetitiva, registra desde el controlador con un método privado
            <code>auditar()</code> o desde un middleware propio que observe las rutas sensibles. La tabla
            nueva se crea desde la consola SQL (Capítulo 13, Flujo A).
        </p>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Consulta la bitácora como admin</span>
            <p>Con la consola SQL: <code>SELECT * FROM bitacora_acciones ORDER BY id DESC LIMIT 50;</code>.
                Para filtrar por persona: <code>… WHERE id_persona = 3 AND accion = 'ELIMINAR'</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap15-r5" data-doc-section data-title="Receta 5 — Exportación CSV/Excel">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-r5">#</a>Receta 5 — Exportación CSV/Excel</h3>
        <p>
            Exportar no necesita librería: un <code>Response</code> con el MIME correcto basta para que el
            navegador descargue un CSV que Excel abre con doble clic (usa <code>;</code> o
            <code>,</code> según tu locale y BOM UTF-8 para los acentos).
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Controlador con exportación</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>public function export(Request $request): Response
{
    $desde = (string) $request-&gt;input('desde', date('Y-m-01'));
    $hasta = (string) $request-&gt;input('hasta', date('Y-m-d'));

    // Cita trae la fecha de su ficha_agenda (join del propio Model)
    $filas = Cita::query()
        -&gt;join('ficha_agenda', 'ficha_agenda.id', '=', 'cita.id_ficha_agenda')
        -&gt;whereRaw('ficha_agenda.fecha BETWEEN ? AND ?', [$desde, $hasta])
        -&gt;orderBy('ficha_agenda.fecha', 'asc')
        -&gt;get();

    $out = fopen('php://temp', 'r+');
    fputcsv($out, ['Codigo', 'Fecha', 'Estado', 'Motivo']); // cabecera

    foreach ($filas as $cita) {
        fputcsv($out, [
            $cita['codigo'],
            $cita['fecha'],
            $cita['estado'],
            $cita['motivo_consulta'],
        ]);
    }

    rewind($out);
    $csv = "\xEF\xBB\xBF" . stream_get_contents($out); // BOM: Excel detecta UTF-8
    fclose($out);

    return Response::make($csv, 200, [
        'Content-Type'        =&gt; 'text/csv; charset=UTF-8',
        'Content-Disposition' =&gt; 'attachment; filename="citas_' . $desde . '_' . $hasta . '.csv"',
    ]);
}</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Enlace de descarga</span>
            <p><code>&lt;a href="&lt;?= e(route('citas_export', ['desde' =&gt; $d, 'hasta' =&gt; $h])) ?&gt;"&gt;
                Exportar&lt;/a&gt;</code> — los parámetros de filtro viajan en la query string.</p>
        </div>
    </section>

    <section class="doc-section" id="cap15-r6" data-doc-section data-title="Receta 6 — Operaciones transaccionales">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-r6">#</a>Receta 6 — Operaciones transaccionales</h3>
        <p>
            Reservar una cita, descontar el cupo de la agenda y avisar por correo son tres pasos que
            deben caer juntos o no caer. <code>Database::transaction()</code> lo garantiza: si lanza
            excepción, todo se revierte.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Reserva de cita con control de cupo (FichaAgenda + Cita)</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use Lib\Core\Database;
use App\Models\Cita;
use App\Models\FichaAgenda;

public function reservar(Request $request): Response
{
    $fichaId = (int) $request-&gt;input('id_ficha_agenda');

    try {
        Database::transaction(function () use ($fichaId, $request) {
            // 1. La ficha debe estar abierta
            $ficha = FichaAgenda::query()
                -&gt;where('id', $fichaId)
                -&gt;where('estado', 'ACTIVA')
                -&gt;first();

            if ($ficha === null) {
                throw new \RuntimeException('Ese horario ya no está disponible.');
            }

            // 2. Candado de cupo: ¿ya se llenó? (dentro de la transacción)
            $ocupadas = Cita::query()
                -&gt;where('id_ficha_agenda', $fichaId)
                -&gt;count();

            if ($ocupadas &gt;= (int) $ficha['capacidad']) {
                throw new \RuntimeException('El horario está completo.');
            }

            // 3. Crear la cita: si falla, todo se revierte
            Cita::query()-&gt;insert([
                'codigo'            =&gt; 'C' . strtoupper(bin2hex(random_bytes(4))),
                'id_paciente'       =&gt; (int) $request-&gt;input('id_paciente'),
                'id_ficha_agenda'   =&gt; $fichaId,
                'estado'            =&gt; 'CONFIRMADA',
                'motivo_consulta'   =&gt; (string) $request-&gt;input('motivo', ''),
                'id_usuario_registro' =&gt; (int) session('id_usuario'),
            ]);
        });
    } catch (\Throwable $e) {
        // Nada se guardó: la transacción revirtió todo
        return $this-&gt;json([
            'success' =&gt; false,
            'message' =&gt; $e-&gt;getMessage() ?: 'No se pudo reservar.',
        ], 409);
    }

    return $this-&gt;json(['success' =&gt; true, 'message' =&gt; 'Cita reservada.']);
}</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Correo fuera de la transacción</span>
            <p>Un fallo SMTP no debe tirar la reserva: envía el correo <em>después</em> de que la
                transacción cierre bien (y captura su error aparte con un aviso, no con un 500).</p>
        </div>
    </section>

    <section class="doc-section" id="cap15-componer" data-doc-section data-title="Cómo componer un sistema completo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-componer">#</a>Cómo componer un sistema completo</h3>
        <p>
            Un sistema complejo real (como el portal de esta clínica) se ve así al ensamblar las recetas:
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 330" width="940" height="330" role="img"
                aria-label="Diagrama de composición: middleware de permisos, controladores, API, reportes y bitácora sobre la base de datos">
                <rect x="20" y="20" width="900" height="52" rx="10" fill="#e7f1ff" stroke="#0d6efd" stroke-width="1.5"></rect>
                <text x="470" y="44" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">ACCESO · Receta 1</text>
                <text x="470" y="63" font-size="11" text-anchor="middle" fill="#1e3a8a">PortalAuth + Permiso/Rol en cada ruta (middleware, no en la vista)</text>

                <line x1="470" y1="72" x2="470" y2="96" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="470,104 464,92 476,92" fill="#94a3b8"></polygon>

                <rect x="20" y="108" width="280" height="86" rx="10" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="160" y="132" font-size="12.5" font-weight="700" text-anchor="middle" fill="#166534">MÓDULO CRUD</text>
                <text x="160" y="154" font-size="11" text-anchor="middle" fill="#14532d">FormRequest + controlador</text>
                <text x="160" y="172" font-size="11" text-anchor="middle" fill="#14532d">vista panel + menú lateral</text>

                <rect x="330" y="108" width="280" height="86" rx="10" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="470" y="132" font-size="12.5" font-weight="700" text-anchor="middle" fill="#92400e">API JSON · Receta 2</text>
                <text x="470" y="154" font-size="11" text-anchor="middle" fill="#78350f">búsqueda, autocompletado,</text>
                <text x="470" y="172" font-size="11" text-anchor="middle" fill="#78350f">paneles y gráficas</text>

                <rect x="640" y="108" width="280" height="86" rx="10" fill="#f3e8ff" stroke="#7c3aed"></rect>
                <text x="780" y="132" font-size="12.5" font-weight="700" text-anchor="middle" fill="#5b21b6">SALIDAS · Recetas 3 y 5</text>
                <text x="780" y="154" font-size="11" text-anchor="middle" fill="#6d28d9">PDF con dompdf,</text>
                <text x="780" y="172" font-size="11" text-anchor="middle" fill="#6d28d9">CSV para Excel</text>

                <line x1="470" y1="194" x2="470" y2="218" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="470,226 464,214 476,214" fill="#94a3b8"></polygon>

                <rect x="20" y="230" width="440" height="72" rx="10" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="240" y="256" font-size="12.5" font-weight="700" text-anchor="middle" fill="#166534">DATOS · Receta 6</text>
                <text x="240" y="278" font-size="11" text-anchor="middle" fill="#14532d">Model + transacciones en operaciones multi-tabla</text>

                <rect x="480" y="230" width="440" height="72" rx="10" fill="#fde8e8" stroke="#dc2626"></rect>
                <text x="700" y="256" font-size="12.5" font-weight="700" text-anchor="middle" fill="#991b1b">AUDITORÍA · Receta 4</text>
                <text x="700" y="278" font-size="11" text-anchor="middle" fill="#b91c1c">bitácora de accesos y acciones, consultable en consola SQL</text>
            </svg>
            <span class="doc-caption">Figura 15.1 — Un sistema complejo: acceso arriba, piezas de negocio al medio, datos y auditoría abajo.</span>
        </div>

        <div class="doc-h4">Orden de construcción recomendado</div>
        <div class="doc-steps">
            <div class="doc-step">
                <div class="doc-step-title">1. Modelo y tablas primero</div>
                <p>Con la consola SQL diseña y crea las tablas; confirma con <code>SELECT</code> antes de
                    código (Capítulo 13).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">2. Un CRUD base por módulo</div>
                <p>Genera request + controlador, registra rutas, copia una vista similar (Capítulos 12 y 13).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">3. Protege las rutas</div>
                <p>Añade <code>PortalAuth</code>/<code>Permiso</code>/<code>Rol</code> a todas las de
                    escritura (Receta 1).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">4. Endpoints JSON donde la interfaz lo pida</div>
                <p>Búsquedas en vivo y paneles (Receta 2).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">5. Transacciones en las operaciones críticas</div>
                <p>Reservas, pagos, movimientos de stock (Receta 6).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">6. Auditoría y salidas</div>
                <p>Bitácora de acciones sensibles (Receta 4) y al final PDF/CSV para gerencia (Recetas 3 y 5).</p>
            </div>
            <div class="doc-step">
                <div class="doc-step-title">7. Cierre con checklist</div>
                <p><code>php -l</code>, <code>php tests/run.php</code> y la checklist del Capítulo 11,
                    con Depuración OFF (Capítulo 13, Flujo C).</p>
            </div>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Complejo no es grande</span>
            <p>Si un módulo crece demasiado, divídelo en controladores más pequeños con rutas propias
                (uno por área), como ya hace este CMS con <code>PortalCita</code>,
                <code>PortalPaciente</code> y <code>PortalEspecialidad</code>. La cohesión por módulo es lo
                que mantiene legible un sistema grande.</p>
        </div>
    </section>

    <section class="doc-section" id="cap15-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap15-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Aplicas <code>Permiso:NNN</code> a una ruta y confirmas 403 sin permiso / 200 con él.</li>
            <li>Creas un endpoint JSON de búsqueda y lo consumes desde la consola del navegador.</li>
            <li>Generas un PDF de una lista real con dompdf y lo abres desde un enlace del panel.</li>
            <li>Insertas una fila en la bitácora desde un controlador y la recuperas con la consola SQL.</li>
            <li>Exportas un CSV con acentos correctos en Excel (BOM UTF-8 incluido).</li>
            <li>Simulas un fallo dentro de una transacción y confirmas que no quedó ningún cambio a medias.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#docs-cover"><i class="fas fa-arrow-up mr-1"></i>Volver a la portada</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap15') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
