<section class="doc-chapter" id="cap8" data-doc-chapter data-number="8" data-title="Modelos y base de datos">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 8</span>
        <h2 class="doc-chapter-title">Modelos y base de datos</h2>
        <p class="doc-chapter-lead">
            El sistema no usa un ORM pesado: un modelo es una clase pequeña que apunta a una tabla y te
            da un constructor de consultas encadenable. Debajo, PDO con sentencias preparadas y una capa
            <code>Database</code> para transacciones y SQL directo.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap8-modelo">Tu primer modelo</a>
            <a class="doc-chip" href="#cap8-consultas">Consultas</a>
            <a class="doc-chip" href="#cap8-escrituras">Insertar, actualizar, borrar</a>
            <a class="doc-chip" href="#cap8-database">Database directo</a>
        </div>
    </header>

    <section class="doc-section" id="cap8-modelo" data-doc-section data-title="Tu primer modelo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap8-modelo">#</a>Tu primer modelo</h3>
        <p>
            Crea la clase en <code>App/Models/</code>, apunta la tabla y declara qué columnas se pueden
            escribir. Nada más: el resto lo hereda de <code>Lib\Core\Model</code>.
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">App/Models/Producto.php — modelo completo</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>&lt;?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Producto extends Model
{
    protected string $table = 'productos';          // tabla real en la base de datos
    protected string $primaryKey = 'id';            // clave primaria (find() la usa)
    protected bool $timestamps = true;              // created_at / updated_at automaticos

    protected array $fillable = [                   // solo estas columnas se guardan
        'nombre', 'precio', 'stock', 'categoria',
        'created_at', 'updated_at',
    ];

    protected array $casts = [                      // conversion de tipos al guardar
        'precio' =&gt; 'float',
        'stock'  =&gt; 'int',
    ];
}</code></pre>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Propiedad</th>
                        <th>Efecto</th>
                        <th>Por defecto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>$table</code></td>
                        <td>Nombre de la tabla en la base de datos.</td>
                        <td>Obligatoria.</td>
                    </tr>
                    <tr>
                        <td><code>$primaryKey</code></td>
                        <td>Columna que usa <code>find()</code>.</td>
                        <td><code>'id'</code></td>
                    </tr>
                    <tr>
                        <td><code>$fillable</code></td>
                        <td>Lista blanca: <code>insert()</code>/<code>update()</code> descartan cualquier columna que no esté aquí. Vacía = todo permitido (no lo hagas en producción).</td>
                        <td><code>[]</code></td>
                    </tr>
                    <tr>
                        <td><code>$casts</code></td>
                        <td>Convierte tipos al guardar: <code>int</code>, <code>boolean</code>, <code>float</code>, <code>string</code>, <code>json</code>.</td>
                        <td><code>[]</code></td>
                    </tr>
                    <tr>
                        <td><code>$timestamps</code></td>
                        <td>Rellena <code>created_at</code> al crear y <code>updated_at</code> al crear y actualizar. Si es <code>false</code>, elimina esas claves del guardado.</td>
                        <td><code>true</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="doc-section" id="cap8-consultas" data-doc-section data-title="Consultas: el constructor encadenable">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap8-consultas">#</a>Consultas: el constructor encadenable</h3>
        <p>
            Empieza siempre con <code>Modelo::query()</code> y encadena condiciones. La consulta se
            ejecuta con <code>get()</code>, <code>first()</code>, <code>count()</code>… y después se limpia
            sola: cada cadena es una consulta nueva.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Método</th>
                        <th>Qué hace</th>
                        <th>Devuelve</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>query()</code></td><td>Inicia una consulta sobre el modelo.</td><td>Instancia del modelo</td></tr>
                    <tr><td><code>where($col, $valor, $op = '=')</code></td><td>Añade una condición; varias se unen con <code>AND</code>.</td><td>La misma instancia</td></tr>
                    <tr><td><code>orderBy($col, 'DESC')</code></td><td>Ordena (una sola ordenación por consulta).</td><td>La misma instancia</td></tr>
                    <tr><td><code>limit(n)</code> · <code>offset(n)</code></td><td>Pagina resultados.</td><td>La misma instancia</td></tr>
                    <tr><td><code>get()</code></td><td>Ejecuta y trae todas las filas.</td><td><code>array</code> de arrays asociativos</td></tr>
                    <tr><td><code>first()</code></td><td>Primera fila (o <code>null</code>).</td><td><code>?array</code></td></tr>
                    <tr><td><code>find($id)</code></td><td>Atajo de <code>where($primaryKey, $id)-&gt;first()</code>.</td><td><code>?array</code></td></tr>
                    <tr><td><code>count()</code></td><td>Cuenta filas con las condiciones actuales.</td><td><code>int</code></td></tr>
                    <tr><td><code>exists()</code></td><td><code>count() &gt; 0</code>.</td><td><code>bool</code></td></tr>
                    <tr><td><code>pluck($columna)</code></td><td>Array de una sola columna.</td><td><code>array</code></td></tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Consultas de uso diario</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use App\Models\Producto;

// Todas las filas, mas nuevas primero
$productos = Producto::query()-&gt;orderBy('id', 'DESC')-&gt;get();

// Una fila por id
$producto = Producto::query()-&gt;find(17);

// Filtros combinados (AND) + paginacion
$pagina  = Producto::query()
    -&gt;where('categoria', 'ropa')
    -&gt;where('stock', 0, '&gt;')
    -&gt;orderBy('nombre', 'ASC')
    -&gt;limit(25)
    -&gt;offset(50)
    -&gt;get();

// Comprobaciones rapidas
$hay = Producto::query()-&gt;where('nombre', $nombre)-&gt;exists();
$total = Producto::query()-&gt;where('categoria', 'ropa')-&gt;count();

// Lista de ids para un select
$ids = Producto::query()-&gt;pluck('id');</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>Los valores van por parámetros, jamás concatenados</span>
            <p>El constructor usa sentencias preparadas (<code>:w0</code>, <code>:w1</code>…) para los
                valores: escríbelos siempre como segundo argumento de <code>where()</code>. El nombre de
                columna se valida y se escapa según el motor, y el operador debe estar en la lista permitida
                (<code>=</code>, <code>!=</code>, <code>&lt;</code>, <code>&gt;</code>, <code>LIKE</code>…);
                un identificador u operador inválido lanza una excepción antes de tocar la base.</p>
        </div>
    </section>

    <section class="doc-section" id="cap8-escrituras" data-doc-section data-title="Insertar, actualizar y borrar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap8-escrituras">#</a>Insertar, actualizar y borrar</h3>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Escrituras con el modelo</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>use App\Models\Producto;
use Lib\Core\Database;

// Crear: devuelve el id insertado
$id = Producto::query()-&gt;insert([
    'nombre'    =&gt; 'Camiseta basica',
    'precio'    =&gt; '19.95',       // casts la convierte a float
    'stock'     =&gt; '40',
    'categoria' =&gt; 'ropa',
]);
// created_at y updated_at se rellenan solos (timestamps = true)

// Actualizar: condiciones obligatorias; devuelve filas afectadas
Producto::query()-&gt;update(
    ['precio' =&gt; 17.5, 'stock' =&gt; 35],
    ['id' =&gt; $id]
);

// Actualizar usando el where encadenado como condicion
Producto::query()-&gt;where('categoria', 'ropa')-&gt;update(['stock' =&gt; 0]);

// Borrar: tambien exige condiciones
Producto::query()-&gt;delete(['id' =&gt; $id]);

// Operacion multiple coherente: transaccion
Database::transaction(function ($pdo) use ($id) {
    Producto::query()-&gt;update(['stock' =&gt; 0], ['id' =&gt; $id]);
    // ... mas operaciones
});</code></pre>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Método</th>
                        <th>Firma</th>
                        <th>Devuelve</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>insert()</code></td>
                        <td><code>insert(array $data)</code></td>
                        <td>El id del registro insertado (<code>int</code>).</td>
                    </tr>
                    <tr>
                        <td><code>update()</code></td>
                        <td><code>update(array $data, array $condiciones = [])</code></td>
                        <td>Filas afectadas. Si no pasas condiciones, usa el <code>where()</code> encadenado;
                            si tampoco hay, lanza excepción.</td>
                    </tr>
                    <tr>
                        <td><code>delete()</code></td>
                        <td><code>delete(array $condiciones = [])</code></td>
                        <td>Filas borradas. Exige condiciones: no existe «borrar todo» accidental.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>El seguro anti-desastres</span>
            <p><code>update()</code> y <code>delete()</code> lanzan <code>InvalidArgumentException</code> si
                no hay condiciones. Es a propósito: un CRUD bien hecho siempre sabe <em>qué fila</em> toca.
                Si de verdad necesitas borrar todo, usa <code>Database::raw('DELETE FROM productos')</code>
                y que sea una decisión consciente.</p>
        </div>
    </section>

    <section class="doc-section" id="cap8-database" data-doc-section data-title="La capa Database: PDO, transacciones y SQL directo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap8-database">#</a>La capa Database: PDO, transacciones y SQL directo</h3>
        <p>
            Cuando el constructor no llega (reportes, procedimientos, consultas raras), baja un nivel.
            <code>Database</code> gestiona la conexión PDO única del sistema:
        </p>

        <div class="doc-figure-wrap doc-figure-box">
            <svg class="doc-figure" viewBox="0 0 940 250" width="940" height="250" role="img"
                aria-label="Capas: modelo sobre Database sobre PDO">
                <rect x="60" y="34" width="240" height="70" rx="10" fill="#e7f1ff" stroke="#0d6efd"></rect>
                <text x="180" y="62" font-size="13" font-weight="700" text-anchor="middle" fill="#0b4ea8">Tu modelo</text>
                <text x="180" y="82" font-size="11.5" text-anchor="middle" fill="#1e3a8a">Producto::query()-&gt;…</text>

                <rect x="380" y="34" width="240" height="70" rx="10" fill="#e8f5e9" stroke="#22c55e"></rect>
                <text x="500" y="62" font-size="13" font-weight="700" text-anchor="middle" fill="#166534">Lib\Core\Database</text>
                <text x="500" y="82" font-size="11.5" text-anchor="middle" fill="#14532d">connection · raw · transaction · call</text>

                <rect x="700" y="34" width="200" height="70" rx="10" fill="#fff8e1" stroke="#f59e0b"></rect>
                <text x="800" y="62" font-size="13" font-weight="700" text-anchor="middle" fill="#92400e">PDO</text>
                <text x="800" y="82" font-size="11.5" text-anchor="middle" fill="#78350f">MySQL / MariaDB / pgsql</text>

                <line x1="300" y1="69" x2="372" y2="69" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="380,69 368,63 368,75" fill="#94a3b8"></polygon>
                <line x1="620" y1="69" x2="692" y2="69" stroke="#94a3b8" stroke-width="2"></line>
                <polygon points="700,69 688,63 688,75" fill="#94a3b8"></polygon>
                <text x="340" y="56" font-size="10.5" text-anchor="middle" fill="#64748b">sentencias preparadas</text>
                <text x="660" y="56" font-size="10.5" text-anchor="middle" fill="#64748b">DSN de config/database.php</text>

                <rect x="60" y="140" width="840" height="42" rx="9" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="480" y="166" font-size="12" text-anchor="middle" fill="#334155">La conexión se crea una sola vez (singleton) con los datos de config/database.php y las credenciales del .env.</text>

                <rect x="60" y="196" width="840" height="42" rx="9" fill="#f8fafc" stroke="#94a3b8"></rect>
                <text x="480" y="222" font-size="12" text-anchor="middle" fill="#334155">config('database.default') decide el motor: mysql (por defecto) o pgsql. quoteIdentifier() adapta las comillas segun el motor.</text>
            </svg>
            <span class="doc-caption">Figura 8.1 — Las tres capas de datos: modelo → Database → PDO.</span>
        </div>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Método</th>
                        <th>Para qué</th>
                        <th>Ejemplo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>Database::connection()</code></td>
                        <td>El objeto PDO listo (prepare, lastInsertId…).</td>
                        <td><code>Database::connection()-&gt;lastInsertId()</code></td>
                    </tr>
                    <tr>
                        <td><code>Database::raw($sql, $params)</code></td>
                        <td>SQL directo: si empieza por SELECT/SHOW/DESCRIBE/EXPLAIN/WITH devuelve filas; si no, filas afectadas.</td>
                        <td><code>Database::raw('SELECT COUNT(*) c FROM usuarios WHERE status = ?', [1])</code></td>
                    </tr>
                    <tr>
                        <td><code>Database::transaction($callback)</code></td>
                        <td>Ejecuta el callback en transacción: commit automático, rollback si lanza excepción.</td>
                        <td>Ver código de escrituras arriba.</td>
                    </tr>
                    <tr>
                        <td><code>Database::call($rutina, $params)</code></td>
                        <td>Procedimientos almacenados: <code>CALL</code> en MySQL y <code>SELECT * FROM</code> en PostgreSQL.</td>
                        <td><code>Database::call('sp_reporte_ventas', [$anio])</code></td>
                    </tr>
                    <tr>
                        <td><code>Database::reconnect()</code></td>
                        <td>Fuerza una conexión nueva (útil en scripts largos).</td>
                        <td><code>Database::reconnect();</code></td>
                    </tr>
                    <tr>
                        <td><code>Database::isPgsql()</code></td>
                        <td>Detecta el motor para SQL específico.</td>
                        <td><code>if (Database::isPgsql()) { … }</code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>Los parámetros también van preparados</span>
            <p>En <code>raw()</code> usa marcadores (<code>?</code> o <code>:nombre</code>) y pásalos en el
                segundo argumento. Nunca interpoles valores del usuario en el SQL.</p>
        </div>
    </section>

    <section class="doc-section" id="cap8-comprobar" data-doc-section data-title="Comprobar">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap8-comprobar">#</a>Comprobar</h3>
        <ul class="doc-check">
            <li>Abre <strong>Sistema → Base de datos</strong> (<code>/Area-of-work/system/database</code>):
                muestra la conexión activa, la lista de tablas y las columnas de la que selecciones.
                Comprueba ahí que tu tabla existe con los tipos esperados.</li>
            <li>Inserta desde tu módulo y verifica en esa pantalla que <code>created_at</code> se rellenó.</li>
            <li>Intenta <code>Producto::query()-&gt;update(['stock' =&gt; 0])</code> sin condiciones:
                debe lanzar excepción (y no tocar ninguna fila).</li>
            <li>Provoca un fallo dentro de <code>Database::transaction()</code>: los cambios anteriores
                deben revertirse.</li>
            <li>Si ves <code>Database connection failed</code>: revisa <code>.env</code> y
                <code>config/database.php</code> (host, base, usuario, contraseña).</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap9"><i class="fas fa-arrow-right mr-1"></i>Siguiente: API interna, PDF y correos</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap8') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
