<?php
/**
 * Historial clínico del paciente: dashboard con línea de tiempo de
 * consultas, diagnósticos, estudios vinculados y evaluaciones previas.
 */
$paciente = $paciente ?? [
    'id' => 0,
    'ci' => '',
    'nombre_completo' => '',
    'total_citas' => 0,
];
$expediente = $expediente ?? null;
$consultas = $consultas ?? [];
$urlExpediente = $urlExpediente ?? '';

$idPaciente = (int) ($paciente['id'] ?? 0);

$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

$fechaObj = static function (?string $valor): ?DateTimeInterface {
    if ($valor === null || $valor === '') {
        return null;
    }
    $d = date_create($valor);
    return ($d === false) ? null : $d;
};

$fechaCorta = static function (?string $valor): string {
    return ($valor === null || $valor === '') ? '—' : substr($valor, 0, 16);
};

$edad = static function (?string $nacimiento): string {
    if ($nacimiento === null || $nacimiento === '') {
        return '—';
    }
    $n = date_create_from_format('Y-m-d', substr($nacimiento, 0, 10));
    if ($n === false) {
        return '—';
    }
    $hoy = new DateTime();
    return ((int) $hoy->diff($n)->y) . ' años';
};

$totalFinalizadas = 0;
$conEvaluacion = 0;
$totalEstudios = 0;
$totalDiagnosticos = 0;
foreach ($consultas as $c) {
    if ((string) ($c['consulta_estado'] ?? '') === 'FINALIZADA') {
        $totalFinalizadas++;
    }
    if (!empty($c['evaluaciones'])) {
        $conEvaluacion++;
    }
    $totalEstudios += count($c['estudios'] ?? []);
    $totalDiagnosticos += count($c['diagnosticos'] ?? []);
}

require __DIR__ . '/../base/confirmar.php';
?>

<style>
    .hi-topbar {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;
        margin-bottom: 20px;
    }
    .hi-topbar h3 { margin: 0; font-size: 1.35rem; color: #0b2a38; }
    .hi-topbar p { margin: 4px 0 0; color: #6b7c83; }
    .hi-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    .hi-card {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; margin-bottom: 18px;
    }
    .hi-card-header {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;
        margin-bottom: 14px;
    }
    .hi-card-title { font-size: 1.05rem; font-weight: 700; color: #0b2a38; margin: 0; }
    .hi-card-sub { color: #6b7c83; font-size: 0.85rem; margin: 2px 0 0; }

    .hi-datos-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px 16px; font-size: 0.88rem;
    }
    .hi-dato { background: #f8fafb; border-radius: 8px; padding: 10px 12px; }
    .hi-dato-label { font-size: 0.7rem; color: #6b7c83; text-transform: uppercase; font-weight: 700; }
    .hi-dato-value { color: #0b2a38; font-weight: 600; }

    .hi-stats {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 22px;
    }
    .hi-stat {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 12px; padding: 14px;
        display: flex; align-items: center; gap: 12px;
    }
    .hi-stat-icon {
        width: 42px; height: 42px; border-radius: 10px; background: #e8f4f3; color: #0f8b84;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    }
    .hi-stat-count { font-size: 1.25rem; font-weight: 700; color: #0b2a38; line-height: 1; }
    .hi-stat-label { font-size: 0.75rem; color: #6b7c83; }

    .hi-timeline { position: relative; padding-left: 28px; }
    .hi-timeline::before {
        content: ''; position: absolute; left: 10px; top: 8px; bottom: 0; width: 2px; background: #e4e9e7;
    }
    .hi-timeline-item { position: relative; margin-bottom: 18px; }
    .hi-timeline-marker {
        position: absolute; left: -24px; top: 14px; width: 14px; height: 14px; border-radius: 50%;
        background: #0f8b84; border: 2px solid #fff; box-shadow: 0 0 0 2px #e4e9e7;
    }
    .hi-timeline-item.finalizada .hi-timeline-marker { background: #28a745; }
    .hi-timeline-item.encurso .hi-timeline-marker { background: #ffc107; }

    .hi-consulta {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; overflow: hidden;
    }
    .hi-consulta-header {
        display: flex; align-items: center; gap: 14px; padding: 14px 16px; cursor: pointer;
        background: #fff; transition: background 0.15s;
    }
    .hi-consulta-header:hover { background: #f8fafb; }
    .hi-consulta-date {
        min-width: 52px; text-align: center; background: #f1f5f4; border-radius: 10px; padding: 8px 6px;
    }
    .hi-consulta-day { font-size: 1.3rem; font-weight: 700; color: #0b2a38; line-height: 1; }
    .hi-consulta-month { font-size: 0.7rem; color: #0f8b84; font-weight: 700; text-transform: uppercase; }
    .hi-consulta-year { font-size: 0.65rem; color: #6b7c83; }
    .hi-consulta-info { flex: 1; min-width: 0; }
    .hi-consulta-info h5 { margin: 0; font-size: 0.98rem; font-weight: 700; color: #0b2a38; }
    .hi-consulta-info p { margin: 2px 0 0; color: #6b7c83; font-size: 0.82rem; }
    .hi-consulta-badges { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .hi-chevron { color: #0f8b84; transition: transform 0.2s; }
    .hi-consulta-header[aria-expanded="true"] .hi-chevron { transform: rotate(180deg); }

    .hi-consulta-body { padding: 16px; border-top: 1px solid #e9eeec; }
    .hi-section-title {
        font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        color: #0f8b84; margin: 18px 0 10px; display: flex; align-items: center; gap: 6px;
    }
    .hi-section-title:first-child { margin-top: 0; }

    .hi-clinical-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
    @media (min-width: 992px) { .hi-clinical-grid { grid-template-columns: 1fr 1fr; } }
    .hi-clinical-block {
        background: #f8fafb; border: 1px solid #e9eeec; border-radius: 10px; padding: 12px;
        font-size: 0.88rem; color: #0b2a38; white-space: pre-line;
    }
    .hi-clinical-block strong {
        display: block; font-size: 0.72rem; color: #6b7c83; text-transform: uppercase; margin-bottom: 4px;
    }
    .hi-clinical-block.vacio { color: #7d8f96; font-style: italic; }

    .hi-diagnostico-card {
        border: 1px solid #e9eeec; border-radius: 10px; margin-bottom: 10px; overflow: hidden;
    }
    .hi-diagnostico-header {
        display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff;
    }
    .hi-diagnostico-title { font-weight: 600; color: #0b2a38; font-size: 0.92rem; }
    .hi-diagnostico-obs { color: #6b7c83; font-size: 0.8rem; padding: 0 12px 10px; }
    .hi-estudios-list { background: #f8fafb; border-top: 1px solid #e9eeec; padding: 10px 12px; }
    .hi-estudio-item {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;
        padding: 8px; background: #fff; border: 1px solid #e4e9e7; border-radius: 8px; margin-bottom: 8px;
    }
    .hi-estudio-item:last-child { margin-bottom: 0; }
    .hi-estudio-title { font-weight: 600; color: #0b2a38; font-size: 0.88rem; }
    .hi-estudio-meta { color: #6b7c83; font-size: 0.76rem; }

    .hi-evaluacion-card {
        background: #f8fafb; border: 1px solid #e9eeec; border-radius: 10px; padding: 12px;
    }
    .hi-evaluacion-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;
    }
    .hi-signos-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px;
    }
    .hi-signo {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 8px; padding: 8px; text-align: center;
    }
    .hi-signo-value { font-size: 0.95rem; font-weight: 700; color: #0f8b84; }
    .hi-signo-label { font-size: 0.65rem; color: #6b7c83; text-transform: uppercase; }

    .hi-empty { text-align: center; padding: 36px 16px; color: #6b7c83; }
    .hi-empty i { font-size: 2.2rem; color: #adb5bd; }
    .hi-empty-small { color: #7d8f96; font-style: italic; font-size: 0.85rem; margin: 0; }

    @media (max-width: 767.98px) {
        .hi-timeline { padding-left: 0; }
        .hi-timeline::before { display: none; }
        .hi-timeline-marker { display: none; }
        .hi-consulta-header { flex-wrap: wrap; }
    }
</style>

<div class="hi-topbar">
    <div>
        <h3><i class="fas fa-history text-secondary mr-2"></i> Historial clínico</h3>
        <p>
            <?= e((string) ($paciente['nombre_completo'] ?? '')) ?>
            <?php if (!empty($paciente['ci'])) { ?> · CI <?= e((string) $paciente['ci']) ?><?php } ?>
        </p>
    </div>
    <div class="hi-actions">
        <a href="<?= e(route('dashboard_atencion') ?? '/portal/dashboard/atencion') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Bandeja
        </a>
        <?php if ($urlExpediente !== '') { ?>
            <a href="<?= e($urlExpediente) ?>" class="btn btn-outline-primary">
                <i class="fas fa-folder-open mr-1"></i> Expediente
            </a>
        <?php } ?>
    </div>
</div>

<!-- Resumen del paciente -->
<div class="hi-card">
    <div class="hi-card-header">
        <div>
            <h4 class="hi-card-title"><i class="fas fa-id-card text-secondary mr-2"></i> Resumen del paciente</h4>
            <p class="hi-card-sub">Datos de identificación y expediente clínico.</p>
        </div>
        <span class="badge <?= $expediente !== null ? 'badge-success' : 'badge-warning' ?>">
            <?= $expediente !== null ? 'Expediente #' . (int) $expediente['id'] : 'Sin expediente' ?>
        </span>
    </div>
    <div class="hi-datos-grid">
        <div class="hi-dato">
            <div class="hi-dato-label">Paciente</div>
            <div class="hi-dato-value"><?= e((string) ($paciente['nombre_completo'] ?? '—')) ?></div>
        </div>
        <div class="hi-dato">
            <div class="hi-dato-label">CI</div>
            <div class="hi-dato-value"><?= e((string) ($paciente['ci'] ?? '—')) ?></div>
        </div>
        <div class="hi-dato">
            <div class="hi-dato-label">Edad</div>
            <div class="hi-dato-value"><?= e($edad((string) ($paciente['fecha_nacimiento'] ?? ''))) ?></div>
        </div>
        <div class="hi-dato">
            <div class="hi-dato-label">Total citas</div>
            <div class="hi-dato-value"><?= (int) ($paciente['total_citas'] ?? 0) ?></div>
        </div>
        <div class="hi-dato">
            <div class="hi-dato-label">Grupo sanguíneo</div>
            <div class="hi-dato-value">
                <?= $expediente !== null && trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) !== '' ? e(trim((string) $expediente['grupo_sanguineo'] . (string) $expediente['factor_rh']) . ' (' . e((string) ($expediente['factor_rh'] ?? '')) . ')') : '—' ?>
            </div>
        </div>
        <div class="hi-dato">
            <div class="hi-dato-label">Apertura expediente</div>
            <div class="hi-dato-value"><?= $expediente !== null ? e(substr((string) ($expediente['fecha_apertura'] ?? '—'), 0, 10)) : '—' ?></div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="hi-stats">
    <div class="hi-stat">
        <div class="hi-stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div>
            <div class="hi-stat-count"><?= count($consultas) ?></div>
            <div class="hi-stat-label">Consultas</div>
        </div>
    </div>
    <div class="hi-stat">
        <div class="hi-stat-icon"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="hi-stat-count"><?= $totalFinalizadas ?></div>
            <div class="hi-stat-label">Finalizadas</div>
        </div>
    </div>
    <div class="hi-stat">
        <div class="hi-stat-icon"><i class="fas fa-clipboard-check"></i></div>
        <div>
            <div class="hi-stat-count"><?= $conEvaluacion ?></div>
            <div class="hi-stat-label">Con evaluación</div>
        </div>
    </div>
    <div class="hi-stat">
        <div class="hi-stat-icon"><i class="fas fa-stethoscope"></i></div>
        <div>
            <div class="hi-stat-count"><?= $totalDiagnosticos ?></div>
            <div class="hi-stat-label">Diagnósticos</div>
        </div>
    </div>
    <div class="hi-stat">
        <div class="hi-stat-icon"><i class="fas fa-vial"></i></div>
        <div>
            <div class="hi-stat-count"><?= $totalEstudios ?></div>
            <div class="hi-stat-label">Estudios</div>
        </div>
    </div>
</div>

<!-- Línea de tiempo de consultas -->
<?php if ($consultas === []) { ?>
    <div class="hi-card hi-empty">
        <i class="fas fa-inbox"></i>
        <h5 class="mt-3 mb-1">Sin atenciones registradas</h5>
        <p class="mb-0">Este paciente aún no tiene consultas médicas en el sistema.</p>
    </div>
<?php } else { ?>
    <div class="hi-timeline">
        <?php foreach ($consultas as $i => $consulta) {
            $fechaCita = $fechaObj((string) ($consulta['fecha_hora_inicio'] ?? ''));
            $estadoConsulta = (string) ($consulta['consulta_estado'] ?? '');
            $claseEstado = $estadoConsulta === 'FINALIZADA' ? 'finalizada' : 'encurso';
            $tieneEvaluacion = !empty($consulta['evaluaciones']);
            $collapseId = 'hi-consulta-' . $i;
        ?>
            <div class="hi-timeline-item <?= $claseEstado ?>">
                <div class="hi-timeline-marker"></div>
                <div class="hi-consulta">
                    <div class="hi-consulta-header" data-toggle="collapse" data-target="#<?= e($collapseId) ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="<?= e($collapseId) ?>">
                        <div class="hi-consulta-date">
                            <?php if ($fechaCita !== null) { ?>
                                <div class="hi-consulta-day"><?= e(date_format($fechaCita, 'd')) ?></div>
                                <div class="hi-consulta-month"><?= e($meses[(int) date_format($fechaCita, 'n') - 1]) ?></div>
                                <div class="hi-consulta-year"><?= e(date_format($fechaCita, 'Y')) ?></div>
                            <?php } else { ?>
                                <div class="hi-consulta-day">—</div>
                                <div class="hi-consulta-month">—</div>
                                <div class="hi-consulta-year">—</div>
                            <?php } ?>
                        </div>
                        <div class="hi-consulta-info">
                            <h5>
                                <?= e((string) ($consulta['especialidad'] ?? 'Consulta médica')) ?>
                                <small class="text-muted" style="font-weight:500;">· <?= e((string) ($consulta['medico'] ?? '—')) ?></small>
                            </h5>
                            <p>
                                <code><?= e((string) ($consulta['codigo'] ?? '')) ?></code>
                                <?php if (!empty($consulta['motivo_consulta'])) { ?> · <?= e((string) $consulta['motivo_consulta']) ?><?php } ?>
                            </p>
                        </div>
                        <div class="hi-consulta-badges">
                            <?php if ($estadoConsulta !== '') { ?>
                                <span class="badge <?= $estadoConsulta === 'FINALIZADA' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $estadoConsulta === 'FINALIZADA' ? 'Finalizada' : 'En curso' ?>
                                </span>
                            <?php } ?>
                            <span class="badge <?= $tieneEvaluacion ? 'badge-info' : 'badge-light border' ?>">
                                <i class="fas fa-clipboard-check mr-1"></i> <?= $tieneEvaluacion ? 'Con evaluación' : 'Sin evaluación' ?>
                            </span>
                            <?php if (!empty($consulta['diagnosticos'])) { ?>
                                <span class="badge badge-secondary"><?= count($consulta['diagnosticos']) ?> diagnóstico<?= count($consulta['diagnosticos']) > 1 ? 's' : '' ?></span>
                            <?php } ?>
                            <?php if (!empty($consulta['estudios'])) { ?>
                                <span class="badge badge-secondary"><?= count($consulta['estudios']) ?> estudio<?= count($consulta['estudios']) > 1 ? 's' : '' ?></span>
                            <?php } ?>
                        </div>
                        <i class="fas fa-chevron-down hi-chevron"></i>
                    </div>

                    <div id="<?= e($collapseId) ?>" class="collapse <?= $i === 0 ? 'show' : '' ?>">
                        <div class="hi-consulta-body">

                            <!-- Información clínica -->
                            <div class="hi-section-title"><i class="fas fa-notes-medical"></i> Información clínica</div>
                            <div class="hi-clinical-grid">
                                <div class="hi-clinical-block <?= empty($consulta['motivo_consulta']) ? 'vacio' : '' ?>">
                                    <strong>Motivo de consulta</strong>
                                    <?= e(trim((string) ($consulta['motivo_consulta'] ?? '')) !== '' ? (string) $consulta['motivo_consulta'] : 'Sin información') ?>
                                </div>
                                <div class="hi-clinical-block <?= empty($consulta['anamnesis']) ? 'vacio' : '' ?>">
                                    <strong>Anamnesis</strong>
                                    <?= e(trim((string) ($consulta['anamnesis'] ?? '')) !== '' ? (string) $consulta['anamnesis'] : 'Sin información') ?>
                                </div>
                                <div class="hi-clinical-block <?= empty($consulta['examen_fisico']) ? 'vacio' : '' ?>">
                                    <strong>Examen físico</strong>
                                    <?= e(trim((string) ($consulta['examen_fisico'] ?? '')) !== '' ? (string) $consulta['examen_fisico'] : 'Sin información') ?>
                                </div>
                                <div class="hi-clinical-block <?= empty($consulta['plan_tratamiento']) ? 'vacio' : '' ?>">
                                    <strong>Plan de tratamiento</strong>
                                    <?= e(trim((string) ($consulta['plan_tratamiento'] ?? '')) !== '' ? (string) $consulta['plan_tratamiento'] : 'Sin información') ?>
                                </div>
                            </div>

                            <!-- Diagnósticos y estudios -->
                            <div class="hi-section-title"><i class="fas fa-stethoscope"></i> Diagnósticos y estudios vinculados</div>
                            <?php if (empty($consulta['diagnosticos'])) { ?>
                                <p class="hi-empty-small">Sin diagnósticos registrados.</p>
                            <?php } ?>
                            <?php foreach ($consulta['diagnosticos'] as $diagnostico) {
                                $diagId = (int) $diagnostico['id'];
                                $esPrincipal = (string) $diagnostico['tipo'] === 'PRINCIPAL';
                                $estudiosDiag = [];
                                foreach (($consulta['estudios'] ?? []) as $estudio) {
                                    if ((int) ($estudio['id_diagnostico'] ?? 0) === $diagId) {
                                        $estudiosDiag[] = $estudio;
                                    }
                                }
                            ?>
                                <div class="hi-diagnostico-card">
                                    <div class="hi-diagnostico-header">
                                        <span class="badge <?= $esPrincipal ? 'badge-success' : 'badge-secondary' ?>"><?= e((string) $diagnostico['tipo']) ?></span>
                                        <span class="hi-diagnostico-title"><?= e((string) $diagnostico['descripcion']) ?></span>
                                    </div>
                                    <?php if (!empty($diagnostico['observaciones'])) { ?>
                                        <div class="hi-diagnostico-obs"><?= e((string) $diagnostico['observaciones']) ?></div>
                                    <?php } ?>
                                    <div class="hi-estudios-list">
                                        <?php if ($estudiosDiag === []) { ?>
                                            <p class="hi-empty-small mb-0">Sin estudios vinculados a este diagnóstico.</p>
                                        <?php } else { ?>
                                            <?php foreach ($estudiosDiag as $estudio) { ?>
                                                <div class="hi-estudio-item">
                                                    <div>
                                                        <div class="hi-estudio-title"><i class="fas fa-vial text-secondary mr-1"></i> <?= e((string) ($estudio['tipo_estudio'] ?? '')) ?></div>
                                                        <div class="hi-estudio-meta">
                                                            <?= e((string) ($estudio['indicacion'] ?? '')) ?>
                                                            · Solicitado: <?= e($fechaCorta((string) ($estudio['fecha_solicitud'] ?? ''))) ?>
                                                        </div>
                                                        <?php if (!empty($estudio['resultado'])) { ?>
                                                            <div class="hi-estudio-meta"><strong>Resultado:</strong> <?= e((string) $estudio['resultado']) ?></div>
                                                        <?php } ?>
                                                    </div>
                                                    <span class="badge badge-info"><?= e((string) ($estudio['estado'] ?? '')) ?></span>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php
                            $estudiosSinDiag = [];
                            foreach (($consulta['estudios'] ?? []) as $estudio) {
                                if (empty($estudio['id_diagnostico'])) {
                                    $estudiosSinDiag[] = $estudio;
                                }
                            }
                            ?>
                            <?php if ($estudiosSinDiag !== []) { ?>
                                <div class="hi-section-title" style="font-size:0.75rem;"><i class="fas fa-vial"></i> Estudios sin diagnóstico vinculado</div>
                                <div class="hi-estudios-list" style="background:transparent;border:none;padding:0;">
                                    <?php foreach ($estudiosSinDiag as $estudio) { ?>
                                        <div class="hi-estudio-item">
                                            <div>
                                                <div class="hi-estudio-title"><i class="fas fa-vial text-secondary mr-1"></i> <?= e((string) ($estudio['tipo_estudio'] ?? '')) ?></div>
                                                <div class="hi-estudio-meta">
                                                    <?= e((string) ($estudio['indicacion'] ?? '')) ?>
                                                    · Solicitado: <?= e($fechaCorta((string) ($estudio['fecha_solicitud'] ?? ''))) ?>
                                                </div>
                                                <?php if (!empty($estudio['resultado'])) { ?>
                                                    <div class="hi-estudio-meta"><strong>Resultado:</strong> <?= e((string) $estudio['resultado']) ?></div>
                                                <?php } ?>
                                            </div>
                                            <span class="badge badge-info"><?= e((string) ($estudio['estado'] ?? '')) ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <!-- Evaluación previa -->
                            <div class="hi-section-title"><i class="fas fa-clipboard-check"></i> Evaluación previa</div>
                            <?php if (empty($consulta['evaluaciones'])) { ?>
                                <p class="hi-empty-small">No se registró evaluación previa para esta cita.</p>
                            <?php } else { ?>
                                <?php foreach ($consulta['evaluaciones'] as $evaluacion) {
                                    $estadoEv = (string) ($evaluacion['estado'] ?? '');
                                ?>
                                    <div class="hi-evaluacion-card">
                                        <div class="hi-evaluacion-header">
                                            <span class="badge <?= $estadoEv === 'COMPLETADA' ? 'badge-success' : ($estadoEv === 'ANULADA' ? 'badge-dark' : 'badge-info') ?>">
                                                <?= e($estadoEv) ?>
                                            </span>
                                            <span class="text-muted" style="font-size:0.8rem;"><?= e($fechaCorta((string) ($evaluacion['fecha_hora'] ?? ''))) ?></span>
                                        </div>
                                        <div class="hi-section-title" style="margin:0 0 8px;font-size:0.7rem;"><i class="fas fa-heartbeat"></i> Signos vitales</div>
                                        <div class="hi-signos-grid">
                                            <div class="hi-signo">
                                                <div class="hi-signo-value"><?= e(trim(((string) ($evaluacion['presion_sistolica'] ?? '')) . '/' . ((string) ($evaluacion['presion_diastolica'] ?? '')), '/') !== '' ? trim(((string) ($evaluacion['presion_sistolica'] ?? '')) . '/' . ((string) ($evaluacion['presion_diastolica'] ?? '')), '/') : '—') ?></div>
                                                <div class="hi-signo-label">Presión</div>
                                            </div>
                                            <div class="hi-signo">
                                                <div class="hi-signo-value"><?= e((string) ($evaluacion['temperatura'] ?? '—')) ?></div>
                                                <div class="hi-signo-label">Temperatura</div>
                                            </div>
                                            <div class="hi-signo">
                                                <div class="hi-signo-value"><?= e((string) ($evaluacion['frecuencia_cardiaca'] ?? '—')) ?></div>
                                                <div class="hi-signo-label">Frec. cardiaca</div>
                                            </div>
                                            <div class="hi-signo">
                                                <div class="hi-signo-value"><?= e((string) ($evaluacion['frecuencia_respiratoria'] ?? '—')) ?></div>
                                                <div class="hi-signo-label">Frec. respiratoria</div>
                                            </div>
                                            <div class="hi-signo">
                                                <div class="hi-signo-value"><?= e((string) ($evaluacion['saturacion_oxigeno'] ?? '—')) ?></div>
                                                <div class="hi-signo-label">SpO₂</div>
                                            </div>
                                            <div class="hi-signo">
                                                <div class="hi-signo-value"><?= e(trim(((string) ($evaluacion['peso_kg'] ?? '')) . ' kg / ' . ((string) ($evaluacion['talla_cm'] ?? '')) . ' cm', ' kg /  cm') !== '' ? trim(((string) ($evaluacion['peso_kg'] ?? '')) . ' kg / ' . ((string) ($evaluacion['talla_cm'] ?? '')) . ' cm', ' kg /  cm') : '—') ?></div>
                                                <div class="hi-signo-label">Peso / Talla</div>
                                            </div>
                                        </div>
                                        <?php if (!empty($evaluacion['anamnesis_preliminar'])) { ?>
                                            <div class="hi-section-title" style="margin-top:12px;font-size:0.7rem;"><i class="fas fa-comments"></i> Anamnesis preliminar</div>
                                            <div class="hi-clinical-block"><?= e((string) $evaluacion['anamnesis_preliminar']) ?></div>
                                        <?php } ?>
                                        <?php if (!empty($evaluacion['observaciones'])) { ?>
                                            <div class="hi-section-title" style="margin-top:12px;font-size:0.7rem;"><i class="fas fa-comment-medical"></i> Observaciones</div>
                                            <div class="hi-clinical-block"><?= e((string) $evaluacion['observaciones']) ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>

<script>
    $(function () {
        $(document).on('click', '.hi-consulta-header', function () {
            var $icon = $(this).find('.hi-chevron');
            var expanded = $(this).attr('aria-expanded') === 'true';
            $icon.css('transform', expanded ? 'rotate(0deg)' : 'rotate(180deg)');
            $(this).closest('.hi-timeline').find('.hi-consulta-header').not(this).each(function () {
                $(this).find('.hi-chevron').css('transform', 'rotate(0deg)');
            });
        });
    });
</script>
