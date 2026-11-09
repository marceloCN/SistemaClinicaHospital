<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal del paciente · Clínica Vida Salud</title>
    <meta name="description" content="Acceso seguro al portal de pacientes y personal de la clínica.">
    <link rel="icon" href="<?= asset('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= asset('code/dist/css/adminlte.purged.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('code/plugins/fontawesome-free/css/all.min.css') ?>">
    <style>
        .login-page {
            background: linear-gradient(135deg, #f7f9f6 0%, #e6f5f2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 1rem;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-logo a {
            color: #0f8b84;
            font-weight: 800;
            font-size: clamp(1.25rem, 4vw, 1.5rem);
            text-decoration: none;
        }

        .login-card-body {
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(11, 42, 56, 0.1);
            padding: 1.5rem;
        }

        .login-box-msg {
            font-size: 1rem;
            color: #5d7078;
            margin-bottom: 1.25rem;
        }

        .btn-primary {
            background-color: #0f8b84;
            border-color: #0f8b84;
            border-radius: 10px;
            font-weight: 600;
            padding: 0.55rem 1rem;
        }

        .btn-primary:hover {
            background-color: #0a6b67;
            border-color: #0a6b67;
        }

        .btn-outline-primary {
            color: #0f8b84;
            border-color: #0f8b84;
            border-radius: 10px;
            font-weight: 600;
            padding: 0.55rem 1rem;
        }

        .btn-outline-primary:hover {
            background-color: #0f8b84;
            color: #fff;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            margin-top: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .login-options a {
            color: #0f8b84;
            text-decoration: none;
        }

        .login-options a:hover {
            text-decoration: underline;
        }

        .attempts-alert {
            display: none;
            font-size: 0.85rem;
            color: #c0392b;
            margin-top: 8px;
        }

        .login-footer {
            margin-top: 1rem;
            text-align: center;
            color: #5d7078;
            font-size: 0.85rem;
        }

        @media (max-width: 575.98px) {
            .login-page {
                padding: 0.75rem;
                justify-content: flex-start;
                padding-top: 2rem;
            }

            .login-card-body {
                padding: 1.25rem;
                border-radius: 14px;
            }

            .login-box-msg {
                font-size: 0.95rem;
            }

            .login-options {
                flex-direction: column;
                align-items: flex-start;
                font-size: 0.85rem;
            }

            /* Evita el zoom automático de iOS al enfocar un campo. */
            input[type="text"],
            input[type="password"] {
                font-size: 16px;
            }
        }
    </style>
</head>

<body class="hold-transition login-page">
    <main class="login-box">
        <div class="login-logo text-center mb-3">
            <a href="<?= route('inicio') ?>"><b>Portal · Clínica Vida Salud</b></a>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg text-center">Ingresa tus datos de acceso</p>

                <form id="login-form" action="<?= route('portal_verify_credentials') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="input-group mb-3">
                        <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Usuario o correo electrónico" required autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-user"></span></div>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>

                    <div id="attempts-alert" class="attempts-alert">
                        <i class="fas fa-exclamation-circle"></i> <span id="attempts-text"></span>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block" id="btn-login">
                                <span class="normal-text"><i class="fas fa-sign-in-alt"></i> Ingresar</span>
                                <span class="loading-text" style="display:none"><i class="fas fa-circle-notch fa-spin"></i> Verificando...</span>
                            </button>
                        </div>
                    </div>

                    <div class="login-options">
                        <a href="<?= route('inicio') ?>"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
                        <a href="<?= route('portal_password_solicitar') ?>">¿Olvidaste tu contraseña?</a>
                    </div>
                </form>

                <hr class="my-3">

                <button type="button" class="btn btn-outline-primary btn-block" id="btn-abrir-registro">
                    <i class="fas fa-user-plus"></i> Crear usuario paciente
                </button>
                <p class="text-center text-muted mt-2 mb-0" style="font-size:0.8rem;">
                    ¿Todavía no tienes cuenta? Regístrate como paciente en un solo paso.
                </p>
            </div>
        </div>

        <p class="login-footer">
            &copy; <?= date('Y') ?> Clínica Vida Salud · Sistema de citas médicas
        </p>
    </main>

    <script src="<?= asset('code/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset('code/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('code/dist/js/adminlte.min.js') ?>"></script>
    <script src="<?= asset('code/js/notification.js') ?>"></script>

    <script>
        $(function () {
            var $form = $('#login-form');
            var $btn = $('#btn-login');
            var $alertBox = $('#attempts-alert');
            var $alertText = $('#attempts-text');

            $('#btn-abrir-registro').on('click', function () {
                $('#modal-registro-paciente').modal('show');
            });

            function toggleLoading(loading) {
                $btn.prop('disabled', loading);
                $btn.find('.normal-text').toggle(!loading);
                $btn.find('.loading-text').toggle(loading);
            }

            $form.on('submit', function (e) {
                e.preventDefault();

                var usuario = $.trim($('#usuario').val());
                var password = $('#password').val();

                if (!usuario || !password) {
                    showNotification('warning', 'Completa usuario y contraseña.');
                    return;
                }

                toggleLoading(true);
                $alertBox.hide();

                $.ajax({
                    url: "<?= route('portal_verify_credentials') ?>",
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    data: JSON.stringify({
                        usuario: usuario,
                        password: password,
                        _token: "<?= csrf_token() ?>"
                    }),
                    dataType: 'json'
                })
                .done(function (data) {
                    if (data.success) {
                        showNotification('success', 'Bienvenido al portal.');
                        setTimeout(function () {
                            window.location.href = data.redirect || "<?= route('portal_dashboard') ?>";
                        }, 500);
                    } else {
                        toggleLoading(false);
                        showNotification('error', data.message || 'Error al iniciar sesión.');
                    }
                })
                .fail(function (xhr) {
                    toggleLoading(false);
                    var data = xhr.responseJSON || {};
                    var message = data.message || 'Error al iniciar sesión.';
                    showNotification('error', message);

                    if (xhr.status === 401 && message.toLowerCase().indexOf('intentos') !== -1) {
                        $alertText.text(message);
                        $alertBox.show();
                    }
                });
            });
        });
    </script>

    <?= view('portal.auth.registro') ?>
</body>

</html>
