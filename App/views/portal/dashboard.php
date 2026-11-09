<?php
$rolClave = $rolClave ?? '';
$nombre = $nombre ?? 'Usuario';

use Lib\Support\Session;

$portalPermisos = (array) Session::get('portal_permisos', []);
$verPacientes = $rolClave === 'ADMIN' || in_array('120', $portalPermisos, true);

$roles = [
    'ADMIN' => ['label' => 'Administrador', 'icon' => 'fa-user-shield', 'color' => '#dc3545'],
    'RECEP' => ['label' => 'Recepcionista', 'icon' => 'fa-user-clock', 'color' => '#0f8b84'],
    'MEDICO' => ['label' => 'Médico', 'icon' => 'fa-user-md', 'color' => '#0b2a38'],
    'PAC' => ['label' => 'Paciente', 'icon' => 'fa-user-injured', 'color' => '#17a2b8'],
];
$rolInfo = $roles[$rolClave] ?? ['label' => 'Usuario', 'icon' => 'fa-user', 'color' => '#6c757d'];
?>

<div class="portal-welcome">
    <div class="welcome-content">
        <div>
            <h2>¡Hola, <?= e((string) $nombre) ?>!</h2>
            <p>Bienvenido a tu portal. Desde aquí puedes acceder rápidamente a las funciones disponibles para tu perfil.</p>
        </div>
        <span class="welcome-role-badge" style="--role-color: <?= e($rolInfo['color']) ?>">
            <i class="fas <?= e($rolInfo['icon']) ?>"></i> <?= e($rolInfo['label']) ?>
        </span>
    </div>
</div>

<?php if ($rolClave === 'ADMIN') { ?>
    <div class="portal-section-title"><i class="fas fa-briefcase-medical"></i> Gestión clínica</div>
    <div class="row">
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#0f8b84;"><i class="fas fa-book-medical text-white"></i></div>
                <h4>Especialidades</h4>
                <p>Administra las especialidades médicas, servicios y acreditaciones.</p>
                <a href="<?= route('dashboard_especialidades') ?>" class="btn btn-outline-primary">Ir a especialidades</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#17a2b8;"><i class="fas fa-calendar-alt text-white"></i></div>
                <h4>Agenda médica</h4>
                <p>Gestiona horarios de atención y genera fichas para las citas.</p>
                <a href="<?= route('dashboard_horario_atencion') ?>" class="btn btn-outline-info">Ir a agenda</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#0b2a38;"><i class="fas fa-stethoscope text-white"></i></div>
                <h4>Atención de pacientes</h4>
                <p>Flujo completo: evaluación previa, consulta, expediente e historial.</p>
                <a href="<?= route('dashboard_atencion') ?>" class="btn btn-outline-dark">Ir a atención</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#28a745;"><i class="fas fa-clipboard-check text-white"></i></div>
                <h4>Recepción de citas</h4>
                <p>Check-in, registro de citas y reprogramaciones.</p>
                <a href="<?= route('dashboard_recepcion_citas_medicas') ?>" class="btn btn-outline-success">Ir a recepción</a>
            </div>
        </div>
    </div>

    <div class="portal-section-title"><i class="fas fa-users-cog"></i> Administración del sistema</div>
    <div class="row">
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#dc3545;"><i class="fas fa-user-tie text-white"></i></div>
                <h4>Empleados</h4>
                <p>Administra médicos, recepcionistas y personal administrativo.</p>
                <a href="<?= route('dashboard_usuarios') ?>" class="btn btn-outline-danger">Ir a empleados</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#6f42c1;"><i class="fas fa-user-shield text-white"></i></div>
                <h4>Permisos</h4>
                <p>Configura los permisos asignados a cada rol del portal.</p>
                <a href="<?= route('dashboard_permisos') ?>" class="btn btn-outline-secondary">Ir a permisos</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#fd7e14;"><i class="fas fa-users text-white"></i></div>
                <h4>Pacientes</h4>
                <p>Registra, consulta y actualiza la información de los pacientes.</p>
                <a href="<?= route('dashboard_pacientes') ?>" class="btn btn-outline-warning">Ir a pacientes</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#20c997;"><i class="fas fa-chart-bar text-white"></i></div>
                <h4>Reportes</h4>
                <p>Consulta reportes y estadísticas del sistema.</p>
                <a href="<?= route('dashboard_reportes') ?>" class="btn btn-outline-success">Ir a reportes</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#6c757d;"><i class="fas fa-cogs text-white"></i></div>
                <h4>Configuración</h4>
                <p>Personaliza colores, datos de la clínica y página de inicio.</p>
                <a href="<?= route('portal_configuracion') ?>" class="btn btn-outline-secondary">Configurar</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#343a40;"><i class="fas fa-clipboard-list text-white"></i></div>
                <h4>Bitácoras</h4>
                <p>Revisa accesos y acciones realizadas en el sistema.</p>
                <a href="<?= route('portal_bitacora_acceso') ?>" class="btn btn-outline-dark">Ver bitácoras</a>
            </div>
        </div>
    </div>

<?php } elseif ($rolClave === 'RECEP') { ?>
    <div class="portal-section-title"><i class="fas fa-clipboard-check"></i> Trabajo diario</div>
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#0f8b84;"><i class="fas fa-clipboard-check text-white"></i></div>
                <h4>Recepción de citas</h4>
                <p>Check-in, registro de citas para pacientes y reprogramaciones.</p>
                <a href="<?= route('dashboard_recepcion_citas_medicas') ?>" class="btn btn-outline-primary">Ir a recepción</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#17a2b8;"><i class="fas fa-calendar-alt text-white"></i></div>
                <h4>Agenda médica</h4>
                <p>Consulta y genera fichas para los horarios de atención.</p>
                <a href="<?= route('dashboard_horario_atencion') ?>" class="btn btn-outline-info">Ir a agenda</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#0b2a38;"><i class="fas fa-stethoscope text-white"></i></div>
                <h4>Atención de pacientes</h4>
                <p>Registra la evaluación previa y habilita pacientes para consulta.</p>
                <a href="<?= route('dashboard_atencion') ?>" class="btn btn-outline-dark">Ir a atención</a>
            </div>
        </div>
        <?php if ($verPacientes) { ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="portal-card">
                    <div class="portal-card-icon" style="background:#28a745;"><i class="fas fa-users text-white"></i></div>
                    <h4>Gestión de pacientes</h4>
                    <p>Registra y consulta la información de los pacientes.</p>
                    <a href="<?= route('dashboard_pacientes') ?>" class="btn btn-outline-success">Ir a pacientes</a>
                </div>
            </div>
        <?php } ?>
    </div>

<?php } elseif ($rolClave === 'MEDICO') { ?>
    <div class="portal-section-title"><i class="fas fa-user-md"></i> Mi consulta</div>
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#0f8b84;"><i class="fas fa-stethoscope text-white"></i></div>
                <h4>Atención de pacientes</h4>
                <p>Consulta las citas listas para atención, inicia consultas, registra diagnósticos y estudios.</p>
                <a href="<?= route('dashboard_atencion') ?>" class="btn btn-outline-primary">Ir a atención</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#0b2a38;"><i class="fas fa-folder-open text-white"></i></div>
                <h4>Expedientes e historiales</h4>
                <p>Accede al expediente clínico y al historial de tus pacientes durante la consulta.</p>
                <a href="<?= route('dashboard_atencion') ?>" class="btn btn-outline-dark">Ir a bandeja</a>
            </div>
        </div>
    </div>

<?php } else { ?>
    <div class="portal-section-title"><i class="fas fa-heartbeat"></i> Mis servicios</div>
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#0f8b84;"><i class="fas fa-calendar-plus text-white"></i></div>
                <h4>Reservar cita</h4>
                <p>Agenda una nueva cita con el especialista que necesitas.</p>
                <a href="<?= route('dashboard_citas_medicas') ?>" class="btn btn-outline-primary">Nueva cita</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#17a2b8;"><i class="fas fa-history text-white"></i></div>
                <h4>Mis citas</h4>
                <p>Consulta el estado de tus citas programadas y pasadas.</p>
                <a href="<?= route('dashboard_mis_citas') ?>" class="btn btn-outline-info">Ver mis citas</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon" style="background:#6c757d;"><i class="fas fa-id-card text-white"></i></div>
                <h4>Mi perfil</h4>
                <p>Actualiza tus datos personales, usuario, correo y contraseña.</p>
                <a href="<?= route('portal_perfil') ?>" class="btn btn-outline-secondary">Editar perfil</a>
            </div>
        </div>
    </div>
<?php } ?>

<style>
    .portal-welcome {
        background: linear-gradient(135deg, var(--portal-primary) 0%, var(--portal-primary-dark) 100%);
        color: #fff; border-radius: 20px; padding: 28px 32px; margin-bottom: 28px;
        position: relative; overflow: hidden;
    }
    .portal-welcome::after {
        content: ''; position: absolute; top: -50px; right: -50px;
        width: 220px; height: 220px; background: rgba(255,255,255,0.08); border-radius: 50%;
    }
    .welcome-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: relative; z-index: 1; }
    .portal-welcome h2 { font-weight: 800; margin-bottom: 6px; }
    .portal-welcome p { margin: 0; opacity: 0.92; max-width: 620px; }
    .welcome-role-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255,255,255,0.15); color: #fff;
        border: 1px solid rgba(255,255,255,0.25); border-radius: 50px;
        padding: 8px 16px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
        backdrop-filter: blur(4px);
    }
    .welcome-role-badge i { font-size: 1rem; }

    .portal-section-title {
        font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
        color: #0f8b84; margin: 24px 0 14px; display: flex; align-items: center; gap: 8px;
    }
    .portal-section-title i { font-size: 1rem; }

    .portal-card {
        background: #fff; border-radius: 16px; padding: 24px;
        box-shadow: 0 4px 18px rgba(11, 42, 56, 0.05); height: 100%;
        transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column;
    }
    .portal-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(11, 42, 56, 0.1); }
    .portal-card-icon {
        width: 54px; height: 54px; border-radius: 14px;
        display: grid; place-items: center; font-size: 1.4rem; margin-bottom: 16px;
    }
    .portal-card h4 { font-weight: 700; color: #0b2a38; margin-bottom: 8px; }
    .portal-card p { color: #5d7078; font-size: 0.92rem; flex-grow: 1; margin-bottom: 16px; }
    .portal-card .btn { margin-top: auto; border-radius: 50px; font-weight: 600; }

    @media (max-width: 768px) {
        .welcome-content { flex-direction: column; align-items: flex-start; }
    }
</style>
