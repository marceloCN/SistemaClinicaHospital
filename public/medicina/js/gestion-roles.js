(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cfg = window.GestionRoles;
        if (!cfg || typeof jQuery === 'undefined') {
            return;
        }

        var $ = jQuery;
        var $form = $('#form-rol');
        var $modal = $('#modal-rol');
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

        function lockPermissions(locked, checked) {
            $('.js-permiso-rol').each(function () {
                var $checkbox = $(this);
                $checkbox.prop('disabled', locked);

                if (checked) {
                    $checkbox.prop('checked', true);
                }
            });

            $('.js-toggle-grupo').prop('disabled', locked);
            $('#rol-clave').prop('readonly', locked);

            $('#rol-permisos-ayuda').text(
                locked
                    ? 'El rol Administrador conserva todos los permisos automáticamente.'
                    : 'Marca los permisos que tendrá este rol.'
            );
        }

        function openCreate() {
            editId = null;
            $form[0].reset();
            $('#rol-id').val('');
            $('#rol-clave').prop('readonly', false);
            $('.js-permiso-rol').prop('checked', false);
            lockPermissions(false, false);
            $('#modal-rol-title').text('Nuevo rol');
            $modal.modal('show');
        }

        function openEdit(data) {
            editId = Number(data.id);
            $form[0].reset();
            $('#rol-id').val(editId);
            $('#rol-clave').val(data.claverol || '');
            $('#rol-nombre').val(data.nombrerol || '');
            $('.js-permiso-rol').prop('checked', false);

            var asignados = Array.isArray(data.permisos) ? data.permisos : [];
            asignados.forEach(function (idpermiso) {
                $('#permiso-rol-' + Number(idpermiso)).prop('checked', true);
            });

            lockPermissions(Boolean(data.es_admin), Boolean(data.es_admin));
            $('#modal-rol-title').text('Editar rol');
            $modal.modal('show');
        }

        $('#btn-nuevo-rol').on('click', openCreate);

        $(document).on('click', '.js-editar-rol', function () {
            var data;

            try {
                data = JSON.parse($(this).attr('data-rol'));
            } catch (error) {
                notify('error', 'No se pudieron leer los datos del rol.');
                return;
            }

            openEdit(data);
        });

        $(document).on('click', '.js-toggle-grupo', function () {
            var $grupo = $(this).closest('.rol-grupo-permisos');
            var $checkboxes = $grupo.find('.js-permiso-rol').not(':disabled');
            var marcar = $checkboxes.filter(':not(:checked)').length > 0;
            $checkboxes.prop('checked', marcar);
        });

        $form.on('submit', function (event) {
            event.preventDefault();

            var $button = $('#btn-guardar-rol').prop('disabled', true);
            var url = editId === null
                ? cfg.urls.guardar
                : cfg.urls.actualizar.replace(/0$/, String(editId));

            $.post(url, $form.serialize())
                .done(function (response) {
                    if (response && response.success) {
                        $modal.modal('hide');
                        notify('success', response.message || 'Rol guardado correctamente.');
                        window.setTimeout(function () {
                            window.location.reload();
                        }, 700);
                        return;
                    }

                    notify('error', (response && response.message) || 'No se pudo guardar el rol.');
                })
                .fail(function (xhr) {
                    notify('error', errorMessage(xhr));
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });

        $(document).on('click', '.js-eliminar-rol', function () {
            deleteId = Number($(this).attr('data-id'));
            $('#eliminar-rol-nombre').text($(this).attr('data-nombre') || '');
            $('#modal-eliminar-rol').modal('show');
        });

        $('#btn-confirmar-eliminar-rol').on('click', function () {
            if (deleteId === null) {
                return;
            }

            var $button = $(this).prop('disabled', true);
            var id = deleteId;

            $.post(cfg.urls.eliminar.replace(/0$/, String(id)), {
                _token: cfg.token
            }).done(function (response) {
                if (response && response.success) {
                    $('#modal-eliminar-rol').modal('hide');
                    notify('success', response.message || 'Rol eliminado correctamente.');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 700);
                    return;
                }

                notify('error', (response && response.message) || 'No se pudo eliminar el rol.');
            }).fail(function (xhr) {
                notify('error', errorMessage(xhr));
            }).always(function () {
                $button.prop('disabled', false);
            });
        });
    });
})();
