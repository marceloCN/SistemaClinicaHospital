<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña · Portal Clínica Vida Salud</title>
    <link rel="icon" href="<?= asset('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= asset('code/dist/css/adminlte.purged.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('code/plugins/fontawesome-free/css/all.min.css') ?>">
    <style>
        .login-page { background: linear-gradient(135deg, #f7f9f6 0%, #e6f5f2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; flex-direction: column; }
        .login-box { width: 100%; max-width: 420px; }
        .login-card-body { border-radius: 18px; box-shadow: 0 18px 45px rgba(11,42,56,0.1); }
        .btn-primary { background-color: #0f8b84; border-color: #0f8b84; }
        .btn-primary:hover { background-color: #0a6b67; border-color: #0a6b67; }
    </style>
</head>

<body class="hold-transition login-page">
    <main class="login-box">
        <div class="login-logo">
            <a href="<?= route('inicio') ?>"><b>Portal · Clínica Vida Salud</b></a>
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Crea una nueva contraseña</p>

                <form id="restablecer-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e((string) ($token ?? '')) ?>">

                    <div class="input-group mb-3">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Nueva contraseña" required minlength="6">
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirmar contraseña" required minlength="6">
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block" id="btn-guardar">
                                <span class="normal-text"><i class="fas fa-save"></i> Guardar contraseña</span>
                                <span class="loading-text" style="display:none"><i class="fas fa-circle-notch fa-spin"></i> Guardando...</span>
                            </button>
                        </div>
                    </div>
                </form>

                <p class="mt-3 mb-0 text-center">
                    <a href="<?= route('portal_login') ?>">Volver al inicio de sesión</a>
                </p>
            </div>
        </div>
    </main>

    <script src="<?= asset('code/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset('code/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('code/js/notification.js') ?>"></script>
    <script>
        $(function () {
            var $form = $('#restablecer-form');
            var $btn = $('#btn-guardar');

            function toggleLoading(loading) {
                $btn.prop('disabled', loading);
                $btn.find('.normal-text').toggle(!loading);
                $btn.find('.loading-text').toggle(loading);
            }

            $form.on('submit', function (e) {
                e.preventDefault();

                var password = $('#password').val();
                var confirm = $('#password_confirmation').val();

                if (password !== confirm) {
                    showNotification('warning', 'Las contraseñas no coinciden.');
                    return;
                }

                toggleLoading(true);

                $.ajax({
                    url: "<?= route('portal_password_actualizar') ?>",
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    data: JSON.stringify({
                        token: $form.find('[name="token"]').val(),
                        password: password,
                        _token: "<?= csrf_token() ?>"
                    }),
                    dataType: 'json'
                })
                .done(function (data) {
                    showNotification(data.success ? 'success' : 'error', data.message);
                    if (data.success && data.redirect) {
                        setTimeout(function () {
                            window.location.href = data.redirect;
                        }, 1200);
                    }
                })
                .fail(function () {
                    showNotification('error', 'Error de conexión.');
                })
                .always(function () {
                    toggleLoading(false);
                });
            });
        });
    </script>
</body>
</html>
