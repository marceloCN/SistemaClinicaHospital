<?php
/**
 * Modal de confirmación y notificaciones compartidos por los módulos del
 * portal (especialidades médicas, horarios de atención, ...).
 *
 * Define en window:
 *  - espConfirmar({titulo, mensaje, ayuda, btn, destructivo}, callback)
 *  - espNotificar('success'|'warning'|'error', mensaje) → showNotification()
 *
 * Se incluye una sola vez por página desde el header del módulo:
 *   require __DIR__ . '/../base/confirmar.php';
 */
?>

<style>
    #confirmar-ayuda {
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.86rem;
        color: #3d6a8f;
    }
</style>

<!-- Modal de confirmación compartido por todas las páginas -->
<div class="modal fade" id="modal-confirmar" tabindex="-1" role="dialog" aria-labelledby="modal-confirmar-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modal-confirmar-header" style="background:#c0392b;color:#fff;">
                <h5 class="modal-title" id="modal-confirmar-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <span id="confirmar-titulo">Confirmar acción</span>
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmar-mensaje" class="mb-0" style="white-space:pre-line;"></p>
                <div id="confirmar-ayuda" class="es-ayuda mt-3" style="display:none;">
                    <i class="fas fa-info-circle"></i> <span id="confirmar-ayuda-texto"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-accion">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var alConfirmar = null;

        /**
         * Abre el modal de confirmación.
         * opciones: { titulo, mensaje, ayuda, btn, destructivo }
         * alConfirmar: función a ejecutar si el usuario confirma.
         */
        window.espConfirmar = function (opciones, alConfirmarCallback) {
            opciones = opciones || {};
            alConfirmar = typeof alConfirmarCallback === 'function' ? alConfirmarCallback : null;

            $('#confirmar-titulo').text(opciones.titulo || 'Confirmar acción');
            $('#confirmar-mensaje').text(opciones.mensaje || '');

            if (opciones.ayuda) {
                $('#confirmar-ayuda-texto').text(opciones.ayuda);
                $('#confirmar-ayuda').show();
            } else {
                $('#confirmar-ayuda').hide().find('span').text('');
            }

            var destructivo = opciones.destructivo !== false;

            $('#btn-confirmar-accion')
                .text(opciones.btn || (destructivo ? 'Sí, continuar' : 'Confirmar'))
                .removeClass('btn-danger btn-primary')
                .addClass(destructivo ? 'btn-danger' : 'btn-primary');

            $('#modal-confirmar-header').css('background', destructivo ? '#c0392b' : '#0f8b84');
            $('#modal-confirmar').modal('show');
        };

        $('#btn-confirmar-accion').on('click', function () {
            var callback = alConfirmar;
            alConfirmar = null;
            $('#modal-confirmar').modal('hide');

            if (callback) {
                callback();
            }
        });

        $('#modal-confirmar').on('hidden.bs.modal', function () {
            alConfirmar = null;
        });

        /** Notificación flotante (siempre disponible aunque falle notification.js). */
        window.espNotificar = function (tipo, mensaje) {
            if (typeof window.showNotification === 'function') {
                window.showNotification(tipo === 'success' ? 'success' : tipo === 'warning' ? 'warning' : 'error', mensaje);
                return;
            }

            alert(mensaje);
        };
    });
</script>
