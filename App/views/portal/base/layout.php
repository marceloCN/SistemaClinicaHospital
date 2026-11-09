<?php

use App\Models\ConfiguracionSistema;
use Lib\Support\Session;

$configPortal = $configPortal ?? ConfiguracionSistema::publica();
$portalNombre = (string) ($configPortal['clinica_nombre'] ?? 'Clínica Vida Salud');
$portalPrimario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($configPortal['tema_color_primario'] ?? ''))
    ? (string) $configPortal['tema_color_primario']
    : '#0f8b84';
$portalSecundario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($configPortal['tema_color_secundario'] ?? ''))
    ? (string) $configPortal['tema_color_secundario']
    : '#0b2a38';

$portalPermisos = (array) Session::get('portal_permisos', []);
$verPacientes = ($rolClave ?? '') === 'ADMIN' || in_array('120', $portalPermisos, true);

$rolEtiqueta = [
    'ADMIN' => 'Administrador',
    'RECEP' => 'Recepcionista',
    'MEDICO' => 'Médico',
    'PAC' => 'Paciente',
][$rolClave ?? ''] ?? 'Usuario';

$esAdmin = ($rolClave ?? '') === 'ADMIN';
$esRecep = ($rolClave ?? '') === 'RECEP';
$esMedico = ($rolClave ?? '') === 'MEDICO';
$esPaciente = ($rolClave ?? '') === 'PAC';

// Helpers para marcar elemento activo
$active = $active ?? '';
$isActive = fn(string $clave): string => $active === $clave ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="es"<?= ($configPortal['tema_modo'] ?? 'claro') === 'oscuro' ? ' data-tema="oscuro"' : '' ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi portal · <?= e($portalNombre) ?></title>
    <link rel="icon" href="<?= asset('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= asset('code/dist/css/adminlte.purged.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('code/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('medicina/css/tema-oscuro.css') ?>">
    <script src="<?= asset('code/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset('code/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <style>
        :root {
            --portal-primary: <?= e($portalPrimario) ?>;
            --portal-primary-dark: color-mix(in srgb, var(--portal-primary) 78%, black);
            --portal-sidebar: <?= e($portalSecundario) ?>;
            --portal-sidebar-hover: color-mix(in srgb, var(--portal-sidebar) 82%, white 6%);
            --portal-navbar-height: 64px;
            --portal-sidebar-width: 270px;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f4f6f9;
            overflow: hidden;
        }

        .portal-app { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

        .portal-navbar {
            background: #fff;
            border-bottom: 1px solid #e0e6e3;
            height: var(--portal-navbar-height);
            padding: 0 16px 0 20px;
            position: sticky; top: 0; z-index: 1050;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(11, 42, 56, 0.06);
        }
        .portal-navbar .container-fluid {
            display: flex; justify-content: space-between; align-items: center; height: 100%;
        }
        .portal-navbar-left, .portal-navbar-right { display: flex; align-items: center; gap: 12px; }

        .portal-sidebar-toggle {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border: 1px solid #e0e6e3; border-radius: 10px;
            background: #fff; color: #0b2a38; cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        .portal-sidebar-toggle:hover { background: #f4f6f9; border-color: var(--portal-primary); color: var(--portal-primary); }

        .portal-brand { display: flex; align-items: center; gap: 10px; color: var(--portal-primary); font-weight: 800; font-size: 1.2rem; text-decoration: none; white-space: nowrap; }
        .portal-brand i { font-size: 1.4rem; }

        .portal-user { display: flex; align-items: center; gap: 12px; }
        .portal-user-meta { display: flex; flex-direction: column; align-items: flex-end; line-height: 1.2; }
        .portal-user-name { font-weight: 600; color: #0b2a38; white-space: nowrap; font-size: 0.9rem; }
        .portal-user-role { font-size: 0.7rem; color: #6b7c83; text-transform: uppercase; letter-spacing: 0.03em; }
        .portal-role-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 50px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            background: #e8f4f3; color: var(--portal-primary); border: 1px solid #c5e3df;
        }

        .portal-wrapper { display: flex; flex: 1; overflow: hidden; position: relative; }

        .portal-sidebar {
            position: fixed; top: var(--portal-navbar-height); left: 0; bottom: 0;
            width: min(var(--portal-sidebar-width), 85vw);
            background: var(--portal-sidebar); color: rgba(255, 255, 255, 0.75);
            padding: 16px 0 24px; overflow-y: auto; overflow-x: hidden;
            transform: translateX(-100%); transition: transform 0.3s ease;
            z-index: 1040; box-shadow: 4px 0 20px rgba(11, 42, 56, 0.2);
        }
        .portal-sidebar.open { transform: translateX(0); }

        .portal-sidebar-nav { list-style: none; padding: 0; margin: 0; }

        .nav-section {
            padding: 18px 22px 6px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
            color: rgba(255,255,255,0.45);
        }
        .portal-sidebar-nav li a {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 22px; color: rgba(255, 255, 255, 0.82);
            transition: background 0.18s, color 0.18s, border-color 0.18s;
            text-decoration: none; white-space: nowrap;
            border-left: 3px solid transparent;
        }
        .portal-sidebar-nav li a:hover,
        .portal-sidebar-nav li a.active {
            background: var(--portal-sidebar-hover); color: #fff; border-left-color: var(--portal-primary);
        }
        .portal-sidebar-nav li a i { width: 22px; text-align: center; flex-shrink: 0; font-size: 1rem; }

        .portal-overlay {
            display: none; position: fixed; inset: var(--portal-navbar-height) 0 0 0;
            background: rgba(11, 42, 56, 0.45); z-index: 1035; opacity: 0; transition: opacity 0.3s ease;
        }
        .portal-overlay.open { display: block; opacity: 1; }

        .portal-main { flex: 1; min-width: 0; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; scroll-behavior: smooth; position: relative; }
        .portal-content { flex: 1 0 auto; padding: 28px; padding-bottom: calc(28px + 70px); }

        .portal-footer {
            position: sticky; bottom: 0; background: #f4f6f9; padding: 16px 28px;
            border-top: 1px solid #e0e6e3; color: #5d7078; font-size: 0.85rem; text-align: center; flex-shrink: 0; z-index: 1;
        }

        .portal-card {
            background: #fff; border-radius: 16px; padding: 26px;
            box-shadow: 0 4px 18px rgba(11, 42, 56, 0.05); height: 100%;
            transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column;
        }
        .portal-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(11, 42, 56, 0.1); }

        .tab-content>.tab-pane { display: none; }
        .tab-content>.tab-pane.active { display: block; }

        @media (max-width: 768px) {
            .portal-content { padding: 20px; padding-bottom: calc(20px + 70px); }
            .portal-footer { padding: 14px 20px; }
            .portal-user-meta { display: none; }
        }
    </style>
</head>

<body>
    <div class="portal-app">
        <nav class="portal-navbar">
            <div class="container-fluid">
                <div class="portal-navbar-left">
                    <button type="button" class="portal-sidebar-toggle" id="portalSidebarToggle" aria-label="Alternar menú" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="<?= route('inicio') ?>" class="portal-brand">
                        <i class="fas fa-heartbeat"></i> <?= e($portalNombre) ?>
                    </a>
                </div>
                <div class="portal-navbar-right">
                    <div class="portal-user">
                        <div class="portal-user-meta">
                            <span class="portal-user-name"><?= e((string) ($nombre ?? 'Usuario')) ?></span>
                            <span class="portal-user-role"><?= e($rolEtiqueta) ?></span>
                        </div>
                        <span class="portal-role-badge d-none d-md-inline-flex"><i class="fas fa-user-circle"></i> <?= e($rolEtiqueta) ?></span>
                        <a href="<?= route('portal_perfil') ?>" class="btn btn-sm btn-outline-secondary" title="Mi perfil" aria-label="Mi perfil">
                            <i class="fas fa-id-card"></i>
                        </a>
                        <form action="<?= route('portal_logout') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline">Salir</span></button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="portal-wrapper">
            <aside class="portal-sidebar" id="portalSidebar" aria-label="Menú principal">
                <ul class="portal-sidebar-nav">
                    <li class="nav-section">Principal</li>
                    <li><a href="<?= route('portal_dashboard') ?>" class="<?= $isActive('dashboard') ?>"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                    <li><a href="<?= route('portal_perfil') ?>" class="<?= $isActive('perfil') ?>"><i class="fas fa-id-card"></i> <span>Mi perfil</span></a></li>

                    <?php if ($esAdmin || $esRecep) { ?>
                        <li class="nav-section">Agenda y citas</li>
                        <?php if ($esAdmin) { ?>
                            <li><a href="<?= route('dashboard_especialidades') ?>" class="<?= $isActive('especialidades') ?>"><i class="fas fa-book-medical"></i> <span>Especialidades</span></a></li>
                        <?php } ?>
                        <li><a href="<?= route('dashboard_horario_atencion') ?>" class="<?= $isActive('horario-atencion') ?>"><i class="fas fa-calendar-alt"></i> <span>Agenda médica</span></a></li>
                        <li><a href="<?= route('dashboard_recepcion_citas_medicas') ?>" class="<?= $isActive('recepcion-citas') ?>"><i class="fas fa-clipboard-check"></i> <span>Recepción de citas</span></a></li>
                    <?php } ?>

                    <?php if ($esAdmin || $esRecep || $esMedico) { ?>
                        <li class="nav-section">Atención médica</li>
                        <li><a href="<?= route('dashboard_atencion') ?>" class="<?= $isActive('atencion') ?>"><i class="fas fa-stethoscope"></i> <span>Atención de pacientes</span></a></li>
                    <?php } ?>

                    <?php if ($esAdmin || $esRecep || $esMedico) { ?>
                        <li class="nav-section">Reportes</li>
                        <li><a href="<?= route('dashboard_reportes') ?>" class="<?= $isActive('reportes') ?>"><i class="fas fa-chart-bar"></i> <span>Reportes</span></a></li>
                    <?php } ?>

                    <?php if ($esPaciente) { ?>
                        <li class="nav-section">Mis citas</li>
                        <li><a href="<?= route('dashboard_citas_medicas') ?>" class="<?= $isActive('citas-medicas') ?>"><i class="fas fa-calendar-plus"></i> <span>Nueva cita médica</span></a></li>
                        <li><a href="<?= route('dashboard_mis_citas') ?>" class="<?= $isActive('mis-citas') ?>"><i class="fas fa-history"></i> <span>Mis citas</span></a></li>
                    <?php } ?>

                    <?php if ($verPacientes) { ?>
                        <li class="nav-section">Pacientes</li>
                        <li><a href="<?= route('dashboard_pacientes') ?>" class="<?= $isActive('pacientes') ?>"><i class="fas fa-users"></i> <span>Gestión de pacientes</span></a></li>
                    <?php } ?>

                    <?php if ($esAdmin) { ?>
                        <li class="nav-section">Administración</li>
                        <li><a href="<?= route('dashboard_usuarios') ?>" class="<?= $isActive('usuarios') ?>"><i class="fas fa-user-tie"></i> <span>Empleados</span></a></li>
                        <li><a href="<?= route('dashboard_permisos') ?>" class="<?= $isActive('permisos') ?>"><i class="fas fa-user-shield"></i> <span>Permisos</span></a></li>
                        <li><a href="<?= route('portal_configuracion') ?>" class="<?= $isActive('configuracion') ?>"><i class="fas fa-cogs"></i> <span>Configuración</span></a></li>

                        <li class="nav-section">Bitácoras</li>
                        <li><a href="<?= route('portal_bitacora_acceso') ?>" class="<?= $isActive('bitacora') ?>"><i class="fas fa-clipboard-list"></i> <span>Accesos</span></a></li>
                        <li><a href="<?= route('portal_bitacora_accion') ?>" class="<?= $isActive('bitacora_accion') ?>"><i class="fas fa-clipboard-check"></i> <span>Acciones</span></a></li>
                    <?php } ?>
                </ul>
            </aside>

            <div class="portal-overlay" id="portalOverlay" aria-hidden="true"></div>

            <main class="portal-main">
                <div class="portal-content">
                    <?= $content ?? '' ?>
                </div>

                <div class="portal-footer">
                    &copy; <?= date('Y') ?> <?= e($portalNombre) ?>. Todos los derechos reservados.
                </div>
            </main>
        </div>
    </div>

    <script src="<?= asset('code/dist/js/adminlte.min.js') ?>" defer></script>
    <script src="<?= asset('code/js/notification.js') ?>" defer></script>
    <script>
        (function() {
            const sidebar = document.getElementById('portalSidebar');
            const toggle = document.getElementById('portalSidebarToggle');
            const overlay = document.getElementById('portalOverlay');
            if (!sidebar || !toggle) return;

            function openSidebar() {
                sidebar.classList.add('open');
                overlay && overlay.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebar.classList.remove('open');
                overlay && overlay.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
            function toggleSidebar() {
                sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
            }

            toggle.addEventListener('click', function(e) { e.preventDefault(); toggleSidebar(); });
            overlay && overlay.addEventListener('click', closeSidebar);
            sidebar.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() { closeSidebar(); });
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
            });
        })();
    </script>
</body>

</html>
