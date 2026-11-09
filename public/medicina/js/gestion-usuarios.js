(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cfg = window.GestionUsuarios;
        if (!cfg || typeof jQuery === 'undefined') {
            return;
        }

        var $ = jQuery;
        var $form = $('#form-usuario');
        var $modal = $('#modal-usuario');
        var editId = null;
        var deleteId = null;

        function notify(type, message) {
            if (typeof window.showNotification === 'function') {
                window.showNotification(type, message);
            } else {
                window.alert(message);
            }
        }

        function errorMessage(xhr) {
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }
            return 'No se pudo procesar la solicitud.';
        }

        function openCreate() {
            editId = null;
            $form[0].reset();
            $('#usuario-id').val('');
            $('#modal-usuario-title').text('Nuevo usuario');
            $('#usuario-password, #usuario-password-confirmation').prop('required', true);
            $('#usuario-password-help').text('Mínimo 6 caracteres.');
            $modal.modal('show');
        }

        function openEdit(data) {
            editId = Number(data.id);
            $form[0].reset();
            $('#usuario-id').val(editId);
            $('#usuario-ci').val(data.ci || '');
            $('#usuario-nombre').val(data.nombre || '');
            $('#usuario-apellido').val(data.apellido || '');
            $('#usuario-telefono').val(data.telefono || '');
            $('#usuario-rol').val(String(data.idrol));
            $('#usuario-usuario').val(data.usuario || '');
            $('#usuario-correo').val(data.correo || '');
            $('#usuario-password, #usuario-password-confirmation').prop('required', false);
            $('#usuario-password-help').text('Déjala vacía para conservar la contraseña actual.');
            $('#modal-usuario-title').text('Editar usuario');
            $modal.modal('show');
        }

        $('#btn-nuevo-usuario').on('click', openCreate);

        $(document).on('click', '.js-editar-usuario', function () {
            var data;

            try {
                data = JSON.parse($(this).attr('data-usuario'));
            } catch (error) {
                notify('error', 'No se pudieron leer los datos del usuario.');
                return;
            }

            openEdit(data);
        });

        $form.on('submit', function (event) {
            event.preventDefault();

            var $button = $('#btn-guardar-usuario').prop('disabled', true);
            var url = editId === null
                ? cfg.urls.guardar
                : cfg.urls.actualizar.replace(/0$/, String(editId));

            $.post(url, $form.serialize())
                .done(function (response) {
                    if (response && response.success) {
                        $modal.modal('hide');
                        notify('success', response.message || 'Usuario guardado correctamente.');
                        window.setTimeout(function () {
                            window.location.reload();
                        }, 700);
                        return;
                    }

                    notify('error', (response && response.message) || 'No se pudo guardar el usuario.');
                })
                .fail(function (xhr) {
                    notify('error', errorMessage(xhr));
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });

        $(document).on('change', '.js-estado-usuario', function () {
            var $checkbox = $(this);
            var enabled = $checkbox.is(':checked');
            var id = Number($checkbox.attr('data-id'));
            var nombre = $checkbox.attr('data-nombre') || 'este usuario';
            var accion = enabled ? 'activar' : 'desactivar';

            if (!window.confirm('¿Deseas ' + accion + ' a ' + nombre + '?')) {
                $checkbox.prop('checked', !enabled);
                return;
            }

            $.post(cfg.urls.estado.replace(/0$/, String(id)), {
                _token: cfg.token,
                enabled: enabled ? 1 : 0
            }).done(function (response) {
                if (response && response.success) {
                    notify('success', response.message || 'Estado actualizado.');
                    return;
                }

                $checkbox.prop('checked', !enabled);
                notify('error', (response && response.message) || 'No se pudo cambiar el estado.');
            }).fail(function (xhr) {
                $checkbox.prop('checked', !enabled);
                notify('error', errorMessage(xhr));
            });
        });

        $(document).on('click', '.js-eliminar-usuario', function () {
            deleteId = Number($(this).attr('data-id'));
            $('#eliminar-usuario-nombre').text($(this).attr('data-nombre') || '');
            $('#modal-eliminar-usuario').modal('show');
        });

        $('#btn-confirmar-eliminar-usuario').on('click', function () {
            if (deleteId === null) {
                return;
            }

            var $button = $(this).prop('disabled', true);
            var id = deleteId;

            $.post(cfg.urls.eliminar.replace(/0$/, String(id)), {
                _token: cfg.token
            }).done(function (response) {
                if (response && response.success) {
                    $('#modal-eliminar-usuario').modal('hide');
                    notify('success', response.message || 'Usuario eliminado correctamente.');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 700);
                    return;
                }

                notify('error', (response && response.message) || 'No se pudo eliminar el usuario.');
            }).fail(function (xhr) {
                notify('error', errorMessage(xhr));
            }).always(function () {
                $button.prop('disabled', false);
            });
        });

        function filtrar() {
            var texto = String($('#filtro-buscar').val() || '').toLowerCase();
            var rol = String($('#filtro-rol').val() || '');

            $('#tabla-usuarios tbody tr').each(function () {
                var $row = $(this);

                if (!$row.attr('data-id')) {
                    return;
                }

                var coincideTexto = texto === '' || $row.text().toLowerCase().indexOf(texto) !== -1;
                var coincideRol = rol === '' || String($row.attr('data-rol')) === rol;

                $row.toggle(coincideTexto && coincideRol);
            });
        }

        $('#filtro-buscar').on('input', filtrar);
        $('#filtro-rol').on('change', filtrar);
    });
})();
