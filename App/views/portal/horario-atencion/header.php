<?php
/**
 * Header del módulo de horarios de atención.
 *
 * Incluye el bloque de estilos compartidos, el título del módulo,
 * el badge de total y las tarjetas de estadísticas.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'activos' => 0,
    'inactivos' => 0,
    'medicos' => 0,
    'fichas' => 0,
];
?>

<style>
    .ha-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .ha-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .ha-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .ha-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .ha-stat-icon.total { background: #e8f4f3; color: var(--portal-primary); }
    .ha-stat-icon.activos { background: #e7f7ed; color: #1e8e3e; }
    .ha-stat-icon.inactivos { background: #fdecea; color: #c0392b; }
    .ha-stat-icon.medicos { background: #e8f0fe; color: #1a73e8; }
    .ha-stat-icon.fichas { background: #fef7e0; color: #b8860b; }

    .ha-stat-num {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1.1;
    }

    .ha-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .ha-filtros {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }

    .ha-filtros .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .ha-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .ha-tabla table { margin-bottom: 0; }

    .ha-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }

    .ha-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.92rem;
    }

    .ha-nombre {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .ha-nombre small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.8rem;
    }

    .ha-hora {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .ha-paginacion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .ha-paginacion .page-link {
        color: #44545c;
        border-color: #e0e6e3;
    }

    .ha-paginacion .page-item.active .page-link {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
    }

    .ha-form .form-control,
    .ha-form .form-control:focus {
        background: #f7f9f8;
        border-color: #e0e6e3;
        box-shadow: none;
    }

    .ha-form label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .ha-alerta-error { display: none; }

    .ha-ayuda {
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.86rem;
        color: #3d6a8f;
    }

    @media (max-width: 575.98px) {
        .ha-filtros { padding: 16px; }

        .ha-tabla thead { display: none; }
        .ha-tabla,
        .ha-tabla tbody,
        .ha-tabla tr,
        .ha-tabla td { display: block; width: 100%; }
        .ha-tabla tr { border-bottom: 1px solid #eef2f1; padding: 10px 0; }
        .ha-tabla td { border: 0; padding: 4px 16px; }
    }
</style>

<div class="ha-header">
    <div>
        <h3 class="m-0"><i class="fas fa-clock text-secondary"></i> Horarios de atención</h3>
        <p class="text-muted mb-0">Configura las franjas recurrentes de atención de cada médico y genera las fichas de agenda.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= (int) ($estadisticas['total'] ?? 0) ?> horario(s)</span>
</div>

<div class="ha-stats">
    <div class="ha-stat">
        <span class="ha-stat-icon total"><i class="fas fa-clock"></i></span>
        <div>
            <div class="ha-stat-num"><?= (int) ($estadisticas['total'] ?? 0) ?></div>
            <div class="ha-stat-label">Total horarios</div>
        </div>
    </div>
    <div class="ha-stat">
        <span class="ha-stat-icon activos"><i class="fas fa-check-circle"></i></span>
        <div>
            <div class="ha-stat-num"><?= (int) ($estadisticas['activos'] ?? 0) ?></div>
            <div class="ha-stat-label">Activos</div>
        </div>
    </div>
    <div class="ha-stat">
        <span class="ha-stat-icon inactivos"><i class="fas fa-times-circle"></i></span>
        <div>
            <div class="ha-stat-num"><?= (int) ($estadisticas['inactivos'] ?? 0) ?></div>
            <div class="ha-stat-label">Inactivos</div>
        </div>
    </div>
    <div class="ha-stat">
        <span class="ha-stat-icon medicos"><i class="fas fa-user-md"></i></span>
        <div>
            <div class="ha-stat-num"><?= (int) ($estadisticas['medicos'] ?? 0) ?></div>
            <div class="ha-stat-label">Médicos con horario</div>
        </div>
    </div>
    <div class="ha-stat">
        <span class="ha-stat-icon fichas"><i class="fas fa-calendar-check"></i></span>
        <div>
            <div class="ha-stat-num"><?= (int) ($estadisticas['fichas'] ?? 0) ?></div>
            <div class="ha-stat-label">Fichas generadas</div>
        </div>
    </div>
</div>

<!-- Modal de confirmación + espConfirmar/espNotificar compartidos del portal -->
<?php require __DIR__ . '/../base/confirmar.php'; ?>
