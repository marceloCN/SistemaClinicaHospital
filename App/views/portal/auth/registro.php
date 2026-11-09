<?php
/**
 * Formulario de alta de usuario paciente (modal del login del portal).
 *
 * Se muestra sin recargar la página: el botón "Crear usuario paciente"
 * abre este modal y el envío se hace por AJAX contra portal_registro.
 */
?>
<div class="modal fade" id="modal-registro-paciente" tabindex="-1" role="dialog"
    aria-labelledby="modal-registro-paciente-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-registro-dialog" role="document">
        <div class="modal-content modal-registro-content">
            <div class="modal-header modal-registro-header">
                <h5 class="modal-title" id="modal-registro-paciente-title">
                    <i class="fas fa-user-plus mr-2"></i> Crear usuario paciente
                </h5>
                <button type="button" class="close modal-registro-close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="registro-paciente-form" action="<?= route('portal_registro') ?>" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="modal-body modal-registro-body">
                    <p class="text-muted small mb-3 modal-registro-lead">
                        Completa tus datos personales y los de tu cuenta de acceso. Verificamos que tu cédula,
                        usuario y correo todavía no estén registrados.
                    </p>

                    <p class="text-uppercase font-weight-bold text-muted small mb-2 section-title">Datos de la persona</p>

                    <div class="form-row">
                        <div class="form-group col-12 col-md-4">
                            <label for="reg-ci">Cédula de identidad *</label>
                            <input type="text" class="form-control" id="reg-ci" name="ci" maxlength="15"
                                inputmode="numeric" autocomplete="off" placeholder="12345678" required>
                        </div>
                        <div class="form-group col-12 col-md-4">
                            <label for="reg-nombre">Nombre *</label>
                            <input type="text" class="form-control" id="reg-nombre" name="nombre" maxlength="100"
                                autocomplete="given-name" placeholder="Juan" required>
                        </div>
                        <div class="form-group col-12 col-md-4">
                            <label for="reg-apellido">Apellido *</label>
                            <input type="text" class="form-control" id="reg-apellido" name="apellido" maxlength="100"
                                autocomplete="family-name" placeholder="Pérez" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-12 col-md-4">
                            <label for="reg-telefono">Teléfono</label>
                            <input type="tel" class="form-control" id="reg-telefono" name="telefono" maxlength="15"
                                inputmode="tel" autocomplete="tel" placeholder="70987654">
                        </div>
                        <div class="form-group col-12 col-md-8">
                            <label for="reg-direccion">Dirección</label>
                            <input type="text" class="form-control" id="reg-direccion" name="direccion" maxlength="255"
                                autocomplete="street-address" placeholder="Av. Siempre Viva 742">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-12 col-md-6">
                            <label for="reg-fecha-nacimiento">Fecha de nacimiento</label>
                            <input type="date" class="form-control" id="reg-fecha-nacimiento" name="fecha_nacimiento">
                        </div>
                        <div class="form-group col-12 col-md-6">
                            <label for="reg-sexo">Sexo</label>
                            <select class="form-control" id="reg-sexo" name="sexo">
                                <option value="">Sin especificar</option>
                                <option value="MASCULINO">Masculino</option>
                                <option value="FEMENINO">Femenino</option>
                            </select>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <p class="text-uppercase font-weight-bold text-muted small mb-2 section-title">Datos de la cuenta</p>

                    <div class="form-row">
                        <div class="form-group col-12 col-md-6">
                            <label for="reg-usuario">Usuario *</label>
                            <input type="text" class="form-control" id="reg-usuario" name="usuario" maxlength="50"
                                autocomplete="username" placeholder="jperez" required>
                            <div class="reg-check info" id="reg-usuario-check"></div>
                        </div>
                        <div class="form-group col-12 col-md-6">
                            <label for="reg-correo">Correo electrónico *</label>
                            <input type="email" class="form-control" id="reg-correo" name="correo" maxlength="150"
                                autocomplete="email" placeholder="jperez@correo.com" required>
                            <div class="reg-check info" id="reg-correo-check"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-12 col-md-6">
                            <label for="reg-password">Contraseña *</label>
                            <input type="password" class="form-control" id="reg-password" name="password"
                                autocomplete="new-password" minlength="8" placeholder="Mínimo 8 caracteres" required>
                            <ul class="list-unstyled reg-password-checklist" id="reg-password-checklist">
                                <li data-regla="largo" class="text-muted"><i class="far fa-square mr-1"></i> 8 caracteres o más</li>
                                <li data-regla="mayuscula" class="text-muted"><i class="far fa-square mr-1"></i> Al menos una mayúscula</li>
                                <li data-regla="minuscula" class="text-muted"><i class="far fa-square mr-1"></i> Al menos una minúscula</li>
                                <li data-regla="numero" class="text-muted"><i class="far fa-square mr-1"></i> Al menos un número</li>
                                <li data-regla="simbolo" class="text-muted"><i class="far fa-square mr-1"></i> Al menos un símbolo (!@#$...)</li>
                            </ul>
                        </div>
                        <div class="form-group col-12 col-md-6">
                            <label for="reg-password-confirm">Confirmar contraseña *</label>
                            <input type="password" class="form-control" id="reg-password-confirm"
                                name="password_confirmation" autocomplete="new-password" minlength="8"
                                placeholder="Repite la contraseña" required>
                            <div class="reg-check info" id="reg-password-confirm-check"></div>
                        </div>
                    </div>

                    <div id="registro-error" class="alert alert-danger d-none mb-0"></div>
                </div>

                <div class="modal-footer modal-registro-footer">
                    <button type="button" class="btn btn-secondary modal-registro-btn" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary modal-registro-btn" id="btn-registro">
                        <span class="normal-text"><i class="fas fa-user-plus"></i> Registrarme</span>
                        <span class="loading-text" style="display:none"><i class="fas fa-circle-notch fa-spin"></i>
                            Registrando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #modal-registro-paciente .modal-registro-dialog {
        max-width: 900px;
        margin: 0.5rem auto;
    }

    #modal-registro-paciente .modal-registro-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        max-height: calc(100vh - 1rem);
        max-height: calc(100dvh - 1rem);
    }

    #modal-registro-paciente .modal-registro-header {
        background: linear-gradient(135deg, #0f8b84 0%, #0b6b67 100%);
        color: #fff;
        border-bottom: none;
        padding: 1rem 1.25rem;
    }

    #modal-registro-paciente .modal-registro-header .modal-title {
        font-weight: 700;
        font-size: 1.15rem;
    }

    #modal-registro-paciente .modal-registro-close {
        color: #fff;
        opacity: 0.9;
        text-shadow: none;
        margin: -0.5rem -0.5rem -0.5rem auto;
    }

    #modal-registro-paciente .modal-registro-close:hover {
        opacity: 1;
    }

    #modal-registro-paciente form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }

    #modal-registro-paciente .modal-registro-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        padding: 1.25rem;
        background: #fafbfb;
    }

    #modal-registro-paciente .modal-registro-lead {
        line-height: 1.5;
    }

    #modal-registro-paciente .section-title {
        letter-spacing: 0.4px;
        color: #0b2a38;
    }

    #modal-registro-paciente .section-divider {
        border-top: 1px solid #e0e6e3;
        margin: 1.25rem 0;
    }

    #modal-registro-paciente .reg-check {
        font-size: 0.78rem;
        margin-top: 0.35rem;
        min-height: 1rem;
        font-weight: 500;
    }

    #modal-registro-paciente .reg-check.info {
        color: #7f8c8d;
    }

    #modal-registro-paciente .reg-check.ok {
        color: #1f9d55;
    }

    #modal-registro-paciente .reg-check.error {
        color: #d9534f;
    }

    #modal-registro-paciente .reg-password-checklist {
        margin: 0.5rem 0 0 0;
        font-size: 0.78rem;
        line-height: 1.7;
    }

    #modal-registro-paciente .reg-password-checklist li.text-success i {
        color: #1f9d55;
    }

    #modal-registro-paciente .form-control {
        border-radius: 10px;
        min-height: 42px;
    }

    #modal-registro-paciente label {
        font-weight: 600;
        color: #3b4f58;
        font-size: 0.9rem;
        margin-bottom: 0.35rem;
    }

    #modal-registro-paciente .modal-registro-footer {
        flex-shrink: 0;
        background: #fff;
        border-top: 1px solid #e0e6e3;
        padding: 1rem 1.25rem;
        display: flex;
        gap: 0.75rem;
    }

    #modal-registro-paciente .modal-registro-btn {
        min-width: 150px;
        border-radius: 10px;
        padding: 0.55rem 1.25rem;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        #modal-registro-paciente .modal-registro-dialog {
            margin: 0.25rem;
            max-width: none;
        }

        #modal-registro-paciente .modal-registro-content {
            max-height: calc(100vh - 0.5rem);
            max-height: calc(100dvh - 0.5rem);
            border-radius: 12px;
        }

        #modal-registro-paciente .modal-registro-header {
            padding: 0.875rem 1rem;
        }

        #modal-registro-paciente .modal-registro-header .modal-title {
            font-size: 1rem;
        }

        #modal-registro-paciente .modal-registro-body {
            padding: 1rem;
        }

        #modal-registro-paciente .modal-registro-footer {
            flex-direction: column;
            padding: 0.875rem 1rem;
            gap: 0.5rem;
        }

        #modal-registro-paciente .modal-registro-btn {
            width: 100%;
            min-width: 0;
        }

        #modal-registro-paciente label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        #modal-registro-paciente .form-group {
            margin-bottom: 0.75rem;
        }

        #modal-registro-paciente .form-control {
            font-size: 16px; /* evita zoom automático en iOS */
        }

        #modal-registro-paciente .section-divider {
            margin: 1rem 0;
        }
    }
</style>

<script>
    $(function () {
        var $form = $('#registro-paciente-form');
        var $btn = $('#btn-registro');
        var $error = $('#registro-error');

        // null = sin verificar todavía, true/false = último resultado.
        // Se guarda el valor verificado para detectar cambios posteriores.
        var usuarioOk = null;
        var usuarioVerificado = '';
        var correoOk = null;
        var correoVerificado = '';

        function toggleLoading(loading) {
            $btn.prop('disabled', loading);
            $btn.find('.normal-text').toggle(!loading);
            $btn.find('.loading-text').toggle(loading);
        }

        function showError(message) {
            $error.removeClass('d-none').text(message);
            $error[0].scrollIntoView({ block: 'nearest' });
        }

        /* ------------------------------------------------------------------
         * Verificación en vivo de usuario y correo
         * ---------------------------------------------------------------- */
        function verificarDisponibilidad(campo, valor, url, okCallback) {
            var d = $.Deferred();

            function terminar(disponible, mensaje) {
                if (okCallback) { okCallback(disponible, mensaje); }
                d.resolve(disponible);
            }

            $.getJSON(url, campo + '=' + encodeURIComponent(valor))
                .done(function (r) {
                    terminar(!!(r && r.success && r.disponible), (r && r.message) || '');
                })
                .fail(function (xhr) {
                    var r = xhr && xhr.responseJSON;
                    terminar(false, (r && r.message) || 'No se pudo verificar. Intenta de nuevo.');
                });

            return d;
        }

        $('#reg-usuario').on('blur', function () {
            var usuario = $.trim($(this).val());
            var $check = $('#reg-usuario-check').removeClass('ok error').addClass('info');

            if (!usuario) { $check.text(''); usuarioOk = null; usuarioVerificado = ''; return; }
            $check.text('Verificando...');

            verificarDisponibilidad('usuario', usuario,
                "<?= route('portal_registro_usuario_disponible') ?>",
                function (disponible, mensaje) {
                    if (disponible) {
                        usuarioOk = true;
                        usuarioVerificado = usuario;
                        $check.removeClass('info error').addClass('ok').text('✓ Usuario disponible');
                    } else {
                        usuarioOk = false;
                        usuarioVerificado = usuario;
                        $check.removeClass('info ok').addClass('error')
                            .text('✗ ' + (mensaje || 'Ese nombre de usuario ya está en uso'));
                    }
                });
        });

        $('#reg-correo').on('blur', function () {
            var correo = $.trim($(this).val());
            var $check = $('#reg-correo-check').removeClass('ok error').addClass('info');

            if (!correo) { $check.text(''); correoOk = null; correoVerificado = ''; return; }
            $check.text('Verificando...');

            verificarDisponibilidad('correo', correo,
                "<?= route('portal_registro_correo_disponible') ?>",
                function (disponible, mensaje) {
                    if (disponible) {
                        correoOk = true;
                        correoVerificado = correo;
                        $check.removeClass('info error').addClass('ok').text('✓ Correo disponible');
                    } else {
                        correoOk = false;
                        correoVerificado = correo;
                        $check.removeClass('info ok').addClass('error')
                            .text('✗ ' + (mensaje || 'Ese correo ya está registrado'));
                    }
                });
        });

        /* ------------------------------------------------------------------
         * Checklist de la contraseña en vivo
         * ---------------------------------------------------------------- */
        function evaluarPassword(valor) {
            return {
                largo: valor.length >= 8,
                mayuscula: /[A-Z]/.test(valor),
                minuscula: /[a-z]/.test(valor),
                numero: /\d/.test(valor),
                simbolo: /[^A-Za-z0-9]/.test(valor)
            };
        }

        function pintarChecklist(reglas) {
            $('#reg-password-checklist li').each(function () {
                var regla = $(this).attr('data-regla');
                var ok = !!reglas[regla];
                $(this)
                    .toggleClass('text-muted', !ok)
                    .toggleClass('text-success', ok)
                    .html((ok
                        ? '<i class="fas fa-check-square mr-1"></i> '
                        : '<i class="far fa-square mr-1"></i> ')
                        + $(this).text().replace(/^[✓✗]?\s*/, ''));
            });
        }

        function reglasFaltantes(valor) {
            var reglas = evaluarPassword(valor);
            var faltantes = [];
            var etiquetas = {
                largo: '8 caracteres o más',
                mayuscula: 'una mayúscula',
                minuscula: 'una minúscula',
                numero: 'un número',
                simbolo: 'un símbolo'
            };

            $.each(etiquetas, function (clave, etiqueta) {
                if (!reglas[clave]) {
                    faltantes.push(etiqueta);
                }
            });

            return faltantes;
        }

        function actualizarConfirmacion() {
            var valor = $('#reg-password').val() || '';
            var confirmacion = $('#reg-password-confirm').val() || '';
            var $check = $('#reg-password-confirm-check').removeClass('ok error').addClass('info');

            if (confirmacion === '') {
                $check.text('');
                return;
            }

            if (valor === confirmacion) {
                $check.removeClass('info error').addClass('ok').text('✓ Las contraseñas coinciden');
            } else {
                $check.removeClass('info ok').addClass('error').text('✗ Las contraseñas no coinciden');
            }
        }

        $('#reg-password').on('input', function () {
            var valor = $(this).val() || '';

            if (valor === '') {
                pintarChecklist({ largo: false, mayuscula: false, minuscula: false, numero: false, simbolo: false });
            } else {
                pintarChecklist(evaluarPassword(valor));
            }

            actualizarConfirmacion();
        });

        $('#reg-password-confirm').on('input', actualizarConfirmacion);

        /* ------------------------------------------------------------------
         * Envío del registro
         * ---------------------------------------------------------------- */
        $form.on('submit', function (e) {
            e.preventDefault();

            $error.addClass('d-none').text('');

            var payload = {};
            $.each($form.serializeArray(), function (_, field) {
                payload[field.name] = field.value;
            });

            payload._token = "<?= csrf_token() ?>";

            var obligatorios = ['ci', 'nombre', 'apellido', 'usuario', 'correo', 'password', 'password_confirmation'];

            for (var i = 0; i < obligatorios.length; i++) {
                if (!payload[obligatorios[i]]) {
                    showError('Completa todos los campos obligatorios.');
                    return;
                }
            }

            var faltantes = reglasFaltantes(payload.password);

            if (faltantes.length > 0) {
                showError('La contraseña aún no cumple: ' + faltantes.join(', ') + '.');
                return;
            }

            if (payload.password !== payload.password_confirmation) {
                showError('Las contraseñas no coinciden.');
                return;
            }

            // Si aún no se verificó usuario/correo (o el campo cambió tras
            // el último chequeo), se comprueba ahora antes de registrar.
            var usuarioActual = $.trim(payload.usuario);
            var correoActual = $.trim(payload.correo);
            var usuarioPendiente = !(usuarioOk === true && usuarioActual === usuarioVerificado);
            var correoPendiente = !(correoOk === true && correoActual === correoVerificado);

            toggleLoading(true);

            $.when(
                usuarioPendiente ? verificarDisponibilidad('usuario', usuarioActual,
                    "<?= route('portal_registro_usuario_disponible') ?>",
                    function (disponible, mensaje) {
                        usuarioOk = disponible;
                        usuarioVerificado = usuarioActual;
                        $('#reg-usuario-check')
                            .removeClass('info')
                            .addClass(disponible ? 'ok' : 'error')
                            .text(disponible
                                ? '✓ Usuario disponible'
                                : '✗ ' + (mensaje || 'Ese nombre de usuario ya está en uso'));
                    }) : $.Deferred().resolve(true),
                correoPendiente ? verificarDisponibilidad('correo', correoActual,
                    "<?= route('portal_registro_correo_disponible') ?>",
                    function (disponible, mensaje) {
                        correoOk = disponible;
                        correoVerificado = correoActual;
                        $('#reg-correo-check')
                            .removeClass('info')
                            .addClass(disponible ? 'ok' : 'error')
                            .text(disponible
                                ? '✓ Correo disponible'
                                : '✗ ' + (mensaje || 'Ese correo ya está registrado'));
                    }) : $.Deferred().resolve(true)
            ).then(function (usuarioDisponible, correoDisponible) {
                if (!usuarioDisponible) {
                    toggleLoading(false);
                    showError('Ese nombre de usuario ya está en uso. Elige otro.');
                    return;
                }

                if (!correoDisponible) {
                    toggleLoading(false);
                    showError('Ese correo ya está registrado. Ingresa otro correo.');
                    return;
                }

                enviar(payload);
            });
        });

        function enviar(payload) {
            $.ajax({
                url: "<?= route('portal_registro') ?>",
                type: 'POST',
                contentType: 'application/json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: JSON.stringify(payload),
                dataType: 'json'
            })
                .done(function (data) {
                    if (data.success) {
                        showNotification('success', data.message || 'Usuario registrado correctamente.');
                        setTimeout(function () {
                            window.location.href = data.redirect || '/';
                        }, 1200);
                        return;
                    }

                    toggleLoading(false);
                    showError(data.message || 'No se pudo registrar el usuario.');
                })
                .fail(function (xhr) {
                    toggleLoading(false);

                    var data = xhr.responseJSON || {};
                    showError(data.message || 'No se pudo registrar el usuario.');
                });
        }
    });
</script>
