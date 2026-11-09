(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cfg = window.GestionPermisos;
        if (!cfg || typeof jQuery === 'undefined') {
            return;
        }

        var $ = jQuery;
        var $form = $('#form-permiso');
        var $modal = $('#modal-permiso');
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
            $('#permiso-id').val('');
            $('#modal-permiso-title').text('Nuevo permiso');
            $modal.modal('show');
        }

        function openEdit(data) {
            editId = Number(data.codigo);
            $form[0].reset();
            $('#permiso-id').val(editId);
            $('#permiso-nombre').val(data.nombre || '');
            $('#modal-permiso-title').text('Editar permiso');
            $modal.modal('show');
        }

        $('#btn-nuevo-permiso').on('click', openCreate);

        $(document).on('click', '.js-editar-permiso', function () {
            var data;

            try {
                data = JSON.parse($(this).attr('data-permiso'));
            } catch (error) {
                notify('error', 'No se pudieron leer los datos del permiso.');
                return;
            }

            openEdit(data);
        });

        $form.on('submit', function (event) {
            event.preventDefault();

            var $button = $('#btn-guardar-permiso').prop('disabled', true);
            var url = editId === null
                ? cfg.urls.guardar
                : cfg.urls.actualizar.replace(/0$/, String(editId));

            $.post(url, $form.serialize())
                .done(function (response) {
                    if (response && response.success) {
                        $modal.modal('hide');
                        notify('success', response.message || 'Permiso guardado correctamente.');
                        window.setTimeout(function () {
                            window.location.reload();
                        }, 700);
                        return;
                    }

                    notify('error', (response && response.message) || 'No se pudo guardar el permiso.');
                })
                .fail(function (xhr) {
                    notify('error', errorMessage(xhr));
                })
                .always(function () {
                    $button.prop('disabled', false);
                });
        });

        $(document).on('click', '.js-eliminar-permiso', function () {
            deleteId = Number($(this).attr('data-id'));
            $('#eliminar-permiso-nombre').text($(this).attr('data-nombre') || '');
            $('#modal-eliminar-permiso').modal('show');
        });

        $('#btn-confirmar-eliminar-permiso').on('click', function () {
            if (deleteId === null) {
                return;
            }

            var $button = $(this).prop('disabled', true);
            var id = deleteId;

            $.post(cfg.urls.eliminar.replace(/0$/, String(id)), {
                _token: cfg.token
            }).done(function (response) {
                if (response && response.success) {
                    $('#modal-eliminar-permiso').modal('hide');
                    notify('success', response.message || 'Permiso eliminado correctamente.');
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 700);
                    return;
                }

                notify('error', (response && response.message) || 'No se pudo eliminar el permiso.');
            }).fail(function (xhr) {
                notify('error', errorMessage(xhr));
            }).always(function () {
                $button.prop('disabled', false);
            });
        });
    });
})();
