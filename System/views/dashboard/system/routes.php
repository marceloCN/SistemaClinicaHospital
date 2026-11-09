<?php
    $webCount = 0;
    $appCount = 0;

    foreach ($routes as $route) {
        if (str_contains((string) $route['origin_file'], 'app.php')) {
            $appCount++;
        } else {
            $webCount++;
        }
    }
?>
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Rutas y enlaces registrados</h3>
                <div class="card-tools">
                    <span class="badge badge-info">Página de inicio: <?= e($homeRoute) ?></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ml-1 route-filter active" data-filter="all">
                        Todas <span class="badge badge-light"><?= count($routes) ?></span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary ml-1 route-filter" data-filter="routes/web.php">
                        web.php <span class="badge badge-light"><?= $webCount ?></span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success ml-1 route-filter" data-filter="routes/app.php">
                        app.php <span class="badge badge-light"><?= $appCount ?></span>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px">Método</th>
                            <th>URL</th>
                            <th>Nombre</th>
                            <th>Archivo</th>
                            <th>Middlewares</th>
                            <th>Área</th>
                            <th class="text-center" style="width: 90px">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $i => $route) {
                            $isApp = str_contains((string) $route['origin_file'], 'app.php');
                        ?>
                            <tr data-origin="<?= e($route['origin_file']) ?>">
                                <td>
                                    <span class="badge badge-<?= $route['method'] === 'GET' ? 'info' : ($route['method'] === 'POST' ? 'success' : 'warning') ?>">
                                        <?= e($route['method']) ?>
                                    </span>
                                </td>
                                <td><code><?= e($route['uri']) ?></code></td>
                                <td>
                                    <?= $route['name'] !== null ? e($route['name']) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <?php if ($isApp) { ?>
                                        <span class="badge badge-success">app.php</span>
                                    <?php } else { ?>
                                        <span class="badge badge-primary">web.php</span>
                                    <?php } ?>
                                    <?php if ($route['origin_line'] !== null) { ?>
                                        <small class="text-muted">línea <?= (int) $route['origin_line'] ?></small>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (empty($route['middleware'])) { ?>
                                        <span class="text-muted">—</span>
                                    <?php } else { ?>
                                        <?php foreach ($route['middleware'] as $mw) { ?>
                                            <span class="badge badge-secondary"><?= e(explode(':', $mw)[0]) ?></span>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if ($route['area'] === 'admin') { ?>
                                        <span class="badge badge-dark">Administración</span>
                                    <?php } else { ?>
                                        <span class="badge badge-light border">Sitio</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($route['area'] === 'admin') { ?>
                                        <i class="fas fa-lock text-muted" title="Ruta protegida del panel"></i>
                                    <?php } else { ?>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox"
                                                class="custom-control-input route-toggle"
                                                id="route-toggle-<?= (int) $i ?>"
                                                data-key="<?= e($route['key']) ?>"
                                                <?= $route['enabled'] ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="route-toggle-<?= (int) $i ?>"></label>
                                        </div>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small">
                <strong>web.php</strong> define las rutas públicas del sitio y
                <strong>app.php</strong> las del panel de administración; la columna Archivo indica en
                qué archivo (y línea) está declarada cada una.
                Al deshabilitar una ruta del sitio, las visitas a esa URL reciben 404 y sus enlaces se ocultan del menú público.
                Las rutas del panel de administración están protegidas.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.route-filter', function() {
        var filter = $(this).data('filter');

        $('.route-filter').removeClass('active');
        $(this).addClass('active');

        $('tr[data-origin]').each(function() {
            var show = filter === 'all' || $(this).data('origin') === filter;
            $(this).toggle(show);
        });
    });

    $(document).on('change', '.route-toggle', function() {
        var $el = $(this);

        $.ajax({
            url: "<?= \lib\Route::route('system_route_toggle') ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                key: $el.data('key'),
                enabled: $el.is(':checked') ? '1' : '0',
                _token: "<?= csrf_token() ?>"
            },
            success: function(res) {
                if (res.success) {
                    showNotification('success', 'Ruta actualizada correctamente.');
                } else {
                    showNotification('error', res.message || 'No se pudo actualizar la ruta.');
                    $el.prop('checked', !$el.is(':checked'));
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ?
                    xhr.responseJSON.message :
                    'Error al procesar la solicitud.';
                showNotification('error', msg);
                $el.prop('checked', !$el.is(':checked'));
            }
        });
    });
});
</script>
