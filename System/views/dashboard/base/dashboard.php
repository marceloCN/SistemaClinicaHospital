<!DOCTYPE html>
<html lang="es">

<head>


    {head}

    <!-- AdminLTE (incluye Bootstrap) - version purgada de selectores no usados -->
    <link rel="stylesheet" href="<?= asset('code/dist/css/adminlte.purged.min.css') ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= asset('code/plugins/fontawesome-free/css/all.min.css') ?>">
    <!-- Ajustes del sistema -->
    <link rel="stylesheet" href="<?= asset('code/css/app.css') ?>">

    {link}

</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed" style="height: auto;">
    <div class="wrapper">

        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="<?= \lib\Route::route('system') ?>" class="nav-link">Panel</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="<?= asset('/') ?>" target="_blank" class="nav-link">Ver sitio</a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link"> Fecha <?= date("d-m-Y") ?> </span>
                </li>
                <li class="nav-item">
                    <span class="nav-link" id="reloj"> </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
            </ul>
            <script>
                function actualizarReloj() {
                    var ahora = new Date();
                    var hora = ahora.getHours();
                    var minutos = ahora.getMinutes();
                    var segundos = ahora.getSeconds();
                    document.getElementById('reloj').innerHTML = '  Hora: ' + hora + ':' + minutos + ':' + segundos;

                    setTimeout(actualizarReloj, 1000); // Actualizar cada segundo
                }

                actualizarReloj(); // Iniciar la actualización del reloj
            </script>
        </nav>

        <?php
        $cmsCurrentPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $cmsActive = static function (string $name) use ($cmsCurrentPath): string {
            $path = \lib\Route::route($name);

            return $path !== null && rtrim($path, '/') === $cmsCurrentPath ? ' active' : '';
        };
        ?>
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="<?= \lib\Route::route('system') ?>" class="brand-link">
                <img src="<?= asset('code/dist/img/AdminLTELogo.png') ?>" alt="logo" class="brand-image img-circle elevation-3" style="opacity: 0.8" />
                <span class="brand-text font-weight-light">Administrador</span>
            </a>
            <div class="sidebar">
                <div class="user-panel">

                    <div class="user-panel mt-3 pb-3 mb-3 d-flex">

                        <div class="info">
                            <a href="#" class="d-block" data-toggle="modal" data-target="#modal-mi-usuario" title="Editar mi usuario">
                                <i class="fas fa-user-edit mr-1"></i>
                                <?= e((string) \Lib\Support\Session::get('nombre', 'Usuario')) ?>
                            </a>
                        </div>



                    </div>

                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        <li class="nav-header">Sistema</li>
                        <li class="nav-item">
                            <a href="<?= \lib\Route::route('system') ?>" class="nav-link<?= $cmsActive('system') ?>">
                                <i class="fas fa-tachometer-alt nav-icon"></i>
                                <p>Resumen</p>
                            </a>
                        </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('usuarios') ?>" class="nav-link<?= $cmsActive('usuarios') ?>">
                                    <i class="fas fa-users nav-icon"></i>
                                    <p>Usuarios</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('system_routes') ?>" class="nav-link<?= $cmsActive('system_routes') ?>">
                                    <i class="fas fa-route nav-icon"></i>
                                    <p>Rutas y enlaces</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('system_middleware') ?>" class="nav-link<?= $cmsActive('system_middleware') ?>">
                                    <i class="fas fa-shield-alt nav-icon"></i>
                                    <p>Middlewares</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('system_generators') ?>" class="nav-link<?= $cmsActive('system_generators') ?>">
                                    <i class="fas fa-magic nav-icon"></i>
                                    <p>Generadores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('system_home') ?>" class="nav-link<?= $cmsActive('system_home') ?>">
                                    <i class="fas fa-home nav-icon"></i>
                                    <p>Página de inicio</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('system_database') ?>" class="nav-link<?= $cmsActive('system_database') ?>">
                                    <i class="fas fa-database nav-icon"></i>
                                    <p>Base de datos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('system_docs') ?>" class="nav-link<?= $cmsActive('system_docs') ?>">
                                    <i class="fas fa-book nav-icon"></i>
                                    <p>Documentación</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= \lib\Route::route('system_docs_download_base') ?>" class="nav-link">
                                    <i class="fas fa-file-archive nav-icon"></i>
                                    <p>Descargar base CMS</p>
                                </a>
                            </li>

                        <li class="nav-header">Sesión</li>
                        <li class="nav-item">
                            <a href="<?= \lib\Route::route('panel') ?>" class="nav-link<?= $cmsActive('panel') ?>">
                                <i class="fas fa-id-badge nav-icon"></i>
                                <p>Panel</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= asset('/') ?>" target="_blank" class="nav-link">
                                <i class="fas fa-globe nav-icon"></i>
                                <p>Ver sitio</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <form action="<?= \lib\Route::route('logout') ?>" method="POST" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="nav-link btn btn-link text-left w-100 border-0">
                                    <i class="fas fa-sign-out-alt nav-icon"></i>
                                    <p>Cerrar Sesión</p>
                                </button>
                            </form>
                        </li>

                    </ul>
                </nav>

            </div>
        </aside>


        <main class="content-wrapper" role="main">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">{title}</h1>
                        </div>
                        <!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="<?= \lib\Route::route('system') ?>">Sistema</a></li>
                                <li class="breadcrumb-item active">{session}</li>
                            </ol>
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.container-fluid -->
            </div>

            <div class="content">
                {content}
            </div>
        </main>
        <!-- Main Footer -->
        <footer class="main-footer">
            <strong><?= $appName ?? 'Sistema CMS' ?></strong> &copy; <?= date('Y') ?>
        </footer>

    </div>

    <div class="modal fade" id="modal-mi-usuario" tabindex="-1" role="dialog" aria-labelledby="modal-mi-usuario-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="form-mi-usuario" autocomplete="off">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-mi-usuario-title">Mi usuario</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="mi-usuario-name">Nombre</label>
                            <input type="text" class="form-control" id="mi-usuario-name" name="name" maxlength="100"
                                value="<?= e((string) \Lib\Support\Session::get('nombre', '')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mi-usuario-email">Usuario o correo</label>
                            <input type="text" class="form-control" id="mi-usuario-email" name="email" maxlength="150"
                                value="<?= e((string) \Lib\Support\Session::get('email', '')) ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mi-usuario-password">Contraseña</label>
                                    <input type="password" class="form-control" id="mi-usuario-password" name="password" autocomplete="new-password">
                                    <small class="form-text text-muted">Déjala vacía para conservar la actual.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mi-usuario-password-confirmation">Confirmar contraseña</label>
                                    <input type="password" class="form-control" id="mi-usuario-password-confirmation" name="password_confirmation" autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
            $('#form-mi-usuario').on('submit', function(e) {
                e.preventDefault();

                $.post("<?= route('usuarios_actualizar', ['id' => (int) \Lib\Support\Session::get('id_usuario', 0)]) ?>", $(this).serialize(), function(res) {
                    if (res && res.success) {
                        $('#modal-mi-usuario').modal('hide');
                        showNotification('success', res.message || 'Usuario actualizado correctamente.');
                        setTimeout(function() { window.location.reload(); }, 800);
                    }
                }, 'json').fail(function(xhr) {
                    var mensaje = (xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar la solicitud.';
                    showNotification('error', mensaje);
                });
            });
        });
    </script>

    {src}

    <?php if (isset($_SESSION[constant('APP')]['mensaje'])) { ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('error', <?= json_encode($_SESSION[constant('APP')]['mensaje']) ?>);
            });
        </script>
    <?php }
    unset($_SESSION[constant('APP')]['mensaje']);
    ?>

    <?php if (isset($_SESSION[constant('APP')]['success'])) { ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('success', <?= json_encode($_SESSION[constant('APP')]['success']) ?>);
            });
        </script>
    <?php }
    unset($_SESSION[constant('APP')]['success']);
    ?>

</body>



</html>
