<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Página de inicio del sitio</h3>
            </div>
            <form id="home-route-form">
                <div class="card-body">
                    <p class="text-muted">
                        Selecciona la ruta pública que se mostrará cuando un visitante entre a la raíz del sitio (<code>/</code>).
                    </p>

                    <div class="form-group">
                        <label for="home-route">Ruta de inicio</label>
                        <select class="form-control" id="home-route" name="route" <?= empty($options) ? 'disabled' : '' ?>>
                            <?php if (empty($options)) { ?>
                                <option value="">— Aún no hay rutas públicas —</option>
                            <?php } ?>
                            <?php foreach ($options as $option) { ?>
                                <option value="<?= e($option['name']) ?>" <?= $option['name'] === $current ? 'selected' : '' ?>>
                                    <?= e($option['name']) ?> — <?= e($option['uri']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <?php $currentValid = in_array($current, array_column($options, 'name'), true); ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        <?php if (empty($options)) { ?>
                            Aún no has registrado rutas públicas con nombre; aparecerán aquí al definirlas en <code>routes/web.php</code>.
                        <?php } elseif ($currentValid) { ?>
                            Actualmente el inicio es <strong><?= e($current) ?></strong>.
                            Si la ruta elegida está deshabilitada en <em>Rutas y enlaces</em>, el sitio mostrará un 404.
                        <?php } else { ?>
                            No hay una ruta de inicio válida seleccionada. Elige una y guarda.
                        <?php } ?>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" <?= empty($options) ? 'disabled' : '' ?>>
                        <i class="fas fa-save mr-1"></i> Guardar inicio
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Estado del sitio</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr>
                            <th style="width: 45%">Nombre del sitio</th>
                            <td><?= e((string) ($settings['site_name'] ?? config('app.name'))) ?></td>
                        </tr>
                        <tr>
                            <th>Zona horaria</th>
                            <td><?= e((string) ($settings['timezone'] ?? config('app.timezone'))) ?></td>
                        </tr>
                        <tr>
                            <th>Modo mantenimiento</th>
                            <td>
                                <?= ((int) ($settings['maintenance_mode'] ?? 0)) === 1
                                    ? '<span class="badge badge-danger">Activado</span>'
                                    : '<span class="badge badge-success">Desactivado</span>' ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#home-route-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);

        $.ajax({
            url: "<?= \lib\Route::route('system_home_update') ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                route: $('#home-route').val(),
                _token: "<?= csrf_token() ?>"
            },
            success: function(res) {
                if (res.success) {
                    showNotification('success', 'Página de inicio actualizada.');
                } else {
                    showNotification('error', res.message || 'No se pudo actualizar el inicio.');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ?
                    xhr.responseJSON.message :
                    'Error al procesar la solicitud.';
                showNotification('error', msg);
            }
        });
    });
});
</script>
