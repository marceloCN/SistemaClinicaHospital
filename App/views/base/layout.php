<?php
$config = $config ?? [];
$imagenFondoConfig = (string) ($config['pagina_inicial_imagen_fondo'] ?? '');
$imagenFondoUrl = imagenHeroUrl($imagenFondoConfig);
?>
<!DOCTYPE html>
<html lang="es"<?= ($config['tema_modo'] ?? 'claro') === 'oscuro' ? ' data-tema="oscuro"' : '' ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {head}

    <link rel="stylesheet" href="<?= asset('code/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('medicina/css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('medicina/css/inicio.css') ?>">
    <?php if (($config['tema_modo'] ?? 'claro') === 'oscuro') { ?>
        <link rel="stylesheet" href="<?= asset('medicina/css/tema-oscuro.css') ?>">
    <?php } ?>
    <style>
        :root {
            --cl-primary: <?= e((string) ($config['tema_color_primario'] ?? '#0f8b84')) ?>;
            --cl-primary-dark: color-mix(in srgb, var(--cl-primary) 78%, black);
            --cl-secondary: <?= e((string) ($config['tema_color_secundario'] ?? '#0b2a38')) ?>;
            --cl-accent: color-mix(in srgb, var(--cl-primary) 12%, white);
            --cl-hero-bg: <?= $imagenFondoUrl !== '' ? "url('" . e($imagenFondoUrl) . "')" : 'none' ?>;
            --med-primary: var(--cl-primary);
            --med-primary-dark: var(--cl-primary-dark);
            --med-accent: var(--cl-accent);
        }
    </style>
</head>

<?php
$contacto = $contacto ?? [];
$config = $config ?? [];
$appName = (string) ($config['clinica_nombre'] ?? 'GUPADIS');
$appTag = (string) ($config['clinica_eslogan'] ?? 'Clínica privada');
$telefono = (string) ($contacto['telefono'] ?? '');
$emergencias = (string) ($contacto['emergencias'] ?? '');
$correo = (string) ($contacto['correo'] ?? '');
$direccion = (string) ($contacto['direccion'] ?? '');
$horario = (string) ($contacto['horario'] ?? '');
$sabado = (string) ($contacto['sabado'] ?? '');
$telHref = 'tel:' . preg_replace('/[^0-9+]/', '', $telefono);
$emergenciaHref = 'tel:' . preg_replace('/[^0-9+]/', '', $emergencias);
$mostrarServicios = (bool) ($config['inicio_mostrar_servicios'] ?? true);
$mostrarEspecialidades = (bool) ($config['inicio_mostrar_especialidades'] ?? true);
$mostrarMedicos = (bool) ($config['inicio_mostrar_medicos'] ?? true);
$mostrarContacto = (bool) ($config['inicio_mostrar_contacto'] ?? true);
$anclaContacto = $mostrarContacto ? URL . '/#contacto' : URL . '/';
?>

<body class="cl-body">
    <a class="cl-skip-link" href="#contenido">Saltar al contenido</a>

    <div class="cl-topbar">
        <div class="cl-container cl-topbar-inner">
            <div class="cl-topbar-group">
                <a href="<?= e($telHref) ?>"><i class="fas fa-phone-alt"></i> <?= e($telefono) ?></a>
                <span class="cl-topbar-address"><i class="fas fa-map-marker-alt"></i> <?= e($direccion) ?></span>
            </div>
            <div class="cl-topbar-group">
                <span class="cl-topbar-hours"><i class="fas fa-clock"></i> <?= e($horario) ?></span>
                <a class="cl-topbar-emergency" href="<?= e($emergenciaHref) ?>">
                    <i class="fas fa-ambulance"></i> Emergencias <?= e($emergencias) ?>
                </a>
            </div>
        </div>
    </div>

    <header class="cl-nav" id="clNav">
        <div class="cl-container cl-nav-inner">
            <a class="cl-brand" href="<?= URL ?>/" aria-label="<?= e($appName) ?>, ir al inicio">
                <span class="cl-brand-mark"><i class="fas fa-heartbeat"></i></span>
                <span class="cl-brand-text">
                    <strong><?= e($appName) ?></strong>
                    <small><?= e($appTag) ?></small>
                </span>
            </a>

            <button class="cl-nav-toggle" type="button" id="clNavToggle"
                aria-label="Abrir menú de navegación" aria-expanded="false" aria-controls="clNavLinks">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="cl-nav-links" id="clNavLinks" aria-label="Navegación principal">
                <a href="<?= URL ?>/">Inicio</a>
                <?php if ($mostrarServicios) { ?>
                <a href="<?= URL ?>/#servicios">Servicios</a>
                <?php } ?>
                <?php if ($mostrarEspecialidades) { ?>
                <a href="<?= URL ?>/#especialidades">Especialidades</a>
                <?php } ?>
                <?php if ($mostrarMedicos) { ?>
                <a href="<?= URL ?>/#medicos">Médicos</a>
                <?php } ?>
                <?php if ($mostrarContacto) { ?>
                <a href="<?= URL ?>/#contacto">Contacto</a>
                <?php } ?>
                <a class="cl-btn cl-btn-primary cl-btn-sm cl-nav-cta" href="<?= route('portal_login') ?>">
                    <i class="fas fa-user-lock"></i> Portal del paciente
                </a>
            </nav>
        </div>
    </header>

    <main id="contenido" class="cl-main-content">
        {content_base}
    </main>

    <footer class="cl-footer">
        <div class="cl-container cl-footer-grid">
            <div class="cl-footer-brand">
                <a class="cl-brand cl-brand-light" href="<?= URL ?>/">
                    <span class="cl-brand-mark"><i class="fas fa-heartbeat"></i></span>
                    <span class="cl-brand-text">
                        <strong><?= e($appName) ?></strong>
                        <small><?= e($appTag) ?></small>
                    </span>
                </a>
                <p>Atención médica integral con calidez humana, tecnología y especialistas comprometidos con tu bienestar.</p>
                <div class="cl-footer-social">
                    <a href="<?= $anclaContacto ?>" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?= $anclaContacto ?>" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="<?= $anclaContacto ?>" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <div class="cl-footer-col">
                <h4>Navegación</h4>
                <ul>
                    <li><a href="<?= URL ?>/">Inicio</a></li>
                    <?php if ($mostrarServicios) { ?>
                    <li><a href="<?= URL ?>/#servicios">Servicios</a></li>
                    <?php } ?>
                    <?php if ($mostrarEspecialidades) { ?>
                    <li><a href="<?= URL ?>/#especialidades">Especialidades</a></li>
                    <?php } ?>
                    <?php if ($mostrarMedicos) { ?>
                    <li><a href="<?= URL ?>/#medicos">Médicos</a></li>
                    <?php } ?>
                </ul>
            </div>

            <?php if ($mostrarServicios) { ?>
            <div class="cl-footer-col">
                <h4>Servicios</h4>
                <ul>
                    <li><a href="<?= URL ?>/#servicios">Consulta especializada</a></li>
                    <li><a href="<?= URL ?>/#servicios">Laboratorio clínico</a></li>
                    <li><a href="<?= URL ?>/#servicios">Diagnóstico por imagen</a></li>
                    <li><a href="<?= URL ?>/#servicios">Emergencias 24/7</a></li>
                </ul>
            </div>
            <?php } ?>

            <div class="cl-footer-col">
                <h4>Contacto</h4>
                <ul class="cl-footer-contact">
                    <li><i class="fas fa-phone-alt"></i> <a href="<?= e($telHref) ?>"><?= e($telefono) ?></a></li>
                    <li><i class="fas fa-ambulance"></i> <a href="<?= e($emergenciaHref) ?>"><?= e($emergencias) ?></a></li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:<?= e($correo) ?>"><?= e($correo) ?></a></li>
                    <li><i class="fas fa-map-marker-alt"></i> <?= e($direccion) ?></li>
                </ul>
            </div>
        </div>

        <div class="cl-footer-bottom">
            <div class="cl-container">
                <span>&copy; <?= date('Y') ?> <?= e($appName) ?>.</span>
                <span><?= e($horario) ?> · <?= e($sabado) ?></span>
            </div>
        </div>
    </footer>

    <script src="<?= asset('code/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset('medicina/js/app.js') ?>" defer></script>
</body>

</html>
