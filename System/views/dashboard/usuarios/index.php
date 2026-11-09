<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Usuarios del sistema</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-usuario">
                        <i class="fas fa-user-plus mr-1"></i> Nuevo usuario
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px">#</th>
                            <th>Nombre</th>
                            <th>Usuario o correo</th>
                            <th class="text-center" style="width: 100px">Estado</th>
                            <th style="width: 150px">Registrado</th>
                            <th class="text-right" style="width: 160px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario) { ?>
                            <?php $esActual = (int) $usuario['id'] === $currentId; ?>
                            <tr data-id="<?= (int) $usuario['id'] ?>">
                                <td><?= (int) $usuario['id'] ?></td>
                                <td>
                                    <?= e((string) $usuario['name']) ?>
                                    <?php if ($esActual) { ?>
                                        <span class="badge badge-light border ml-1">Tú</span>
                                    <?php } ?>
                                </td>
                                <td><?= e((string) $usuario['email']) ?></td>
                                <td class="text-center">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox"
                                            class="custom-control-input usuario-estado"
                                            id="usuario-estado-<?= (int) $usuario['id'] ?>"
                                            data-id="<?= (int) $usuario['id'] ?>"
                                            <?= (int) $usuario['status'] === 1 ? 'checked' : '' ?>
                                            <?= $esActual ? 'disabled' : '' ?>>
                                        <label class="custom-control-label" for="usuario-estado-<?= (int) $usuario['id'] ?>"></label>
                                    </div>
                                </td>
                                <td>
                                    <?= $usuario['created_at'] !== null ? e(date('d-m-Y H:i', strtotime((string) $usuario['created_at']))) : '—' ?>
                                </td>
                                <td class="text-right">
                                    <button type="button"
                                        class="btn btn-xs btn-info btn-editar"
                                        data-usuario="<?= e(json_encode([
                                            'id' => (int) $usuario['id'],
                                            'name' => (string) $usuario['name'],
                                            'email' => (string) $usuario['email'],
                                        ])) ?>">
                                        <i class="fas fa-pen"></i> Editar
                                    </button>
                                    <button type="button"
                                        class="btn btn-xs btn-danger btn-eliminar"
                                        data-id="<?= (int) $usuario['id'] ?>"
                                        data-nombre="<?= e((string) $usuario['name']) ?>"
                                        <?= $esActual ? 'disabled' : '' ?>>
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (empty($usuarios)) { ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay usuarios registrados.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small">
                El estado desactiva el acceso del usuario al sistema. No puedes desactivar ni eliminar tu propia cuenta
                y siempre debe quedar al menos un usuario activo.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-usuario" tabindex="-1" role="dialog" aria-labelledby="modal-usuario-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="form-usuario" autocomplete="off">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="usuario-id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-usuario-title">Nuevo usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="usuario-name">Nombre</label>
                        <input type="text" class="form-control" id="usuario-name" name="name" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="usuario-email">Usuario o correo</label>
                        <input type="text" class="form-control" id="usuario-email" name="email" maxlength="150" required>
                        <small class="form-text text-muted">Se usa para iniciar sesión (sirve un usuario o un correo).</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="usuario-password">Contraseña</label>
                                <input type="password" class="form-control" id="usuario-password" name="password" autocomplete="new-password">
                                <small class="form-text text-muted" id="usuario-password-help">Mínimo 6 caracteres.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="usuario-password-confirmation">Confirmar contraseña</label>
                                <input type="password" class="form-control" id="usuario-password-confirmation" name="password_confirmation" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-usuario">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var currentId = <?= json_encode((int) $currentId) ?>;
    var csrfToken = "<?= csrf_token() ?>";
    var urlGuardar = "<?= route('usuarios_guardar') ?>";
    var urlActualizar = "<?= route('usuarios_actualizar') ?>";
    var urlEstado = "<?= route('usuarios_estado') ?>";
    var urlEliminar = "<?= route('usuarios_eliminar') ?>";
    var editarId = null;

    function abrirModalCrear() {
        editarId = null;
        $('#form-usuario')[0].reset();
        $('#usuario-id').val('');
        $('#modal-usuario-title').text('Nuevo usuario');
        $('#usuario-password').prop('required', true);
        $('#usuario-password-help').text('Mínimo 6 caracteres.');
        $('#modal-usuario').modal('show');
    }

    function abrirModalEditar(usuario) {
        editarId = usuario.id;
        $('#form-usuario')[0].reset();
        $('#usuario-id').val(usuario.id);
        $('#usuario-name').val(usuario.name);
        $('#usuario-email').val(usuario.email);
        $('#modal-usuario-title').text('Editar usuario');
        $('#usuario-password').prop('required', false);
        $('#usuario-password-help').text('Déjala vacía para conservar la contraseña actual.');
        $('#modal-usuario').modal('show');
    }

    function quitarMensaje() {
        $('#cms-error-modal').remove();
    }

    function notificar(mensaje) {
        if (typeof showNotification === 'function') {
            showNotification('success', mensaje);
        }
    }

    function manejarError(xhr) {
        var mensaje = (xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar la solicitud.';
        if (typeof showNotification === 'function') {
            showNotification('error', mensaje);
        }
    }

    $('#btn-nuevo-usuario').on('click', abrirModalCrear);

    $(document).on('click', '.btn-editar', function() {
        abrirModalEditar(JSON.parse($(this).attr('data-usuario')));
    });

    $('#form-usuario').on('submit', function(e) {
        e.preventDefault();

        var data = $(this).serialize();
        var url = editarId === null ? urlGuardar : urlActualizar.replace('{id}', editarId);

        $.post(url, data, function(res) {
            if (res && res.success) {
                quitarMensaje();
                $('#modal-usuario').modal('hide');
                notificar(res.message || 'Usuario guardado correctamente.');
                setTimeout(function() { window.location.reload(); }, 800);
            }
        }, 'json').fail(manejarError);
    });

    $(document).on('change', '.usuario-estado', function() {
        var el = $(this);
        var id = el.data('id');

        $.post(urlEstado.replace('{id}', id), {
            enabled: el.is(':checked') ? '1' : '0',
            _token: csrfToken
        }, function(res) {
            if (res && res.success) {
                notificar(res.enabled ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.');
            } else {
                el.prop('checked', !el.is(':checked'));
                manejarError({ responseJSON: res });
            }
        }, 'json').fail(function(xhr) {
            el.prop('checked', !el.is(':checked'));
            manejarError(xhr);
        });
    });

    $(document).on('click', '.btn-eliminar', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');

        if (!window.confirm('¿Eliminar al usuario "' + nombre + '"? Esta acción no se puede deshacer.')) {
            return;
        }

        $.post(urlEliminar.replace('{id}', id), {
            _token: csrfToken
        }, function(res) {
            if (res && res.success) {
                notificar(res.message || 'Usuario eliminado correctamente.');
                setTimeout(function() { window.location.reload(); }, 800);
            }
        }, 'json').fail(manejarError);
    });
});
</script>
