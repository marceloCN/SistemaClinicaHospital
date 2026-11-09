<?php
/**
 * Reporte de expediente clínico e historial de citas del paciente.
 *
 * Busca por nombre o CI y, si encuentra un paciente, muestra su expediente,
 * alergias, antecedentes, medicamentos y una línea de tiempo de citas con
 * evaluación previa, consulta médica, diagnósticos y estudios solicitados.
 */
$busqueda = $busqueda ?? '';
$coincidencias = $coincidencias ?? [];
$paciente = $paciente ?? null;
$expediente = $expediente ?? null;
$alergias = $alergias ?? [];
$antecedentes = $antecedentes ?? [];
$medicamentos = $medicamentos ?? [];
$citas = $citas ?? [];

$tieneExpediente = $expediente !== null;
$tieneCitas = $citas !== [];

$esAdmin = ($rolClave ?? '') === 'ADMIN';

$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

$fechaCorta = static function (?string $valor): string {
    return ($valor === null || $valor === '') ? '—' : substr($valor, 0, 16);
};

$fechaSolo = static function (?string $valor): string {
    return ($valor === null || $valor === '') ? '—' : substr($valor, 0, 10);
};

$edad = static function (?string $nacimiento): string {
    if ($nacimiento === null || $nacimiento === '') {
        return '—';
    }
    $n = \DateTime::createFromFormat('Y-m-d', substr($nacimiento, 0, 10));
    if ($n === false) {
        return '—';
    }
    return ((int) (new \DateTime())->diff($n)->y) . ' años';
};

$badgeCita = [
    'PROGRAMADA' => 'badge-info',
    'CONFIRMADA' => 'badge-success',
    'REPROGRAMADA' => 'badge-info',
    'EN_RECEPCION' => 'badge-info',
    'EN_ATENCION' => 'badge-warning',
    'ATENDIDA' => 'badge-success',
    'CANCELADA' => 'badge-danger',
    'INASISTENCIA' => 'badge-danger',
];

$badgeConsulta = [
    'EN_CURSO' => 'badge-warning',
    'FINALIZADA' => 'badge-success',
];

$badgeDiagnostico = [
    'PRINCIPAL' => 'badge-success',
    'SECUNDARIO' => 'badge-secondary',
];

$claseAlergia = [
    'GRAVE' => 'badge-danger',
    'MODERADA' => 'badge-warning',
    'LEVE' => 'badge-secondary',
    'DESCONOCIDA' => 'badge-light',
];

$tipoAntecedenteIcono = [
    'ALERGIA' => 'fa-allergies',
    'PERSONAL' => 'fa-user',
    'FAMILIAR' => 'fa-users',
    'QUIRURGICO' => 'fa-procedures',
    'HOSPITALIZACION' => 'fa-hospital',
    'ENFERMEDAD_CRONICA' => 'fa-heartbeat',
    'OTRO' => 'fa-notes-medical',
];

$urlReportes = route('dashboard_reportes') ?? '/portal/dashboard/reportes';
$urlExpediente = route('portal_atencion_expediente', ['id' => $paciente['id'] ?? 0]) ?? '';
?>

<style>
    .pe-header {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;
        margin-bottom: 18px;
    }
    .pe-header h3 { margin: 0; font-size: 1.35rem; color: #0b2a38; }
    .pe-header p { margin: 4px 0 0; color: #6b7c83; }

    .pe-search {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 20px 22px; margin-bottom: 20px;
    }
    .pe-search .form-control { background: #f7f9f8; border-color: #e0e6e3; box-shadow: none; }
    .pe-search .form-control:focus { background: #fff; border-color: #0f8b84; }
    .pe-search .form-label {
        font-size: 0.8rem; font-weight: 700; color: #44545c; text-transform: uppercase; letter-spacing: 0.04em;
    }

    .pe-card {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 20px 22px; margin-bottom: 20px;
    }
    .pe-card-header {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;
    }
    .pe-card-title { font-size: 1.05rem; font-weight: 700; color: #0b2a38; margin: 0; }
    .pe-card-sub { color: #6b7c83; font-size: 0.85rem; margin: 2px 0 0; }

    .pe-datos-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px 16px; font-size: 0.88rem;
    }
    .pe-dato { background: #f8fafb; border-radius: 8px; padding: 10px 12px; }
    .pe-dato-label { font-size: 0.7rem; color: #6b7c83; text-transform: uppercase; font-weight: 700; }
    .pe-dato-value { color: #0b2a38; font-weight: 600; }

    .pe-stats {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 22px;
    }
    .pe-stat {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 12px; padding: 14px;
        display: flex; align-items: center; gap: 12px;
    }
    .pe-stat-icon {
        width: 42px; height: 42px; border-radius: 10px; background: #e8f4f3; color: #0f8b84;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    }
    .pe-stat-count { font-size: 1.25rem; font-weight: 700; color: #0b2a38; line-height: 1; }
    .pe-stat-label { font-size: 0.75rem; color: #6b7c83; }

    .pe-empty { text-align: center; padding: 42px 16px; color: #6b7c83; }
    .pe-empty i { font-size: 2.4rem; color: #adb5bd; margin-bottom: 12px; }

    .pe-lista-pacientes { display: grid; gap: 10px; }
    .pe-paciente-item {
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        background: #fff; border: 1px solid #e4e9e7; border-radius: 12px; padding: 14px 16px;
        transition: box-shadow 0.15s, transform 0.15s;
    }
    .pe-paciente-item:hover { box-shadow: 0 4px 14px rgba(11, 42, 56, 0.08); transform: translateY(-1px); }

    .pe-timeline { position: relative; padding-left: 28px; }
    .pe-timeline::before {
        content: ''; position: absolute; left: 10px; top: 8px; bottom: 0; width: 2px; background: #e4e9e7;
    }
    .pe-timeline-item { position: relative; margin-bottom: 18px; }
    .pe-timeline-marker {
        position: absolute; left: -24px; top: 14px; width: 14px; height: 14px; border-radius: 50%;
        background: #0f8b84; border: 2px solid #fff; box-shadow: 0 0 0 2px #e4e9e7;
    }
    .pe-timeline-item.atendida .pe-timeline-marker { background: #28a745; }
    .pe-timeline-item.cancelada .pe-timeline-marker { background: #dc3545; }

    .pe-cita {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; overflow: hidden;
    }
    .pe-cita-header {
        display: flex; align-items: center; gap: 14px; padding: 14px 16px; cursor: pointer;
        background: #fff; transition: background 0.15s;
    }
    .pe-cita-header:hover { background: #f8fafb; }
    .pe-cita-date {
        min-width: 52px; text-align: center; background: #f1f5f4; border-radius: 10px; padding: 8px 6px;
    }
    .pe-cita-day { font-size: 1.3rem; font-weight: 700; color: #0b2a38; line-height: 1; }
    .pe-cita-month { font-size: 0.7rem; color: #0f8b84; font-weight: 700; text-transform: uppercase; }
    .pe-cita-year { font-size: 0.65rem; color: #6b7c83; }
    .pe-cita-info { flex: 1; min-width: 0; }
    .pe-cita-info h5 { margin: 0; font-size: 0.98rem; font-weight: 700; color: #0b2a38; }
    .pe-cita-info p { margin: 2px 0 0; color: #6b7c83; font-size: 0.82rem; }
    .pe-cita-badges { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .pe-chevron { color: #0f8b84; transition: transform 0.2s; }
    .pe-cita-header[aria-expanded="true"] .pe-chevron { transform: rotate(180deg); }

    .pe-cita-body { padding: 16px; border-top: 1px solid #e9eeec; }
    .pe-section-title {
        font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        color: #0f8b84; margin: 18px 0 10px; display: flex; align-items: center; gap: 6px;
    }
    .pe-section-title:first-child { margin-top: 0; }

    .pe-clinical-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
    @media (min-width: 992px) { .pe-clinical-grid { grid-template-columns: 1fr 1fr; } }
    .pe-clinical-block {
        background: #f8fafb; border: 1px solid #e9eeec; border-radius: 10px; padding: 12px;
        font-size: 0.88rem; color: #0b2a38; white-space: pre-line;
    }
    .pe-clinical-block strong {
        display: block; font-size: 0.72rem; color: #6b7c83; text-transform: uppercase; margin-bottom: 4px;
    }
    .pe-clinical-block.vacio { color: #7d8f96; font-style: italic; }

    .pe-diagnostico-card { border: 1px solid #e9eeec; border-radius: 10px; margin-bottom: 10px; overflow: hidden; }
    .pe-diagnostico-header { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff; }
    .pe-diagnostico-title { font-weight: 600; color: #0b2a38; font-size: 0.92rem; }
    .pe-diagnostico-obs { color: #6b7c83; font-size: 0.8rem; padding: 0 12px 10px; }
    .pe-estudios-list { background: #f8fafb; border-top: 1px solid #e9eeec; padding: 10px 12px; }
    .pe-estudio-item {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;
        padding: 8px; background: #fff; border: 1px solid #e4e9e7; border-radius: 8px; margin-bottom: 8px;
    }
    .pe-estudio-item:last-child { margin-bottom: 0; }
    .pe-estudio-title { font-weight: 600; color: #0b2a38; font-size: 0.88rem; }
    .pe-estudio-meta { color: #6b7c83; font-size: 0.76rem; }

    .pe-evaluacion-card { background: #f8fafb; border: 1px solid #e9eeec; border-radius: 10px; padding: 12px; }
    .pe-evaluacion-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .pe-signos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px; }
    .pe-signo { background: #fff; border: 1px solid #e4e9e7; border-radius: 8px; padding: 8px; text-align: center; }
    .pe-signo-value { font-size: 0.95rem; font-weight: 700; color: #0f8b84; }
    .pe-signo-label { font-size: 0.65rem; color: #6b7c83; text-transform: uppercase; }

    .pe-pill-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .pe-pill { background: #f1f5f4; border: 1px solid #e4e9e7; border-radius: 20px; padding: 5px 10px; font-size: 0.82rem; }

    @media (max-width: 767.98px) {
        .pe-timeline { padding-left: 0; }
        .pe-timeline::before { display: none; }
        .pe-timeline-marker { display: none; }
        .pe-cita-header { flex-wrap: wrap; }
    }
</style>

<div class="pe-header">
    <div>
        <h3 class="m-0"><i class="fas fa-folder-open text-secondary"></i> Expediente e historial de citas</h3>
        <p class="text-muted mb-0">Busca un paciente por nombre o CI para ver su expediente clínico y citas.</p>
    </div>
    <a href="<?= e($urlReportes) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Reportes
    </a>
</div>

<form class="pe-search" method="GET" action="<?= route('portal_reporte_paciente_expediente') ?>">
    <div class="row align-items-end">
        <div class="col-lg-5 col-md-6 col-12 mb-3 mb-md-0">
            <label class="form-label" for="pe-busqueda">Paciente (nombre o CI)</label>
            <input type="text" class="form-control" id="pe-busqueda" name="busqueda" maxlength="100"
                value="<?= e((string) $busqueda) ?>" placeholder="Ej: Juan Cruz o 3861676">
        </div>
        <div class="col-lg-2 col-md-3 col-6">
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
        </div>
        <div class="col-lg-2 col-md-3 col-6">
            <a href="<?= route('portal_reporte_paciente_expediente') ?>" class="btn btn-outline-secondary btn-block">
                <i class="fas fa-eraser mr-1"></i> Limpiar
            </a>
        </div>
    </div>
</form>

<?php if ($coincidencias !== []) { ?>
    <div class="pe-card">
        <div class="pe-card-header">
            <div>
                <h4 class="pe-card-title"><i class="fas fa-list text-secondary mr-2"></i> Coincidencias encontradas</h4>
                <p class="pe-card-sub">Selecciona un paciente para ver su expediente.</p>
            </div>
            <span class="badge badge-secondary"><?= count($coincidencias) ?> paciente(s)</span>
        </div>
        <div class="pe-lista-pacientes">
            <?php foreach ($coincidencias as $c) { ?>
                <a class="pe-paciente-item text-decoration-none"
                   href="<?= e((route('portal_reporte_paciente_expediente') ?? '/portal/dashboard/reportes/paciente-expediente') . '?id=' . (int) $c['id']) ?>">
                    <div>
                        <strong class="d-block text-dark"><?= e((string) $c['apellido']) ?>, <?= e((string) $c['nombre']) ?></strong>
                        <small class="text-muted">CI <?= e((string) $c['ci']) ?> · <?= e($edad((string) ($c['fecha_nacimiento'] ?? ''))) ?></small>
                    </div>
                    <span class="btn btn-sm btn-outline-primary">Ver expediente</span>
                </a>
            <?php } ?>
        </div>
    </div>
<?php } elseif ($paciente === null && $busqueda !== '') { ?>
    <div class="pe-card pe-empty">
        <i class="fas fa-user-injured"></i>
        <h5 class="mt-2">No se encontró ningún paciente</h5>
        <p>No existe un paciente registrado con el nombre o CI "<?= e((string) $busqueda) ?>".<br>Verifica la búsqueda o regístralo en el módulo de pacientes.</p>
    </div>
<?php } elseif ($paciente === null) { ?>
    <div class="pe-card pe-empty">
        <i class="fas fa-search"></i>
        <h5 class="mt-2">Busca un paciente</h5>
        <p>Ingresa el nombre o número de CI del paciente para consultar su expediente e historial de citas.</p>
    </div>
<?php } else { ?>

    <!-- Resumen del paciente -->
    <div class="pe-card">
        <div class="pe-card-header">
            <div>
                <h4 class="pe-card-title"><i class="fas fa-id-card text-secondary mr-2"></i> <?= e((string) $paciente['nombre']) ?> <?= e((string) $paciente['apellido']) ?></h4>
                <p class="pe-card-sub">Resumen de identificación y expediente clínico.</p>
            </div>
            <span class="badge <?= $tieneExpediente ? 'badge-success' : 'badge-warning' ?>">
                <?= $tieneExpediente ? 'Expediente #' . (int) $expediente['id'] : 'Sin expediente' ?>
            </span>
        </div>
        <div class="pe-datos-grid">
            <div class="pe-dato"><div class="pe-dato-label">CI</div><div class="pe-dato-value"><?= e((string) $paciente['ci']) ?></div></div>
            <div class="pe-dato"><div class="pe-dato-label">Edad</div><div class="pe-dato-value"><?= e($edad((string) ($paciente['fecha_nacimiento'] ?? ''))) ?></div></div>
            <div class="pe-dato"><div class="pe-dato-label">Sexo</div><div class="pe-dato-value"><?= e((string) ($paciente['sexo'] ?? '—')) ?></div></div>
            <div class="pe-dato"><div class="pe-dato-label">Teléfono</div><div class="pe-dato-value"><?= e((string) ($paciente['telefono'] ?? '—')) ?></div></div>
            <div class="pe-dato"><div class="pe-dato-label">Correo</div><div class="pe-dato-value"><?= e((string) ($paciente['correo'] ?? '—')) ?></div></div>
            <div class="pe-dato"><div class="pe-dato-label">Total citas</div><div class="pe-dato-value"><?= (int) ($paciente['total_citas'] ?? 0) ?></div></div>
            <div class="pe-dato"><div class="pe-dato-label">Grupo sanguíneo</div>
                <div class="pe-dato-value">
                    <?php if ($tieneExpediente && trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) !== '') { ?>
                        <?= e((string) $expediente['grupo_sanguineo']) ?><?= e((string) $expediente['factor_rh']) ?>
                    <?php } else { ?>—<?php } ?>
                </div>
            </div>
        </div>

        <?php if ($tieneExpediente && trim((string) ($expediente['observaciones_generales'] ?? '')) !== '') { ?>
            <div class="mt-3 pe-clinical-block"><strong>Observaciones generales</strong><?= nl2br(e((string) $expediente['observaciones_generales'])) ?></div>
        <?php } ?>
    </div>

    <!-- Estadísticas -->
    <div class="pe-stats">
        <div class="pe-stat"><span class="pe-stat-icon"><i class="fas fa-calendar-check"></i></span><div><div class="pe-stat-count"><?= count($citas) ?></div><div class="pe-stat-label">Citas</div></div></div>
        <div class="pe-stat"><span class="pe-stat-icon"><i class="fas fa-allergies"></i></span><div><div class="pe-stat-count"><?= count($alergias) ?></div><div class="pe-stat-label">Alergias</div></div></div>
        <div class="pe-stat"><span class="pe-stat-icon"><i class="fas fa-notes-medical"></i></span><div><div class="pe-stat-count"><?= count($antecedentes) ?></div><div class="pe-stat-label">Antecedentes</div></div></div>
        <div class="pe-stat"><span class="pe-stat-icon"><i class="fas fa-pills"></i></span><div><div class="pe-stat-count"><?= count($medicamentos) ?></div><div class="pe-stat-label">Medicamentos</div></div></div>
    </div>

    <!-- Detalles del expediente -->
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="pe-card h-100">
                <h5 class="pe-card-title"><i class="fas fa-allergies text-secondary mr-2"></i> Alergias</h5>
                <?php if ($alergias === []) { ?>
                    <p class="pe-empty-small mt-2">No se registraron alergias.</p>
                <?php } else { ?>
                    <div class="pe-pill-list mt-2">
                        <?php foreach ($alergias as $a) { ?>
                            <span class="pe-pill" title="<?= e((string) ($a['reaccion'] ?? '')) ?>">
                                <?= e((string) $a['sustancia']) ?>
                                <span class="badge <?= $claseAlergia[$a['gravedad']] ?? 'badge-light' ?> badge-pill ml-1" style="font-size: 0.65rem;"><?= e((string) $a['gravedad']) ?></span>
                            </span>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="pe-card h-100">
                <h5 class="pe-card-title"><i class="fas fa-notes-medical text-secondary mr-2"></i> Antecedentes</h5>
                <?php if ($antecedentes === []) { ?>
                    <p class="pe-empty-small mt-2">No se registraron antecedentes médicos.</p>
                <?php } else { ?>
                    <ul class="list-unstyled mb-0 mt-2">
                        <?php foreach ($antecedentes as $a) { ?>
                            <li class="mb-2">
                                <i class="fas <?= $tipoAntecedenteIcono[$a['tipo']] ?? 'fa-notes-medical' ?> text-muted mr-1"></i>
                                <strong><?= e((string) $a['tipo']) ?>:</strong> <?= e((string) $a['descripcion']) ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="pe-card h-100">
                <h5 class="pe-card-title"><i class="fas fa-pills text-secondary mr-2"></i> Medicamentos</h5>
                <?php if ($medicamentos === []) { ?>
                    <p class="pe-empty-small mt-2">No se registraron medicamentos.</p>
                <?php } else { ?>
                    <ul class="list-unstyled mb-0 mt-2">
                        <?php foreach ($medicamentos as $m) { ?>
                            <li class="mb-2">
                                <strong><?= e((string) $m['nombre_medicamento']) ?></strong>
                                <?php if ($m['dosis'] !== '' || $m['frecuencia'] !== '') { ?>
                                    <br><small class="text-muted"><?= e((string) $m['dosis']) ?> <?= e((string) $m['frecuencia']) ?></small>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Historial de citas -->
    <div class="pe-card">
        <div class="pe-card-header">
            <div>
                <h4 class="pe-card-title"><i class="fas fa-history text-secondary mr-2"></i> Historial de citas</h4>
                <p class="pe-card-sub">Línea de tiempo con evaluación, consulta, diagnósticos y estudios.</p>
            </div>
        </div>

        <?php if (!$tieneCitas) { ?>
            <div class="pe-empty">
                <i class="fas fa-calendar-times"></i>
                <h5>Sin citas registradas</h5>
                <p>Este paciente no cuenta con citas en el historial.</p>
            </div>
        <?php } else { ?>
            <div class="pe-timeline">
                <?php foreach ($citas as $item) {
                    $cita = $item['cita'];
                    $evaluacion = $item['evaluacion'];
                    $diagnosticos = $item['diagnosticos'];
                    $estudios = $item['estudios'];
                    $estadoCita = (string) ($cita['cita_estado'] ?? '');
                    $claseItem = in_array($estadoCita, ['ATENDIDA'], true) ? 'atendida' : (in_array($estadoCita, ['CANCELADA', 'INASISTENCIA'], true) ? 'cancelada' : '');
                    $fecha = (string) ($cita['fecha'] ?? '');
                    $dia = $fecha !== '' ? (int) substr($fecha, 8, 2) : 0;
                    $mes = $fecha !== '' ? $meses[(int) substr($fecha, 5, 2) - 1] : '';
                    $anio = $fecha !== '' ? substr($fecha, 0, 4) : '';
                    $hora = substr((string) ($cita['fecha_hora_inicio'] ?? ''), 11, 5);
                ?>
                    <div class="pe-timeline-item <?= e($claseItem) ?>">
                        <div class="pe-timeline-marker"></div>
                        <div class="pe-cita">
                            <div class="pe-cita-header" data-toggle="collapse" data-target="#cita-<?= (int) $cita['id_cita'] ?>" aria-expanded="false">
                                <div class="pe-cita-date">
                                    <div class="pe-cita-day"><?= $dia > 0 ? $dia : '—' ?></div>
                                    <div class="pe-cita-month"><?= e($mes) ?></div>
                                    <div class="pe-cita-year"><?= e($anio) ?></div>
                                </div>
                                <div class="pe-cita-info">
                                    <h5><?= e((string) ($cita['especialidad'] ?? '')) ?> · <?= e((string) ($cita['medico'] ?? '')) ?></h5>
                                    <p><?= e($hora) ?> · <?= e((string) ($cita['motivo_consulta'] ?? 'Sin motivo')) ?></p>
                                </div>
                                <div class="pe-cita-badges">
                                    <span class="badge <?= $badgeCita[$estadoCita] ?? 'badge-secondary' ?>"><?= e($estadoCita) ?></span>
                                    <?php if (!empty($cita['consulta_estado'])) { ?>
                                        <span class="badge <?= $badgeConsulta[(string) $cita['consulta_estado']] ?? 'badge-secondary' ?>"><?= e((string) $cita['consulta_estado']) ?></span>
                                    <?php } ?>
                                </div>
                                <i class="fas fa-chevron-down pe-chevron"></i>
                            </div>
                            <div class="collapse pe-cita-body" id="cita-<?= (int) $cita['id_cita'] ?>">

                                <?php if ($evaluacion !== null) { ?>
                                    <div class="pe-section-title"><i class="fas fa-stethoscope"></i> Evaluación previa</div>
                                    <div class="pe-evaluacion-card">
                                        <div class="pe-evaluacion-header">
                                            <small class="text-muted"><?= e($fechaCorta((string) ($evaluacion['fecha_hora'] ?? ''))) ?></small>
                                            <?php if ((string) ($evaluacion['estado'] ?? '') !== '') { ?>
                                                <span class="badge badge-light border"><?= e((string) $evaluacion['estado']) ?></span>
                                            <?php } ?>
                                        </div>
                                        <div class="pe-signos-grid">
                                            <div class="pe-signo"><div class="pe-signo-value"><?= e((string) ($evaluacion['presion_sistolica'] ?? '—')) ?>/<?= e((string) ($evaluacion['presion_diastolica'] ?? '—')) ?></div><div class="pe-signo-label">Presión</div></div>
                                            <div class="pe-signo"><div class="pe-signo-value"><?= e((string) ($evaluacion['frecuencia_cardiaca'] ?? '—')) ?></div><div class="pe-signo-label">FC</div></div>
                                            <div class="pe-signo"><div class="pe-signo-value"><?= e((string) ($evaluacion['frecuencia_respiratoria'] ?? '—')) ?></div><div class="pe-signo-label">FR</div></div>
                                            <div class="pe-signo"><div class="pe-signo-value"><?= e((string) ($evaluacion['temperatura'] ?? '—')) ?></div><div class="pe-signo-label">Temp.</div></div>
                                            <div class="pe-signo"><div class="pe-signo-value"><?= e((string) ($evaluacion['saturacion_oxigeno'] ?? '—')) ?>%</div><div class="pe-signo-label">SatO2</div></div>
                                            <div class="pe-signo"><div class="pe-signo-value"><?= e((string) ($evaluacion['peso_kg'] ?? '—')) ?></div><div class="pe-signo-label">Peso</div></div>
                                            <div class="pe-signo"><div class="pe-signo-value"><?= e((string) ($evaluacion['talla_cm'] ?? '—')) ?></div><div class="pe-signo-label">Talla</div></div>
                                        </div>
                                        <?php if (trim((string) ($evaluacion['motivo_referido'] ?? '')) !== '') { ?>
                                            <div class="mt-2 pe-clinical-block"><strong>Motivo referido</strong><?= nl2br(e((string) $evaluacion['motivo_referido'])) ?></div>
                                        <?php } ?>
                                        <?php if (trim((string) ($evaluacion['anamnesis_preliminar'] ?? '')) !== '') { ?>
                                            <div class="mt-2 pe-clinical-block"><strong>Anamnesis preliminar</strong><?= nl2br(e((string) $evaluacion['anamnesis_preliminar'])) ?></div>
                                        <?php } ?>
                                        <?php if (trim((string) ($evaluacion['observaciones'] ?? '')) !== '') { ?>
                                            <div class="mt-2 pe-clinical-block"><strong>Observaciones</strong><?= nl2br(e((string) $evaluacion['observaciones'])) ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="pe-section-title"><i class="fas fa-stethoscope"></i> Evaluación previa</div>
                                    <p class="pe-empty-small">Esta cita no cuenta con evaluación previa registrada.</p>
                                <?php } ?>

                                <?php if (!empty($cita['id_consulta']) && !empty($cita['consulta_estado'])) { ?>
                                    <div class="pe-section-title"><i class="fas fa-user-md"></i> Consulta médica</div>
                                    <div class="pe-clinical-grid">
                                        <div class="pe-clinical-block <?= trim((string) ($cita['anamnesis'] ?? '')) === '' ? 'vacio' : '' ?>"><strong>Anamnesis</strong><?= nl2br(e(trim((string) ($cita['anamnesis'] ?? '')) !== '' ? (string) $cita['anamnesis'] : 'No registrado')) ?></div>
                                        <div class="pe-clinical-block <?= trim((string) ($cita['examen_fisico'] ?? '')) === '' ? 'vacio' : '' ?>"><strong>Examen físico</strong><?= nl2br(e(trim((string) ($cita['examen_fisico'] ?? '')) !== '' ? (string) $cita['examen_fisico'] : 'No registrado')) ?></div>
                                        <div class="pe-clinical-block <?= trim((string) ($cita['evolucion'] ?? '')) === '' ? 'vacio' : '' ?>"><strong>Evolución</strong><?= nl2br(e(trim((string) ($cita['evolucion'] ?? '')) !== '' ? (string) $cita['evolucion'] : 'No registrado')) ?></div>
                                        <div class="pe-clinical-block <?= trim((string) ($cita['plan_tratamiento'] ?? '')) === '' ? 'vacio' : '' ?>"><strong>Plan de tratamiento</strong><?= nl2br(e(trim((string) ($cita['plan_tratamiento'] ?? '')) !== '' ? (string) $cita['plan_tratamiento'] : 'No registrado')) ?></div>
                                    </div>
                                    <?php if (trim((string) ($cita['consulta_observaciones'] ?? '')) !== '') { ?>
                                        <div class="mt-2 pe-clinical-block"><strong>Observaciones de la consulta</strong><?= nl2br(e((string) $cita['consulta_observaciones'])) ?></div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="pe-section-title"><i class="fas fa-user-md"></i> Consulta médica</div>
                                    <p class="pe-empty-small">Esta cita aún no fue atendida o no tiene consulta médica registrada.</p>
                                <?php } ?>

                                <?php if ($diagnosticos !== []) { ?>
                                    <div class="pe-section-title"><i class="fas fa-diagnoses"></i> Diagnósticos</div>
                                    <?php foreach ($diagnosticos as $d) { ?>
                                        <div class="pe-diagnostico-card">
                                            <div class="pe-diagnostico-header">
                                                <span class="badge <?= $badgeDiagnostico[(string) $d['tipo']] ?? 'badge-secondary' ?>"><?= e((string) $d['tipo']) ?></span>
                                                <span class="pe-diagnostico-title"><?= e((string) $d['descripcion']) ?></span>
                                            </div>
                                            <?php if (trim((string) ($d['observaciones'] ?? '')) !== '') { ?>
                                                <div class="pe-diagnostico-obs"><?= nl2br(e((string) $d['observaciones'])) ?></div>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="pe-section-title"><i class="fas fa-diagnoses"></i> Diagnósticos</div>
                                    <p class="pe-empty-small">No se registraron diagnósticos para esta cita.</p>
                                <?php } ?>

                                <?php if ($estudios !== []) { ?>
                                    <div class="pe-section-title"><i class="fas fa-x-ray"></i> Estudios solicitados</div>
                                    <div class="pe-estudios-list">
                                        <?php foreach ($estudios as $es) { ?>
                                            <div class="pe-estudio-item">
                                                <div>
                                                    <div class="pe-estudio-title"><?= e((string) ($es['tipo_estudio'] ?? '')) ?></div>
                                                    <div class="pe-estudio-meta">
                                                        <?= e($fechaSolo((string) ($es['fecha_solicitud'] ?? ''))) ?> · Estado: <?= e((string) ($es['estado'] ?? '—')) ?>
                                                        <?php if ((string) ($es['diagnostico'] ?? '') !== '') { ?>· Dx: <?= e((string) $es['diagnostico']) ?><?php } ?>
                                                    </div>
                                                    <?php if (trim((string) ($es['indicacion'] ?? '')) !== '') { ?>
                                                        <div class="pe-estudio-meta mt-1"><strong>Indicación:</strong> <?= e((string) $es['indicacion']) ?></div>
                                                    <?php } ?>
                                                    <?php if (trim((string) ($es['resultado'] ?? '')) !== '') { ?>
                                                        <div class="pe-estudio-meta mt-1"><strong>Resultado:</strong> <?= nl2br(e((string) $es['resultado'])) ?></div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="pe-section-title"><i class="fas fa-x-ray"></i> Estudios solicitados</div>
                                    <p class="pe-empty-small">No se registraron estudios para esta cita.</p>
                                <?php } ?>

                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

<?php } ?>
