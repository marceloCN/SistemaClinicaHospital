<?php
/**
 * Base de datos del panel: 3 secciones en pestañas.
 *
 *  - Conexión activa: datos de la conexión actual.
 *  - Tablas: listado de tablas + detalle de columnas.
 *  - Modo consola: ejecuta SQL (SELECT, INSERT, CREATE...) como pgAdmin.
 */
?>
<style>
    #db-tabs .nav-link {
        cursor: pointer;
    }

    .db-console-sql {
        width: 100%;
        min-height: 140px;
        background: #1e2835;
        color: #d7e0ea;
        border: 1px solid #2d3a4a;
        border-radius: .3rem;
        padding: .75rem;
        font-family: Consolas, Monaco, 'Courier New', monospace;
        font-size: .85rem;
        line-height: 1.5;
        resize: vertical;
    }

    .db-console-sql:focus {
        outline: none;
        border-color: #17a2b8;
        box-shadow: 0 0 0 .15rem rgba(23, 162, 184, .25);
    }

    .db-result-box {
        border-radius: .3rem;
        padding: .75rem;
        font-family: Consolas, Monaco, 'Courier New', monospace;
        font-size: .8rem;
        line-height: 1.5;
    }

    .db-result-ok {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .db-result-err {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .db-result-info {
        background: #e2e3e5;
        border: 1px solid #d6d8db;
        color: #383d41;
    }

    .db-result-table {
        max-height: 380px;
        overflow: auto;
        border: 1px solid #dee2e6;
        border-radius: .3rem;
    }

    .db-result-table .table {
        margin-bottom: 0;
        font-size: .8rem;
    }

    /* Reglas de tabs de Bootstrap 4 (el CSS purgado de AdminLTE no las trae). */
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

<ul class="nav nav-tabs" id="db-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="db-conn-tab" data-toggle="tab" href="#db-connection" role="tab">
            <i class="fas fa-plug mr-1"></i> Conexión activa
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="db-tables-tab" data-toggle="tab" href="#db-tables" role="tab">
            <i class="fas fa-table mr-1"></i> Tablas <span class="badge badge-info"><?= count($tables) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="db-console-tab" data-toggle="tab" href="#db-console" role="tab">
            <i class="fas fa-terminal mr-1"></i> Modo consola
        </a>
    </li>
</ul>

<div class="tab-content" id="db-tabs-content">

    <!-- ===================== CONEXIÓN ACTIVA ===================== -->
    <div class="tab-pane fade show active" id="db-connection" role="tabpanel">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Datos de la conexión</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr>
                            <th>Driver</th>
                            <td><?= e((string) ($connection['driver'] ?? '—')) ?></td>
                        </tr>
                        <tr>
                            <th>Servidor</th>
                            <td><?= e((string) ($connection['host'] ?? '—')) ?>:<?= e((string) ($connection['port'] ?? '')) ?></td>
                        </tr>
                        <tr>
                            <th>Base de datos</th>
                            <td><strong><?= e((string) ($connection['database'] ?? '—')) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Usuario</th>
                            <td><?= e((string) ($connection['username'] ?? '—')) ?></td>
                        </tr>
                        <tr>
                            <th>Contraseña</th>
                            <td><?= e((string) ($connection['password'] ?? '—')) ?></td>
                        </tr>
                        <tr>
                            <th>Charset</th>
                            <td><?= e((string) ($connection['charset'] ?? '—')) ?></td>
                        </tr>
                        <tr>
                            <th>Tablas</th>
                            <td><?= count($tables) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small">
                <i class="fas fa-lock mr-1"></i>
                La contraseña está enmascarada. La consola usa esta misma conexión.
            </div>
        </div>
    </div>

    <!-- ===================== TABLAS ===================== -->
    <div class="tab-pane fade" id="db-tables" role="tabpanel">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Tablas en la base de datos</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th>Motor</th>
                            <th class="text-right">Filas aprox.</th>
                            <th class="text-right">Tamaño</th>
                            <th class="text-center" style="width: 90px">Detalle</th>
                            <th class="text-center" style="width: 110px">Consola</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table) { ?>
                            <tr>
                                <td><code><?= e((string) $table['name']) ?></code></td>
                                <td><?= e((string) ($table['engine'] ?? '—')) ?></td>
                                <td class="text-right"><?= $table['rows_estimate'] === null ? '—' : '~' . number_format((int) $table['rows_estimate']) ?></td>
                                <td class="text-right"><?= $table['size_kb'] === null ? '—' : number_format((float) $table['size_kb'], 1) . ' KB' ?></td>
                                <td class="text-center">
                                    <a class="btn btn-xs btn-outline-primary" href="<?= \lib\Route::route('system_database') ?>?table=<?= urlencode((string) $table['name']) ?>">
                                        Ver columnas
                                    </a>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-secondary db-query-table" data-table="<?= e((string) $table['name']) ?>">
                                        <i class="fas fa-terminal"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small">
                El botón <i class="fas fa-terminal"></i> carga un <code>SELECT * FROM "tabla" LIMIT 100;</code> en la pestaña de consola.
            </div>
        </div>

        <?php if ($selected !== '') { ?>
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Columnas de <code><?= e($selected) ?></code></h3>
                    <div class="card-tools">
                        <a class="btn btn-xs btn-outline-secondary" href="<?= \lib\Route::route('system_database') ?>">
                            <i class="fas fa-times mr-1"></i> Cerrar
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Columna</th>
                                <th>Tipo</th>
                                <th class="text-center">Nulo</th>
                                <th class="text-center">Llave</th>
                                <th>Por defecto</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($columns as $column) { ?>
                                <tr>
                                    <td><code><?= e((string) $column['COLUMN_NAME']) ?></code></td>
                                    <td><?= e((string) $column['COLUMN_TYPE']) ?></td>
                                    <td class="text-center"><?= $column['IS_NULLABLE'] === 'YES' ? 'Sí' : 'No' ?></td>
                                    <td class="text-center">
                                        <?php if ($column['COLUMN_KEY'] !== '') { ?>
                                            <span class="badge badge-warning"><?= e((string) $column['COLUMN_KEY']) ?></span>
                                        <?php } else { ?>
                                            —
                                        <?php } ?>
                                    </td>
                                    <td><?= $column['COLUMN_DEFAULT'] === null ? '<span class="text-muted">NULL</span>' : e((string) $column['COLUMN_DEFAULT']) ?></td>
                                    <td><?= e((string) $column['EXTRA']) ?: '—' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- ===================== MODO CONSOLA ===================== -->
    <div class="tab-pane fade" id="db-console" role="tabpanel">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-terminal mr-1"></i> Consola SQL</h3>
                <div class="card-tools">
                    <span class="badge badge-light border"><?= e((string) ($connection['database'] ?? '')) ?></span>
                </div>
            </div>
            <div class="card-body">
                <label class="small font-weight-bold text-muted">COMANDO SQL</label>
                <textarea id="sql-input" class="db-console-sql" spellcheck="false" autocomplete="off"
                    placeholder="SELECT * FROM information_schema.tables LIMIT 10;"></textarea>

                <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                    <span class="small text-muted">
                        <kbd>Ctrl</kbd> + <kbd>Enter</kbd> ejecuta · Varias sentencias separadas por <code>;</code> se ejecutan en orden
                    </span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="sql-clear">
                            <i class="fas fa-eraser mr-1"></i> Limpiar
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="sql-run">
                            <i class="fas fa-play mr-1"></i> Ejecutar acción
                        </button>
                    </div>
                </div>

                <div id="sql-result"></div>

                <hr>
                <label class="small font-weight-bold text-muted">ÚLTIMO RESULTADO</label>
                <div id="sql-table-result"></div>
            </div>
            <div class="card-footer text-muted small">
                <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>
                La consola ejecuta comandos con la conexión activa del sistema. Los cambios (INSERT, UPDATE, DELETE,
                CREATE, DROP...) se aplican de inmediato a la base de datos.
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var URL_QUERY = "<?= \lib\Route::route('system_database_query') ?>";
    var TOKEN = "<?= csrf_token() ?>";
    var running = false;

    function esc(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    /* ======================= TABS (con fallback sin Bootstrap JS) ======================= */

    function onTabShown($link) {
        var id = $link.attr('id') || '';
        if (id.indexOf('console') !== -1) { $('#sql-input').trigger('focus'); }
    }

    if ($.fn.tab) {
        $('#db-tabs a[data-toggle="tab"]').on('shown.bs.tab', function() {
            $(this.getAttribute('href')).css('display', '');
            onTabShown($(this));
        });
    } else {
        $('#db-tabs').on('click', 'a[data-toggle="tab"]', function(e) {
            e.preventDefault();
            var $link = $(this);
            $('#db-tabs .nav-link').removeClass('active');
            $link.addClass('active');
            $('#db-tabs-content .tab-pane').removeClass('show active');
            $($link.attr('href')).addClass('show active');
            onTabShown($link);
        });
    }

    /* ======================= CONSOLA SQL ======================= */

    function renderOk(res) {
        var h = '<div class="db-result-box db-result-ok">';
        h += '<i class="fas fa-check-circle mr-1"></i> <strong>' + esc(res.message) + '</strong>';
        h += ' <span class="text-muted">(comando: ' + esc(res.command) + ' · ' + esc(res.duration) + ' ms)</span>';
        h += '</div>';
        $('#sql-result').html(h);
    }

    function renderError(res) {
        var e = res.error || {};
        var h = '<div class="db-result-box db-result-err">';
        h += '<i class="fas fa-times-circle mr-1"></i> <strong>' + esc(res.message || 'Error al ejecutar el comando.') + '</strong>';
        h += '<div class="mt-2">';
        if (e.sqlstate) { h += '<div><strong>SQLSTATE:</strong> ' + esc(e.sqlstate) + (e.driver_code ? ' (código driver: ' + esc(e.driver_code) + ')' : '') + '</div>'; }
        if (e.detail) { h += '<div><strong>Detalle:</strong> ' + esc(e.detail) + '</div>'; }
        if (e.hint) { h += '<div class="mt-1"><i class="fas fa-lightbulb mr-1"></i><strong>Sugerencia:</strong> ' + esc(e.hint) + '</div>'; }
        h += '</div></div>';
        $('#sql-result').html(h);
    }

    function renderTable(res) {
        if (!res.columns || !res.columns.length || !res.rows || !res.rows.length) {
            var info = '<div class="db-result-box db-result-info">';
            info += '<i class="fas fa-info-circle mr-1"></i> ';
            info += res.success ? esc(res.message) : 'Sin resultado que mostrar.';
            info += '</div>';
            $('#sql-table-result').html(info);
            return;
        }

        var h = '<div class="db-result-table"><table class="table table-striped table-sm"><thead><tr>';
        res.columns.forEach(function(c) { h += '<th>' + esc(c) + '</th>'; });
        h += '</tr></thead><tbody>';

        res.rows.forEach(function(row) {
            h += '<tr>';
            res.columns.forEach(function(c) {
                var v = row[c];
                if (v === null || v === undefined) {
                    h += '<td class="text-muted font-italic">NULL</td>';
                } else {
                    h += '<td>' + esc(v) + '</td>';
                }
            });
            h += '</tr>';
        });

        h += '</tbody></table></div>';
        $('#sql-table-result').html(h);
    }

    function runSql() {
        var sql = $.trim($('#sql-input').val());

        if (!sql) {
            showNotification('warning', 'Escribe un comando SQL primero.');
            return;
        }

        if (running) { return; }
        running = true;
        $('#sql-run').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Ejecutando…');
        $('#sql-result').html('<div class="db-result-box db-result-info"><i class="fas fa-spinner fa-spin mr-1"></i> Ejecutando comando…</div>');

        $.post(URL_QUERY, { _token: TOKEN, sql: sql }, function(res) {
            if (res && res.success) {
                renderOk(res);
                renderTable(res);
            } else {
                renderError(res);
                $('#sql-table-result').html('');
            }
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'Error de red al ejecutar el comando.';
            renderError({ success: false, message: msg, error: (xhr && xhr.responseJSON && xhr.responseJSON.error) || {} });
        }).always(function() {
            running = false;
            $('#sql-run').prop('disabled', false).html('<i class="fas fa-play mr-1"></i> Ejecutar acción');
        });
    }

    $('#sql-run').on('click', runSql);

    $('#sql-clear').on('click', function() {
        $('#sql-input').val('');
        $('#sql-result').html('');
        $('#sql-table-result').html('');
        $('#sql-input').trigger('focus');
    });

    $('#sql-input').on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            runSql();
        }
    });

    /* Botón "Ver en consola" de cada tabla (pestaña Tablas). */
    $(document).on('click', '.db-query-table', function() {
        var table = $(this).data('table');
        $('#sql-input').val('SELECT * FROM "' + String(table).replace(/"/g, '""') + '" LIMIT 100;');

        $('#db-tabs .nav-link').removeClass('active');
        $('#db-console-tab').addClass('active');
        $('#db-tabs-content .tab-pane').removeClass('show active');
        $('#db-console').addClass('show active');

        if ($.fn.tab) {
            $('#db-console-tab').tab('show');
        }

        $('html, body').animate({ scrollTop: $('#db-tabs').offset().top - 70 }, 150);
        runSql();
    });
});
</script>
