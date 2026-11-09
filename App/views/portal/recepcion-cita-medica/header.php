<?php
/**
 * Header del módulo de recepción de citas médicas.
 *
 * Estilos, título y estadísticas del día.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'pendientes' => 0,
    'en_clinica' => 0,
    'atendidas' => 0,
    'canceladas' => 0,
    'inasistencias' => 0,
    'reprogramadas' => 0,
    'recepcionadas' => 0,
];
?>

<style>
    .rc-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
    .rc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .rc-stat { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 16px 18px; display: flex; align-items: center; gap: 12px; }
    .rc-stat-icon { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
    .rc-stat-icon.total { background: #e8f4f3; color: var(--portal-primary); }
    .rc-stat-icon.pendientes { background: #fef7e0; color: #b8860b; }
    .rc-stat-icon.clinica { background: #e8f0fe; color: #1a73e8; }
    .rc-stat-icon.atendidas { background: #e7f7ed; color: #1e8e3e; }
    .rc-stat-icon.canceladas { background: #fdecea; color: #c0392b; }
    .rc-stat-icon.inasistencias { background: #f3e8ff; color: #7c3aed; }
    .rc-stat-icon.reprogramadas { background: #fff3e0; color: #e65100; }
    .rc-stat-icon.recepcionadas { background: #e3f2fd; color: #0277bd; }
    .rc-stat-num { font-size: 1.4rem; font-weight: 800; color: #0b2a38; line-height: 1.1; }
    .rc-stat-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7c83; }
    .rc-tabla { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; overflow: hidden; }
    .rc-tabla table { margin-bottom: 0; }
    .rc-tabla thead th { background: #f4f7f6; border-bottom: 1px solid #e4e9e7; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #44545c; white-space: nowrap; }
    .rc-tabla td { vertical-align: middle; border-color: #eef2f1; font-size: 0.92rem; }
    .rc-hora { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .rc-nombre { font-weight: 600; color: #0b2a38; line-height: 1.25; }
    .rc-nombre small { display: block; font-weight: 500; color: #6b7c83; font-size: 0.8rem; }
    .rc-paginacion { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
    .rc-paginacion .page-link { color: #44545c; border-color: #e0e6e3; }
    .rc-paginacion .page-item.active .page-link { background: var(--portal-primary); border-color: var(--portal-primary); }
    .rc-filtros { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; margin-bottom: 20px; }
    .rc-bloqueos { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; }
    .rc-opcion { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid #eef2f1; background: transparent; padding: 12px 16px; cursor: pointer; }
    .rc-opcion:last-child { border-bottom: 0; }
    .rc-opcion:hover, .rc-opcion.activa { background: #e8f4f3; }
    .rc-buscador { position: relative; }
    .rc-buscador .resultados { position: absolute; z-index: 1000; top: 100%; left: 0; right: 0; max-height: 240px; overflow-y: auto; background: #fff; border: 1px solid #e0e6e3; border-top: 0; border-radius: 0 0 10px 10px; }
    .rc-ficha-card { border: 1px solid #e0e6e3; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .rc-ficha-card:hover, .rc-ficha-card.seleccionada { border-color: var(--portal-primary); background: #f4faf9; }
    .rc-alerta-error { display: none; }
    .rc-paso { display: none; }
    .rc-paso.activo { display: block; }
    @media (max-width: 575.98px) {
        .rc-stats { grid-template-columns: repeat(2, 1fr); }
        .rc-tabla thead { display: none; }
        .rc-tabla, .rc-tabla tbody, .rc-tabla tr, .rc-tabla td { display: block; width: 100%; }
        .rc-tabla tr { border-bottom: 1px solid #eef2f1; padding: 10px 0; }
        .rc-tabla td { border: 0; padding: 4px 16px; }
    }
</style>

<div class="rc-header">
    <div>
        <h3 class="m-0"><i class="fas fa-clipboard-check text-secondary"></i> Recepción de citas</h3>
        <p class="text-muted mb-0">Check-in por código, registro de citas y reprogramación por bloqueos.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= date('d/m/Y') ?></span>
</div>

<div class="rc-stats">
    <div class="rc-stat"><span class="rc-stat-icon total"><i class="fas fa-calendar"></i></span><div><div class="rc-stat-num"><?= (int) ($estadisticas['total'] ?? 0) ?></div><div class="rc-stat-label">Total hoy</div></div></div>
    <div class="rc-stat"><span class="rc-stat-icon pendientes"><i class="fas fa-clock"></i></span><div><div class="rc-stat-num"><?= (int) ($estadisticas['pendientes'] ?? 0) ?></div><div class="rc-stat-label">Pendientes</div></div></div>
    <div class="rc-stat"><span class="rc-stat-icon clinica"><i class="fas fa-hospital"></i></span><div><div class="rc-stat-num"><?= (int) ($estadisticas['en_clinica'] ?? 0) ?></div><div class="rc-stat-label">En clínica</div></div></div>
    <div class="rc-stat"><span class="rc-stat-icon atendidas"><i class="fas fa-check-circle"></i></span><div><div class="rc-stat-num"><?= (int) ($estadisticas['atendidas'] ?? 0) ?></div><div class="rc-stat-label">Atendidas</div></div></div>
    <div class="rc-stat"><span class="rc-stat-icon canceladas"><i class="fas fa-times-circle"></i></span><div><div class="rc-stat-num"><?= (int) ($estadisticas['canceladas'] ?? 0) ?></div><div class="rc-stat-label">Canceladas</div></div></div>
</div>

<?php require __DIR__ . '/../base/confirmar.php'; ?>
