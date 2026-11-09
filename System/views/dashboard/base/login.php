<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login del Sistema</title>
    <meta name="description" content="Acceso al panel de administración de <?= e((string) config('app.name')) ?>.">
    <link rel="icon" href="<?= asset('favicon.ico') ?>">

    <!-- AdminLTE (incluye Bootstrap) - version purgada de selectores no usados -->
    <link rel="stylesheet" href="<?= asset('code/dist/css/adminlte.purged.min.css') ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= asset('code/plugins/fontawesome-free/css/all.min.css') ?>">
    <!-- Ajustes del sistema -->
    <link rel="stylesheet" href="<?= asset('code/css/app.css') ?>">
</head>

<body class="login-page" style="min-height: 496.8px;">
    <main class="login-box">
        <div class="login-logo">
            <a href="<?= \lib\Route::route('login') ?>"><b>Login de acceso</b></a>
        </div>
        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Ingresa tus datos de sesión</p>

                <form id="login-form" action="#" method="post">
                    <div class="input-group mb-3">
                        <input type="text" id="email" class="form-control" placeholder="Login de usuario" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" id="password" class="form-control" placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- /.col -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>

            </div>
            <!-- /.login-card-body -->
        </div>
    </main>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="<?= asset('code/plugins/jquery/jquery.min.js') ?>" defer></script>
    <!-- Bootstrap 4 -->
    <script src="<?= asset('code/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>" defer></script>
    <!-- AdminLTE App -->
    <script src="<?= asset('code/dist/js/adminlte.min.js') ?>" defer></script>
    <!-- Notificaciones -->
    <script src="<?= asset('code/js/notification.js') ?>" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#login-form').on('submit', function(e) {
                e.preventDefault();

                let email = $('#email').val();
                let password = $('#password').val();

                let formData = {
                    email: email,
                    password: password,
                    _token: "<?= csrf_token() ?>"
                };

                $.ajax({
                    type: 'POST',
                    url: "<?= \lib\Route::route('verify_credentials') ?>",
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect || "<?= \lib\Route::route('system') ?>";
                        } else {
                            showNotification('error', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            return;
                        }

                        let message = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'Se produjo un error al procesar su solicitud.';
                        showNotification('error', message);
                    }
                });
            });
        });
    </script>

</body>

</html>
