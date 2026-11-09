<?php
/**
 * Consulta médica de una cita: información de la cita, evaluación
 * previa, formulario clínico, diagnósticos, estudios solicitados y
 * acciones de guardado/finalización.
 */
$cita = $cita ?? [];
$recepcion = $recepcion ?? null;
$evaluacion = $evaluacion ?? null;
$consulta = $consulta ?? null;
$diagnosticos = $diagnosticos ?? [];
$estudios = $estudios ?? [];
$expediente = $expediente ?? null;
$tiposEstudio = $tiposEstudio ?? [];
$urlHistorial = $urlHistorial ?? '';
$urlExpediente = $urlExpediente ?? '';

$idCita = (int) ($cita['id'] ?? 0);
$estadoConsulta = (string) ($consulta['estado'] ?? '');
$enCurso = $estadoConsulta === 'EN_CURSO';
$finalizada = $estadoConsulta === 'FINALIZADA';
$sinIniciar = $consulta === null;

$fechaConsulta = function (?string $valor): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    return substr($valor, 0, 16);
};

require __DIR__ . '/../base/confirmar.php';
?>

<style>
    .cs-grid { display: grid; grid-template-columns: 1fr; gap: 18px; }
    @media (min-width: 992px) { .cs-grid { grid-template-columns: 2fr 1fr; } }
    .cs-card { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; }
    .cs-cabecera { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; }
    .cs-titulo { font-size: 1.05rem; font-weight: 700; color: #0b2a38; margin: 0; }
    .cs-sub { color: #6b7c83; font-size: 0.85rem; margin: 2px 0 0; }
    .cs-datos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; font-size: 0.9rem; }
    .cs-datos dt { color: #6b7c83; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 1px; }
    .cs-datos dd { color: #0b2a38; font-weight: 500; margin-bottom: 0; }
    .cs-seccion { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0f8b84; margin: 18px 0 10px; }
    .cs-item { border: 1px solid #e9eeec; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; }
    .cs-item.cs-principal { border-left: 4px solid #0f8b84; }
    .cs-item.cs-secundario { border-left: 4px solid #adb5bd; }
    .cs-item-cabecera { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; }
    .cs-item-texto { color: #0b2a38; font-size: 0.92rem; }
    .cs-item-meta { color: #6b7c83; font-size: 0.78rem; }
    .cs-vacio { color: #7d8f96; font-style: italic; font-size: 0.88rem; }
    .cs-alerta { display: none; }
    .cs-acciones { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .cs-readonly { background: #f8fafb; border: 1px solid #e9eeec; border-radius: 8px; padding: 10px 12px; white-space: pre-line; min-height: 42px; color: #0b2a38; font-size: 0.92rem; }

    /* Acordeones */
    .cs-acordeon { border: 1px solid #e4e9e7; border-radius: 12px; overflow: hidden; margin-bottom: 12px; background: #fff; }
    .cs-acordeon-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 16px; background: #f8fafb; cursor: pointer;
        border-bottom: 1px solid transparent; transition: background 0.15s;
    }
    .cs-acordeon-header:hover { background: #f1f5f4; }
    .cs-acordeon-header[aria-expanded="true"] { border-bottom-color: #e4e9e7; }
    .cs-acordeon-header h5 { font-size: 0.95rem; font-weight: 700; color: #0b2a38; margin: 0; }
    .cs-acordeon-body { padding: 16px; }
    .cs-acordeon-icono { transition: transform 0.2s; color: #0f8b84; }
    .cs-acordeon-header[aria-expanded="true"] .cs-acordeon-icono { transform: rotate(180deg); }

    /* Asistente clínico */
    .cs-asistente {
        background: #f8fafb; border: 1px dashed #c5d3ce; border-radius: 10px;
        padding: 12px 14px; margin-bottom: 10px; font-size: 0.88rem;
    }
    .cs-asistente-titulo { font-weight: 700; color: #0f8b84; margin-bottom: 8px; }
    .cs-asistente-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 6px; }
    .cs-asistente-grid .form-check { margin: 0; font-size: 0.82rem; }
    .cs-asistente-pregunta { font-weight: 600; color: #0b2a38; margin-bottom: 6px; font-size: 0.85rem; }
    .cs-asistente-btn {
        font-size: 0.8rem; padding: 0.25rem 0.6rem; margin-top: 8px;
    }

    /* Tarjetas de diagnóstico tipo acordeón */
    .cs-diagnostico-card { border: 1px solid #e4e9e7; border-radius: 10px; margin-bottom: 10px; overflow: hidden; }
    .cs-diagnostico-cabecera {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 14px; background: #fff; cursor: pointer;
    }
    .cs-diagnostico-cabecera:hover { background: #f8fafb; }
    .cs-diagnostico-cuerpo { padding: 12px 14px; background: #f8fafb; border-top: 1px solid #e9eeec; }
    .cs-diagnostico-principal { border-left: 4px solid #0f8b84; }
    .cs-diagnostico-secundario { border-left: 4px solid #adb5bd; }
    .cs-diagnostico-texto { font-weight: 600; color: #0b2a38; }
    .cs-diagnostico-acciones { display: flex; gap: 6px; align-items: center; }

    /* Badges de signos vitales */
    .cs-signo-badge {
        display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
        min-width: 70px; padding: 6px 8px; background: #fff;
        border: 1px solid #e4e9e7; border-radius: 8px; font-size: 0.78rem; text-align: center;
    }
    .cs-signo-badge .valor { font-size: 0.95rem; font-weight: 700; color: #0f8b84; }
    .cs-signo-badge .etiqueta { font-size: 0.65rem; color: #6b7c83; text-transform: uppercase; }

    /* Expediente */
    .cs-expediente-sugerencia { text-align: center; padding: 10px 0; }
    .cs-expediente-sugerencia i { font-size: 2rem; color: #adb5bd; }
    .cs-expediente-dato { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.88rem; }
    .cs-expediente-dato i { color: #0f8b84; width: 18px; text-align: center; }
</style>

<div class="cs-cabecera">
    <div>
        <h3 class="m-0"><i class="fas fa-notes-medical text-secondary"></i> Consulta médica</h3>
        <p class="text-muted mb-0">Cita <code><?= e((string) ($cita['codigo'] ?? '')) ?></code> · <?= e((string) ($cita['paciente'] ?? '')) ?></p>
    </div>
    <div class="cs-acciones">
        <a href="<?= e((route('dashboard_atencion') ?? '/portal/dashboard/atencion')) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Bandeja
        </a>
        <?php if ($urlExpediente !== '') { ?>
            <a href="<?= e($urlExpediente) ?>" class="btn btn-outline-primary">
                <i class="fas fa-folder-open mr-1"></i> Expediente
            </a>
        <?php } ?>
        <?php if ($urlHistorial !== '') { ?>
            <a href="<?= e($urlHistorial) ?>" class="btn btn-outline-info">
                <i class="fas fa-history mr-1"></i> Historial
            </a>
        <?php } ?>
    </div>
</div>

<div id="consulta-alerta" class="alert alert-danger cs-alerta"></div>

<?php if ($sinIniciar) { ?>
    <div class="cs-card text-center py-5">
        <i class="fas fa-user-md text-secondary" style="font-size:2.4rem;"></i>
        <h4 class="mt-3">Consulta no iniciada</h4>
        <p class="text-muted mx-auto" style="max-width:520px;">
            El paciente está habilitado para consulta. Al iniciar se creará el registro
            clínico de esta cita y quedarás en estado <strong>En curso</strong>.
        </p>
        <button type="button" class="btn btn-success btn-lg" id="btn-iniciar-consulta" data-id="<?= $idCita ?>">
            <i class="fas fa-notes-medical mr-2"></i> Iniciar consulta médica
        </button>
    </div>
<?php } else { ?>

<div class="cs-grid">
    <div>
        <!-- Información clínica -->
        <div class="cs-card">
            <div class="cs-cabecera">
                <div>
                    <h5 class="cs-titulo">Información clínica</h5>
                    <p class="cs-sub">
                        Inicio: <?= e($fechaConsulta((string) ($consulta['fecha_inicio'] ?? ''))) ?>
                        <?php if ($finalizada) { ?> · Fin: <?= e($fechaConsulta((string) ($consulta['fecha_fin'] ?? ''))) ?><?php } ?>
                    </p>
                </div>
                <span class="badge <?= $enCurso ? 'badge-warning' : 'badge-success' ?>">
                    <?= $enCurso ? 'En curso' : 'Finalizada' ?>
                </span>
            </div>

            <div id="consulta-alerta-form" class="alert alert-danger cs-alerta"></div>

            <form id="form-consulta" autocomplete="off">
                <input type="hidden" name="id_cita" value="<?= $idCita ?>">

                <!-- Anamnesis -->
                <div class="form-group">
                    <label for="consulta-anamnesis">
                        <i class="fas fa-comments text-secondary mr-1"></i> Anamnesis *
                    </label>
                    <?php if ($enCurso) { ?>
                        <div class="cs-asistente" id="asistente-anamnesis">
                            <div class="cs-asistente-titulo"><i class="fas fa-magic mr-1"></i> Asistente rápido de anamnesis</div>
                            <div class="cs-asistente-pregunta">Marque los hallazgos principales:</div>
                            <div class="cs-asistente-grid">
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-anamnesis" value="Refiere dolor generalizado." id="ana-1"><label class="form-check-label" for="ana-1">Dolor generalizado</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-anamnesis" value="Niega alergias medicamentosas conocidas." id="ana-2"><label class="form-check-label" for="ana-2">Sin alergias</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-anamnesis" value="Niega antecedentes quirúrgicos." id="ana-3"><label class="form-check-label" for="ana-3">Sin cirugías</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-anamnesis" value="Refiere malestar desde hace varios días con evolución fluctuante." id="ana-4"><label class="form-check-label" for="ana-4">Evolución fluctuante</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-anamnesis" value="Niega fiebre, sudoración nocturna o pérdida de peso." id="ana-5"><label class="form-check-label" for="ana-5">Síntomas B negados</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-anamnesis" value="Paciente orientado en tiempo, espacio y persona." id="ana-6"><label class="form-check-label" for="ana-6">Orientado</label></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary cs-asistente-btn js-asistente-limpiar" data-target="consulta-anamnesis">
                                <i class="fas fa-eraser mr-1"></i> Limpiar selección
                            </button>
                        </div>
                        <textarea class="form-control" id="consulta-anamnesis" name="anamnesis" rows="4" maxlength="8000" required><?= e((string) ($consulta['anamnesis'] ?? '')) ?></textarea>
                    <?php } else { ?>
                        <div class="cs-readonly"><?= e(trim((string) ($consulta['anamnesis'] ?? '')) !== '' ? (string) $consulta['anamnesis'] : 'Sin información') ?></div>
                    <?php } ?>
                </div>

                <!-- Examen físico -->
                <div class="form-group">
                    <label for="consulta-examen">
                        <i class="fas fa-stethoscope text-secondary mr-1"></i> Examen físico *
                    </label>
                    <?php if ($enCurso) { ?>
                        <div class="cs-asistente" id="asistente-examen">
                            <div class="cs-asistente-titulo"><i class="fas fa-magic mr-1"></i> Plantilla de sistemas</div>
                            <div class="cs-asistente-grid">
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Estado general bueno, hidratado, afebril." id="exa-1"><label class="form-check-label" for="exa-1">Estado general bueno</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Piel y faneras sin lesiones." id="exa-2"><label class="form-check-label" for="exa-2">Piel normal</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Cabeza y cuello sin alteraciones." id="exa-3"><label class="form-check-label" for="exa-3">Cabeza/cuello normal</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Tórax sin alteraciones respiratorias; murmullo vesicular conservado." id="exa-4"><label class="form-check-label" for="exa-4">Tórax normal</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Cardiovascular: ritmo regular, sin soplos." id="exa-5"><label class="form-check-label" for="exa-5">Cardiovascular normal</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Abdomen blando, depresible, no doloroso." id="exa-6"><label class="form-check-label" for="exa-6">Abdomen normal</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Extremidades sin edema ni limitación funcional." id="exa-7"><label class="form-check-label" for="exa-7">Extremidades normal</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-examen" value="Sistema nervioso: reflejos conservados, sensibilidad presente." id="exa-8"><label class="form-check-label" for="exa-8">Neurológico normal</label></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary cs-asistente-btn js-asistente-limpiar" data-target="consulta-examen">
                                <i class="fas fa-eraser mr-1"></i> Limpiar selección
                            </button>
                        </div>
                        <textarea class="form-control" id="consulta-examen" name="examen_fisico" rows="4" maxlength="8000" required><?= e((string) ($consulta['examen_fisico'] ?? '')) ?></textarea>
                    <?php } else { ?>
                        <div class="cs-readonly"><?= e(trim((string) ($consulta['examen_fisico'] ?? '')) !== '' ? (string) $consulta['examen_fisico'] : 'Sin información') ?></div>
                    <?php } ?>
                </div>

                <!-- Evolución -->
                <div class="form-group">
                    <label for="consulta-evolucion">
                        <i class="fas fa-heartbeat text-secondary mr-1"></i> Evolución
                    </label>
                    <?php if ($enCurso) { ?>
                        <textarea class="form-control" id="consulta-evolucion" name="evolucion" rows="3" maxlength="8000" placeholder="Describa la evolución del cuadro actual durante la atención..."><?= e((string) ($consulta['evolucion'] ?? '')) ?></textarea>
                    <?php } else { ?>
                        <div class="cs-readonly"><?= e(trim((string) ($consulta['evolucion'] ?? '')) !== '' ? (string) $consulta['evolucion'] : 'Sin información') ?></div>
                    <?php } ?>
                </div>

                <!-- Plan -->
                <div class="form-group">
                    <label for="consulta-plan">
                        <i class="fas fa-clipboard-list text-secondary mr-1"></i> Plan de tratamiento *
                    </label>
                    <?php if ($enCurso) { ?>
                        <div class="cs-asistente" id="asistente-plan">
                            <div class="cs-asistente-titulo"><i class="fas fa-magic mr-1"></i> Recuerde incluir</div>
                            <div class="cs-asistente-grid">
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-plan" value="Tratamiento farmacológico: " id="plan-1"><label class="form-check-label" for="plan-1">Medicamentos</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-plan" value="Recomendaciones de higiene y reposo." id="plan-2"><label class="form-check-label" for="plan-2">Higiene/reposo</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-plan" value="Solicitud de estudios de laboratorio/imagen." id="plan-3"><label class="form-check-label" for="plan-3">Estudios</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-plan" value="Derivación a especialidad." id="plan-4"><label class="form-check-label" for="plan-4">Derivación</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" data-target="consulta-plan" value="Control ambulatorio." id="plan-5"><label class="form-check-label" for="plan-5">Control ambulatorio</label></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary cs-asistente-btn js-asistente-limpiar" data-target="consulta-plan">
                                <i class="fas fa-eraser mr-1"></i> Limpiar selección
                            </button>
                        </div>
                        <textarea class="form-control" id="consulta-plan" name="plan_tratamiento" rows="3" maxlength="8000" required><?= e((string) ($consulta['plan_tratamiento'] ?? '')) ?></textarea>
                    <?php } else { ?>
                        <div class="cs-readonly"><?= e(trim((string) ($consulta['plan_tratamiento'] ?? '')) !== '' ? (string) $consulta['plan_tratamiento'] : 'Sin información') ?></div>
                    <?php } ?>
                </div>

                <div class="form-group mb-0">
                    <label for="consulta-observaciones">
                        <i class="fas fa-comment-medical text-secondary mr-1"></i> Observaciones
                    </label>
                    <?php if ($enCurso) { ?>
                        <textarea class="form-control" id="consulta-observaciones" name="observaciones" rows="2" maxlength="4000"><?= e((string) ($consulta['observaciones'] ?? '')) ?></textarea>
                    <?php } else { ?>
                        <div class="cs-readonly"><?= e(trim((string) ($consulta['observaciones'] ?? '')) !== '' ? (string) $consulta['observaciones'] : 'Sin información') ?></div>
                    <?php } ?>
                </div>
            </form>

            <?php if ($enCurso) { ?>
                <div class="cs-acciones mt-3">
                    <button type="button" class="btn btn-primary" id="btn-guardar-consulta">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-success" id="btn-finalizar-consulta" data-id="<?= $idCita ?>">
                        <i class="fas fa-check-double mr-1"></i> Finalizar consulta
                    </button>
                </div>
            <?php } ?>
        </div>

        <!-- Diagnósticos -->
        <div class="cs-card mt-4">
            <div class="cs-cabecera">
                <div>
                    <h5 class="cs-titulo"><i class="fas fa-stethoscope text-secondary mr-1"></i> Diagnósticos</h5>
                    <p class="cs-sub">Se requiere al menos un diagnóstico PRINCIPAL para finalizar. Expanda cada uno para ver observaciones y solicitar estudios.</p>
                </div>
            </div>

            <div id="diagnosticos-lista" class="accordion">
                <?php if ($diagnosticos === []) { ?>
                    <p class="cs-vacio mb-0" id="diagnosticos-vacio">Sin diagnósticos registrados.</p>
                <?php } ?>
                <?php foreach ($diagnosticos as $i => $diagnostico) {
                    $diagId = (int) $diagnostico['id'];
                    $diagTipo = (string) $diagnostico['tipo'];
                    $isPrincipal = $diagTipo === 'PRINCIPAL';
                    $collapseId = 'diag-body-' . $diagId;
                    $headerId = 'diag-head-' . $diagId;
                ?>
                    <div class="cs-diagnostico-card <?= $isPrincipal ? 'cs-diagnostico-principal' : 'cs-diagnostico-secundario' ?> cs-item" data-id="<?= $diagId ?>" data-tipo="<?= e($diagTipo) ?>">
                        <div class="cs-diagnostico-cabecera" id="<?= e($headerId) ?>" data-toggle="collapse" data-target="#<?= e($collapseId) ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="<?= e($collapseId) ?>">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <span class="badge <?= $isPrincipal ? 'badge-success' : 'badge-secondary' ?>"><?= e($diagTipo) ?></span>
                                <span class="cs-diagnostico-texto"><?= e((string) $diagnostico['descripcion']) ?></span>
                            </div>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <i class="fas fa-chevron-down cs-acordeon-icono"></i>
                                <?php if ($enCurso) { ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger js-eliminar-diagnostico" data-id="<?= $diagId ?>" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                        <div id="<?= e($collapseId) ?>" class="collapse <?= $i === 0 ? 'show' : '' ?>" aria-labelledby="<?= e($headerId) ?>" data-parent="#diagnosticos-lista">
                            <div class="cs-diagnostico-cuerpo">
                                <?php if (!empty($diagnostico['observaciones'])) { ?>
                                    <p class="mb-2"><strong>Observaciones:</strong> <?= e((string) $diagnostico['observaciones']) ?></p>
                                <?php } else { ?>
                                    <p class="cs-vacio mb-2">Sin observaciones adicionales.</p>
                                <?php } ?>
                                <?php if ($enCurso) { ?>
                                    <button type="button" class="btn btn-sm btn-outline-info js-solicitar-estudio-para" data-diagnostico="<?= $diagId ?>" data-descripcion="<?= e((string) $diagnostico['descripcion']) ?>">
                                        <i class="fas fa-vial mr-1"></i> Solicitar estudio para este diagnóstico
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if ($enCurso) { ?>
                <div class="cs-seccion"><i class="fas fa-plus-circle mr-1"></i> Nuevo diagnóstico</div>
                <div id="diagnostico-alerta" class="alert alert-danger cs-alerta"></div>
                <form id="form-diagnostico" autocomplete="off">
                    <input type="hidden" name="id_cita" value="<?= $idCita ?>">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="diagnostico-descripcion">Descripción *</label>
                            <input type="text" class="form-control" id="diagnostico-descripcion" name="descripcion" maxlength="1000" placeholder="Ej. Gastritis aguda, Diabetes mellitus tipo 2..." required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="diagnostico-tipo">Tipo *</label>
                            <select class="form-control" id="diagnostico-tipo" name="tipo" required>
                                <option value="PRINCIPAL">PRINCIPAL</option>
                                <option value="SECUNDARIO">SECUNDARIO</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="diagnostico-observaciones">Observaciones</label>
                        <input type="text" class="form-control" id="diagnostico-observaciones" name="observaciones" maxlength="2000" placeholder="Notas o códigos CIE-10, evolución, etc.">
                    </div>
                    <button type="button" class="btn btn-primary" id="btn-agregar-diagnostico">
                        <i class="fas fa-plus mr-1"></i> Agregar diagnóstico
                    </button>
                </form>
            <?php } ?>
        </div>

        <!-- Estudios -->
        <div class="cs-card mt-4">
            <div class="cs-cabecera">
                <div>
                    <h5 class="cs-titulo"><i class="fas fa-vial text-secondary mr-1"></i> Estudios solicitados</h5>
                    <p class="cs-sub">Complementarios a la consulta en curso. Puede vincularlos a un diagnóstico.</p>
                </div>
            </div>

            <div id="estudios-lista">
                <?php if ($estudios === []) { ?>
                    <p class="cs-vacio mb-0">Sin estudios solicitados.</p>
                <?php } ?>
                <?php foreach ($estudios as $estudio) { ?>
                    <div class="cs-item cs-secundario" data-id="<?= (int) $estudio['id'] ?>">
                        <div class="cs-item-cabecera">
                            <div>
                                <div class="cs-item-texto"><?= e((string) $estudio['tipo_estudio']) ?></div>
                                <div class="cs-item-meta"><?= e((string) ($estudio['indicacion'] ?? '')) ?></div>
                                <?php if (!empty($estudio['diagnostico'])) { ?>
                                    <div class="mt-1">
                                        <span class="badge badge-outline-info" style="border:1px solid #0f8b84; color:#0f8b84;">
                                            <i class="fas fa-link mr-1"></i> Vinculado a: <?= e((string) $estudio['diagnostico']) ?>
                                        </span>
                                    </div>
                                <?php } ?>
                                <div class="cs-item-meta mt-1">Solicitado: <?= e(substr((string) ($estudio['fecha_solicitud'] ?? ''), 0, 16)) ?></div>
                            </div>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <span class="badge badge-info"><?= e((string) $estudio['estado']) ?></span>
                                <?php if ($enCurso && (string) $estudio['estado'] === 'SOLICITADO') { ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger js-eliminar-estudio" data-id="<?= (int) $estudio['id'] ?>" title="Retirar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <?php if ($enCurso) { ?>
                <div class="cs-seccion"><i class="fas fa-plus-circle mr-1"></i> Solicitar estudio</div>
                <div id="estudio-alerta" class="alert alert-danger cs-alerta"></div>
                <form id="form-estudio" autocomplete="off">
                    <input type="hidden" name="id_cita" value="<?= $idCita ?>">
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label for="estudio-tipo">Tipo de estudio *</label>
                            <select class="form-control" id="estudio-tipo" name="id_tipo_estudio" required>
                                <option value="">Selecciona…</option>
                                <?php foreach ($tiposEstudio as $tipo) { ?>
                                    <option value="<?= (int) $tipo['id'] ?>"><?= e((string) $tipo['nombre']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-7">
                            <label for="estudio-diagnostico"><i class="fas fa-link text-secondary mr-1"></i> Vincular a diagnóstico</label>
                            <select class="form-control" id="estudio-diagnostico" name="id_diagnostico">
                                <option value="">Sin vincular</option>
                                <?php foreach ($diagnosticos as $diagnostico) { ?>
                                    <option value="<?= (int) $diagnostico['id'] ?>"><?= e((string) $diagnostico['descripcion']) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="estudio-indicacion">Indicación / justificación *</label>
                        <textarea class="form-control" id="estudio-indicacion" name="indicacion" rows="2" maxlength="2000" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="estudio-observaciones">Observaciones</label>
                        <input type="text" class="form-control" id="estudio-observaciones" name="observaciones" maxlength="2000">
                    </div>
                    <button type="button" class="btn btn-primary" id="btn-agregar-estudio">
                        <i class="fas fa-plus mr-1"></i> Solicitar estudio
                    </button>
                </form>
            <?php } ?>
        </div>
    </div>

    <!-- Columna lateral -->
    <div>
        <!-- Datos de la cita -->
        <div class="cs-acordeon" id="acordeon-cita">
            <div class="cs-acordeon-header" data-toggle="collapse" data-target="#cuerpo-cita" aria-expanded="true" aria-controls="cuerpo-cita">
                <h5><i class="fas fa-calendar-check text-secondary mr-1"></i> Datos de la cita</h5>
                <i class="fas fa-chevron-down cs-acordeon-icono"></i>
            </div>
            <div id="cuerpo-cita" class="collapse show" aria-labelledby="acordeon-cita" data-parent="#acordeon-cita">
                <div class="cs-acordeon-body">
                    <dl class="cs-datos">
                        <div><dt>Paciente</dt><dd><?= e((string) ($cita['paciente'] ?? '')) ?></dd></div>
                        <div><dt>CI</dt><dd><?= e((string) ($cita['paciente_ci'] ?? '')) ?></dd></div>
                        <div><dt>Médico</dt><dd><?= e((string) ($cita['medico'] ?? '')) ?></dd></div>
                        <div><dt>Especialidad</dt><dd><?= e((string) ($cita['especialidad'] ?? '')) ?></dd></div>
                        <div><dt>Fecha</dt><dd><?= e(substr((string) ($cita['fecha_hora_inicio'] ?? ''), 0, 16)) ?></dd></div>
                        <div><dt>Estado</dt><dd><?= e((string) ($cita['estado'] ?? '')) ?></dd></div>
                        <div><dt>Recepción</dt><dd><?= e((string) ($recepcion['estado'] ?? '—')) ?></dd></div>
                        <div><dt>Llegada</dt><dd><?= e(substr((string) ($recepcion['fecha_hora_llegada'] ?? '—'), 0, 16)) ?></dd></div>
                    </dl>
                    <div class="cs-seccion">Motivo de consulta</div>
                    <div class="cs-readonly"><?= e(trim((string) ($cita['motivo_consulta'] ?? '')) !== '' ? (string) $cita['motivo_consulta'] : 'Sin información') ?></div>
                </div>
            </div>
        </div>

        <!-- Evaluación previa -->
        <div class="cs-acordeon" id="acordeon-evaluacion">
            <div class="cs-acordeon-header" data-toggle="collapse" data-target="#cuerpo-evaluacion" aria-expanded="true" aria-controls="cuerpo-evaluacion">
                <h5><i class="fas fa-clipboard-check text-secondary mr-1"></i> Evaluación previa</h5>
                <div class="d-flex align-items-center" style="gap:8px;">
                    <?php if ($evaluacion !== null) { ?>
                        <span class="badge <?= (string) $evaluacion['estado'] === 'COMPLETADA' ? 'badge-success' : ((string) $evaluacion['estado'] === 'ANULADA' ? 'badge-dark' : 'badge-info') ?>">
                            <?= e((string) $evaluacion['estado']) ?>
                        </span>
                    <?php } else { ?>
                        <span class="badge badge-light border">Sin registro</span>
                    <?php } ?>
                    <i class="fas fa-chevron-down cs-acordeon-icono"></i>
                </div>
            </div>
            <div id="cuerpo-evaluacion" class="collapse show" aria-labelledby="acordeon-evaluacion" data-parent="#acordeon-evaluacion">
                <div class="cs-acordeon-body">
                    <?php if ($evaluacion === null) { ?>
                        <p class="cs-vacio mb-0">El paciente fue habilitado sin evaluación previa.</p>
                    <?php } else { ?>
                        <div class="d-flex flex-wrap" style="gap:8px; margin-bottom:12px;">
                            <div class="cs-signo-badge">
                                <span class="valor"><?= e(trim(((string) ($evaluacion['presion_sistolica'] ?? '')) . '/' . ((string) ($evaluacion['presion_diastolica'] ?? '')), '/') !== '' ? trim(((string) ($evaluacion['presion_sistolica'] ?? '')) . '/' . ((string) ($evaluacion['presion_diastolica'] ?? '')), '/') : '—') ?></span>
                                <span class="etiqueta">Presión</span>
                            </div>
                            <div class="cs-signo-badge">
                                <span class="valor"><?= e((string) ($evaluacion['temperatura'] ?? '—')) ?></span>
                                <span class="etiqueta">Temp.</span>
                            </div>
                            <div class="cs-signo-badge">
                                <span class="valor"><?= e(trim(((string) ($evaluacion['frecuencia_cardiaca'] ?? '')) . '/' . ((string) ($evaluacion['frecuencia_respiratoria'] ?? '')), ' / ') !== '' ? trim(((string) ($evaluacion['frecuencia_cardiaca'] ?? '')) . '/' . ((string) ($evaluacion['frecuencia_respiratoria'] ?? '')), ' / ') : '—') ?></span>
                                <span class="etiqueta">FC / FR</span>
                            </div>
                            <div class="cs-signo-badge">
                                <span class="valor"><?= e((string) ($evaluacion['saturacion_oxigeno'] ?? '—')) ?></span>
                                <span class="etiqueta">SpO₂</span>
                            </div>
                            <div class="cs-signo-badge">
                                <span class="valor"><?= e(trim(((string) ($evaluacion['peso_kg'] ?? '')) . ' kg / ' . ((string) ($evaluacion['talla_cm'] ?? '')) . ' cm', ' kg /  cm') !== '' ? trim(((string) ($evaluacion['peso_kg'] ?? '')) . ' kg / ' . ((string) ($evaluacion['talla_cm'] ?? '')) . ' cm', ' kg /  cm') : '—') ?></span>
                                <span class="etiqueta">Peso / Talla</span>
                            </div>
                        </div>
                        <div class="cs-seccion">Anamnesis preliminar</div>
                        <div class="cs-readonly"><?= e(trim((string) ($evaluacion['anamnesis_preliminar'] ?? '')) !== '' ? (string) $evaluacion['anamnesis_preliminar'] : 'Sin información') ?></div>
                        <div class="text-right mt-2">
                            <span class="cs-item-meta">Registro: <?= e(substr((string) ($evaluacion['fecha_hora'] ?? '—'), 0, 16)) ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Expediente clínico -->
        <div class="cs-card mt-4">
            <div class="cs-cabecera">
                <h5 class="cs-titulo"><i class="fas fa-folder-open text-secondary mr-1"></i> Expediente clínico</h5>
            </div>
            <?php if ($expediente === null) { ?>
                <div class="cs-expediente-sugerencia">
                    <i class="fas fa-folder-plus mb-2"></i>
                    <p class="cs-vacio mb-2">El paciente aún no tiene expediente clínico creado.</p>
                    <?php if ($urlExpediente !== '') { ?>
                        <a href="<?= e($urlExpediente) ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-external-link-alt mr-1"></i> Crear expediente
                        </a>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="cs-expediente-dato">
                    <i class="fas fa-calendar-alt"></i>
                    <span><strong>Apertura:</strong> <?= e(substr((string) ($expediente['fecha_apertura'] ?? '—'), 0, 10)) ?></span>
                </div>
                <div class="cs-expediente-dato">
                    <i class="fas fa-tint"></i>
                    <span><strong>Grupo sanguíneo:</strong> <?= e(trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) !== '' ? trim(((string) ($expediente['grupo_sanguineo'] ?? '')) . ((string) ($expediente['factor_rh'] ?? ''))) : '—') ?></span>
                </div>
                <?php if (!empty($expediente['observaciones_generales'])) { ?>
                    <div class="cs-expediente-dato" style="align-items:flex-start;">
                        <i class="fas fa-notes-medical" style="margin-top:3px;"></i>
                        <span><?= e((string) $expediente['observaciones_generales']) ?></span>
                    </div>
                <?php } ?>
                <div class="mt-3 d-flex flex-wrap" style="gap:8px;">
                    <?php if ($urlExpediente !== '') { ?>
                        <a href="<?= e($urlExpediente) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-folder-open mr-1"></i> Ver expediente completo
                        </a>
                    <?php } ?>
                    <?php if ($urlExpediente !== '') { ?>
                        <a href="<?= e($urlExpediente) ?>" class="btn btn-sm btn-outline-success" target="_blank">
                            <i class="fas fa-plus mr-1"></i> Añadir datos
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php } ?>

<script>
    $(function () {
        var token = '<?= csrf_token() ?>';
        var idCita = <?= (int) $idCita ?>;
        var urlAbrir = '<?= route('portal_atencion_consulta_abrir') ?>';
        var urlGuardar = '<?= route('portal_atencion_consulta_guardar') ?>';
        var urlDiagnostico = '<?= route('portal_atencion_diagnostico') ?>';
        var urlEliminarDiagnostico = '<?= route('portal_atencion_diagnostico_eliminar') ?>';
        var urlEstudio = '<?= route('portal_atencion_estudio') ?>';
        var urlEliminarEstudio = '<?= route('portal_atencion_estudio_eliminar') ?>';
        var urlFinalizar = '<?= route('portal_atencion_consulta_finalizar') ?>';
        var urlConsulta = '<?= route('portal_atencion_consulta', ['id' => '{id}']) ?>';
        var urlBandeja = '<?= route('dashboard_atencion') ?>';

        function mostrarError($el, mensaje) {
            $el.text(mensaje || 'Ocurrió un error.').show();
        }

        function ocultarError($el) {
            $el.hide().text('');
        }

        function escapeHtml(texto) {
            if (texto === null || texto === undefined) return '';
            return String(texto).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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
                    else window.espNotificar('success', respuesta.message || 'Acción completada.');
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

        // Iniciar consulta
        $('#btn-iniciar-consulta').on('click', function () {
            var $btn = $(this);
            post(urlAbrir, { _token: token, id_cita: idCita }, $('#consulta-alerta'), $btn, function (respuesta) {
                window.location.href = respuesta.url || urlConsulta.replace('{id}', idCita);
            });
        });

        // Guardar información clínica
        $('#btn-guardar-consulta').on('click', function () {
            post(urlGuardar, $('#form-consulta').serialize(), $('#consulta-alerta-form'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Información guardada.');
            });
        });

        // Diagnóstico
        $('#btn-agregar-diagnostico').on('click', function () {
            var datos = $('#form-diagnostico').serialize();
            post(urlDiagnostico, datos, $('#diagnostico-alerta'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Diagnóstico registrado.');
                setTimeout(function () { window.location.reload(); }, 900);
            });
        });

        $(document).on('click', '.js-eliminar-diagnostico', function (e) {
            e.stopPropagation();
            var $fila = $(this).closest('.cs-item');
            var id = parseInt($(this).data('id'), 10);

            window.espConfirmar({
                titulo: 'Eliminar diagnóstico',
                mensaje: 'Se eliminará este diagnóstico de la consulta en curso.',
                btn: 'Sí, eliminar',
                destructivo: true
            }, function () {
                post(urlEliminarDiagnostico, { _token: token, id: id }, $('#diagnostico-alerta'), null, function (respuesta) {
                    window.espNotificar('success', respuesta.message || 'Diagnóstico eliminado.');
                    $fila.remove();
                    if (!$('#diagnosticos-lista .cs-item').length) {
                        $('#diagnosticos-lista').html('<p class="cs-vacio mb-0">Sin diagnósticos registrados.</p>');
                    }
                });
            });
        });

        // Estudios
        $('#btn-agregar-estudio').on('click', function () {
            var datos = $('#form-estudio').serialize();
            post(urlEstudio, datos, $('#estudio-alerta'), $(this), function (respuesta) {
                window.espNotificar('success', respuesta.message || 'Estudio solicitado.');
                setTimeout(function () { window.location.reload(); }, 900);
            });
        });

        $(document).on('click', '.js-eliminar-estudio', function () {
            var $fila = $(this).closest('.cs-item');
            var id = parseInt($(this).data('id'), 10);

            window.espConfirmar({
                titulo: 'Retirar estudio',
                mensaje: 'Se retirará este estudio solicitado de la consulta en curso.',
                btn: 'Sí, retirar',
                destructivo: true
            }, function () {
                post(urlEliminarEstudio, { _token: token, id: id }, $('#estudio-alerta'), null, function (respuesta) {
                    window.espNotificar('success', respuesta.message || 'Estudio retirado.');
                    $fila.remove();
                    if (!$('#estudios-lista .cs-item').length) {
                        $('#estudios-lista').html('<p class="cs-vacio mb-0">Sin estudios solicitados.</p>');
                    }
                });
            });
        });

        // Asistentes clínicos: rellenar textareas desde checks sin perder edición manual
        function lineasTextarea(texto) {
            return String(texto || '').split('\n').map(function (l) { return l.trim(); }).filter(function (l) { return l !== ''; });
        }

        function juntarLineas(lineas) {
            return lineas.join('\n');
        }

        function toggleLineaTextarea($ta, linea, agregar) {
            var lineas = lineasTextarea($ta.val());
            var existe = lineas.indexOf(linea) !== -1;
            if (agregar && !existe) {
                lineas.push(linea);
            } else if (!agregar && existe) {
                lineas = lineas.filter(function (l) { return l !== linea; });
            }
            $ta.val(juntarLineas(lineas));
        }

        $(document).on('change', '.form-check-input[data-target]', function () {
            var $cb = $(this);
            var targetId = $cb.data('target');
            toggleLineaTextarea($('#' + targetId), $cb.val(), $cb.is(':checked'));
        });

        $(document).on('click', '.js-asistente-limpiar', function () {
            var targetId = $(this).data('target');
            var $ta = $('#' + targetId);
            var $checks = $('.form-check-input[data-target="' + targetId + '"]');
            $checks.each(function () {
                toggleLineaTextarea($ta, $(this).val(), false);
            });
            $checks.prop('checked', false);
        });

        // Vincular estudio a diagnóstico desde el acordeón
        $(document).on('click', '.js-solicitar-estudio-para', function (e) {
            e.stopPropagation();
            var idDiagnostico = $(this).data('diagnostico');
            var descripcion = $(this).data('descripcion');
            var $select = $('#estudio-diagnostico');
            $select.val(idDiagnostico);
            $('html, body').animate({
                scrollTop: $('#form-estudio').offset().top - 80
            }, 400);
            window.espNotificar('info', 'Estudio vinculado a: ' + descripcion);
            setTimeout(function () { $('#estudio-indicacion').focus(); }, 450);
        });

        // Finalizar consulta
        $('#btn-finalizar-consulta').on('click', function () {
            window.espConfirmar({
                titulo: 'Finalizar consulta',
                mensaje: 'La consulta quedará FINALIZADA, la cita ATENDIDA y el paciente fuera del flujo. Esta acción no se puede deshacer.',
                ayuda: 'Para finalizar debe estar completada la anamnesis, el examen físico, el plan de tratamiento y al menos un diagnóstico PRINCIPAL.',
                btn: 'Sí, finalizar',
                destructivo: true
            }, function () {
                post(urlFinalizar, { _token: token, id_cita: idCita }, $('#consulta-alerta'), null, function (respuesta) {
                    window.espNotificar('success', respuesta.message || 'Consulta finalizada.');
                    setTimeout(function () {
                        window.location.href = respuesta.url || urlBandeja;
                    }, 1200);
                });
            });
        });
    });
</script>
