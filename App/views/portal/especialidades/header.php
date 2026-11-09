<?php
/**
 * Header del gestor de especialidades médicas: título, badge total y
 * las cuatro pestañas (Resumen, Especialidades, Médicos y Servicio
 * médico). El modal de confirmación reutilizable es compartido con los
 * demás módulos y vive en portal/base/confirmar.php.
 *
 * Flujo de trabajo:
 *   Especialidades_medicas → se da de alta el catálogo;
 *   Medicos → se asignan las especialidades del médico (medico_especialidad);
 *   servicio_medico → con esa acreditación se incorpora al médico al trabajo
 *   (servicio_medico) y de ahí nacen horarios y fichas.
 */
$tab = (string) ($tab ?? 'resumen');
$statTotal = (int) ($statTotal ?? 0);
$statMedicos = (int) ($statMedicos ?? 0);

/** Rutas nombradas de cada página del módulo. */
$rutaResumen = route('dashboard_especialidades') ?? '/portal/dashboard/Especialidades';
$rutaEspecialidades = route('dashboard_especialidades_medicas') ?? '/portal/dashboard/Especialidades/Especialidades_medicas';
$rutaMedicos = route('dashboard_especialidades_medicos') ?? '/portal/dashboard/Especialidades/Medicos';
$rutaServicio = route('dashboard_especialidades_servicio') ?? '/portal/dashboard/Especialidades/servicio_medico';
?>

<style>
    .es-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .es-tabs {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 6px 8px;
        margin-bottom: 20px;
    }

    .es-tabs .nav-link {
        color: #44545c;
        font-weight: 600;
        font-size: 0.92rem;
        border-radius: 10px;
        padding: 10px 18px;
    }

    .es-tabs .nav-link.active {
        background: var(--portal-primary);
        color: #fff;
    }

    .es-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .es-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .es-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .es-stat-icon.total {
        background: #e8f4f3;
        color: var(--portal-primary);
    }

    .es-stat-icon.activas {
        background: #e7f7ed;
        color: #1e8e3e;
    }

    .es-stat-icon.inactivas {
        background: #fdecea;
        color: #c0392b;
    }

    .es-stat-icon.acred {
        background: #e8f0fe;
        color: #1a73e8;
    }

    .es-stat-icon.medicos {
        background: #e6f7f6;
        color: #0f8b84;
    }

    .es-stat-icon.servicios {
        background: #fef7e0;
        color: #b8860b;
    }

    .es-stat-icon.bloqueos {
        background: #f3e8fd;
        color: #7b1fa2;
    }

    .es-stat-num {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1.1;
    }

    .es-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .es-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
        align-items: start;
    }

    .es-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .es-card:last-child {
        margin-bottom: 0;
    }

    .es-card h5 {
        font-weight: 700;
        color: #0b2a38;
        font-size: 1rem;
        margin-bottom: 18px;
    }

    .es-fila {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #eef2f1;
    }

    .es-fila:last-child {
        border-bottom: 0;
    }

    .es-fila-main {
        min-width: 0;
        flex: 1 1 auto;
    }

    .es-fila-main strong {
        display: block;
        color: #0b2a38;
        line-height: 1.25;
        word-break: break-word;
    }

    .es-fila-main small {
        display: block;
        color: #6b7c83;
        font-size: 0.82rem;
    }

    .es-barra {
        height: 8px;
        border-radius: 6px;
        background: #eef2f1;
        overflow: hidden;
        margin-top: 6px;
    }

    .es-barra span {
        display: block;
        height: 100%;
        border-radius: 6px;
        background: var(--portal-primary);
    }

    .es-filtros {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }

    .es-filtros .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .es-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .es-tabla table {
        margin-bottom: 0;
    }

    .es-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }

    .es-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.92rem;
    }

    .es-nombre {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .es-nombre small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.8rem;
    }

    .es-fecha {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        color: #0b2a38;
    }

    .es-paginacion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .es-paginacion .page-link {
        color: #44545c;
        border-color: #e0e6e3;
    }

    .es-paginacion .page-item.active .page-link {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
    }

    .es-form .form-control,
    .es-form .form-control:focus {
        background: #f7f9f8;
        border-color: #e0e6e3;
        box-shadow: none;
    }

    .es-form label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .es-alerta-error {
        display: none;
    }

    .es-ayuda {
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.86rem;
        color: #3d6a8f;
    }

    .es-bloqueo-item {
        background: #f7faf9;
        border: 1px solid #eef2f1;
        border-left: 4px solid var(--portal-primary);
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 10px;
    }

    .es-bloqueo-item.anulado {
        border-left-color: #d9534f;
        opacity: 0.75;
    }

    .es-bloqueo-item strong {
        color: #0b2a38;
        display: block;
        font-size: 0.92rem;
    }

    .es-bloqueo-item small {
        color: #6b7c83;
        font-size: 0.82rem;
    }

    @media (max-width: 575.98px) {
        .es-filtros {
            padding: 16px;
        }

        .es-tabla thead {
            display: none;
        }

        .es-tabla,
        .es-tabla tbody,
        .es-tabla tr,
        .es-tabla td {
            display: block;
            width: 100%;
        }

        .es-tabla tr {
            border-bottom: 1px solid #eef2f1;
            padding: 10px 0;
        }

        .es-tabla td {
            border: 0;
            padding: 4px 16px;
        }
    }
</style>

<div class="es-header">
    <div>
        <h3 class="m-0"><i class="fas fa-book-medical text-secondary"></i> Gestor de especialidades médicas</h3>
        <p class="text-muted mb-0">Catálogo de especialidades, especialidades de los médicos, servicios y bloqueos de agenda.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= $statTotal ?> especialidad(es)</span>
</div>

<ul class="nav nav-tabs es-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'resumen' ? 'active' : '' ?>"
            href="<?= e($rutaResumen) ?>">
            <i class="fas fa-chart-pie mr-1"></i> Resumen
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'especialidades' ? 'active' : '' ?>"
            href="<?= e($rutaEspecialidades) ?>">
            <i class="fas fa-book-medical mr-1"></i> Especialidades
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'medicos' ? 'active' : '' ?>"
            href="<?= e($rutaMedicos) ?>">
            <i class="fas fa-user-md mr-1"></i> Médicos
            <?php if ($statMedicos > 0) { ?>
                <span class="badge badge-light ml-1"><?= $statMedicos ?></span>
            <?php } ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'servicio' ? 'active' : '' ?>"
            href="<?= e($rutaServicio) ?>">
            <i class="fas fa-stethoscope mr-1"></i> Servicio médico
        </a>
    </li>
</ul>

<!-- Modal de confirmación compartido: ahora vive en portal/base/confirmar.php -->
<?php require __DIR__ . '/../base/confirmar.php'; ?>
