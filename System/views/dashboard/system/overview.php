<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= e($phpVersion) ?></h3>
                <p>Versión de PHP</p>
            </div>
            <div class="icon">
                <i class="fab fa-php"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box <?= $appEnv === 'production' ? 'bg-success' : 'bg-warning' ?>">
            <div class="inner">
                <h3><?= e($appEnv) ?></h3>
                <p>Entorno</p>
            </div>
            <div class="icon">
                <i class="fas fa-cogs"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= (int) $tableCount ?></h3>
                <p>Tablas en <?= e((string) $database) ?></p>
            </div>
            <div class="icon">
                <i class="fas fa-database"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box <?= $maintenance ? 'bg-danger' : 'bg-success' ?>">
            <div class="inner">
                <h3 id="maintenance-state"><?= $maintenance ? 'ON' : 'OFF' ?></h3>
                <p>Modo mantenimiento</p>
            </div>
            <div class="icon">
                <i class="fas fa-tools"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Entorno del sistema</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr>
                            <th style="width: 40%">Aplicación</th>
                            <td><?= e($appName) ?></td>
                        </tr>
                        <tr>
                            <th>URL</th>
                            <td><?= e($appUrl) ?></td>
                        </tr>
                        <tr>
                            <th>Depuración</th>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="debug-toggle" <?= $appDebug ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="debug-toggle"><?= $appDebug ? 'Activada' : 'Desactivada' ?></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Servidor</th>
                            <td><?= e($server) ?></td>
                        </tr>
                        <tr>
                            <th>Sistema operativo</th>
                            <td><?= e($os) ?></td>
                        </tr>
                        <tr>
                            <th>Ruta base</th>
                            <td><code><?= e($basePath) ?></code></td>
                        </tr>
                        <tr>
                            <th>Página de inicio</th>
                            <td><span class="badge badge-info"><?= e($homeRoute) ?></span></td>
                        </tr>
                        <tr>
                            <th>Rutas registradas</th>
                            <td><?= (int) $routeCount ?></td>
                        </tr>
                        <tr>
                            <th>Usuarios registrados</th>
                            <td><?= $userCount === null ? '—' : (int) $userCount ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-outline <?= $maintenance ? 'card-danger' : 'card-success' ?>">
            <div class="card-header">
                <h3 class="card-title">Modo mantenimiento</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Cuando está activado, todas las URLs públicas responden con la página de mantenimiento (503).
                    El panel de administración permanece accesible. La depuración (APP_DEBUG) se controla con el interruptor de la tabla "Entorno del sistema".
                </p>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="maintenance-toggle" <?= $maintenance ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="maintenance-toggle">Sitio en mantenimiento</label>
                </div>
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Accesos rápidos del sistema</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <i class="fas fa-route mr-2"></i>
                        <a href="<?= \lib\Route::route('system_routes') ?>">Rutas y enlaces del sitio</a>
                        <span class="float-right text-muted">Habilitar / deshabilitar</span>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-shield-alt mr-2"></i>
                        <a href="<?= \lib\Route::route('system_middleware') ?>">Middlewares</a>
                        <span class="float-right text-muted">Control de filtros</span>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-home mr-2"></i>
                        <a href="<?= \lib\Route::route('system_home') ?>">Página de inicio</a>
                        <span class="float-right text-muted"><?= e($homeRoute) ?></span>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-database mr-2"></i>
                        <a href="<?= \lib\Route::route('system_database') ?>">Base de datos</a>
                        <span class="float-right text-muted"><?= (int) $tableCount ?> tablas</span>
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-book mr-2"></i>
                        <a href="<?= \lib\Route::route('system_docs') ?>">Documentación</a>
                        <span class="float-right text-muted">Cómo construir con el sistema</span>
                    </li>
                </ul>
            </div>
        </div>

        <?php if ($maintenance) { ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                El modo mantenimiento está activado: el frontend responde con 503.
            </div>
        <?php } ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('change', '#maintenance-toggle', function() {
        var $el = $(this);
        var enabled = $el.is(':checked') ? '1' : '0';

        $.ajax({
            url: "<?= \lib\Route::route('system_maintenance_toggle') ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                enabled: enabled,
                _token: "<?= csrf_token() ?>"
            },
            success: function(res) {
                if (res.success) {
                    $('#maintenance-state').text(enabled === '1' ? 'ON' : 'OFF');
                    showNotification('success', enabled === '1' ?
                        'Modo mantenimiento activado.' :
                        'Modo mantenimiento desactivado.');
                } else {
                    showNotification('error', res.message || 'No se pudo cambiar el modo mantenimiento.');
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

    $(document).on('change', '#debug-toggle', function() {
        var $el = $(this);
        var enabled = $el.is(':checked') ? '1' : '0';

        $.ajax({
            url: "<?= \lib\Route::route('system_debug_toggle') ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                enabled: enabled,
                _token: "<?= csrf_token() ?>"
            },
            success: function(res) {
                if (res.success) {
                    $('label[for="debug-toggle"]').text(enabled === '1' ? 'Activada' : 'Desactivada');
                    showNotification('success', enabled === '1' ?
                        'Modo depuración activado (APP_DEBUG=true). Recarga para ver detalles de errores.' :
                        'Modo depuración desactivado (APP_DEBUG=false).');
                } else {
                    showNotification('error', res.message || 'No se pudo cambiar el modo depuración.');
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
