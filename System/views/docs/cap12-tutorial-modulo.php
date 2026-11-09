<section class="doc-chapter" id="cap12" data-doc-chapter data-number="12" data-title="Tutorial: un CRUD completo paso a paso">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 12</span>
        <h2 class="doc-chapter-title">Tutorial: un CRUD completo paso a paso</h2>
        <p class="doc-chapter-lead">
            Vamos a construir el módulo <strong>Productos</strong> de principio a fin: tabla, modelo,
            <code>FormRequest</code>, rutas, controlador, vista con modal y enlace en el menú. Al terminar
            tendrás listado, alta, edición, activar/desactivar y borrado, todo con validación en el servidor
            y respuestas JSON para el panel. Es el mismo patrón que usan <strong>Usuarios</strong> y las
            páginas del sistema.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap12-tabla">1. Tabla</a>
            <a class="doc-chip" href="#cap12-modelo">2. Modelo</a>
            <a class="doc-chip" href="#cap12-request">3. FormRequest</a>
            <a class="doc-chip" href="#cap12-rutas">4. Rutas</a>
            <a class="doc-chip" href="#cap12-controlador">5. Controlador</a>
            <a class="doc-chip" href="#cap12-vista">6. Vista</a>
            <a class="doc-chip" href="#cap12-probar">7. Probar</a>
        </div>
    </header>

    <section class="doc-section" id="cap12-tabla" data-doc-section data-title="Paso 1 — Crear la tabla">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-tabla">#</a>Paso 1 — Crear la tabla</h3>
        <p>
            Añade el bloque al final de <code>config/setup.php</code> (se ejecuta al arrancar y crea la
            tabla si no existe) o ejecuta el SQL directamente en tu gestor:
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">config/setup.php — tabla productos (MySQL)</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>if (!$db-&gt;query("SHOW TABLES LIKE 'productos'")-&gt;fetchColumn()) {
    $db-&gt;exec("
        CREATE TABLE IF NOT EXISTS productos (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(190) NOT NULL,
            precio DECIMAL(10,2) NOT NULL DEFAULT 0,
            stock INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        )
    ");
}</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>PostgreSQL</span>
            <p>El CMS soporta ambos motores. En PostgreSQL usa
                <code>id BIGSERIAL PRIMARY KEY</code>, <code>is_active BOOLEAN NOT NULL DEFAULT TRUE</code> y
                <code>TIMESTAMP</code>. El modelo y el resto del tutorial no cambian.</p>
        </div>
    </section>

    <section class="doc-section" id="cap12-modelo" data-doc-section data-title="Paso 2 — Crear el modelo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-modelo">#</a>Paso 2 — Crear el modelo</h3>
        <p>
            Crea <code>App/Models/Productos.php</code>. El nombre del archivo debe coincidir con la clase
            (PSR-4). <code>$fillable</code> es la lista blanca de columnas que <code>insert()</code> y
            <code>update()</code> aceptan; lo que no esté ahí se descarta.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Models/Productos.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Productos extends Model
{
    protected string $table = 'productos';
    protected string $primaryKey = 'id';
    protected array $fillable = ['nombre', 'precio', 'stock', 'is_active'];
    protected array $casts = [
        'precio' =&gt; 'float',
        'stock' =&gt; 'int',
        'is_active' =&gt; 'boolean',
    ];
    protected bool $timestamps = true;
}</code></pre>
        </div>

        <p>
            Con <code>$timestamps = true</code> (valor por defecto), <code>insert()</code> rellena
            <code>created_at</code> y <code>updated_at</code>, y <code>update()</code> refresca
            <code>updated_at</code>.
        </p>
    </section>

    <section class="doc-section" id="cap12-request" data-doc-section data-title="Paso 3 — El FormRequest">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-request">#</a>Paso 3 — El FormRequest</h3>
        <p>
            La validación vive en <code>App/Requests/StoreProductoRequest.php</code>. El router lo detecta
            por el tipo del parámetro del controlador, ejecuta <code>authorize()</code> y <code>rules()</code>
            <strong>antes</strong> de entrar al método, y responde 422 con <code>errors</code> si algo falla.
            <code>authorize()</code> es el lugar para comprobar método y CSRF.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Requests/StoreProductoRequest.php</span>
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
            'nombre' =&gt; 'required|min:3|max:190',
            'precio' =&gt; 'required|numeric',
            'stock' =&gt; 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' =&gt; 'El nombre del producto es obligatorio.',
            'nombre.min' =&gt; 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' =&gt; 'El nombre no puede superar 190 caracteres.',
            'precio.required' =&gt; 'El precio es obligatorio.',
            'precio.numeric' =&gt; 'El precio debe ser un numero.',
            'stock.required' =&gt; 'El stock es obligatorio.',
            'stock.integer' =&gt; 'El stock debe ser un numero entero.',
        ];
    }
}</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i><code>min</code> y <code>max</code> cuentan caracteres</span>
            <p>En este validador <code>min</code>/<code>max</code> miden longitud de texto, no valor
                numérico. Para rangos de precio usa una comprobación propia en el controlador; para texto,
                <code>min</code>/<code>max</code> es exactamente lo que necesitas.</p>
        </div>
    </section>

    <section class="doc-section" id="cap12-rutas" data-doc-section data-title="Paso 4 — Registrar las rutas">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-rutas">#</a>Paso 4 — Registrar las rutas</h3>
        <p>
            Añade el bloque en <code>routes/app.php</code>, dentro del <code>Route::prefix('join/access/dashboard', …)</code>
            existente. La acción se escribe como una cadena <strong>solo dentro</strong> de
            <code>Route::controller()</code>; ahí el router la convierte en <code>[Controlador, metodo]</code>.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">routes/app.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use App\Controllers\ProductosController;

Route::prefix('join/access/dashboard', function () {
    // ... rutas existentes ...

    Route::controller(ProductosController::class, function () {
        Route::middleware(['Auth'], function () {
            Route::prefix('/Area-of-work/productos', function () {
                Route::get('/', 'index')-&gt;name('productos');
                Route::post('/guardar', 'guardar')-&gt;name('productos_guardar');
                Route::post('/actualizar/{id}', 'actualizar')-&gt;name('productos_actualizar');
                Route::post('/estado/{id}', 'estado')-&gt;name('productos_estado');
                Route::post('/eliminar/{id}', 'eliminar')-&gt;name('productos_eliminar');
            });
        });
    });
});</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>No uses <code>'Controlador@metodo'</code></span>
            <p>El router no parsea ese formato. Fuera de <code>Route::controller()</code>, una cadena solo
                funciona si es el nombre de un método de un controlador ya activo; en cualquier otro caso
                termina en 404. Usa el grupo <code>Route::controller()</code>, un array
                <code>[Controlador::class, 'metodo']</code> o un closure.</p>
        </div>

        <p>
            El parámetro <code>{id}</code> llega al método convertido a <code>int</code> si tipas el
            parámetro como <code>int $id</code> (ver Capítulo 3). El orden de los parámetros es: primero los
            que el router inyecta (<code>Request</code> o <code>FormRequest</code>) y después los de la ruta.
        </p>
    </section>

    <section class="doc-section" id="cap12-controlador" data-doc-section data-title="Paso 5 — El controlador">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-controlador">#</a>Paso 5 — El controlador</h3>
        <p>
            Cada acción vuelve a comprobar la sesión: las páginas con <code>isAdmin()</code> + redirect y
            las AJAX con <code>isAdmin()</code> + 403 JSON. Los POST que no usan <code>FormRequest</code>
            (estado y eliminar) validan método y CSRF con <code>validatePost()</code>.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Controllers/ProductosController.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Productos;
use App\Requests\StoreProductoRequest;
use Lib\Core\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;

class ProductosController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this-&gt;isAdmin()) {
            return $this-&gt;redirect('login');
        }

        return $this-&gt;view('dashboard.base.dashboard', [
            'head' =&gt; View::render('dashboard.base.head', [
                'title' =&gt; 'Productos',
                'icon' =&gt; asset('code/dist/img/AdminLTELogo.png'),
            ]),
            'title' =&gt; 'Productos',
            'session' =&gt; 'Productos',
            'content' =&gt; View::component('dashboard.productos.index', [
                'productos' =&gt; Productos::query()-&gt;orderBy('id', 'DESC')-&gt;get(),
            ]),
        ]);
    }

    public function guardar(StoreProductoRequest $request): Response
    {
        if (!$this-&gt;isAdmin()) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'No autorizado.'], 403);
        }

        $data = $request-&gt;validated();
        $data['is_active'] = 1;

        $id = Productos::query()-&gt;insert($data);

        return $this-&gt;json([
            'success' =&gt; true,
            'id' =&gt; $id,
            'message' =&gt; 'Producto creado correctamente.',
        ]);
    }

    public function actualizar(StoreProductoRequest $request, int $id): Response
    {
        if (!$this-&gt;isAdmin()) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'No autorizado.'], 403);
        }

        if (Productos::query()-&gt;find($id) === null) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'Producto no encontrado.'], 404);
        }

        Productos::query()-&gt;update($request-&gt;validated(), ['id' =&gt; $id]);

        return $this-&gt;json(['success' =&gt; true, 'message' =&gt; 'Producto actualizado.']);
    }

    public function estado(Request $request, int $id): Response
    {
        if (!$this-&gt;isAdmin()) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'No autorizado.'], 403);
        }

        if ($response = $this-&gt;validatePost($request)) {
            return $response;
        }

        if (Productos::query()-&gt;find($id) === null) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'Producto no encontrado.'], 404);
        }

        $enabled = (string) $request-&gt;input('enabled', '1') === '1';

        Productos::query()-&gt;update(['is_active' =&gt; $enabled ? 1 : 0], ['id' =&gt; $id]);

        return $this-&gt;json(['success' =&gt; true, 'enabled' =&gt; $enabled]);
    }

    public function eliminar(Request $request, int $id): Response
    {
        if (!$this-&gt;isAdmin()) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'No autorizado.'], 403);
        }

        if ($response = $this-&gt;validatePost($request)) {
            return $response;
        }

        if (Productos::query()-&gt;find($id) === null) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'Producto no encontrado.'], 404);
        }

        Productos::query()-&gt;delete(['id' =&gt; $id]);

        return $this-&gt;json(['success' =&gt; true, 'message' =&gt; 'Producto eliminado.']);
    }

    private function validatePost(Request $request): ?Response
    {
        if (!$request-&gt;isPost()) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'Metodo no permitido.'], 405);
        }

        if (!Csrf::validate($request-&gt;input('_token'))) {
            return $this-&gt;json(['success' =&gt; false, 'message' =&gt; 'Token invalido.'], 403);
        }

        return null;
    }
}</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>El préstamo del <code>FormRequest</code></span>
            <p><code>actualizar()</code> usa el mismo <code>StoreProductoRequest</code> porque las reglas son
                idénticas. Si la edición permite campos distintos, crea
                <code>UpdateProductoRequest</code> y tipa ese parámetro; no necesitas tocar las rutas.</p>
        </div>
    </section>

    <section class="doc-section" id="cap12-vista" data-doc-section data-title="Paso 6 — La vista y el modal">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-vista">#</a>Paso 6 — La vista y el modal</h3>
        <p>
            Crea <code>App/views/dashboard/productos/index.php</code>. La vista recibe <code>$productos</code>
            como array de arrays. Escapa siempre con <code>e()</code>. El botón «Editar» guarda los datos en
            atributos <code>data-*</code> y el mismo modal sirve para alta y edición.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/views/dashboard/productos/index.php</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;div class="card card-outline card-primary"&gt;
    &lt;div class="card-header"&gt;
        &lt;h3 class="card-title"&gt;Listado de productos&lt;/h3&gt;
        &lt;div class="card-tools"&gt;
            &lt;button class="btn btn-primary btn-sm" id="btn-nuevo"&gt;
                &lt;i class="fas fa-plus mr-1"&gt;&lt;/i&gt;Nuevo producto
            &lt;/button&gt;
        &lt;/div&gt;
    &lt;/div&gt;
    &lt;div class="card-body table-responsive"&gt;
        &lt;table class="table table-sm table-striped"&gt;
            &lt;thead&gt;
                &lt;tr&gt;
                    &lt;th&gt;#&lt;/th&gt;
                    &lt;th&gt;Nombre&lt;/th&gt;
                    &lt;th&gt;Precio&lt;/th&gt;
                    &lt;th&gt;Stock&lt;/th&gt;
                    &lt;th&gt;Estado&lt;/th&gt;
                    &lt;th&gt;Acciones&lt;/th&gt;
                &lt;/tr&gt;
            &lt;/thead&gt;
            &lt;tbody&gt;
                &lt;?php foreach ($productos as $producto): ?&gt;
                    &lt;tr&gt;
                        &lt;td&gt;&lt;?= e((string) $producto['id']) ?&gt;&lt;/td&gt;
                        &lt;td&gt;&lt;?= e((string) $producto['nombre']) ?&gt;&lt;/td&gt;
                        &lt;td&gt;&lt;?= e(number_format((float) $producto['precio'], 2)) ?&gt;&lt;/td&gt;
                        &lt;td&gt;&lt;?= e((string) $producto['stock']) ?&gt;&lt;/td&gt;
                        &lt;td&gt;
                            &lt;?php if ((int) $producto['is_active'] === 1): ?&gt;
                                &lt;span class="badge badge-success"&gt;Activo&lt;/span&gt;
                            &lt;?php else: ?&gt;
                                &lt;span class="badge badge-secondary"&gt;Inactivo&lt;/span&gt;
                            &lt;?php endif; ?&gt;
                        &lt;/td&gt;
                        &lt;td&gt;
                            &lt;button class="btn btn-xs btn-info btn-editar"
                                data-id="&lt;?= e((string) $producto['id']) ?&gt;"
                                data-nombre="&lt;?= e((string) $producto['nombre']) ?&gt;"
                                data-precio="&lt;?= e((string) $producto['precio']) ?&gt;"
                                data-stock="&lt;?= e((string) $producto['stock']) ?&gt;"&gt;
                                &lt;i class="fas fa-edit"&gt;&lt;/i&gt;
                            &lt;/button&gt;
                            &lt;button class="btn btn-xs btn-warning btn-estado"
                                data-id="&lt;?= e((string) $producto['id']) ?&gt;"
                                data-enabled="&lt;?= (int) $producto['is_active'] === 1 ? '0' : '1' ?&gt;"&gt;
                                &lt;i class="fas fa-power-off"&gt;&lt;/i&gt;
                            &lt;/button&gt;
                            &lt;button class="btn btn-xs btn-danger btn-eliminar"
                                data-id="&lt;?= e((string) $producto['id']) ?&gt;"&gt;
                                &lt;i class="fas fa-trash"&gt;&lt;/i&gt;
                            &lt;/button&gt;
                        &lt;/td&gt;
                    &lt;/tr&gt;
                &lt;?php endforeach; ?&gt;
            &lt;/tbody&gt;
        &lt;/table&gt;
    &lt;/div&gt;
&lt;/div&gt;

&lt;div class="modal fade" id="modal-producto" tabindex="-1"&gt;
    &lt;div class="modal-dialog"&gt;
        &lt;form class="modal-content" id="form-producto"&gt;
            &lt;div class="modal-header"&gt;
                &lt;h5 class="modal-title"&gt;Producto&lt;/h5&gt;
                &lt;button type="button" class="close" data-dismiss="modal"&gt;&amp;times;&lt;/button&gt;
            &lt;/div&gt;
            &lt;div class="modal-body"&gt;
                &lt;input type="hidden" name="id" id="producto-id"&gt;
                &lt;div class="form-group"&gt;
                    &lt;label&gt;Nombre&lt;/label&gt;
                    &lt;input type="text" name="nombre" id="producto-nombre" class="form-control" required&gt;
                &lt;/div&gt;
                &lt;div class="form-row"&gt;
                    &lt;div class="form-group col-6"&gt;
                        &lt;label&gt;Precio&lt;/label&gt;
                        &lt;input type="number" step="0.01" name="precio" id="producto-precio" class="form-control" required&gt;
                    &lt;/div&gt;
                    &lt;div class="form-group col-6"&gt;
                        &lt;label&gt;Stock&lt;/label&gt;
                        &lt;input type="number" name="stock" id="producto-stock" class="form-control" required&gt;
                    &lt;/div&gt;
                &lt;/div&gt;
                &lt;?= csrf_field() ?&gt;
            &lt;/div&gt;
            &lt;div class="modal-footer"&gt;
                &lt;button type="submit" class="btn btn-primary"&gt;Guardar&lt;/button&gt;
            &lt;/div&gt;
        &lt;/form&gt;
    &lt;/div&gt;
&lt;/div&gt;

&lt;script&gt;
    var urlGuardar = "&lt;?= route('productos_guardar') ?&gt;";
    var urlActualizar = "&lt;?= route('productos_actualizar', ['id' =&gt; '__ID__']) ?&gt;";
    var urlEstado = "&lt;?= route('productos_estado', ['id' =&gt; '__ID__']) ?&gt;";
    var urlEliminar = "&lt;?= route('productos_eliminar', ['id' =&gt; '__ID__']) ?&gt;";

    function abrirModal(data) {
        $('#producto-id').val(data ? data.id : '');
        $('#producto-nombre').val(data ? data.nombre : '');
        $('#producto-precio').val(data ? data.precio : '');
        $('#producto-stock').val(data ? data.stock : '0');
        $('#modal-producto').modal('show');
    }

    $('#btn-nuevo').on('click', function () { abrirModal(null); });

    $('.btn-editar').on('click', function () {
        abrirModal($(this).data());
    });

    $('#form-producto').on('submit', function (event) {
        event.preventDefault();

        var id = $('#producto-id').val();
        var url = id ? urlActualizar.replace('__ID__', id) : urlGuardar;

        $.post(url, $(this).serialize(), function (res) {
            if (res.success) {
                showNotification('success', res.message);
                setTimeout(function () { window.location.reload(); }, 700);
            } else {
                showNotification('error', res.message);
            }
        }, 'json').fail(function (xhr) {
            var res = xhr.responseJSON || {};
            showNotification('error', res.message || 'No se pudo guardar.');
        });
    });

    $('.btn-estado').on('click', function () {
        var id = $(this).data('id');
        var enabled = String($(this).data('enabled'));

        $.post(urlEstado.replace('__ID__', id), {
            enabled: enabled,
            _token: "&lt;?= csrf_token() ?&gt;"
        }, function (res) {
            if (res.success) {
                window.location.reload();
            }
        }, 'json');
    });

    $('.btn-eliminar').on('click', function () {
        if (!confirm('¿Eliminar este producto?')) return;

        $.post(urlEliminar.replace('__ID__', $(this).data('id')), {
            _token: "&lt;?= csrf_token() ?&gt;"
        }, function (res) {
            if (res.success) {
                showNotification('success', res.message);
                setTimeout(function () { window.location.reload(); }, 700);
            }
        }, 'json');
    });
&lt;/script&gt;</code></pre>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>Editar sin otro modal</span>
            <p><code>$(this).data()</code> devuelve todos los atributos <code>data-*</code> del botón como
                objeto; por eso <code>abrirModal()</code> funciona igual con los datos de una fila. Si
                añades campos, agrega el <code>data-*</code> correspondiente y una línea en
                <code>abrirModal()</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap12-menu" data-doc-section data-title="Paso 7 — Enlace en el menú">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-menu">#</a>Paso 7 — Enlace en el menú</h3>
        <p>
            Añade el ítem en <code>System/views/dashboard/base/dashboard.php</code>, en el bloque
            <strong>Sistema</strong>, junto a Usuarios. Usa el helper <code>route()</code> y el mismo patrón
            de clases que los demás enlaces.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">System/views/dashboard/base/dashboard.php — nuevo ítem</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;li class="nav-item"&gt;
    &lt;a href="&lt;?= route('productos') ?&gt;" class="nav-link&lt;?= $cmsActive('productos') ?&gt;"&gt;
        &lt;i class="fas fa-box nav-icon"&gt;&lt;/i&gt;
        &lt;p&gt;Productos&lt;/p&gt;
    &lt;/a&gt;
&lt;/li&gt;</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>La única edición del núcleo</span>
            <p>El menú es parte del layout compartido, así que este es el único archivo de
                <code>System/</code> que el tutorial toca. Todo lo demás vive en <code>App/</code> y
                <code>routes/</code>. Si prefieres no tocarlo, puedes entrar por la URL directa
                <code>/join/access/dashboard/Area-of-work/productos</code>.</p>
        </div>
    </section>

    <section class="doc-section" id="cap12-probar" data-doc-section data-title="Paso 8 — Probar el módulo completo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap12-probar">#</a>Paso 8 — Probar el módulo completo</h3>
        <ul class="doc-check">
            <li><code>php -l</code> en los cuatro archivos nuevos (modelo, request, controlador, vista).</li>
            <li>Entra al panel → <strong>Productos</strong>: la tabla se ve vacía y sin errores.</li>
            <li>Crea un producto: aparece la notificación y la fila se suma al listado.</li>
            <li>Edita un producto: el modal se rellena y el cambio persiste al recargar.</li>
            <li>Desactiva y reactiva: el badge cambia entre Activo e Inactivo.</li>
            <li>Elimina: pide confirmación y la fila desaparece.</li>
            <li>Envía el formulario con nombre vacío: el router responde 422 y el modal muestra el error.</li>
            <li>Quita el <code>_token</code> del POST: responde 403.</li>
            <li><strong>Sistema → Rutas</strong>: <code>productos</code> aparece con sus cinco rutas.</li>
            <li><strong>Sistema → Base de datos</strong>: la tabla <code>productos</code> existe con sus
                columnas, tipos y <code>primary key</code>; puedes listar sus filas desde la consola.</li>
            <li><strong>Sistema → Base de datos</strong>: confirma las columnas y los tipos.</li>
            <li><code>php tests/run.php</code> sigue terminando en «0 fallidas».</li>
        </ul>

        <div class="doc-h4">Variante: probar el modelo contra la base real</div>
        <p>
            Si quieres comprobar el modelo sin abrir el navegador, ejecuta un script de humo dentro del
            contenedor. Recuerda borrarlo al terminar:
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Verificación rápida (temporal)</span>
                <span class="doc-code-lang">bash</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>docker exec php_apache_gupadis php -r '
    require "/var/www/html/vendor/autoload.php";
    Lib\Support\Env::load("/var/www/html");
    Lib\Core\Database::configure(config("database"));

    $id = App\Models\Productos::query()->insert([
        "nombre" =&gt; "Prueba",
        "precio" =&gt; 1.5,
        "stock"  =&gt; 3,
    ]);

    var_dump(App\Models\Productos::query()-&gt;find($id));
    App\Models\Productos::query()-&gt;delete(["id" =&gt; $id]);
'</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Borra la prueba</span>
            <p>Ese script inserta y elimina una fila real. Si falla a mitad, elimina el producto «Prueba»
                desde el panel o con <code>delete(['nombre' =&gt; 'Prueba'])</code> antes de continuar.</p>
        </div>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap13"><i class="fas fa-arrow-right mr-1"></i>Siguiente: dashboard de administración</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap12') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
