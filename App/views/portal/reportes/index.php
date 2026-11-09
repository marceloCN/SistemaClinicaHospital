<?php
/**
 * Panel de reportes del portal.
 *
 * Punto de entrada de los reportes del sistema: cada tarjeta enlaza a
 * un reporte concreto. El controlador PortalReporteController es el
 * gestor de consultas que alimenta cada uno.
 */
$estadisticas = $estadisticas ?? [
    'fichas_totales' => 0,
    'horarios_vencidos' => 0,
];
$rolClave = $rolClave ?? '';
$esAdmin = $rolClave === 'ADMIN';
?>

<style>
    .rp-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .rp-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .rp-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .rp-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
        background: #e8f4f3;
        color: var(--portal-primary);
    }

    .rp-stat-num {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1.1;
    }

    .rp-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .rp-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
    }

    .rp-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 22px;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .rp-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 22px rgba(11, 42, 56, 0.1);
        text-decoration: none;
        color: inherit;
    }

    .rp-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #e8f4f3;
        color: var(--portal-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .rp-card-title {
        font-weight: 700;
        color: #0b2a38;
    }

    .rp-card-text {
        font-size: 0.88rem;
        color: #6b7c83;
        margin: 0;
    }
</style>

<div class="rp-header">
    <div>
        <h3 class="m-0"><i class="fas fa-chart-bar text-secondary"></i> Reportes</h3>
        <p class="text-muted mb-0">Consultas del sistema en función a los parámetros de cada reporte.</p>
    </div>
</div>

<div class="rp-stats">
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-calendar-check"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) ($estadisticas['fichas_totales'] ?? 0) ?></div>
            <div class="rp-stat-label">Fichas de agenda</div>
        </div>
    </div>
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-clock"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) ($estadisticas['horarios_vencidos'] ?? 0) ?></div>
            <div class="rp-stat-label">Horarios vencidos</div>
        </div>
    </div>
</div>

<div class="rp-cards">
    <?php if ($esAdmin) { ?>
        <a class="rp-card" href="<?= route('portal_reporte_ficha_historica') ?>">
            <span class="rp-card-icon"><i class="fas fa-calendar-day"></i></span>
            <span class="rp-card-title">Fichas de agenda</span>
            <p class="rp-card-text">
                Consulta todas las fichas de agenda con filtros por fecha, médico, especialidad,
                estado de la ficha, vigencia del horario y paciente.
            </p>
            <span class="badge badge-secondary align-self-start"><?= (int) ($estadisticas['fichas_totales'] ?? 0) ?> ficha(s)</span>
        </a>
    <?php } ?>
    <a class="rp-card" href="<?= route('portal_reporte_paciente_expediente') ?>">
        <span class="rp-card-icon"><i class="fas fa-folder-open"></i></span>
        <span class="rp-card-title">Expediente e historial de citas</span>
        <p class="rp-card-text">
            Busca un paciente por nombre o CI y revisa su expediente clínico,
            citas, evaluaciones, consultas, diagnósticos y estudios.
        </p>
        <span class="badge badge-secondary align-self-start">Pacientes</span>
    </a>
</div>
