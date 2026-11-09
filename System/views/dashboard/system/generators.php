<?php
/**
 * Autogestion del CMS: explorador de codigo + generadores.
 *
 * Explorar: lista classes de App/ y muestra sus metodos con el codigo real.
 * Crear: genera archivos NUEVOS en App/ (nunca sobreescribe).
 */
?>
<style>
    .exp-class-item {
        cursor: pointer;
    }

    .exp-class-item.active {
        background: #0062cc;
        border-color: #0062cc;
        color: #fff;
    }

    .exp-class-item.active .text-muted {
        color: #cfe2ff !important;
    }

    .exp-method {
        border: 1px solid #dee2e6;
        border-radius: .3rem;
        margin-bottom: .5rem;
    }

    .exp-method .exp-method-head {
        cursor: pointer;
        padding: .5rem .75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8f9fa;
    }

    .exp-method .exp-method-head:hover {
        background: #e9ecef;
    }

    .exp-method .exp-method-body {
        padding: .75rem;
        border-top: 1px solid #eee;
    }

    .exp-method pre {
        background: #1e2835;
        color: #d7e0ea;
        border-radius: .3rem;
        padding: .7rem;
        font-size: .72rem;
        line-height: 1.45;
        max-height: 320px;
        overflow: auto;
        margin: 0;
    }

    .gen-preview pre {
        background: #1e2835;
        color: #d7e0ea;
        border-radius: .3rem;
        padding: .7rem;
        font-size: .72rem;
        line-height: 1.45;
        max-height: 360px;
        overflow: auto;
        margin: 0;
    }

    .gen-hint {
        background: #eef6ff;
        border-left: 3px solid #17a2b8;
        padding: .5rem .75rem;
        border-radius: 0 .3rem .3rem 0;
        font-size: .85rem;
    }

    .badge-vis {
        font-size: .65rem;
    }

    /*
     * Reglas de tabs de Bootstrap 4. El CSS purgado de AdminLTE no trae
     * .tab-pane/.tab-content, y sin ellas los paneles se apilan y "Crear"
     * queda muy abajo, detras de todo el explorador.
     */
    .tab-content > .tab-pane {
        display: none;
    }

    .tab-content > .active {
        display: block;
    }

    .fade {
        transition: opacity .15s linear;
    }

    .fade:not(.show) {
        opacity: 0;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-magic mr-1"></i> Autogestión del CMS</h3>
    </div>
    <div class="card-body pb-0">
        <ul class="nav nav-tabs" id="gen-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-explore-tab" data-toggle="tab" href="#tab-explore" role="tab">
                    <i class="fas fa-search mr-1"></i> Explorar código
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-middleware-tab" data-toggle="tab" href="#tab-middleware" role="tab">
                    <i class="fas fa-shield-alt mr-1"></i> Crear Middleware
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-request-tab" data-toggle="tab" href="#tab-request" role="tab">
                    <i class="fas fa-check-double mr-1"></i> Crear FormRequest
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-controller-tab" data-toggle="tab" href="#tab-controller" role="tab">
                    <i class="fas fa-code mr-1"></i> Crear Controlador
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body pt-3">
        <div class="tab-content" id="gen-tabs-content">

            <!-- ===================== EXPLORAR ===================== -->
            <div class="tab-pane fade show active" id="tab-explore" role="tabpanel">
                <div class="mb-3">
                    <span class="text-muted small mr-2">Ver:</span>
                    <div class="btn-group btn-group-sm" id="exp-type-switch">
                        <button type="button" class="btn btn-primary exp-type-btn" data-type="controller">
                            Controllers <span class="badge badge-light" id="cnt-controller">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary exp-type-btn" data-type="middleware">
                            Middlewares <span class="badge badge-light" id="cnt-middleware">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary exp-type-btn" data-type="request">
                            FormRequests <span class="badge badge-light" id="cnt-request">0</span>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-outline card-secondary">
                            <div class="card-header py-2">
                                <h6 class="mb-0">Clases en <code id="exp-dir">App/Controllers/</code></h6>
                            </div>
                            <div class="list-group list-group-flush" id="exp-class-list" style="max-height:480px;overflow:auto">
                                <div class="list-group-item text-muted small">Cargando…</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div id="exp-detail" style="max-height:540px;overflow:auto">
                            <div class="text-muted text-center py-5">
                                <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                                <p class="mb-0">Selecciona una clase de la lista para ver sus métodos.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================== CREAR MIDDLEWARE ===================== -->
            <div class="tab-pane fade" id="tab-middleware" role="tabpanel">
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mw-name">Nombre de la clase</label>
                            <input type="text" class="form-control gen-input" id="mw-name" data-gen="middleware" placeholder="Ej: VerificarCita" autocomplete="off">
                            <small class="text-muted">Ej: <code>VerificarCita</code> (sin espacios).</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mw-template">Plantilla de <code>handle()</code></label>
                            <select class="form-control gen-input" id="mw-template" data-gen="middleware">
                                <option value="custom">Personalizado (plantilla vacía)</option>
                                <option value="auth">Exigir sesión iniciada</option>
                                <option value="rol">Exigir un rol concreto</option>
                                <option value="permiso">Exigir un permiso del rol</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mw-description">Descripción (docblock)</label>
                            <input type="text" class="form-control gen-input" id="mw-description" data-gen="middleware" placeholder="Opcional" autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="gen-hint mb-3">
                    <code>handle(Request $request)</code> recibe la petición actual (<code>isAjax()</code>, <code>isPost()</code>, <code>input('clave')</code>…)
                    y devuelve <code>true</code> (deja pasar), <code>false</code> o un <code>Response</code> (corta).
                </div>

                <h6>Vista previa del código <span class="small text-muted font-weight-normal" id="mw-file"></span></h6>
                <div class="gen-preview mb-3"><pre><code id="mw-code">Escribe un nombre para ver el código…</code></pre></div>

                <div class="text-right mb-3">
                    <button type="button" class="btn btn-outline-secondary mr-2" id="mw-copy"><i class="fas fa-copy mr-1"></i> Copiar</button>
                    <button type="button" class="btn btn-primary" id="mw-create"><i class="fas fa-file-medical mr-1"></i> Crear en App/Middleware/</button>
                </div>

                <hr>
                <h6>Tus middlewares en <code>App/Middleware/</code></h6>
                <div id="mw-list" class="small text-muted">Cargando…</div>
            </div>

            <!-- ===================== CREAR FORMREQUEST ===================== -->
            <div class="tab-pane fade" id="tab-request" role="tabpanel">
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="rq-name">Nombre de la clase</label>
                            <input type="text" class="form-control gen-input" id="rq-name" data-gen="request" placeholder="Ej: StoreCita" autocomplete="off">
                            <small class="text-muted">Se añade <code>Request</code>: <code>StoreCitaRequest</code>.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="rq-description">Descripción (docblock)</label>
                            <input type="text" class="form-control gen-input" id="rq-description" data-gen="request" placeholder="Opcional" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="d-block">authorize() por defecto</label>
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input gen-input" id="rq-post" data-gen="request" checked>
                            <label class="custom-control-label" for="rq-post">Exigir POST (405)</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input gen-input" id="rq-csrf" data-gen="request" checked>
                            <label class="custom-control-label" for="rq-csrf">Validar token CSRF</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="rq-rules">Reglas (una por línea: <code>'campo' => 'regla'</code>)</label>
                    <textarea class="form-control gen-input" id="rq-rules" data-gen="request" rows="3" spellcheck="false"
                        placeholder="'nombre' => 'required|min:3|max:100',&#10;'email' => 'required|email',"></textarea>
                </div>

                <div class="gen-hint mb-3">
                    El router ejecuta <code>authorize()</code> y <code>rules()</code> <em>antes</em> de tu método del controlador.
                    Si falla, responde 403/422 solo. Tú solo usas <code>$request->validated()</code>.
                    <br>Úsalo así: <code>public function store(<span id="rq-hint-class">MiRequest</span> $request): Response</code>
                </div>

                <h6>Vista previa del código <span class="small text-muted font-weight-normal" id="rq-file"></span></h6>
                <div class="gen-preview mb-3"><pre><code id="rq-code">Escribe un nombre para ver el código…</code></pre></div>

                <div class="text-right mb-3">
                    <button type="button" class="btn btn-outline-secondary mr-2" id="rq-copy"><i class="fas fa-copy mr-1"></i> Copiar</button>
                    <button type="button" class="btn btn-primary" id="rq-create"><i class="fas fa-file-medical mr-1"></i> Crear en App/Requests/</button>
                </div>

                <hr>
                <h6>Tus FormRequests en <code>App/Requests/</code></h6>
                <div id="rq-list" class="small text-muted">Cargando…</div>
            </div>

            <!-- ===================== CREAR CONTROLADOR ===================== -->
            <div class="tab-pane fade" id="tab-controller" role="tabpanel">
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="ct-name">Nombre de la clase</label>
                            <input type="text" class="form-control gen-input" id="ct-name" data-gen="controller" placeholder="Ej: PortalCita" autocomplete="off">
                            <small class="text-muted">Se añade <code>Controller</code>: <code>PortalCitaController</code>.</small>
                        </div>
                        <div class="form-group">
                            <label for="ct-description">Descripción (docblock)</label>
                            <input type="text" class="form-control gen-input" id="ct-description" data-gen="controller" placeholder="Opcional" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label>Métodos a incluir</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input gen-input ct-method" id="ct-m-index" value="index" data-gen="controller" checked>
                            <label class="custom-control-label" for="ct-m-index"><code>index()</code> <span class="text-muted small">Listado · GET /recurso</span></label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input gen-input ct-method" id="ct-m-store" value="store" data-gen="controller" checked>
                            <label class="custom-control-label" for="ct-m-store"><code>store()</code> <span class="text-muted small">Alta · POST /recurso</span></label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input gen-input ct-method" id="ct-m-show" value="show" data-gen="controller">
                            <label class="custom-control-label" for="ct-m-show"><code>show()</code> <span class="text-muted small">Detalle · GET /recurso/{id}</span></label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input gen-input ct-method" id="ct-m-update" value="update" data-gen="controller">
                            <label class="custom-control-label" for="ct-m-update"><code>update()</code> <span class="text-muted small">Editar · POST /recurso/{id}</span></label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input gen-input ct-method" id="ct-m-destroy" value="destroy" data-gen="controller">
                            <label class="custom-control-label" for="ct-m-destroy"><code>destroy()</code> <span class="text-muted small">Baja · DELETE /recurso/{id}</span></label>
                        </div>
                    </div>
                </div>

                <h6>Vista previa del código <span class="small text-muted font-weight-normal" id="ct-file"></span></h6>
                <div class="gen-preview mb-3"><pre><code id="ct-code">Escribe un nombre para ver el código…</code></pre></div>

                <div class="text-right mb-3">
                    <button type="button" class="btn btn-outline-secondary mr-2" id="ct-copy"><i class="fas fa-copy mr-1"></i> Copiar</button>
                    <button type="button" class="btn btn-primary" id="ct-create"><i class="fas fa-file-medical mr-1"></i> Crear en App/Controllers/</button>
                </div>

                <hr>
                <h6>Tus controllers en <code>App/Controllers/</code></h6>
                <div id="ct-list" class="small text-muted">Cargando…</div>
            </div>

        </div>
    </div>

    <div class="card-footer text-muted small">
        <i class="fas fa-lock mr-1"></i>
        El archivo solo se crea si aún no existe; si ya hay una clase con ese nombre, te avisará y no la sobreescribe.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var URL_SCAN = "<?= \lib\Route::route('system_generators_scan') ?>";
    var URL_SOURCE = "<?= \lib\Route::route('system_generators_source') ?>";
    var URL_PREVIEW = "<?= \lib\Route::route('system_generators_preview') ?>";
    var URL_CREATE = "<?= \lib\Route::route('system_generators_create') ?>";
    var TOKEN = "<?= csrf_token() ?>";

    var cache = null;
    var currentType = 'controller';
    var previewTimers = {};

    function esc(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    /* ======================= CARGA INICIAL ======================= */

    if (!URL_SCAN || !URL_SOURCE || !URL_PREVIEW || !URL_CREATE) {
        $('#exp-class-list').html('<div class="list-group-item list-group-item-danger small">Error: faltan rutas del sistema. Recarga la página.</div>');
        return;
    }

    function loadScan() {
        $.post(URL_SCAN, { _token: TOKEN }, function(res) {
            if (!res || !res.success) {
                $('#exp-class-list').html('<div class="list-group-item list-group-item-danger small">' + esc((res && res.message) || 'Error al escanear.') + '</div>');
                return;
            }

            cache = res.types;

            $('#cnt-controller').text((cache.controller || []).length);
            $('#cnt-middleware').text((cache.middleware || []).length);
            $('#cnt-request').text((cache.request || []).length);

            renderClassList();
            paintSimpleList('middleware', '#mw-list');
            paintSimpleList('request', '#rq-list');
            paintSimpleList('controller', '#ct-list');
        }, 'json').fail(function(xhr, status) {
            var msg = 'No se pudo cargar el escaneo (' + status + ').';
            if (xhr && xhr.status) { msg += ' HTTP ' + xhr.status; }
            $('#exp-class-list').html('<div class="list-group-item list-group-item-danger small">' + esc(msg) + '</div>');
        });
    }

    function renderClassList() {
        var dirs = { controller: 'App/Controllers/', middleware: 'App/Middleware/', request: 'App/Requests/' };
        $('#exp-dir').text(dirs[currentType] || '');

        var items = (cache && cache[currentType]) || [];
        var html = '';

        if (!items.length) {
            html = '<div class="list-group-item text-muted small">No hay clases de este tipo todavía.</div>';
        } else {
            items.forEach(function(item, i) {
                var badge = item.abstract ? ' <span class="badge badge-secondary badge-vis">base</span>' : '';
                html += '<a href="#" class="list-group-item list-group-item-action exp-class-item" data-i="' + i + '">' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                    '<strong>' + esc(item.class) + badge + '</strong>' +
                    '<span class="badge badge-info">' + item.methods.length + '</span>' +
                    '</div>' +
                    (item.docblock ? '<div class="small text-muted text-truncate">' + esc(item.docblock) + '</div>' : '') +
                    '</a>';
            });
        }

        $('#exp-class-list').html(html);
        $('#exp-detail').html('<div class="text-muted text-center py-5"><i class="fas fa-mouse-pointer fa-2x mb-2"></i><p class="mb-0">Selecciona una clase de la lista.</p></div>');
    }

    /* ======================= EXPLORAR ======================= */

    $('#exp-type-switch').on('click', '.exp-type-btn', function() {
        currentType = $(this).data('type');
        $('#exp-type-switch .exp-type-btn').removeClass('btn-primary active').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        renderClassList();
    });

    $('#exp-class-list').on('click', '.exp-class-item', function(e) {
        e.preventDefault();
        $('#exp-class-list .exp-class-item').removeClass('active');
        $(this).addClass('active');

        var items = (cache && cache[currentType]) || [];
        var item = items[$(this).data('i')];
        if (!item) { return; }

        $('#exp-detail').html('<div class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin"></i> Analizando ' + esc(item.class) + '…</div>');

        $.post(URL_SOURCE, { _token: TOKEN, type: currentType, class: item.class }, function(res) {
            if (!res || !res.success) {
                $('#exp-detail').html('<div class="alert alert-danger mb-0">' + esc((res && res.message) || 'Error al cargar.') + '</div>');
                return;
            }
            renderDetail(res);
            // Llevar el panel al tope para ver los metodos de inmediato.
            $('#exp-detail').scrollTop(0);
        }, 'json').fail(function(xhr) {
            var msg = 'No se pudo cargar el código.';
            if (xhr && xhr.status === 403) { msg += ' (403: sesión o token)'; }
            if (xhr && xhr.status === 404) { msg += ' (404: no encontrada)'; }
            if (xhr && xhr.status === 500) { msg += ' (500: error interno)'; }
            $('#exp-detail').html('<div class="alert alert-danger mb-0">' + esc(msg) + '</div>');
        });
    });

    function renderDetail(res) {
        var h = '';

        h += '<div class="card card-outline card-secondary">';
        h += '<div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center">';
        h += '<h5 class="mb-0"><i class="fas fa-file-code mr-1"></i> ' + esc(res.class) + ' <span class="badge badge-light">' + esc(res.file) + '</span></h5>';
        h += '<button type="button" class="btn btn-sm btn-outline-secondary" id="exp-toggle-all">Expandir / contraer todo</button>';
        h += '</div><div class="card-body">';

        if (!res.methods.length) {
            h += '<p class="text-muted mb-0">Esta clase no tiene métodos detectables.</p>';
        }

        res.methods.forEach(function(m, i) {
            var visColor = m.visibility === 'public' ? 'success' : (m.visibility === 'protected' ? 'warning' : 'secondary');

            h += '<div class="exp-method">';
            h += '<div class="exp-method-head exp-toggle" data-i="' + i + '">';
            h += '<code class="text-truncate">' + esc(m.signature) + '</code>';
            h += '<span class="text-nowrap ml-2">';
            h += '<span class="badge badge-' + visColor + ' badge-vis">' + m.visibility + '</span> ';
            if (m.static) { h += '<span class="badge badge-info badge-vis">static</span> '; }
            h += '<i class="fas fa-chevron-down text-muted"></i></span>';
            h += '</div>';

            h += '<div class="exp-method-body" id="exp-body-' + i + '" style="display:none">';

            if (m.docblock) {
                h += '<p class="small text-muted"><em>' + esc(m.docblock) + '</em></p>';
            }

            if (m.flow && m.flow.length) {
                h += '<div class="mb-2"><strong class="small text-uppercase text-muted d-block">Llama a / usa</strong>';
                m.flow.forEach(function(f) {
                    h += '<code class="d-block small">' + esc(f.target) + '</code>';
                });
                h += '</div>';
            }

            if (m.code) {
                h += '<button type="button" class="btn btn-link btn-sm p-0 mb-1 exp-show-code" data-i="' + i + '"><i class="fas fa-code mr-1"></i>Ver código de ' + esc(m.name) + '()</button>';
                h += '<pre id="exp-code-' + i + '" style="display:none"><code>' + esc(m.code) + '</code></pre>';
            }

            h += '</div></div>';
        });

        h += '</div></div>';
        $('#exp-detail').html(h);
    }

    $('#exp-detail').on('click', '.exp-toggle', function() {
        $('#exp-body-' + $(this).data('i')).slideToggle(100);
    });

    $('#exp-detail').on('click', '.exp-show-code', function() {
        $('#exp-code-' + $(this).data('i')).slideToggle(100);
    });

    $('#exp-detail').on('click', '#exp-toggle-all', function() {
        var $bodies = $('#exp-detail .exp-method-body');
        var hidden = $bodies.filter(':hidden').length > 0;
        $bodies.toggle(hidden);
        $('#exp-detail pre[id^="exp-code-"]').toggle(hidden);
    });

    /* ======================= GENERADORES ======================= */

    function genTypeOf(el) {
        return $(el).data('gen');
    }

    function collect(type) {
        var d = { type: type, _token: TOKEN };

        if (type === 'middleware') {
            d.name = $('#mw-name').val();
            d.template = $('#mw-template').val();
            d.description = $('#mw-description').val();
        } else if (type === 'request') {
            d.name = $('#rq-name').val();
            d.description = $('#rq-description').val();
            d.rules = $('#rq-rules').val();
            d.authorize_post = $('#rq-post').is(':checked') ? '1' : '0';
            d.authorize_csrf = $('#rq-csrf').is(':checked') ? '1' : '0';
        } else {
            d.name = $('#ct-name').val();
            d.description = $('#ct-description').val();
            d.methods = $('.ct-method:checked').map(function() { return this.value; }).get();
        }

        return d;
    }

    function prefix(type) {
        return type === 'middleware' ? 'mw' : (type === 'request' ? 'rq' : 'ct');
    }

    function refreshPreview(type) {
        var p = prefix(type);
        var data = collect(type);
        var $code = $('#' + p + '-code');
        var $file = $('#' + p + '-file');

        if (!data.name) {
            $code.text('Escribe un nombre para ver el código…');
            $file.text('');
            if (type === 'request') { $('#rq-hint-class').text('MiRequest'); }
            return;
        }

        $.post(URL_PREVIEW, data, function(res) {
            if (res && res.success) {
                $code.text(res.code);
                $file.text('→ ' + res.file);
                if (type === 'request') { $('#rq-hint-class').text(res.class); }
            } else {
                $code.text('// ' + ((res && res.message) || 'No se pudo generar.'));
            }
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Error de red al generar.';
            $code.text('// ' + msg);
        });
    }

    $(document).on('input change', '.gen-input', function() {
        var type = genTypeOf(this);
        if (!type) { return; }
        clearTimeout(previewTimers[type]);
        previewTimers[type] = setTimeout(function() { refreshPreview(type); }, 350);
    });

    function onTabShown($link) {
        var id = $link.attr('id') || '';
        if (id.indexOf('middleware') !== -1) { refreshPreview('middleware'); }
        if (id.indexOf('request') !== -1) { refreshPreview('request'); }
        if (id.indexOf('controller') !== -1) { refreshPreview('controller'); }

        // Subir el card al tope para que el contenido de "Crear" quede visible.
        $('html, body').animate({ scrollTop: $('.card-outline.card-primary').offset().top - 70 }, 150);
    }

    if ($.fn.tab) {
        // Bootstrap disponible: el muestra/oculta los paneles con fade.
        $('#gen-tabs a[data-toggle="tab"]').on('shown.bs.tab', function() {
            // Quitar cualquier display:inline residual (ej: de un .hide()
            // previo) que pudiera tapar la regla .active { display:block }.
            $(this.getAttribute('href')).css('display', '');
            onTabShown($(this));
        });
    } else {
        // Fallback sin Bootstrap JS: cambiar las clases y dejar que el CSS
        // inline de esta vista (.tab-pane { display:none } / .active { block })
        // se encargue de ocultar/mostrar (sin estilos inline de jQuery).
        $('#gen-tabs').on('click', 'a[data-toggle="tab"]', function(e) {
            e.preventDefault();
            var $link = $(this);

            $('#gen-tabs .nav-link').removeClass('active');
            $link.addClass('active');

            $('#gen-tabs-content .tab-pane').removeClass('show active');
            $($link.attr('href')).addClass('show active');

            onTabShown($link);
        });
    }

    function paintSimpleList(type, sel) {
        var items = ((cache && cache[type]) || []).filter(function(c) { return !c.abstract; });
        var html = '';

        items.forEach(function(c) {
            var ms = c.methods.map(function(m) { return m.name + '()'; }).join(', ');
            html += '<div><i class="fas fa-file-code text-muted mr-1"></i><strong>' + esc(c.class) + '</strong> ' +
                '<span class="text-muted">' + esc(ms || 'sin métodos') + '</span></div>';
        });

        $(sel).html(html || '<span class="text-muted">Aún no hay clases de este tipo.</span>');
    }

    function bindCopy(btn, codeSel) {
        $(btn).on('click', function() {
            var code = $(codeSel).text();
            if (!code || code.indexOf('Escribe un nombre') === 0 || code.indexOf('//') === 0) {
                showNotification('warning', 'No hay código para copiar todavía.');
                return;
            }
            var $t = $('<textarea>').val(code).appendTo('body').select();
            try { document.execCommand('copy'); showNotification('success', 'Código copiado.'); }
            catch (e) { showNotification('error', 'No se pudo copiar.'); }
            $t.remove();
        });
    }

    bindCopy('#mw-copy', '#mw-code');
    bindCopy('#rq-copy', '#rq-code');
    bindCopy('#ct-copy', '#ct-code');

    function bindCreate(btn, type) {
        $(btn).on('click', function() {
            var $btn = $(this).prop('disabled', true);
            var data = collect(type);

            if (!data.name) {
                showNotification('warning', 'Escribe el nombre de la clase primero.');
                $btn.prop('disabled', false);
                return;
            }

            $.post(URL_CREATE, data, function(res) {
                if (res && res.success) {
                    showNotification('success', res.message || 'Archivo creado.');
                    cache = null;
                    loadScan();
                    refreshPreview(type);
                } else {
                    showNotification('error', (res && res.message) || 'No se pudo crear.');
                }
            }, 'json').fail(function(xhr) {
                var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Error de red.';
                showNotification('error', msg);
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });
    }

    bindCreate('#mw-create', 'middleware');
    bindCreate('#rq-create', 'request');
    bindCreate('#ct-create', 'controller');

    /* Arranque */
    loadScan();
});
</script>
