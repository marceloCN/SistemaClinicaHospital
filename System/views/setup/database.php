<?php
/**
 * Panel de configuracion inicial de base de datos (primera vez).
 * Se renderiza solo cuando no existe una conexion establecida.
 * Minimalista: solo pide a donde conectar, probar y guardar en .env.
 * No depende de la base de datos ni de layout alguno.
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración de base de datos</title>
    <link rel="icon" href="<?= e($assetBase) ?>/favicon.ico">
    <link rel="stylesheet" href="<?= e($assetBase) ?>/code/dist/css/adminlte.purged.min.css">
    <link rel="stylesheet" href="<?= e($assetBase) ?>/code/plugins/fontawesome-free/css/all.min.css">
    <style>
        body.login-page {
            background: linear-gradient(160deg, #f4faf8 0%, #e3f3ef 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 24px 12px;
        }

        .login-box {
            width: 100%;
            max-width: 640px;
            margin: auto;
        }

        .login-logo b {
            color: #0b2a38;
            font-size: 1.05rem;
        }

        .login-logo b i {
            color: #0f8b84;
        }

        .login-card-body {
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(11, 42, 56, .12);
            padding: 26px 28px;
        }

        .form-group,
        .form-control,
        .input-group {
            min-width: 0;
        }

        .input-group-text {
            background: rgba(15, 139, 132, .08);
            color: #0f8b84;
            border-color: #d7e8e5;
        }

        .form-control:focus {
            border-color: #0f8b84;
            box-shadow: 0 0 0 .15rem rgba(15, 139, 132, .18);
        }

        label {
            font-weight: 600;
            font-size: .82rem;
            color: #33465a;
        }

        .btn-primary {
            background-color: #0f8b84;
            border-color: #0f8b84;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #0a6b67;
            border-color: #0a6b67;
        }

        .btn-outline-primary {
            color: #0f8b84;
            border-color: #0f8b84;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background-color: #0f8b84;
            border-color: #0f8b84;
            color: #fff;
        }

        .bd-eye {
            background: #fff;
            border: 1px solid #d7e8e5;
            color: #5d7078;
            cursor: pointer;
            padding: 0 12px;
        }

        .bd-eye:hover {
            color: #0f8b84;
        }

        .bd-status {
            border-radius: 10px;
            padding: 9px 12px;
            font-size: .84rem;
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 12px;
            min-height: 40px;
        }

        .bd-status.d-none {
            display: none !important;
        }

        .bd-status.alert-success {
            background: #e8f7ef;
            color: #146c43;
            border: 1px solid #bfe6d2;
        }

        .bd-status.alert-danger {
            background: #fdecea;
            color: #c0392b;
            border: 1px solid #f5c6c2;
        }

        .bd-status.alert-info {
            background: rgba(15, 139, 132, .08);
            color: #0a6b67;
            border: 1px solid #c8e6e2;
        }

        .bd-status .bd-status-icon {
            flex-shrink: 0;
            width: 16px;
            text-align: center;
        }

        details.bd-env {
            border: 1px solid #e3eeeb;
            border-radius: 12px;
            background: #fff;
            margin-top: 14px;
            overflow: hidden;
        }

        details.bd-env>summary {
            list-style: none;
            cursor: pointer;
            padding: 10px 12px;
            font-weight: 600;
            font-size: .84rem;
            color: #0b2a38;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fbfdfc;
        }

        details.bd-env>summary::-webkit-details-marker {
            display: none;
        }

        details.bd-env>summary .bd-caret {
            margin-left: auto;
            transition: transform .2s;
            color: #5d7078;
        }

        details.bd-env[open]>summary .bd-caret {
            transform: rotate(180deg);
        }

        details.bd-env .bd-env-body {
            padding: 10px 12px 12px;
            border-top: 1px solid #e3eeeb;
        }

        .env-table code {
            font-size: .76rem;
            color: #33465a;
            background: #f1f5f9;
            padding: 1px 5px;
            border-radius: 4px;
        }

        .env-table td {
            vertical-align: middle;
            word-break: break-all;
        }

        .bd-note {
            font-size: .76rem;
            color: #5d7078;
            margin: 10px 0 0;
        }

        .bd-note code {
            font-size: .72rem;
        }

        @media (max-width: 575.98px) {
            .login-card-body {
                padding: 20px 16px;
            }

            .bd-actions {
                grid-template-columns: 1fr !important;
            }
        }

        .bd-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
    </style>
</head>

<body class="hold-transition login-page">
    <main class="login-box">
        <div class="login-logo mb-3">
            <b><i class="fas fa-database"></i> Configuración de base de datos</b>
        </div>

        <div class="card">
            <div class="card-body login-card-body">

                <?php if ($envMode): ?>
                    <div class="alert alert-warning py-2 small mb-3" style="border-radius:10px">
                        <i class="fas fa-info-circle"></i>
                        Faltan parámetros del entorno. Completa el nombre y la URL del sitio.
                    </div>
                <?php elseif ($detail !== null && $detail !== ''): ?>
                    <div class="alert alert-danger py-2 small mb-3" style="border-radius:10px">
                        <i class="fas fa-exclamation-triangle"></i>
                        No se pudo conectar con la configuración actual.
                        <code style="font-size:.72rem;word-break:break-all"><?= e($detail) ?></code>
                    </div>
                <?php endif; ?>

                <form method="post" action="" id="bd-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_bd_setup" value="1">
                    <input type="hidden" name="action" id="bd-action" value="save">

                    <?php if ($envMode): ?>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="app_name">Nombre del sistema</label>
                                <input type="text" class="form-control" id="app_name" name="app_name"
                                    value="<?= e($values['app_name']) ?>" placeholder="Clínica Vida Salud" required>
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <label for="app_url">URL del sitio</label>
                                <input type="text" class="form-control" id="app_url" name="app_url"
                                    value="<?= e($values['app_url']) ?>" placeholder="https://ejemplo.com" required>
                            </div>
                        </div>
                    <?php endif; ?>

                    <p class="small text-muted mb-2">¿A dónde quieres conectar el sistema?</p>

                    <div class="form-row">
                        <div class="form-group col-12 col-md-4">
                            <label for="connection">Motor</label>
                            <select class="form-control" id="connection" name="connection">
                                <option value="pgsql"<?= $values['connection'] === 'pgsql' ? ' selected' : '' ?>>PostgreSQL</option>
                                <option value="mysql"<?= $values['connection'] === 'mysql' ? ' selected' : '' ?>>MySQL / MariaDB</option>
                            </select>
                        </div>
                        <div class="form-group col-12 col-md-5">
                            <label for="host">Host</label>
                            <input type="text" class="form-control" id="host" name="host"
                                value="<?= e($values['host']) ?>" placeholder="localhost" required
                                autocapitalize="none" spellcheck="false">
                        </div>
                        <div class="form-group col-12 col-md-3">
                            <label for="port">Puerto</label>
                            <input type="text" class="form-control" id="port" name="port"
                                value="<?= e($values['port']) ?>" placeholder="5432" inputmode="numeric">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-12 col-md-5">
                            <label for="database">Base de datos</label>
                            <input type="text" class="form-control" id="database" name="database"
                                value="<?= e($values['database']) ?>" placeholder="medicina" required
                                autocapitalize="none" spellcheck="false">
                        </div>
                        <div class="form-group col-12 col-md-4">
                            <label for="username">Usuario</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?= e($values['username']) ?>" placeholder="postgres" required
                                autocomplete="username" autocapitalize="none" spellcheck="false">
                        </div>
                        <div class="form-group col-12 col-md-3">
                            <label for="charset">Charset</label>
                            <input type="text" class="form-control" id="charset" name="charset"
                                value="<?= e($values['charset']) ?>" placeholder="utf8">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password"
                                autocomplete="new-password" style="border-right:0"
                                placeholder="<?= $passwordSet ? 'Déjalo vacío para conservar la actual' : 'Contraseña de la base de datos' ?>">
                            <div class="input-group-append">
                                <button type="button" class="bd-eye" id="btn-ojo" aria-label="Mostrar contraseña"
                                    title="Mostrar u ocultar contraseña">
                                    <i class="fas fa-eye" id="ojo-icono"></i>
                                </button>
                            </div>
                        </div>
                        <?php if ($passwordSet): ?>
                            <small class="form-text text-muted">
                                Ya hay una contraseña en <code>.env</code>; déjala vacía para no cambiarla.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="bd-status d-none" id="estado" role="status" aria-live="polite">
                        <span class="bd-status-icon" id="estado-icono"><i class="fas fa-circle-notch fa-spin"></i></span>
                        <span id="estado-texto"></span>
                    </div>

                    <div class="bd-actions mb-2">
                        <button type="button" class="btn btn-outline-primary" id="btn-test">
                            <i class="fas fa-plug"></i> Probar conexión
                        </button>
                        <button type="submit" class="btn btn-primary" id="btn-guardar">
                            <span class="normal-text"><i class="fas fa-save"></i> Guardar y continuar</span>
                            <span class="loading-text" style="display:none"><i class="fas fa-circle-notch fa-spin"></i> Guardando…</span>
                        </button>
                    </div>

                    <p class="bd-note">
                        <i class="fas fa-shield-alt"></i>
                        Al guardar se escribirán en <code>.env</code>:
                        <code>DB_CONNECTION</code>, <code>DB_HOST</code>, <code>DB_PORT</code>,
                        <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code> y
                        <code>DB_CHARSET</code>.
                    </p>
                </form>

                <?php if ($envRows !== []): ?>
                    <details class="bd-env">
                        <summary>
                            <i class="fas fa-file-code"></i>
                            Parámetros actuales de <code>.env</code>
                            <i class="fas fa-chevron-down bd-caret"></i>
                        </summary>
                        <div class="bd-env-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered env-table mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 40%;">Parámetro</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($envRows as $row): ?>
                                            <tr>
                                                <td class="text-nowrap"><code><?= e($row['key']) ?></code></td>
                                                <td<?= $row['secret'] ? ' class="text-muted"' : '' ?>>
                                                    <?php if ($row['secret']): ?>
                                                        <i class="fas fa-lock" title="valor oculto"></i>
                                                    <?php endif; ?>
                                                    <?= e($row['value']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        </div>

        <p class="small text-muted mt-3 mb-0 text-center">
            Esta pantalla solo aparece mientras no exista una conexión con la base de datos.
        </p>
    </main>

    <script src="<?= e($assetBase) ?>/code/plugins/jquery/jquery.min.js"></script>
    <script src="<?= e($assetBase) ?>/code/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        (function ($) {
            var url = $('#bd-form').attr('action') || window.location.href;
            var $estado = $('#estado');
            var $estadoTexto = $('#estado-texto');
            var $estadoIcono = $('#estado-icono');

            function mostrarEstado(tipo, texto, iconoHtml) {
                $estado
                    .removeClass('d-none alert-success alert-danger alert-info alert-warning')
                    .addClass('alert-' + tipo);
                $estadoIcono.html(iconoHtml || '<i class="fas fa-circle-notch fa-spin"></i>');
                $estadoTexto.text(texto);
            }

            /* Puerto automatico al cambiar de motor */
            $('#connection').on('change', function () {
                var $port = $('#port');
                var actual = $port.val().trim();
                var nuevo = $(this).val() === 'mysql' ? '3306' : '5432';

                if (actual === '' || actual === '5432' || actual === '5433' || actual === '3306') {
                    $port.val(nuevo);
                }
            });

            /* Mostrar / ocultar contrasena */
            $('#btn-ojo').on('click', function () {
                var $input = $('#password');
                var visible = $input.attr('type') === 'text';

                $input.attr('type', visible ? 'password' : 'text');
                $('#ojo-icono').attr('class', visible ? 'fas fa-eye' : 'fas fa-eye-slash');
            });

            /* Probar conexion */
            $('#btn-test').on('click', function () {
                var $btn = $(this).prop('disabled', true);

                mostrarEstado('info', 'Probando la conexión…');

                $.ajax({ url: url, method: 'POST', data: $('#bd-form').serialize() + '&action=test' })
                    .done(function (r) {
                        if (r && r.success) {
                            var ms = (typeof r.ms === 'number') ? ' (' + r.ms + ' ms)' : '';
                            mostrarEstado('success', (r.message || 'Conexión exitosa.') + ms,
                                '<i class="fas fa-check-circle"></i>');
                        } else {
                            mostrarEstado('danger', (r && r.message) ? r.message : 'La conexión falló.',
                                '<i class="fas fa-times-circle"></i>');
                        }
                    })
                    .fail(function (xhr) {
                        var r = xhr.responseJSON || {};
                        mostrarEstado('danger', r.message || 'No se pudo probar la conexión.',
                            '<i class="fas fa-times-circle"></i>');
                    })
                    .always(function () {
                        $btn.prop('disabled', false);
                    });
            });

            /* Guardar y continuar */
            $('#bd-form').on('submit', function (e) {
                e.preventDefault();

                var $form = $(this);
                var $btn = $('#btn-guardar').prop('disabled', true);

                $btn.find('.normal-text').hide();
                $btn.find('.loading-text').show();
                mostrarEstado('info', 'Verificando y guardando…');

                $.ajax({ url: url, method: 'POST', data: $form.serialize() })
                    .done(function (r) {
                        if (r && r.success) {
                            mostrarEstado('success', r.message || 'Guardado. Recargando…',
                                '<i class="fas fa-check-circle"></i>');
                            setTimeout(function () { window.location.reload(); }, 700);
                        } else {
                            mostrarEstado('danger', (r && r.message) ? r.message : 'No se pudo guardar.',
                                '<i class="fas fa-times-circle"></i>');
                        }
                    })
                    .fail(function (xhr) {
                        var r = xhr.responseJSON || {};
                        mostrarEstado('danger', r.message || 'No se pudo guardar.',
                            '<i class="fas fa-times-circle"></i>');
                    })
                    .always(function () {
                        $btn.prop('disabled', false);
                        $btn.find('.normal-text').show();
                        $btn.find('.loading-text').hide();
                    });
            });
        })(jQuery);
    </script>
</body>

</html>
