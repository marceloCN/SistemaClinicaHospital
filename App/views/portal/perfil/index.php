<?php
/**
 * Perfil del usuario del portal.
 *
 * El guardado está separado en dos formularios independientes:
 *  - Datos de acceso (usuario y correo), con su propio botón.
 *  - Datos personales y cambio de contraseña, con un único botón.
 *
 * El guardado se hace por AJAX. El botón "Cancelar" devuelve al dashboard.
 */
$persona = $persona ?? [];
$cuenta = $cuenta ?? [];
$rol = $rol ?? [];
$paciente = $paciente ?? [];
$iniciales = (string) ($iniciales ?? 'P');
$rolClave = (string) ($rolClave ?? '');

$nombreRol = (string) ($rol['nombre'] ?? 'Paciente');
$fechaAlta = (string) ($paciente['fecha_alta'] ?? '');
$endpointAcceso = route('portal_perfil_acceso') ?? '/perfil/acceso';
$endpointDatos = route('portal_perfil_guardar') ?? '/perfil/guardar';
$endpointUsuario = route('portal_perfil_usuario_disponible') ?? '/perfil/usuario-disponible';
?>

<style>
    .perfil-hero {
        display: flex;
        align-items: center;
        gap: 20px;
        background: linear-gradient(135deg, var(--portal-primary) 0%, var(--portal-primary-dark) 100%);
        color: #fff;
        border-radius: 20px;
        padding: 26px;
        margin-bottom: 26px;
    }

    .perfil-avatar {
        width: 78px;
        height: 78px;
        min-width: 78px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.18);
        border: 2px solid rgba(255, 255, 255, 0.45);
        display: grid;
        place-items: center;
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .perfil-hero h2 {
        font-weight: 800;
        margin: 0 0 4px;
    }

    .perfil-hero p {
        margin: 0;
        opacity: 0.9;
        font-size: 0.92rem;
    }

    .perfil-hero .perfil-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 18px;
        margin-top: 10px;
        font-size: 0.85rem;
        opacity: 0.92;
    }

    .perfil-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .perfil-card h5 {
        font-weight: 800;
        color: #0b2a38;
        margin-bottom: 4px;
    }

    .perfil-card .perfil-card-desc {
        color: #6b7c83;
        font-size: 0.9rem;
        margin-bottom: 18px;
    }

    .perfil-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }

    .perfil-readonly {
        background: #f4f7f6;
        border: 1px dashed #cfdcd8;
        color: #5d7078;
    }

    .perfil-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 8px;
    }

    .perfil-actions .btn {
        border-radius: 50px;
        font-weight: 600;
        padding: 10px 26px;
    }

    .perfil-alert {
        display: none;
        margin-bottom: 18px;
    }

    .perfil-alert.visible { display: block; }

    .perfil-feedback {
        display: block;
        min-height: 18px;
        margin-top: 5px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .perfil-feedback.is-valid { color: #1e8e5a; }
    .perfil-feedback.is-invalid { color: #c0392b; }
    .perfil-feedback.is-checking { color: #6b7c83; }

    .perfil-toggle-pass {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: 0;
        color: #6b7c83;
        padding: 6px 8px;
        cursor: pointer;
    }

    .perfil-pass-wrap { position: relative; }

    .perfil-pass-wrap .form-control { padding-right: 42px; }

    .perfil-requisitos {
        list-style: none;
        padding: 0;
        margin: 0 0 1rem;
        font-size: 0.85rem;
        color: #6b7c83;
    }

    .perfil-requisitos li {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .perfil-requisitos li i {
        width: 16px;
        text-align: center;
    }

    .perfil-requisitos li.valid {
        color: #1e8e5a;
    }

    .perfil-requisitos li.invalid {
        color: #6b7c83;
    }

    @media (max-width: 575.98px) {
        .perfil-hero { flex-direction: column; text-align: center; padding: 22px 18px; }
        .perfil-hero .perfil-meta { justify-content: center; }
        .perfil-card { padding: 18px 15px; }
        .perfil-actions { flex-direction: column-reverse; }
        .perfil-actions .btn { width: 100%; }
    }
</style>

<div class="perfil-hero">
    <div class="perfil-avatar"><?= e($iniciales) ?></div>
    <div>
        <h2><?= e(trim((string) ($persona['nombre'] ?? '') . ' ' . (string) ($persona['apellido'] ?? ''))) ?></h2>
        <p><?= e($nombreRol) ?> · Clínica Vida Salud</p>
        <div class="perfil-meta">
            <span><i class="fas fa-id-card"></i> CI: <?= e((string) ($persona['ci'] ?? '—')) ?></span>
            <?php if ($fechaAlta !== '') { ?>
                <span><i class="fas fa-calendar-check"></i> Paciente desde: <?= e($fechaAlta) ?></span>
            <?php } ?>
            <span><i class="fas fa-user"></i> <span id="perfil-hero-usuario"><?= e((string) ($cuenta['usuario'] ?? '')) ?></span></span>
        </div>
    </div>
</div>

<!-- ================================================================
     Datos de acceso: formulario independiente
     ================================================================ -->
<form id="perfil-acceso-form" action="<?= e($endpointAcceso) ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <div id="perfil-acceso-alert" class="alert alert-danger perfil-alert" role="alert"></div>

    <div class="perfil-card">
        <h5>Datos de acceso</h5>
        <p class="perfil-card-desc">Tu usuario y tu correo son únicos en el sistema.</p>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-usuario">Nombre de usuario *</label>
                <input type="text" class="form-control" id="perfil-usuario" name="usuario" maxlength="50"
                    autocomplete="username" required
                    value="<?= e((string) ($cuenta['usuario'] ?? '')) ?>">
                <small id="perfil-usuario-feedback" class="perfil-feedback" aria-live="polite"></small>
            </div>
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-correo">Correo electrónico *</label>
                <input type="email" class="form-control" id="perfil-correo" name="correo" maxlength="150"
                    autocomplete="email" required
                    value="<?= e((string) ($cuenta['correo'] ?? '')) ?>">
            </div>
        </div>

        <div class="perfil-actions">
            <button type="submit" class="btn btn-primary" id="perfil-acceso-guardar">
                <span class="normal-text"><i class="fas fa-save"></i> Guardar cambios</span>
                <span class="loading-text" style="display:none"><i class="fas fa-circle-notch fa-spin"></i> Guardando...</span>
            </button>
        </div>
    </div>
</form>

<!-- ================================================================
     Datos personales + cambio de contraseña: un único guardado
     ================================================================ -->
<form id="perfil-datos-form" action="<?= e($endpointDatos) ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <div id="perfil-datos-alert" class="alert alert-danger perfil-alert" role="alert"></div>

    <div class="perfil-card">
        <h5>Datos personales</h5>
        <p class="perfil-card-desc">Estos datos aparecen en tu ficha de paciente.</p>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-nombre">Nombre *</label>
                <input type="text" class="form-control" id="perfil-nombre" name="nombre" maxlength="100"
                    value="<?= e((string) ($persona['nombre'] ?? '')) ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-apellido">Apellido *</label>
                <input type="text" class="form-control" id="perfil-apellido" name="apellido" maxlength="100"
                    value="<?= e((string) ($persona['apellido'] ?? '')) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-telefono">Teléfono</label>
                <input type="tel" class="form-control" id="perfil-telefono" name="telefono" maxlength="15"
                    inputmode="tel" autocomplete="tel"
                    value="<?= e((string) ($persona['telefono'] ?? '')) ?>">
            </div>
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-direccion">Dirección</label>
                <input type="text" class="form-control" id="perfil-direccion" name="direccion" maxlength="255"
                    autocomplete="street-address"
                    value="<?= e((string) ($persona['direccion'] ?? '')) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-correo-personal">Correo personal</label>
                <input type="email" class="form-control" id="perfil-correo-personal" name="correo" maxlength="150"
                    autocomplete="email" value="<?= e((string) ($persona['correo'] ?? '')) ?>">
            </div>
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-sexo">Sexo</label>
                <input type="text" class="form-control" id="perfil-sexo" name="sexo"
                    value="<?= e((string) ($persona['sexo'] ?? '')) ?>" disabled>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-ci">Cédula de identidad</label>
                <input type="text" class="form-control perfil-readonly" id="perfil-ci"
                    value="<?= e((string) ($persona['ci'] ?? '')) ?>" disabled>
            </div>
            <div class="form-group col-md-6">
                <label class="perfil-label" for="perfil-nacimiento">Fecha de nacimiento</label>
                <input type="text" class="form-control perfil-readonly" id="perfil-nacimiento"
                    value="<?= e((string) ($persona['fecha_nacimiento'] ?? '—')) ?>" disabled>
            </div>
        </div>
    </div>

    <div class="perfil-card">
        <h5>Cambiar contraseña</h5>
        <p class="perfil-card-desc">
            Deja estos campos vacíos si no quieres cambiar tu contraseña.
            Ingresa tu contraseña actual para confirmar el cambio.
        </p>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label class="perfil-label" for="perfil-password-actual">Contraseña actual</label>
                <div class="perfil-pass-wrap">
                    <input type="text" class="form-control" id="perfil-password-actual" name="password_actual"
                        autocomplete="current-password" placeholder="Escribe tu contraseña actual">
                    <button type="button" class="perfil-toggle-pass js-ver-password"
                        data-target="#perfil-password-actual" aria-label="Ocultar contraseña">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                <small class="form-text text-muted">Este campo se muestra en texto plano para que puedas verificar lo que escribes.</small>
            </div>
            <div class="form-group col-md-4">
                <label class="perfil-label" for="perfil-password">Nueva contraseña</label>
                <div class="perfil-pass-wrap">
                    <input type="password" class="form-control" id="perfil-password" name="password"
                        autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                    <button type="button" class="perfil-toggle-pass js-ver-password"
                        data-target="#perfil-password" aria-label="Mostrar contraseña">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group col-md-4">
                <label class="perfil-label" for="perfil-password-confirm">Confirmar contraseña</label>
                <div class="perfil-pass-wrap">
                    <input type="password" class="form-control" id="perfil-password-confirm"
                        name="password_confirmation" autocomplete="new-password">
                    <button type="button" class="perfil-toggle-pass js-ver-password"
                        data-target="#perfil-password-confirm" aria-label="Mostrar contraseña">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <ul class="perfil-requisitos" id="perfil-requisitos-pass">
            <li class="invalid" data-req="length"><i class="fas fa-circle"></i> Mínimo 8 caracteres</li>
            <li class="invalid" data-req="uppercase"><i class="fas fa-circle"></i> Al menos una mayúscula</li>
            <li class="invalid" data-req="lowercase"><i class="fas fa-circle"></i> Al menos una minúscula</li>
            <li class="invalid" data-req="number"><i class="fas fa-circle"></i> Al menos un número</li>
            <li class="invalid" data-req="symbol"><i class="fas fa-circle"></i> Al menos un símbolo (!@#$%^&*...)</li>
        </ul>

        <div class="perfil-actions">
            <button type="button" class="btn btn-outline-secondary" id="perfil-cancelar">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="submit" class="btn btn-primary" id="perfil-datos-guardar">
                <span class="normal-text"><i class="fas fa-save"></i> Guardar cambios</span>
                <span class="loading-text" style="display:none"><i class="fas fa-circle-notch fa-spin"></i> Guardando...</span>
            </button>
        </div>
    </div>
</form>

<script>
    // Esperamos a que el DOM esté listo y los scripts con defer (jQuery) se hayan cargado.
    document.addEventListener('DOMContentLoaded', function () {
        $(function () {
        // -----------------------------------------------------------------
        // Utilidades
        // -----------------------------------------------------------------
        function toggleLoading($boton, loading) {
            $boton.prop('disabled', loading);
            $boton.find('.normal-text').toggle(!loading);
            $boton.find('.loading-text').toggle(loading);
        }

        function mensajeDeError(data, porDefecto) {
            var mensaje = data.message || porDefecto;

            if (data.errors) {
                var lista = [];

                $.each(data.errors, function (_, textos) {
                    lista.push(Array.isArray(textos) ? textos[0] : textos);
                });

                if (lista.length > 0) {
                    mensaje = lista.join(' ');
                }
            }

            return mensaje;
        }

        function notificar(mensaje, porDefecto) {
            var texto = mensaje || porDefecto;

            if (typeof window.showNotification === 'function') {
                window.showNotification('success', texto);
            } else {
                window.alert(texto);
            }
        }

        function escaparHtml(texto) {
            return $('<div>').text(texto).html();
        }

        function correoValido(correo) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(correo).trim());
        }

        // -----------------------------------------------------------------
        // Formulario de datos de acceso (usuario y correo)
        // -----------------------------------------------------------------
        var $formAcceso = $('#perfil-acceso-form');
        var $botonAcceso = $('#perfil-acceso-guardar');
        var $alertaAcceso = $('#perfil-acceso-alert');
        var $campoUsuario = $('#perfil-usuario');
        var $feedbackUsuario = $('#perfil-usuario-feedback');
        var $campoCorreo = $('#perfil-correo');
        var endpointUsuario = "<?= e($endpointUsuario) ?>";
        var usuarioOriginal = $campoUsuario.length ? $campoUsuario.val().trim().toLowerCase() : '';
        var temporizadorUsuario = null;
        var consultaUsuario = 0;

        function pintarFeedbackUsuario(texto, estado) {
            $feedbackUsuario.text(texto).attr('class', 'perfil-feedback' + (estado ? ' is-' + estado : ''));
            $campoUsuario.toggleClass('is-invalid', estado === 'invalid');
        }

        function revisarUsuario() {
            var valor = $campoUsuario.val().trim();

            if (valor.toLowerCase() === usuarioOriginal) {
                pintarFeedbackUsuario('', '');
                return;
            }

            if (valor.length < 3) {
                pintarFeedbackUsuario('El nombre de usuario debe tener al menos 3 caracteres.', 'invalid');
                return;
            }

            var consulta = ++consultaUsuario;
            pintarFeedbackUsuario('Verificando disponibilidad...', 'checking');

            $.ajax({
                url: endpointUsuario,
                type: 'GET',
                dataType: 'json',
                data: { usuario: valor },
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .done(function (respuesta) {
                    if (consulta !== consultaUsuario) {
                        return;
                    }

                    if (respuesta.disponible) {
                        pintarFeedbackUsuario(respuesta.message || 'Nombre de usuario disponible.', 'valid');
                    } else {
                        pintarFeedbackUsuario(respuesta.message || 'Ese nombre de usuario ya está en uso.', 'invalid');
                    }
                })
                .fail(function () {
                    if (consulta !== consultaUsuario) {
                        return;
                    }

                    pintarFeedbackUsuario('', '');
                });
        }

        $campoUsuario.on('input', function () {
            window.clearTimeout(temporizadorUsuario);
            temporizadorUsuario = window.setTimeout(revisarUsuario, 450);
        });

        $campoUsuario.on('blur', function () {
            window.clearTimeout(temporizadorUsuario);
            revisarUsuario();
        });

        $formAcceso.on('submit', function (event) {
            event.preventDefault();
            $alertaAcceso.text('').removeClass('visible');

            // Validar correo
            if (!correoValido($campoCorreo.val())) {
                $alertaAcceso.text('Ingresa un correo electrónico válido.').addClass('visible');
                $campoCorreo.addClass('is-invalid');
                return;
            }

            $campoCorreo.removeClass('is-invalid');

            // Validar que el usuario esté disponible
            if ($feedbackUsuario.hasClass('is-invalid')) {
                $alertaAcceso.text('El nombre de usuario no está disponible.').addClass('visible');
                return;
            }

            var payload = {};

            $formAcceso.serializeArray().forEach(function (field) {
                payload[field.name] = field.value;
            });

            toggleLoading($botonAcceso, true);

            $.ajax({
                url: "<?= e($endpointAcceso) ?>",
                type: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': payload._token || ''
                },
                data: JSON.stringify(payload)
            })
                .done(function (respuesta) {
                    toggleLoading($botonAcceso, false);

                    if (!respuesta.success) {
                        $alertaAcceso.text(respuesta.message || 'No se pudieron guardar los cambios.').addClass('visible');

                        if (respuesta.errors && respuesta.errors.usuario) {
                            $campoUsuario.addClass('is-invalid');
                            pintarFeedbackUsuario(respuesta.errors.usuario[0], 'invalid');
                        }

                        return;
                    }

                    if (respuesta.usuario) {
                        $('#perfil-hero-usuario').text(respuesta.usuario);
                        $campoUsuario.val(respuesta.usuario);
                        usuarioOriginal = respuesta.usuario.toLowerCase();
                        pintarFeedbackUsuario('', '');
                    }

                    notificar(respuesta.message, 'Tus datos de acceso se actualizaron correctamente.');
                })
                .fail(function (xhr) {
                    toggleLoading($botonAcceso, false);
                    $alertaAcceso.text(mensajeDeError(xhr.responseJSON || {}, 'No se pudieron guardar los cambios.')).addClass('visible');
                });
        });

        // -----------------------------------------------------------------
        // Formulario de datos personales + contraseña
        // -----------------------------------------------------------------
        var $formDatos = $('#perfil-datos-form');
        var $botonDatos = $('#perfil-datos-guardar');
        var $alertaDatos = $('#perfil-datos-alert');
        var $campoPassword = $('#perfil-password');
        var $campoConfirmacion = $('#perfil-password-confirm');
        var $listaRequisitos = $('#perfil-requisitos-pass');

        function validarRequisitosPassword(valor) {
            return {
                length: valor.length >= 8,
                uppercase: /[A-Z]/.test(valor),
                lowercase: /[a-z]/.test(valor),
                number: /[0-9]/.test(valor),
                symbol: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(valor)
            };
        }

        function actualizarRequisitosPassword() {
            var valor = $campoPassword.val();
            var requisitos = validarRequisitosPassword(valor);

            $listaRequisitos.find('li').each(function () {
                var $item = $(this);
                var clave = $item.data('req');
                var cumple = requisitos[clave] || false;

                $item.toggleClass('valid', cumple).toggleClass('invalid', !cumple);
                $item.find('i').attr('class', cumple ? 'fas fa-check-circle' : 'fas fa-circle');
            });
        }

        function passwordSegura() {
            var valor = $campoPassword.val();

            if (valor === '') {
                return true; // No se cambia la contraseña.
            }

            var requisitos = validarRequisitosPassword(valor);
            return Object.values(requisitos).every(function (cumple) { return cumple; });
        }

        $campoPassword.on('input', actualizarRequisitosPassword);

        $campoConfirmacion.on('input', function () {
            var valor = $campoConfirmacion.val();
            var pass = $campoPassword.val();

            if (valor !== '' && valor !== pass) {
                $campoConfirmacion.addClass('is-invalid');
            } else {
                $campoConfirmacion.removeClass('is-invalid');
            }
        });

        $formDatos.on('submit', function (event) {
            event.preventDefault();
            $alertaDatos.text('').removeClass('visible');

            if (!passwordSegura()) {
                $alertaDatos.text('La nueva contraseña no cumple con todos los requisitos de seguridad.').addClass('visible');
                $campoPassword.focus();
                return;
            }

            if ($campoPassword.val() !== '' && $campoPassword.val() !== $campoConfirmacion.val()) {
                $alertaDatos.text('La confirmación de la contraseña no coincide.').addClass('visible');
                $campoConfirmacion.addClass('is-invalid');
                return;
            }

            var payload = {};

            $formDatos.serializeArray().forEach(function (field) {
                payload[field.name] = field.value;
            });

            toggleLoading($botonDatos, true);

            $.ajax({
                url: "<?= e($endpointDatos) ?>",
                type: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': payload._token || ''
                },
                data: JSON.stringify(payload)
            })
                .done(function (respuesta) {
                    toggleLoading($botonDatos, false);

                    if (!respuesta.success) {
                        $alertaDatos.text(respuesta.message || 'No se pudieron guardar los cambios.').addClass('visible');
                        return;
                    }

                    if (respuesta.nombre) {
                        $('.portal-user-name').html('<i class="fas fa-user-circle"></i> ' + escaparHtml(respuesta.nombre));
                    }

                    $('#perfil-password, #perfil-password-confirm, #perfil-password-actual').val('');
                    actualizarRequisitosPassword();
                    $campoConfirmacion.removeClass('is-invalid');

                    notificar(respuesta.message, 'Tus datos se actualizaron correctamente.');
                })
                .fail(function (xhr) {
                    toggleLoading($botonDatos, false);
                    $alertaDatos.text(mensajeDeError(xhr.responseJSON || {}, 'No se pudieron guardar los cambios.')).addClass('visible');
                });
        });

        // -----------------------------------------------------------------
        // Mostrar / ocultar contraseñas
        // -----------------------------------------------------------------
        $('.js-ver-password').on('click', function () {
            var $boton = $(this);
            var $campo = $($boton.data('target'));
            var visible = $campo.attr('type') === 'text';

            $campo.attr('type', visible ? 'password' : 'text');
            $boton.html(visible ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>');
        });

        // -----------------------------------------------------------------
        // Cancelar: vuelve al dashboard sin guardar nada
        // -----------------------------------------------------------------
        $('#perfil-cancelar').on('click', function () {
            window.location.href = "<?= route('portal_dashboard') ?? '/portal/dashboard' ?>";
        });
        });
    });
</script>
