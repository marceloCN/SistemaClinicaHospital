<section class="doc-chapter" id="cap11" data-doc-chapter data-number="11" data-title="Pruebas, depuración y errores comunes">
    <header class="doc-chapter-header">
        <span class="doc-chapter-num">Capítulo 11</span>
        <h2 class="doc-chapter-title">Pruebas, depuración y errores comunes</h2>
        <p class="doc-chapter-lead">
            Antes de desplegar, comprueba que las piezas que tocaste siguen funcionando. Este capítulo
            explica el runner de pruebas del proyecto (<code>tests/run.php</code>), cómo depurar con el
            navegador y el log, y una tabla de síntomas con su causa y solución para los fallos que más
            aparecen al construir módulos.
        </p>
        <div class="doc-chapter-actions">
            <a class="doc-chip" href="#cap11-tests">Runner de pruebas</a>
            <a class="doc-chip" href="#cap11-debug">Depurar</a>
            <a class="doc-chip" href="#cap11-errores">Errores comunes</a>
            <a class="doc-chip" href="#cap11-checklist">Checklist</a>
        </div>
    </header>

    <section class="doc-section" id="cap11-tests" data-doc-section data-title="El runner de pruebas (tests/run.php)">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap11-tests">#</a>El runner de pruebas (<code>tests/run.php</code>)</h3>
        <p>
            El proyecto no depende de PHPUnit para probar el núcleo: <code>tests/run.php</code> es un
            runner mínimo, sin dependencias, que configura una base <strong>SQLite en memoria</strong>,
            crea tablas de prueba y ejecuta comprobaciones sobre <code>Lib\Core\Model</code>,
            <code>Validator</code>, <code>Request</code> y las clases de soporte. Se ejecuta
            con:
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Terminal — ejecutar las pruebas</span>
                <span class="doc-code-lang">bash</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code># En el host (si tienes PHP con pdo_sqlite)
php tests/run.php

# Dentro del contenedor del proyecto
docker exec php_apache_gupadis php /var/www/html/tests/run.php

# Salida esperada
63 comprobaciones, 0 fallidas</code></pre>
        </div>

        <p>
            El runner devuelve código de salida <code>0</code> si todo pasa y <code>1</code> si algo falla,
            así que puedes encadenarlo en un script de despliegue:
            <code>php tests/run.php &amp;&amp; docker compose up -d</code>.
        </p>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Qué comprueba</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Escritura y lectura</td>
                        <td><code>insert()</code>, <code>find()</code>, filtrado de <code>$fillable</code>,
                            <code>get()</code>, <code>first()</code>, <code>pluck()</code> y <code>count()</code>.</td>
                    </tr>
                    <tr>
                        <td>Condiciones</td>
                        <td><code>where()</code> con operadores (<code>&gt;</code>, <code>LIKE</code>),
                            <code>whereIn()</code>, <code>orWhere()</code>, <code>whereNull()</code> y
                            <code>whereNotNull()</code>.</td>
                    </tr>
                    <tr>
                        <td>Regresión del operador</td>
                        <td><code>update()</code> y <code>delete()</code> con <code>where()</code> encadenado
                            respetan el operador y no tocan filas que no cumplen la condición.</td>
                    </tr>
                    <tr>
                        <td>Seguridad</td>
                        <td>Un <code>orderBy()</code> con identificador inválido lanza excepción; un operador
                            no permitido también. La tabla sigue existiendo tras el intento.</td>
                    </tr>
                    <tr>
                        <td>Agrupación y paginación</td>
                        <td><code>groupBy()</code> + <code>select()</code> y el objeto de
                            <code>paginate()</code> (<code>items()</code>, <code>total()</code>,
                            <code>lastPage()</code>, <code>toArray()</code>).</td>
                    </tr>
                    <tr>
                        <td>Relaciones</td>
                        <td><code>belongsTo()</code> y <code>hasMany()</code> con <code>with()</code>
                            (eager loading) y el caso sin resultados.</td>
                    </tr>
                    <tr>
                        <td>Casts y timestamps</td>
                        <td>Conversión de <code>int</code>/<code>bool</code> al guardar y relleno automático de
                            <code>created_at</code>/<code>updated_at</code>.</td>
                    </tr>
                    <tr>
                        <td>SQL crudo</td>
                        <td><code>raw()</code> devuelve filas en un <code>SELECT</code> y filas afectadas en un
                            <code>UPDATE</code>.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-h4">Añadir una comprobación</div>
        <p>
            Al final del archivo están las funciones <code>check()</code> y <code>expectException()</code>.
            Define tus modelos de prueba junto a los existentes (con una tabla temporal) y añade líneas:
        </p>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">tests/run.php — patrón de una prueba nueva</span>
                <span class="doc-code-lang">php</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code>// Modelo de prueba (tabla creada con $pdo-&gt;exec antes de las pruebas)
class TestProducto extends Model
{
    protected string $table = 't_productos';
    protected array $fillable = ['nombre', 'precio'];
    protected array $casts = ['precio' =&gt; 'float'];
    protected bool $timestamps = false;
}

// DDL
$pdo-&gt;exec('CREATE TABLE t_productos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT,
    precio REAL
)');

// Comprobaciones
$id = (new TestProducto())-&gt;insert(['nombre' =&gt; 'Camisa', 'precio' =&gt; 10]);
check('insert devuelve id', $id === 1);
check('where con LIKE', TestProducto::query()-&gt;where('nombre', 'cam%', 'LIKE')-&gt;count() === 1);
expectException('operador invalido', fn () =&gt; TestProducto::query()-&gt;where('precio', 1, '==')-&gt;get());</code></pre>
        </div>

        <div class="doc-note">
            <span class="doc-note-title"><i class="fas fa-circle-info mr-1"></i>¿Por qué SQLite?</span>
            <p>SQLite en memoria no necesita servidor ni credenciales, y el runner configura
                <code>Database::configure()</code> directamente. Así las pruebas del ORM no tocan la base
                real. Para probar tu módulo contra MySQL, usa el panel o un script de humo como el del
                Capítulo 12.</p>
        </div>
    </section>

    <section class="doc-section" id="cap11-debug" data-doc-section data-title="Depurar: errores, log y navegador">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap11-debug">#</a>Depurar: errores, log y navegador</h3>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Herramienta</th>
                        <th>Cómo se usa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>APP_DEBUG</code></td>
                        <td>En <code>.env</code>: <code>true</code> muestra errores en pantalla;
                            <code>false</code> los oculta y los manda al log. <strong>Nunca</strong> dejes
                            <code>true</code> en producción.</td>
                    </tr>
                    <tr>
                        <td><code>php -l</code></td>
                        <td>Comprueba sintaxis sin ejecutar:
                            <code>php -l App/Controllers/ProductosController.php</code>.</td>
                    </tr>
                    <tr>
                        <td><code>error_log()</code></td>
                        <td>Escribe en el log del servidor sin romper la respuesta:
                            <code>error_log('Producto: ' . json_encode($data));</code>.</td>
                    </tr>
                    <tr>
                        <td>DevTools → Network</td>
                        <td>Para AJAX: mira el código de estado, la pestaña <em>Response</em> (JSON con
                            <code>message</code> y <code>errors</code>) y el <em>Payload</em> (que
                            <code>_token</code> viaje).</td>
                    </tr>
                    <tr>
                        <td>Log de Apache/PHP</td>
                        <td>En el contenedor: <code>docker logs php_apache_gupadis</code>. Ahí aparecen los
                            <code>error_log</code> y los errores fatales.</td>
                    </tr>
                    <tr>
                        <td>Sistema → Rutas</td>
                        <td>Confirma que la ruta existe, su método, su nombre y si está habilitada.</td>
                    </tr>
                    <tr>
                        <td><code>composer dump-autoload</code></td>
                        <td>Solo si una clase nueva no se encuentra. El autoload PSR-4 ya resuelve
                            <code>App\</code>, <code>Lib\</code> y <code>System\</code>.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-code">
            <div class="doc-code-header">
                <span class="doc-code-file">Inspeccionar una petición desde la terminal</span>
                <span class="doc-code-lang">bash</span>
                <button type="button" class="doc-copy" data-doc-copy>Copiar</button>
            </div>
            <pre><code># Una ruta JSON de tu módulo (con la cabecera que exige SoloAjax)
curl -i -H "Accept: application/json" \
     -H "X-Requested-With: XMLHttpRequest" \
     "http://localhost:8095/join/access/dashboard/Area-of-work/productos/buscar?q=cam"

# Ver el código de estado y las cabeceras de una página
curl -I "http://localhost:8095/join/access/dashboard/Area-of-work/system"</code></pre>
        </div>

        <div class="doc-warn">
            <span class="doc-warn-title"><i class="fas fa-triangle-exclamation mr-1"></i>No depures con <code>var_dump</code> en JSON</span>
            <p>Si tu endpoint responde JSON y haces <code>var_dump()</code> antes del <code>return</code>,
                romperás el JSON y el JavaScript mostrará «Error al procesar». Usa <code>error_log()</code>
                o devuelve los datos de depuración en una clave propia (<code>'debug' =&gt; …</code>) y
                quítala después.</p>
        </div>
    </section>

    <section class="doc-section" id="cap11-errores" data-doc-section data-title="Errores comunes: síntoma, causa y solución">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap11-errores">#</a>Errores comunes: síntoma, causa y solución</h3>

        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Síntoma</th>
                        <th>Causa habitual</th>
                        <th>Solución</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>404 en una ruta que sí escribiste</td>
                        <td>Usaste una cadena como acción fuera de un grupo <code>Route::controller()</code>:
                            el router solo resuelve <code>'metodo'</code> dentro de ese grupo.</td>
                        <td>Envuélvela en <code>Route::controller(MiController::class, function () { … })</code>
                            o pasa <code>[MiController::class, 'metodo']</code> / un closure.</td>
                    </tr>
                    <tr>
                        <td>404 solo en algunas rutas</td>
                        <td>La ruta está deshabilitada en <strong>Sistema → Rutas y enlaces</strong>, o el
                            middleware está apagado y la petición cae en otra coincidencia.</td>
                        <td>Revisa el interruptor y el nombre de la ruta; <code>Route::isEnabled('nombre')</code>
                            te lo confirma.</td>
                    </tr>
                    <tr>
                        <td><code>Class "App\..." not found</code></td>
                        <td>Namespace o ruta de archivo no coinciden (PSR-4), o falta
                            <code>composer dump-autoload</code>.</td>
                        <td>El archivo debe estar en <code>App/Controllers/ProductosController.php</code> con
                            <code>namespace App\Controllers;</code> y el nombre de clase exacto.</td>
                    </tr>
                    <tr>
                        <td><code>View [...] not found</code></td>
                        <td>La vista no existe o la ruta con puntos no coincide con la carpeta.</td>
                        <td><code>'dashboard.productos.index'</code> → <code>App/views/dashboard/productos/index.php</code>
                            (o <code>System/views/…</code>).</td>
                    </tr>
                    <tr>
                        <td>403 «Token inválido» en un POST</td>
                        <td>Falta <code>_token</code> o se generó antes de regenerar la sesión (login).</td>
                        <td>Incluye <code>csrf_field()</code> en el formulario o
                            <code>_token: csrf_token()</code> en el AJAX; recarga tras iniciar sesión.</td>
                    </tr>
                    <tr>
                        <td>405 «Método no permitido»</td>
                        <td>El endpoint está registrado como <code>POST</code> y se llamó por <code>GET</code>
                            (o al revés).</td>
                        <td>Ajusta el verbo en <code>routes/app.php</code> o en la llamada.</td>
                    </tr>
                    <tr>
                        <td>422 con modal de errores</td>
                        <td>El <code>FormRequest</code> rechazó datos: el router responde
                            <code>errors</code> y el panel abre el modal automáticamente.</td>
                        <td>Corrige el campo que indica <code>errors</code>; revisa <code>messages()</code>
                            para el texto exacto.</td>
                    </tr>
                    <tr>
                        <td>500 «Update requires conditions»</td>
                        <td>Llamaste <code>update()</code> sin condiciones y sin <code>where()</code>
                            encadenado. El ORM lo bloquea para no actualizar toda la tabla.</td>
                        <td>Usa <code>where('id', $id)-&gt;update([...])</code> o
                            <code>update([...], ['id' =&gt; $id])</code>. Igual para <code>delete()</code>.</td>
                    </tr>
                    <tr>
                        <td>500 «Unsupported operator» / «Invalid identifier»</td>
                        <td>Pasaste un operador fuera de la lista o un nombre de columna con caracteres
                            raros. Es la protección contra inyección.</td>
                        <td>Usa operadores válidos (<code>=</code>, <code>!=</code>, <code>&lt;</code>,
                            <code>&gt;</code>, <code>LIKE</code>…) y nombres de columna reales.</td>
                    </tr>
                    <tr>
                        <td><code>SQLSTATE[42S22] Unknown column</code></td>
                        <td>La columna no existe en la tabla o el <code>$fillable</code> del modelo no la
                            incluye (el dato se descarta antes del INSERT).</td>
                        <td>Compara con <strong>Sistema → Base de datos</strong> y añade la columna a
                            <code>$fillable</code> si debe guardarse.</td>
                    </tr>
                    <tr>
                        <td>503 con página de mantenimiento</td>
                        <td>El modo mantenimiento está activo en <code>settings.maintenance_mode</code>.</td>
                        <td>Entra al panel (no se bloquea) y desactívalo en el resumen o en
                            <strong>Página de inicio</strong>.</td>
                    </tr>
                    <tr>
                        <td>«Middleware [X] not found»</td>
                        <td>El nombre del middleware no coincide con la clase
                            (<code>System\Middleware\X</code> o <code>App\Middleware\X</code>).</td>
                        <td>Revisa mayúsculas y que el archivo/clase existan; el nombre en la ruta va sin
                            namespace, solo la clase.</td>
                    </tr>
                    <tr>
                        <td>La sesión se cierra sola</td>
                        <td><code>SESSION_LIFETIME</code> (minutos) expiró o cambió la cookie.</td>
                        <td>Ajusta <code>SESSION_LIFETIME</code> en <code>.env</code> (minutos); si cambias de
                            dominio o borras la cookie <code>PHPSESSID</code>, la sesi&oacute;n se pierde.</td>
                    </tr>
                    <tr>
                        <td>Cambios que no aparecen</td>
                        <td>Caché de assets por <code>?v=filemtime</code> o una ruta deshabilitada.</td>
                        <td>Recarga forzada (Ctrl+F5); confirma que el archivo se guardó donde se carga.</td>
                    </tr>
                    <tr>
                        <td>El PDF de la documentación falla</td>
                        <td>Falta <code>resources/dompdf/</code> o falta un capítulo/vista.</td>
                        <td>Comprueba el log; el controlador captura la excepción y responde 500 con el
                            mensaje en modo debug.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="doc-tip">
            <span class="doc-tip-title"><i class="fas fa-lightbulb mr-1"></i>El orden de diagnóstico que funciona</span>
            <p>1) ¿La ruta existe y está habilitada? (Sistema → Rutas). 2) ¿El método HTTP coincide?
                3) ¿Hay 403/422 con mensaje? (DevTools → Response). 4) ¿Un 500? (log del contenedor).
                5) Recién entonces revisa el código del controlador o del modelo.</p>
        </div>
    </section>

    <section class="doc-section" id="cap11-checklist" data-doc-section data-title="Checklist antes de dar por terminado un módulo">
        <h3 class="doc-section-title"><a class="doc-anchor" href="#cap11-checklist">#</a>Checklist antes de dar por terminado un módulo</h3>
        <ul class="doc-check">
            <li><code>php -l</code> sin errores en cada archivo PHP nuevo.</li>
            <li><code>php tests/run.php</code> termina con «0 fallidas».</li>
            <li>La ruta aparece en <strong>Sistema → Rutas</strong> y su nombre es único.</li>
            <li>La tabla del modelo existe y tiene las columnas esperadas (comprobado en
                <strong>Sistema → Base de datos</strong>).</li>
            <li>El formulario envía <code>_token</code>; un POST sin token devuelve 403.</li>
            <li>Los datos inválidos devuelven 422 y el modal muestra el mensaje correcto.</li>
            <li>El guardado se ve en <strong>Base de datos</strong> con los tipos esperados.</li>
            <li>El listado escapa la salida con <code>e()</code> y no imprime datos sin filtrar.</li>
            <li>Si el módulo tiene rutas públicas, el mantenimiento las bloquea con 503.</li>
        </ul>

        <div class="doc-chapter-actions mt-3">
            <a class="doc-chip" href="#cap12"><i class="fas fa-arrow-right mr-1"></i>Siguiente: tutorial CRUD</a>
            <a class="doc-chip" target="_blank" rel="noopener"
                href="<?= e((route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') . '?cap=cap11') ?>">
                <i class="fas fa-file-pdf mr-1"></i>Descargar este capítulo (PDF)
            </a>
        </div>
    </section>
</section>
