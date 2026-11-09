<?php
/**
 * Expediente clínico del paciente: dashboard con menú lateral,
 * resumen del paciente y secciones de perfil médico, alergias,
 * antecedentes y medicamentos.
 */
$paciente = $paciente ?? [
    'id' => 0,
    'ci' => '',
    'nombre_completo' => '',
    'telefono' => '',
    'correo' => '',
    'fecha_nacimiento' => '',
    'sexo' => '',
    'total_citas' => 0,
];
$expediente = $expediente ?? null;
$alergias = $alergias ?? [];
$antecedentes = $antecedentes ?? [];
$medicamentos = $medicamentos ?? [];

$idPaciente = (int) ($paciente['id'] ?? 0);
$idExpediente = $expediente !== null ? (int) $expediente['id'] : 0;

$tiposAlergia = ['MEDICAMENTO' => 'Medicamento', 'ALIMENTO' => 'Alimento', 'AMBIENTAL' => 'Ambiental', 'LATEX' => 'Látex', 'OTRO' => 'Otro'];
$gravedades = ['LEVE' => 'Leve', 'MODERADA' => 'Moderada', 'GRAVE' => 'Grave', 'DESCONOCIDA' => 'Desconocida'];
$tiposAntecedente = [
    'PERSONAL' => 'Personal',
    'FAMILIAR' => 'Familiar',
    'QUIRURGICO' => 'Quirúrgico',
    'HOSPITALIZACION' => 'Hospitalización',
    'ENFERMEDAD_CRONICA' => 'Enfermedad crónica',
    'ALERGIA' => 'Alergia',
    'OTRO' => 'Otro',
];

$fechaCorta = static function (?string $valor): string {
    return ($valor === null || $valor === '') ? '—' : substr($valor, 0, 10);
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
    $diff = $hoy->diff($n);
    return ((int) $diff->y) . ' años';
};

require __DIR__ . '/../base/confirmar.php';
?>

<style>
    .ex-alerta { display: none; }

    .ex-topbar {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;
        margin-bottom: 20px;
    }
    .ex-topbar h3 { margin: 0; font-size: 1.35rem; color: #0b2a38; }
    .ex-topbar p { margin: 4px 0 0; color: #6b7c83; }
    .ex-acciones { display: flex; gap: 8px; flex-wrap: wrap; }

    .ex-dashboard {
        display: grid; grid-template-columns: 1fr; gap: 18px;
    }
    @media (min-width: 992px) {
        .ex-dashboard { grid-template-columns: 260px 1fr; }
    }

    .ex-sidebar {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 16px;
        align-self: flex-start;
    }
    .ex-patient-mini {
        text-align: center; padding-bottom: 14px; border-bottom: 1px solid #eef2f1; margin-bottom: 12px;
    }
    .ex-avatar {
        width: 64px; height: 64px; margin: 0 auto 10px; border-radius: 50%;
        background: linear-gradient(135deg, #0f8b84 0%, #0b2a38 100%);
        color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
    }
    .ex-patient-name { font-weight: 700; color: #0b2a38; font-size: 0.95rem; }
    .ex-patient-meta { color: #6b7c83; font-size: 0.8rem; }

    .ex-menu { display: flex; flex-direction: column; gap: 6px; }
    .ex-menu-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 12px; border-radius: 10px; color: #0b2a38;
        text-decoration: none; font-size: 0.9rem; font-weight: 600;
        transition: background 0.15s, color 0.15s;
    }
    .ex-menu-item:hover { background: #f1f5f4; color: #0f8b84; text-decoration: none; }
    .ex-menu-item.active { background: #e8f4f3; color: #0f8b84; }
    .ex-menu-item i { width: 22px; text-align: center; margin-right: 8px; }

    .ex-content { min-width: 0; }
    .ex-panel { display: none; }
    .ex-panel.active { display: block; animation: ex-fade-in 0.25s ease; }
    @keyframes ex-fade-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

    .ex-card {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; margin-bottom: 18px;
    }
    .ex-card-header {
        display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;
        margin-bottom: 16px;
    }
    .ex-card-title { font-size: 1.05rem; font-weight: 700; color: #0b2a38; margin: 0; }
    .ex-card-sub { color: #6b7c83; font-size: 0.85rem; margin: 2px 0 0; }

    .ex-resumen-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;
    }
    .ex-resumen-item {
        background: #f8fafb; border: 1px solid #e9eeec; border-radius: 10px; padding: 12px;
        text-align: center;
    }
    .ex-resumen-item i { font-size: 1.2rem; color: #0f8b84; margin-bottom: 6px; }
    .ex-resumen-label { font-size: 0.7rem; color: #6b7c83; text-transform: uppercase; font-weight: 700; }
    .ex-resumen-value { font-size: 0.95rem; color: #0b2a38; font-weight: 600; }

    .ex-datos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; font-size: 0.9rem; }
    .ex-datos dt { color: #6b7c83; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 1px; }
    .ex-datos dd { color: #0b2a38; font-weight: 500; margin-bottom: 0; }

    .ex-summary-cards {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 18px;
    }
    .ex-summary-card {
        background: #fff; border: 1px solid #e4e9e7; border-radius: 12px; padding: 14px;
        display: flex; align-items: center; gap: 12px; cursor: pointer; transition: box-shadow 0.15s;
    }
    .ex-summary-card:hover { box-shadow: 0 4px 12px rgba(11,42,56,0.08); }
    .ex-summary-icon { width: 40px; height: 40px; border-radius: 10px; background: #e8f4f3; color: #0f8b84; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
    .ex-summary-text { flex: 1; }
    .ex-summary-count { font-size: 1.15rem; font-weight: 700; color: #0b2a38; }
    .ex-summary-label { font-size: 0.75rem; color: #6b7c83; }

    .ex-item {
        border: 1px solid #e9eeec; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px;
        background: #fff;
    }
    .ex-item-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; }
    .ex-item-title { color: #0b2a38; font-size: 0.95rem; font-weight: 600; }
    .ex-item-meta { color: #6b7c83; font-size: 0.8rem; }
    .ex-vacio { color: #7d8f96; font-style: italic; font-size: 0.88rem; }

    .ex-form-box {
        background: #f8fafb; border: 1px dashed #c5d3ce; border-radius: 12px; padding: 16px; margin-top: 16px;
    }
    .ex-form-title { font-size: 0.85rem; font-weight: 700; color: #0f8b84; margin-bottom: 12px; text-transform: uppercase; }

    .ex-badge-grave { background: #dc3545; }
    .ex-badge-mod { background: #ffc107; color: #212529; }
    .ex-badge-leve { background: #6c757d; }
    .ex-badge-activo { background: #0f8b84; }

    .ex-empty-state { text-align: center; padding: 24px 10px; }
    .ex-empty-state i { font-size: 2.2rem; color: #adb5bd; }
    .ex-empty-state p { color: #6b7c83; margin-top: 8px; }
</style>

<div class="ex-topbar">
    <div>
        <h3><i class="fas fa-folder-open text-secondary mr-2"></i> Expediente clínico</h3>
        <p>
            <?= e((string) ($paciente['nombre_completo'] ?? '')) ?>
            <?php if (!empty($paciente['ci'])) { ?> · CI <?= e((string) $paciente['ci']) ?><?php } ?>
        </p>
    </div>
    <div class="ex-acciones">
        <a href="<?= e(route('dashboard_atencion') ?? '/portal/dashboard/atencion') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Bandeja
        </a>
        <a href="<?= e(route('portal_atencion_historial', ['id' => $idPaciente]) ?? '') ?>" class="btn btn-outline-info">
            <i class="fas fa-history mr-1"></i> Historial
        </a>
    </div>
</div>

<div id="expediente-alerta" class="alert alert-danger ex-alerta"></div>

<?php if ($expediente === null) { ?>
    <div class="ex-card text-center" style="max-width:640px;margin:0 auto;">
        <div class="ex-empty-state">
            <i class="fas fa-file-medical"></i>
            <h4 class="mt-2">Sin expediente clínico</h4>
            <p>Este paciente aún no tiene un expediente clínico. Créalo para registrar perfil, alergias, antecedentes y medicamentos.</p>
        </div>
        <form id="form-crear-expediente" class="text-left" autocomplete="off">
            <input type="hidden" name="id_paciente" value="<?= $idPaciente ?>">
            <div class="form-group">
                <label for="expediente-observaciones">Observaciones generales</label>
                <textarea class="form-control" id="expediente-observaciones" name="observaciones_generales" rows="3" maxlength="4000"></textarea>
            </div>
            <div class="ex-acciones">
                <button type="button" class="btn btn-success" id="btn-crear-expediente">
                    <i class="fas fa-folder-plus mr-1"></i> Crear expediente
                </button>
            </div>
        </form>
    </div>
<?php } else { ?>

<div class="ex-dashboard">
    <!-- Menú lateral -->
    <aside class="ex-sidebar">
        <div class="ex-patient-mini">
            <div class="ex-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="ex-patient-name"><?= e((string) ($paciente['nombre_completo'] ?? '')) ?></div>
            <div class="ex-patient-meta">CI <?= e((string) ($paciente['ci'] ?? '—')) ?></div>
        </div>
        <nav class="ex-menu" id="ex-menu">
            <a href="#" class="ex-menu-item active" data-target="panel-resumen">
                <span><i class="fas fa-user-injured"></i> Resumen</span>
            </a>
            <a href="#" class="ex-menu-item" data-target="panel-perfil">
                <span><i class="fas fa-heartbeat"></i> Perfil médico</span>
            </a>
            <a href="#" class="ex-menu-item" data-target="panel-alergias">
                <span><i class="fas fa-allergies"></i> Alergias</span>
                <span class="badge badge-pill badge-light border"><?= count($alergias) ?></span>
            </a>
            <a href="#" class="ex-menu-item" data-target="panel-antecedentes">
                <span><i class="fas fa-notes-medical"></i> Antecedentes</span>
                <span class="badge badge-pill badge-light border"><?= count($antecedentes) ?></span>
            </a>
            <a href="#" class="ex-menu-item" data-target="panel-medicamentos">
                <span><i class="fas fa-pills"></i> Medicamentos</span>
                <span class="badge badge-pill badge-light border"><?= count($medicamentos) ?></span>
            </a>
        </nav>
    </aside>

    <!-- Contenido -->
    <div class="ex-content">

        <!-- RESUMEN -->
        <section id="panel-resumen" class="ex-panel active">
            <div class="ex-card">
                <div class="ex-card-header">
                    <div>
                        <h4 class="ex-card-title"><i class="fas fa-id-card text-secondary mr-2"></i> Datos del paciente</h4>
                        <p class="ex-card-sub">Expediente #<?= $idExpediente ?> · Abierto <?= e($fechaCorta((string) ($expediente['fecha_apertura'] ?? ''))) ?></p>
                    </div>
                    <span class="badge <?= !empty($expediente['estado']) ? 'badge-success' : 'badge-secondary' ?>">
                        <?= !empty($expediente['estado']) ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>

                <div class="ex-resumen-grid">
                    <div class="ex-resumen-item">
                        <i class="fas fa-birthday-cake"></i>
                        <div class="ex-resumen-label">Nacimiento</div>
                        <div class="ex-resumen-value"><?= e($fechaCorta((string) ($paciente['fecha_nacimiento'] ?? ''))) ?></div>
                    </div>
                    <div class="ex-resumen-item">
                        <i class="fas fa-hourglass-half"></i>
                        <div class="ex-resumen-label">Edad</div>
                        <div class="ex-resumen-value"><?= e($edad((string) ($paciente['fecha_nacimiento'] ?? ''))) ?></div>
                    </div>
                    <div class="ex-resumen-item">
                        <i class="fas fa-venus-mars"></i>
                        <div class="ex-resumen-label">Sexo</div>
                        <div class="ex-resumen-value"><?= e((string) ($paciente['sexo'] ?? '—')) ?></div>
                    </div>
                    <div class="ex-resumen-item">
                        <i class="fas fa-phone"></i>
                        <div class="ex-resumen-label">Teléfono</div>
                        <div class="ex-resumen-value"><?= e((string) ($paciente['telefono'] ?? '—')) ?></div>
                    </div>
                    <div class="ex-resumen-item">
                        <i class="fas fa-envelope"></i>
                        <div class="ex-resumen-label">Correo</div>
                        <div class="ex-resumen-value"><?= e((string) ($paciente['correo'] ?? '—')) ?></div>
                    </div>
                    <div class="ex-resumen-item">
                        <i class="fas fa-tint"></i>
                        <div class="ex-resumen-label">Grupo sanguíneo</div>
                        <div class="ex-resumen-value">
                            <?= e(trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) !== '' ? trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) : '—') ?>
                        </div>
                    </div>
                    <div class="ex-resumen-item">
                        <i class="fas fa-calendar-check"></i>
                        <div class="ex-resumen-label">Total citas</div>
                        <div class="ex-resumen-value"><?= (int) ($paciente['total_citas'] ?? 0) ?></div>
                    </div>
                </div>

                <div class="ex-form-box mt-4">
                    <div class="ex-form-title"><i class="fas fa-sticky-note mr-1"></i> Observaciones generales</div>
                    <div style="white-space:pre-line;color:#0b2a38;">
                        <?= e(trim((string) ($expediente['observaciones_generales'] ?? '')) !== '' ? (string) $expediente['observaciones_generales'] : 'Sin observaciones registradas.') ?>
                    </div>
                </div>
            </div>

            <div class="ex-summary-cards">
                <div class="ex-summary-card" data-target="panel-perfil">
                    <div class="ex-summary-icon"><i class="fas fa-heartbeat"></i></div>
                    <div class="ex-summary-text">
                        <div class="ex-summary-count"><?= e(trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) !== '' ? trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) : '—') ?></div>
                        <div class="ex-summary-label">Perfil médico</div>
                    </div>
                </div>
                <div class="ex-summary-card" data-target="panel-alergias">
                    <div class="ex-summary-icon"><i class="fas fa-allergies"></i></div>
                    <div class="ex-summary-text">
                        <div class="ex-summary-count"><?= count($alergias) ?></div>
                        <div class="ex-summary-label">Alergias</div>
                    </div>
                </div>
                <div class="ex-summary-card" data-target="panel-antecedentes">
                    <div class="ex-summary-icon"><i class="fas fa-notes-medical"></i></div>
                    <div class="ex-summary-text">
                        <div class="ex-summary-count"><?= count($antecedentes) ?></div>
                        <div class="ex-summary-label">Antecedentes</div>
                    </div>
                </div>
                <div class="ex-summary-card" data-target="panel-medicamentos">
                    <div class="ex-summary-icon"><i class="fas fa-pills"></i></div>
                    <div class="ex-summary-text">
                        <div class="ex-summary-count"><?= count($medicamentos) ?></div>
                        <div class="ex-summary-label">Medicamentos</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PERFIL MÉDICO -->
        <section id="panel-perfil" class="ex-panel">
            <div class="ex-card">
                <div class="ex-card-header">
                    <div>
                        <h4 class="ex-card-title"><i class="fas fa-heartbeat text-secondary mr-2"></i> Perfil médico</h4>
                        <p class="ex-card-sub">Datos base del expediente como grupo sanguíneo y observaciones médicas.</p>
                    </div>
                </div>
                <div id="perfil-alerta" class="alert alert-danger ex-alerta"></div>
                <form id="form-perfil" autocomplete="off">
                    <input type="hidden" name="id_expediente" value="<?= $idExpediente ?>">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="perfil-grupo">Grupo sanguíneo *</label>
                            <select class="form-control" id="perfil-grupo" name="grupo_sanguineo" required>
                                <option value="">Selecciona…</option>
                                <?php foreach (['A', 'B', 'AB', 'O'] as $grupo) { ?>
                                    <option value="<?= $grupo ?>" <?= (string) ($expediente['grupo_sanguineo'] ?? '') === $grupo ? 'selected' : '' ?>><?= $grupo ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="perfil-rh">Factor RH *</label>
                            <select class="form-control" id="perfil-rh" name="factor_rh" required>
                                <option value="">Selecciona…</option>
                                <option value="+" <?= (string) ($expediente['factor_rh'] ?? '') === '+' ? 'selected' : '' ?>>Positivo (+)</option>
                                <option value="-" <?= (string) ($expediente['factor_rh'] ?? '') === '-' ? 'selected' : '' ?>>Negativo (−)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="perfil-observaciones">Observaciones del perfil</label>
                        <textarea class="form-control" id="perfil-observaciones" name="observaciones" rows="3" maxlength="4000"><?= e((string) ($expediente['perfil_observaciones'] ?? '')) ?></textarea>
                    </div>
                    <div class="ex-acciones">
                        <button type="button" class="btn btn-primary" id="btn-guardar-perfil">
                            <i class="fas fa-save mr-1"></i> Guardar perfil
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ALERGIAS -->
        <section id="panel-alergias" class="ex-panel">
            <div class="ex-card">
                <div class="ex-card-header">
                    <div>
                        <h4 class="ex-card-title"><i class="fas fa-allergies text-secondary mr-2"></i> Alergias</h4>
                        <p class="ex-card-sub">Registro de alergias conocidas del paciente.</p>
                    </div>
                </div>

                <div id="alergias-lista">
                    <?php if ($alergias === []) { ?>
                        <div class="ex-empty-state">
                            <i class="fas fa-allergies"></i>
                            <p>No hay alergias registradas.</p>
                        </div>
                    <?php } ?>
                    <?php foreach ($alergias as $alergia) {
                        $grav = (string) ($alergia['gravedad'] ?? '');
                        $badgeClass = $grav === 'GRAVE' ? 'ex-badge-grave' : ($grav === 'MODERADA' ? 'ex-badge-mod' : 'ex-badge-leve');
                    ?>
                        <div class="ex-item" data-id="<?= (int) $alergia['id'] ?>">
                            <div class="ex-item-header">
                                <div>
                                    <div class="ex-item-title"><?= e((string) $alergia['sustancia']) ?></div>
                                    <div class="ex-item-meta">
                                        <?= e($tiposAlergia[(string) $alergia['tipo']] ?? (string) $alergia['tipo']) ?>
                                        <?php if (!empty($alergia['reaccion'])) { ?> · Reacción: <?= e((string) $alergia['reaccion']) ?><?php } ?>
                                    </div>
                                    <div class="ex-item-meta">Registrada: <?= e($fechaCorta((string) ($alergia['fecha_registro'] ?? ''))) ?></div>
                                </div>
                                <span class="badge <?= $badgeClass ?>"><?= e($gravedades[$grav] ?? $grav) ?></span>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="ex-form-box">
                    <div class="ex-form-title"><i class="fas fa-plus mr-1"></i> Registrar nueva alergia</div>
                    <div id="alergia-alerta" class="alert alert-danger ex-alerta"></div>
                    <form id="form-alergia" autocomplete="off">
                        <input type="hidden" name="id_expediente" value="<?= $idExpediente ?>">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="alergia-sustancia">Sustancia *</label>
                                <input type="text" class="form-control" id="alergia-sustancia" name="sustancia" maxlength="150" placeholder="Ej. Penicilina, maní, polen..." required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="alergia-tipo">Tipo *</label>
                                <select class="form-control" id="alergia-tipo" name="tipo" required>
                                    <?php foreach ($tiposAlergia as $clave => $etiqueta) { ?>
                                        <option value="<?= $clave ?>"><?= e($etiqueta) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="alergia-gravedad">Gravedad *</label>
                                <select class="form-control" id="alergia-gravedad" name="gravedad" required>
                                    <?php foreach ($gravedades as $clave => $etiqueta) { ?>
                                        <option value="<?= $clave ?>"><?= e($etiqueta) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="alergia-reaccion">Reacción</label>
                            <input type="text" class="form-control" id="alergia-reaccion" name="reaccion" maxlength="1000" placeholder="Describa la reacción observada">
                        </div>
                        <div class="ex-acciones">
                            <button type="button" class="btn btn-primary" id="btn-agregar-alergia">
                                <i class="fas fa-plus mr-1"></i> Agregar alergia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- ANTECEDENTES -->
        <section id="panel-antecedentes" class="ex-panel">
            <div class="ex-card">
                <div class="ex-card-header">
                    <div>
                        <h4 class="ex-card-title"><i class="fas fa-notes-medical text-secondary mr-2"></i> Antecedentes médicos</h4>
                        <p class="ex-card-sub">Historia clínica relevante clasificada por tipo.</p>
                    </div>
                </div>

                <div id="antecedentes-lista">
                    <?php if ($antecedentes === []) { ?>
                        <div class="ex-empty-state">
                            <i class="fas fa-notes-medical"></i>
                            <p>No hay antecedentes registrados.</p>
                        </div>
                    <?php } ?>
                    <?php foreach ($antecedentes as $antecedente) { ?>
                        <div class="ex-item" data-id="<?= (int) $antecedente['id'] ?>">
                            <div class="ex-item-header">
                                <div>
                                    <div class="ex-item-title"><?= e((string) $antecedente['descripcion']) ?></div>
                                    <div class="ex-item-meta">
                                        <span class="badge badge-light border"><?= e($tiposAntecedente[(string) $antecedente['tipo']] ?? (string) $antecedente['tipo']) ?></span>
                                        · <?= e($fechaCorta((string) ($antecedente['fecha_registro'] ?? ''))) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="ex-form-box">
                    <div class="ex-form-title"><i class="fas fa-plus mr-1"></i> Registrar nuevo antecedente</div>
                    <div id="antecedente-alerta" class="alert alert-danger ex-alerta"></div>
                    <form id="form-antecedente" autocomplete="off">
                        <input type="hidden" name="id_expediente" value="<?= $idExpediente ?>">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="antecedente-tipo">Tipo *</label>
                                <select class="form-control" id="antecedente-tipo" name="tipo" required>
                                    <?php foreach ($tiposAntecedente as $clave => $etiqueta) { ?>
                                        <option value="<?= $clave ?>"><?= e($etiqueta) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label for="antecedente-descripcion">Descripción *</label>
                                <textarea class="form-control" id="antecedente-descripcion" name="descripcion" rows="2" maxlength="2000" placeholder="Describa el antecedente médico..." required></textarea>
                            </div>
                        </div>
                        <div class="ex-acciones">
                            <button type="button" class="btn btn-primary" id="btn-agregar-antecedente">
                                <i class="fas fa-plus mr-1"></i> Agregar antecedente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- MEDICAMENTOS -->
        <section id="panel-medicamentos" class="ex-panel">
            <div class="ex-card">
                <div class="ex-card-header">
                    <div>
                        <h4 class="ex-card-title"><i class="fas fa-pills text-secondary mr-2"></i> Medicamentos</h4>
                        <p class="ex-card-sub">Medicación actual o asociada al paciente.</p>
                    </div>
                </div>

                <div id="medicamentos-lista">
                    <?php if ($medicamentos === []) { ?>
                        <div class="ex-empty-state">
                            <i class="fas fa-pills"></i>
                            <p>No hay medicamentos registrados.</p>
                        </div>
                    <?php } ?>
                    <?php foreach ($medicamentos as $medicamento) { ?>
                        <div class="ex-item" data-id="<?= (int) $medicamento['id'] ?>">
                            <div class="ex-item-header">
                                <div>
                                    <div class="ex-item-title"><?= e((string) $medicamento['nombre_medicamento']) ?></div>
                                    <div class="ex-item-meta">
                                        <?php if (!empty($medicamento['dosis'])) { ?>Dosis: <?= e((string) $medicamento['dosis']) ?><?php } ?>
                                        <?php if (!empty($medicamento['frecuencia'])) { ?> · Frecuencia: <?= e((string) $medicamento['frecuencia']) ?><?php } ?>
                                    </div>
                                    <div class="ex-item-meta">
                                        <?= e($fechaCorta((string) ($medicamento['fecha_inicio'] ?? ''))) ?> → <?= e($fechaCorta((string) ($medicamento['fecha_fin'] ?? ''))) ?>
                                        <?php if (!empty($medicamento['prescrito_por'])) { ?> · Prescrito por: <?= e((string) $medicamento['prescrito_por']) ?><?php } ?>
                                    </div>
                                </div>
                                <span class="badge <?= !empty($medicamento['estado']) ? 'ex-badge-activo' : 'badge-light border' ?>"><?= !empty($medicamento['estado']) ? 'Activo' : 'Inactivo' ?></span>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="ex-form-box">
                    <div class="ex-form-title"><i class="fas fa-plus mr-1"></i> Registrar nuevo medicamento</div>
                    <div id="medicamento-alerta" class="alert alert-danger ex-alerta"></div>
                    <form id="form-medicamento" autocomplete="off">
                        <input type="hidden" name="id_expediente" value="<?= $idExpediente ?>">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="medicamento-nombre">Nombre *</label>
                                <input type="text" class="form-control" id="medicamento-nombre" name="nombre_medicamento" maxlength="150" placeholder="Ej. Losartán 50 mg" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="medicamento-dosis">Dosis</label>
                                <input type="text" class="form-control" id="medicamento-dosis" name="dosis" maxlength="100" placeholder="Ej. 1 tableta">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="medicamento-frecuencia">Frecuencia</label>
                                <input type="text" class="form-control" id="medicamento-frecuencia" name="frecuencia" maxlength="150" placeholder="Ej. Cada 8 horas">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="medicamento-inicio">Inicio</label>
                                <input type="date" class="form-control" id="medicamento-inicio" name="fecha_inicio">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="medicamento-fin">Fin</label>
                                <input type="date" class="form-control" id="medicamento-fin" name="fecha_fin">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="medicamento-prescrito">Prescrito por</label>
                                <input type="text" class="form-control" id="medicamento-prescrito" name="prescrito_por" maxlength="150" placeholder="Nombre del médico">
                            </div>
                        </div>
                        <div class="ex-acciones">
                            <button type="button" class="btn btn-primary" id="btn-agregar-medicamento">
                                <i class="fas fa-plus mr-1"></i> Agregar medicamento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

    </div>
</div>

<?php } ?>

<script>
    $(function () {
        var token = '<?= csrf_token() ?>';
        var idPaciente = <?= (int) $idPaciente ?>;
        var urlCrear = '<?= route('portal_atencion_expediente_crear') ?>';
        var urlPerfil = '<?= route('portal_atencion_perfil') ?>';
        var urlAlergia = '<?= route('portal_atencion_alergia') ?>';
        var urlAntecedente = '<?= route('portal_atencion_antecedente') ?>';
        var urlMedicamento = '<?= route('portal_atencion_medicamento') ?>';
        var urlExpediente = '<?= route('portal_atencion_expediente', ['id' => '{id}']) ?>';

        function mostrarError($el, mensaje) {
            $el.text(mensaje || 'Ocurrió un error.').show();
        }

        function ocultarError($el) {
            $el.hide().text('');
        }

        function post(url, datos, $alerta, $btn, alExito) {
            if ($alerta) ocultarError($alerta);
            if ($btn) $btn.prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: datos,
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if ($btn) $btn.prop('disabled', false);
                if (respuesta && respuesta.success) {
                    if (alExito) alExito(respuesta);
                    else window.espNotificar('success', respuesta.message || 'Guardado correctamente.');
                    return;
                }
                var mensaje = (respuesta && respuesta.message) || 'No se pudo completar la acción.';
                if ($alerta) mostrarError($alerta, mensaje);
                else window.espNotificar('error', mensaje);
            }).fail(function (xhr) {
                if ($btn) $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                var mensaje = (r && r.message) || 'No se pudo completar la acción.';
                if ($alerta) mostrarError($alerta, mensaje);
                else window.espNotificar('error', mensaje);
            });
        }

        function recargar() {
            setTimeout(function () {
                window.location.href = urlExpediente.replace('{id}', idPaciente);
            }, 900);
        }

        $('#btn-crear-expediente').on('click', function () {
            post(urlCrear, $('#form-crear-expediente').serialize(), $('#expediente-alerta'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Expediente creado.');
                recargar();
            });
        });

        $('#btn-guardar-perfil').on('click', function () {
            post(urlPerfil, $('#form-perfil').serialize(), $('#perfil-alerta'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Perfil guardado.');
                recargar();
            });
        });

        $('#btn-agregar-alergia').on('click', function () {
            post(urlAlergia, $('#form-alergia').serialize(), $('#alergia-alerta'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Alergia registrada.');
                recargar();
            });
        });

        $('#btn-agregar-antecedente').on('click', function () {
            post(urlAntecedente, $('#form-antecedente').serialize(), $('#antecedente-alerta'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Antecedente registrado.');
                recargar();
            });
        });

        $('#btn-agregar-medicamento').on('click', function () {
            post(urlMedicamento, $('#form-medicamento').serialize(), $('#medicamento-alerta'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Medicamento registrado.');
                recargar();
            });
        });

        // Navegación del dashboard
        $(document).on('click', '#ex-menu .ex-menu-item', function (e) {
            e.preventDefault();
            var target = $(this).data('target');
            $('#ex-menu .ex-menu-item').removeClass('active');
            $(this).addClass('active');
            $('.ex-panel').removeClass('active');
            $('#' + target).addClass('active');
        });

        $(document).on('click', '.ex-summary-card', function () {
            var target = $(this).data('target');
            if (!target) return;
            $('#ex-menu .ex-menu-item').removeClass('active');
            $('#ex-menu .ex-menu-item[data-target="' + target + '"]').addClass('active');
            $('.ex-panel').removeClass('active');
            $('#' + target).addClass('active');
        });
    });
</script>
