<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Middlewares en uso</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($middlewares)) { ?>
                    <p class="text-muted p-3 mb-0">No hay middlewares aplicados a las rutas registradas.</p>
                <?php } else { ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Middleware</th>
                                <th class="text-center" style="width: 90px">Rutas</th>
                                <th>Aplicado en</th>
                                <th class="text-center" style="width: 90px">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($middlewares as $i => $middleware) { ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-shield-alt mr-1 text-muted"></i>
                                        <strong><?= e($middleware['name']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= (int) $middleware['count'] ?></span>
                                    </td>
                                    <td>
                                        <?php foreach (array_slice($middleware['routes'], 0, 6) as $route) { ?>
                                            <span class="badge badge-light border"><?= e($route) ?></span>
                                        <?php } ?>
                                        <?php if (count($middleware['routes']) > 6) { ?>
                                            <span class="text-muted small">+<?= count($middleware['routes']) - 6 ?> más</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($middleware['protected']) { ?>
                                            <i class="fas fa-lock text-muted" title="Middleware protegido"></i>
                                        <?php } else { ?>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox"
                                                    class="custom-control-input middleware-toggle"
                                                    id="middleware-toggle-<?= (int) $i ?>"
                                                    data-name="<?= e($middleware['name']) ?>"
                                                    <?= $middleware['enabled'] ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="middleware-toggle-<?= (int) $i ?>"></label>
                                            </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
            <div class="card-footer text-muted small">
                Un middleware deshabilitado deja de ejecutarse en todas las rutas donde esté aplicado.
                El middleware de autenticación (Auth) está protegido para no exponer el panel.
                Para generar uno nuevo en <code>App/Middleware/</code>, usa la sección de
                <a href="<?= \lib\Route::route('system_generators') ?>">Generadores</a>.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('change', '.middleware-toggle', function() {
        var $el = $(this);

        $.ajax({
            url: "<?= \lib\Route::route('system_middleware_toggle') ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                name: $el.data('name'),
                enabled: $el.is(':checked') ? '1' : '0',
                _token: "<?= csrf_token() ?>"
            },
            success: function(res) {
                if (res.success) {
                    showNotification('success', 'Middleware actualizado correctamente.');
                } else {
                    showNotification('error', res.message || 'No se pudo actualizar el middleware.');
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
