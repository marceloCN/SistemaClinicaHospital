<?php
/**
 * Header del módulo de atención integral de pacientes.
 *
 * Estilos, título y estadísticas de la bandeja (recepción o médica).
 */
$vista = $vista ?? 'recepcion';

$estadisticas = $estadisticas ?? ($vista === 'medico' ? [
    'listos' => 0,
    'encurso' => 0,
    'atendidas' => 0,
] : [
    'espera' => 0,
    'evaluacion' => 0,
    'listos' => 0,
    'finalizados' => 0,
]);
?>

<style>
    .at-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
    .at-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .at-stat { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 16px 18px; display: flex; align-items: center; gap: 12px; }
    .at-stat-icon { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
    .at-stat-icon.espera { background: #fef7e0; color: #b8860b; }
    .at-stat-icon.evaluacion { background: #e8f0fe; color: #1a73e8; }
    .at-stat-icon.listos { background: #e7f7ed; color: #1e8e3e; }
    .at-stat-icon.finalizados { background: #e8f4f3; color: var(--portal-primary); }
    .at-stat-icon.encurso { background: #fff3e0; color: #e65100; }
    .at-stat-icon.atendidas { background: #e7f7ed; color: #1e8e3e; }
    .at-stat-num { font-size: 1.4rem; font-weight: 800; color: #0b2a38; line-height: 1.1; }
    .at-stat-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7c83; }
    .at-tabla { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; overflow: hidden; }
    .at-tabla table { margin-bottom: 0; }
    .at-tabla thead th { background: #f4f7f6; border-bottom: 1px solid #e4e9e7; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #44545c; white-space: nowrap; }
    .at-tabla td { vertical-align: middle; border-color: #eef2f1; font-size: 0.92rem; }
    .at-hora { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .at-nombre { font-weight: 600; color: #0b2a38; line-height: 1.25; }
    .at-nombre small { display: block; font-weight: 500; color: #6b7c83; font-size: 0.8rem; }
    .at-filtros { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; margin-bottom: 20px; }
    .at-paginacion { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
    .at-paginacion .page-link { color: #44545c; border-color: #e0e6e3; }
    .at-paginacion .page-item.active .page-link { background: var(--portal-primary); border-color: var(--portal-primary); }
    .at-alerta-error { display: none; }
    .at-detalle-linea { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
    .at-detalle-linea:last-child { margin-bottom: 0; }
    .at-detalle-datos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; font-size: 0.9rem; }
    .at-detalle-datos dt { color: #6b7c83; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; margin-bottom: 1px; }
    .at-detalle-datos dd { color: #0b2a38; font-weight: 500; margin-bottom: 0; }
    .at-detalle-bloque { margin-top: 14px; }
    .at-detalle-bloque-label { color: #6b7c83; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; margin-bottom: 4px; }
    .at-detalle-bloque-texto { color: #0b2a38; font-size: 0.9rem; background: #f8fafb; border-radius: 6px; padding: 10px 12px; min-height: 38px; white-space: pre-line; }
    .at-detalle-bloque-texto.at-vacio { color: #7d8f96; font-style: italic; }
    .at-detalle-separador { border-top: 1px solid #e3eceb; margin: 14px 0; }
    .at-seccion-titulo { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0f8b84; margin: 16px 0 8px; }
    .at-ficha { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; margin-bottom: 18px; }
    .at-ficha-cabecera { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; }
    .at-ficha-titulo { font-size: 1.05rem; font-weight: 700; color: #0b2a38; margin: 0; }
    .at-ficha-sub { color: #6b7c83; font-size: 0.85rem; margin: 2px 0 0; }
    .at-lista-item { border: 1px solid #e9eeec; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; background: #fff; }
    .at-lista-item.at-item-principal { border-left: 4px solid #0f8b84; }
    .at-lista-item.at-item-secundario { border-left: 4px solid #adb5bd; }
    .at-lista-cabecera { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; }
    .at-lista-texto { color: #0b2a38; font-size: 0.92rem; }
    .at-lista-meta { color: #6b7c83; font-size: 0.78rem; }
    .at-vacio { color: #7d8f96; font-style: italic; }
    @media (max-width: 575.98px) {
        .at-stats { grid-template-columns: repeat(2, 1fr); }
        .at-tabla thead { display: none; }
        .at-tabla, .at-tabla tbody, .at-tabla tr, .at-tabla td { display: block; width: 100%; }
        .at-tabla tr { border-bottom: 1px solid #eef2f1; padding: 10px 0; }
        .at-tabla td { border: 0; padding: 4px 16px; }
        .at-detalle-datos { grid-template-columns: 1fr; }
    }
</style>

<div class="at-header">
    <div>
        <h3 class="m-0">
            <i class="fas fa-stethoscope text-secondary"></i>
            <?= $vista === 'medico' ? 'Bandeja de atención' : 'Atención de pacientes' ?>
        </h3>
        <p class="text-muted mb-0">
            <?= $vista === 'medico'
                ? 'Pacientes habilitados para consulta y consultas en curso.'
                : 'Espera, evaluación previa y habilitación de pacientes para consulta.' ?>
        </p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= date('d/m/Y') ?></span>
</div>

<div class="at-stats">
    <?php if ($vista === 'medico') { ?>
        <div class="at-stat"><span class="at-stat-icon listos"><i class="fas fa-user-check"></i></span><div><div class="at-stat-num"><?= (int) ($estadisticas['listos'] ?? 0) ?></div><div class="at-stat-label">Listos para consulta</div></div></div>
        <div class="at-stat"><span class="at-stat-icon encurso"><i class="fas fa-notes-medical"></i></span><div><div class="at-stat-num"><?= (int) ($estadisticas['encurso'] ?? 0) ?></div><div class="at-stat-label">Consultas en curso</div></div></div>
        <div class="at-stat"><span class="at-stat-icon atendidas"><i class="fas fa-check-circle"></i></span><div><div class="at-stat-num"><?= (int) ($estadisticas['atendidas'] ?? 0) ?></div><div class="at-stat-label">Atendidas hoy</div></div></div>
    <?php } else { ?>
        <div class="at-stat"><span class="at-stat-icon espera"><i class="fas fa-clock"></i></span><div><div class="at-stat-num"><?= (int) ($estadisticas['espera'] ?? 0) ?></div><div class="at-stat-label">En espera</div></div></div>
        <div class="at-stat"><span class="at-stat-icon evaluacion"><i class="fas fa-clipboard-list"></i></span><div><div class="at-stat-num"><?= (int) ($estadisticas['evaluacion'] ?? 0) ?></div><div class="at-stat-label">En evaluación</div></div></div>
        <div class="at-stat"><span class="at-stat-icon listos"><i class="fas fa-user-check"></i></span><div><div class="at-stat-num"><?= (int) ($estadisticas['listos'] ?? 0) ?></div><div class="at-stat-label">Listos para consulta</div></div></div>
        <div class="at-stat"><span class="at-stat-icon finalizados"><i class="fas fa-check-double"></i></span><div><div class="at-stat-num"><?= (int) ($estadisticas['finalizados'] ?? 0) ?></div><div class="at-stat-label">Finalizados hoy</div></div></div>
    <?php } ?>
</div>

<?php require __DIR__ . '/../base/confirmar.php'; ?>
