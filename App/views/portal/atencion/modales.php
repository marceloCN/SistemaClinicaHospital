<?php
/**
 * Modales de la bandeja de atención: detalle de la cita, evaluación
 * previa (iniciar/continuar/guardar) y habilitación sin evaluación.
 */
$vista = $vista ?? 'recepcion';
?>

<style>
    /* Modal de detalle de atención */
    .at-detalle-cabecera {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #0f8b84;
    }
    .at-detalle-paciente {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f8b84;
    }
    .at-detalle-paciente small {
        display: block;
        font-size: 0.8rem;
        font-weight: 400;
        color: #6c757d;
    }
    .at-detalle-codigo {
        font-size: 0.95rem;
        font-weight: 600;
        background: #e9ecef;
        padding: 0.25rem 0.6rem;
        border-radius: 0.25rem;
    }
    .at-detalle-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .at-detalle-item {
        background: #f8f9fa;
        border-radius: 0.35rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.88rem;
    }
    .at-detalle-item dt {
        font-size: 0.72rem;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 0.15rem;
    }
    .at-detalle-item dd {
        margin: 0;
        font-weight: 600;
        color: #212529;
    }
    .at-seccion-titulo {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f8b84;
        margin: 1.25rem 0 0.75rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .gap-2 { gap: 0.5rem; }
    .at-signos-flex {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .at-signo-badge {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 85px;
        padding: 0.5rem;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.35rem;
        font-size: 0.82rem;
    }
    .at-signo-badge .valor {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f8b84;
    }
    .at-signo-badge .etiqueta {
        font-size: 0.7rem;
        color: #6c757d;
        text-transform: uppercase;
    }
    .at-lista-estudio {
        padding: 0.5rem 0.75rem;
        border-left: 3px solid #17a2b8;
        background: #f8f9fa;
        border-radius: 0 0.35rem 0.35rem 0;
        margin-bottom: 0.5rem;
    }
    .at-estado-badge {
        font-size: 0.75rem;
        padding: 0.2em 0.5em;
        border-radius: 0.25rem;
    }

    /* Evaluación previa interactiva */
    .at-perfil-btn .btn { font-size: 0.9rem; }
    .at-perfil-btn .btn input { position: absolute; clip: rect(0,0,0,0); }

    .at-signo-grupo { position: relative; }
    .at-signo-grupo .at-rango-ayuda {
        display: block;
        font-size: 0.72rem;
        color: #6c757d;
        margin-top: 0.2rem;
        min-height: 1rem;
    }
    .at-signo-grupo .form-control { transition: border-color 0.2s, background-color 0.2s; }
    .at-signo-grupo .form-control.at-signo-normal { border-color: #28a745; }
    .at-signo-grupo .form-control.at-signo-alerta { border-color: #ffc107; background-color: #fff8e1; }
    .at-signo-grupo .form-control.at-signo-peligro { border-color: #dc3545; background-color: #f8d7da; }

    .at-cuestionario-titulo {
        font-size: 0.85rem;
        font-weight: 600;
        color: #0f8b84;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.35rem;
    }
    .at-check-sintomas { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; }
    .at-check-sintomas .form-check { min-width: 110px; margin: 0; }
</style>

<!-- Modal: detalle de la cita dentro del flujo -->
<div class="modal fade" id="modal-detalle-atencion" tabindex="-1" role="dialog" aria-labelledby="modal-detalle-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-detalle-title"><i class="fas fa-file-medical-alt mr-2"></i> Detalle de la atención</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="detalle-alerta" class="alert alert-danger at-alerta-error"></div>
                <div id="detalle-contenido" class="text-center text-muted py-4">Cargando…</div>
            </div>
            <div class="modal-footer" id="detalle-footer" style="display:none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="#" id="detalle-link-expediente" class="btn btn-outline-primary" style="display:none;">
                    <i class="fas fa-folder-open mr-1"></i> Expediente
                </a>
                <button type="button" class="btn btn-primary" id="detalle-btn-evaluacion" style="display:none;">
                    <i class="fas fa-clipboard-list mr-1"></i> Evaluar
                </button>
                <button type="button" class="btn btn-warning" id="detalle-btn-sin-evaluacion" style="display:none;">
                    <i class="fas fa-forward mr-1"></i> Sin evaluación
                </button>
                <button type="button" class="btn btn-success" id="detalle-btn-consulta" style="display:none;">
                    <i class="fas fa-notes-medical mr-1"></i> Iniciar consulta
                </button>
                <a href="#" id="detalle-link-consulta" class="btn btn-warning" style="display:none;">
                    <i class="fas fa-file-medical mr-1"></i> Abrir consulta
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: evaluación previa -->
<div class="modal fade" id="modal-evaluacion" tabindex="-1" role="dialog" aria-labelledby="modal-evaluacion-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-evaluacion-title"><i class="fas fa-clipboard-list mr-2"></i> Evaluación previa</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="evaluacion-alerta" class="alert alert-danger at-alerta-error"></div>
                <div id="evaluacion-info" class="mb-3"></div>

                <form id="form-evaluacion" autocomplete="off">
                    <input type="hidden" name="id_cita" id="evaluacion-id-cita" value="">

                    <!-- Perfil del paciente: solo afecta rangos de referencia visuales -->
                    <div class="form-group">
                        <label class="font-weight-bold">Perfil del paciente <small class="text-muted font-weight-normal">(define los rangos de referencia)</small></label>
                        <div class="btn-group btn-group-toggle w-100 at-perfil-btn" data-toggle="buttons">
                            <label class="btn btn-outline-primary active">
                                <input type="radio" name="evaluacion_perfil" value="adulto-hombre" checked>
                                <i class="fas fa-male mr-1"></i> Adulto hombre
                            </label>
                            <label class="btn btn-outline-danger">
                                <input type="radio" name="evaluacion_perfil" value="adulto-mujer">
                                <i class="fas fa-female mr-1"></i> Adulto mujer
                            </label>
                            <label class="btn btn-outline-info">
                                <input type="radio" name="evaluacion_perfil" value="nino">
                                <i class="fas fa-child mr-1"></i> Niño
                            </label>
                            <label class="btn btn-outline-warning">
                                <input type="radio" name="evaluacion_perfil" value="nina">
                                <i class="fas fa-child mr-1"></i> Niña
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="evaluacion-motivo">Motivo de la derivación</label>
                        <input type="text" class="form-control" id="evaluacion-motivo" name="motivo_referido" maxlength="500" placeholder="Ej. dolor torácico, fiebre, control prenatal">
                    </div>

                    <!-- Signos vitales interactivos -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light font-weight-bold">
                            <i class="fas fa-heartbeat mr-2 text-danger"></i>Signos vitales
                        </div>
                        <div class="card-body pb-2">
                            <div class="row">
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-sistolica">Presión sistólica</label>
                                    <input type="number" class="form-control" id="evaluacion-sistolica" name="presion_sistolica" min="30" max="300" inputmode="numeric">
                                    <small class="at-rango-ayuda" data-signo="sistolica"></small>
                                </div>
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-diastolica">Presión diastólica</label>
                                    <input type="number" class="form-control" id="evaluacion-diastolica" name="presion_diastolica" min="10" max="250" inputmode="numeric">
                                    <small class="at-rango-ayuda" data-signo="diastolica"></small>
                                </div>
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-fc">Frec. cardíaca</label>
                                    <input type="number" class="form-control" id="evaluacion-fc" name="frecuencia_cardiaca" min="20" max="250" inputmode="numeric">
                                    <small class="at-rango-ayuda" data-signo="fc"></small>
                                </div>
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-fr">Frec. respiratoria</label>
                                    <input type="number" class="form-control" id="evaluacion-fr" name="frecuencia_respiratoria" min="5" max="100" inputmode="numeric">
                                    <small class="at-rango-ayuda" data-signo="fr"></small>
                                </div>
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-temperatura">Temperatura (°C)</label>
                                    <input type="number" class="form-control" id="evaluacion-temperatura" name="temperatura" min="30" max="45" step="0.1" inputmode="decimal">
                                    <small class="at-rango-ayuda" data-signo="temperatura"></small>
                                </div>
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-spo2">Sat. O₂ (%)</label>
                                    <input type="number" class="form-control" id="evaluacion-spo2" name="saturacion_oxigeno" min="0" max="100" step="0.01" inputmode="decimal">
                                    <small class="at-rango-ayuda" data-signo="spo2"></small>
                                </div>
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-peso">Peso (kg)</label>
                                    <input type="number" class="form-control" id="evaluacion-peso" name="peso_kg" min="0.1" max="500" step="0.01" inputmode="decimal">
                                    <small class="at-rango-ayuda">Usado para calcular IMC</small>
                                </div>
                                <div class="col-md-3 col-6 form-group at-signo-grupo">
                                    <label for="evaluacion-talla">Talla (cm)</label>
                                    <input type="number" class="form-control" id="evaluacion-talla" name="talla_cm" min="30" max="250" step="0.01" inputmode="decimal">
                                    <small class="at-rango-ayuda">Usado para calcular IMC</small>
                                </div>
                            </div>
                            <div id="evaluacion-imc" class="alert alert-light border mt-2 mb-0 py-2 text-center text-muted">
                                <i class="fas fa-calculator mr-1"></i> Complete peso y talla para calcular el IMC.
                            </div>
                        </div>
                    </div>

                    <!-- Cuestionario de anamnesis preliminar -->
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light font-weight-bold">
                            <i class="fas fa-question-circle mr-2 text-info"></i>Anamnesis preliminar
                        </div>
                        <div class="card-body">
                            <div class="at-cuestionario-titulo">
                                Responde las preguntas más frecuentes; luego genera el resumen que se enviará al médico.
                            </div>

                            <div class="form-group">
                                <label for="anamnesis-motivo">1. ¿Cuál es el motivo principal de consulta?</label>
                                <input type="text" class="form-control form-control-sm anamnesis-campo" id="anamnesis-motivo" placeholder="Ej. dolor de cabeza intenso">
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="anamnesis-inicio">2. ¿Cuándo inició y cómo ha evolucionado?</label>
                                    <input type="text" class="form-control form-control-sm anamnesis-campo" id="anamnesis-inicio" placeholder="Inicio, duración, evolución">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="anamnesis-caracteristicas">3. Características del síntoma</label>
                                    <input type="text" class="form-control form-control-sm anamnesis-campo" id="anamnesis-caracteristicas" placeholder="Localización, intensidad, irradiación">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>4. Síntomas asociados</label>
                                <div class="at-check-sintomas">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Fiebre" id="sint-fiebre">
                                        <label class="form-check-label" for="sint-fiebre">Fiebre</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Dolor" id="sint-dolor">
                                        <label class="form-check-label" for="sint-dolor">Dolor</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Náuseas/vómitos" id="sint-nauseas">
                                        <label class="form-check-label" for="sint-nauseas">Náuseas/vómitos</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Tos" id="sint-tos">
                                        <label class="form-check-label" for="sint-tos">Tos</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Disnea" id="sint-disnea">
                                        <label class="form-check-label" for="sint-disnea">Disnea</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Palidez" id="sint-palidez">
                                        <label class="form-check-label" for="sint-palidez">Palidez</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Alteración del sueño" id="sint-sueno">
                                        <label class="form-check-label" for="sint-sueno">Alteración del sueño</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Cambios de conducta" id="sint-conducta">
                                        <label class="form-check-label" for="sint-conducta">Cambios de conducta</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Irritabilidad" id="sint-irritabilidad">
                                        <label class="form-check-label" for="sint-irritabilidad">Irritabilidad</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input anamnesis-campo" type="checkbox" value="Rechazo al alimento" id="sint-alimento">
                                        <label class="form-check-label" for="sint-alimento">Rechazo al alimento</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="anamnesis-medicamentos">5. Medicamentos que toma actualmente</label>
                                    <input type="text" class="form-control form-control-sm anamnesis-campo" id="anamnesis-medicamentos" placeholder="Si no aplica, dejar en blanco">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="anamnesis-alergias">6. Alergias conocidas</label>
                                    <input type="text" class="form-control form-control-sm anamnesis-campo" id="anamnesis-alergias" placeholder="Si no aplica, dejar en blanco">
                                </div>
                            </div>

                            <div class="form-group campo-pediatrico" style="display:none;">
                                <label for="anamnesis-alimentacion">7. Última ingesta / hidratación (pediatría)</label>
                                <input type="text" class="form-control form-control-sm anamnesis-campo" id="anamnesis-alimentacion" placeholder="Ej. lactancia materna hace 2 horas">
                            </div>

                            <div class="form-group">
                                <label for="anamnesis-adicionales">7. Otras observaciones relevantes</label>
                                <textarea class="form-control form-control-sm anamnesis-campo" id="anamnesis-adicionales" rows="2" placeholder="Antecedentes relevantes, factores desencadenantes, etc."></textarea>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-construir-anamnesis">
                                <i class="fas fa-magic mr-1"></i> Generar resumen de anamnesis
                            </button>
                        </div>
                    </div>

                    <!-- Campo real que viaja al backend -->
                    <div class="form-group">
                        <label for="evaluacion-anamnesis" class="font-weight-bold">
                            Resumen de anamnesis preliminar
                            <small class="text-muted font-weight-normal">(se enviará al médico)</small>
                        </label>
                        <textarea class="form-control" id="evaluacion-anamnesis" name="anamnesis_preliminar" rows="4" maxlength="4000"></textarea>
                        <small class="form-text text-muted">
                            Puedes editar este texto directamente o usar el cuestionario de arriba para generarlo automáticamente.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="evaluacion-observaciones">Observaciones adicionales de la evaluación</label>
                        <textarea class="form-control" id="evaluacion-observaciones" name="observaciones" rows="2" maxlength="4000"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-guardar-evaluacion">
                    <i class="fas fa-check mr-1"></i> Guardar y habilitar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: paciente sin evaluación previa -->
<div class="modal fade" id="modal-sin-evaluacion" tabindex="-1" role="dialog" aria-labelledby="modal-sin-evaluacion-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#e65100;color:#fff;">
                <h5 class="modal-title" id="modal-sin-evaluacion-title"><i class="fas fa-forward mr-2"></i> Habilitar sin evaluación</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="sin-evaluacion-alerta" class="alert alert-danger at-alerta-error"></div>
                <p class="mb-2">Paciente: <strong id="sin-evaluacion-paciente"></strong></p>
                <p class="text-muted">El paciente pasará a <strong>Listo para consulta</strong>. Deja constancia del motivo:</p>
                <form id="form-sin-evaluacion" autocomplete="off">
                    <input type="hidden" name="id_cita" id="sin-evaluacion-id-cita" value="">
                    <div class="form-group mb-0">
                        <label for="sin-evaluacion-motivo">Motivo *</label>
                        <textarea class="form-control" id="sin-evaluacion-motivo" name="motivo" rows="3" maxlength="500" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-confirmar-sin-evaluacion">
                    <i class="fas fa-forward mr-1"></i> Habilitar para consulta
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var token = '<?= csrf_token() ?>';
        var vista = <?= json_encode($vista) ?>;
        var urlDetalle = '<?= route('portal_atencion_detalle', ['id' => '{id}']) ?>';
        var urlIniciarEvaluacion = '<?= route('portal_atencion_evaluacion_iniciar') ?>';
        var urlGuardarEvaluacion = '<?= route('portal_atencion_evaluacion_guardar') ?>';
        var urlSinEvaluacion = '<?= route('portal_atencion_evaluacion_sin') ?>';
        var urlAbrirConsulta = '<?= route('portal_atencion_consulta_abrir') ?>';
        var urlConsulta = '<?= route('portal_atencion_consulta', ['id' => '{id}']) ?>';
        var urlExpediente = '<?= route('portal_atencion_expediente', ['id' => '{id}']) ?>';

        var detalleActual = null;

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

        function bloque(etiqueta, valor) {
            var vacio = (valor === null || valor === undefined || String(valor).trim() === '');
            return '<div class="at-detalle-bloque">' +
                '<div class="at-detalle-bloque-label">' + escapeHtml(etiqueta) + '</div>' +
                '<div class="at-detalle-bloque-texto' + (vacio ? ' at-vacio' : '') + '">' +
                escapeHtml(vacio ? 'Sin información' : String(valor)) + '</div></div>';
        }

        function etiquetaRecepcion(estado) {
            var mapa = {
                'EN_ESPERA': 'En espera',
                'EN_EVALUACION': 'En evaluación',
                'LISTO_PARA_CONSULTA': 'Listo para consulta',
                'FINALIZADO': 'Finalizado'
            };
            return mapa[estado] || estado || '—';
        }

        function cargarDetalle(idCita) {
            return $.ajax({
                url: urlDetalle.replace('{id}', idCita),
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        }

        function badgeRecepcion(estado) {
            var clases = {
                'EN_ESPERA': 'badge-secondary',
                'EN_EVALUACION': 'badge-warning',
                'LISTO_PARA_CONSULTA': 'badge-info',
                'FINALIZADO': 'badge-success'
            };
            return clases[estado] || 'badge-light';
        }

        function signoVitalBadge(valor, etiqueta, unidad) {
            if (valor === null || valor === undefined || valor === '') return '';
            return '<div class="at-signo-badge"><span class="valor">' + escapeHtml(String(valor) + (unidad ? ' ' + unidad : '')) + '</span><span class="etiqueta">' + escapeHtml(etiqueta) + '</span></div>';
        }

        function pintarDetalle(datos) {
            var cita = datos.cita || {};
            var recepcion = datos.recepcion || null;
            var evaluacion = datos.evaluacion || null;
            var consulta = datos.consulta || null;
            var diagnosticos = datos.diagnosticos || [];
            var estudios = datos.estudios || [];
            var expediente = datos.expediente || null;
            var html = '';

            // Cabecera: paciente, CI, especialidad, código y estado de recepción
            html += '<div class="at-detalle-cabecera">' +
                '<div class="at-detalle-paciente">' + escapeHtml(cita.paciente || '') +
                '<small>CI ' + escapeHtml(cita.paciente_ci || '—') + ' · ' + escapeHtml(cita.especialidad || '—') + '</small></div>' +
                '<div class="text-right">' +
                '<div class="at-detalle-codigo">' + escapeHtml(cita.codigo || '') + '</div>' +
                '<span class="badge ' + badgeRecepcion(recepcion ? recepcion.estado : '') + ' mt-1">' + escapeHtml(etiquetaRecepcion(recepcion ? recepcion.estado : '')) + '</span>' +
                '</div></div>';

            // Datos generales
            html += '<div class="at-detalle-grid">' +
                '<div class="at-detalle-item"><dt>Médico</dt><dd>' + escapeHtml(cita.medico || '—') + '</dd></div>' +
                '<div class="at-detalle-item"><dt>Fecha y hora</dt><dd>' + escapeHtml(String(cita.fecha_hora_inicio || '').substr(0, 16)) + '</dd></div>' +
                '<div class="at-detalle-item"><dt>Estado de la cita</dt><dd>' + escapeHtml(cita.estado || '—') + '</dd></div>' +
                '<div class="at-detalle-item"><dt>Llegada</dt><dd>' + escapeHtml(String((recepcion && recepcion.fecha_hora_llegada) || '—').substr(0, 16)) + '</dd></div>' +
                '<div class="at-detalle-item"><dt>Recepción</dt><dd>' + escapeHtml(etiquetaRecepcion(recepcion ? recepcion.estado : '')) + '</dd></div>' +
                '</div>';

            html += bloque('Motivo de consulta', cita.motivo_consulta);

            // Evaluación previa
            html += '<div class="at-seccion-titulo"><i class="fas fa-clipboard-list"></i> Evaluación previa</div>';
            if (!evaluacion) {
                html += bloque('Estado', 'Sin registro de evaluación previa');
            } else {
                html += '<div class="at-detalle-grid">' +
                    '<div class="at-detalle-item"><dt>Estado</dt><dd>' + escapeHtml(evaluacion.estado || '—') + '</dd></div>' +
                    '<div class="at-detalle-item"><dt>Fecha</dt><dd>' + escapeHtml(String(evaluacion.fecha_hora || '').substr(0, 16)) + '</dd></div>' +
                    '</div>';

                html += '<div class="d-flex flex-wrap gap-2 mb-3">' +
                    signoVitalBadge([evaluacion.presion_sistolica, evaluacion.presion_diastolica].filter(function (v) { return v !== null && v !== undefined && v !== ''; }).join('/'), 'Presión', 'mmHg') +
                    signoVitalBadge(evaluacion.frecuencia_cardiaca, 'Frec. cardíaca', 'lpm') +
                    signoVitalBadge(evaluacion.frecuencia_respiratoria, 'Frec. respiratoria', 'rpm') +
                    signoVitalBadge(evaluacion.temperatura, 'Temperatura', '°C') +
                    signoVitalBadge(evaluacion.saturacion_oxigeno, 'Sat. O₂', '%') +
                    signoVitalBadge(evaluacion.peso_kg, 'Peso', 'kg') +
                    signoVitalBadge(evaluacion.talla_cm, 'Talla', 'cm') +
                    '</div>';

                html += bloque('Motivo de derivación', evaluacion.motivo_referido);
                html += bloque('Anamnesis preliminar', evaluacion.anamnesis_preliminar);
                html += bloque('Observaciones de evaluación', evaluacion.observaciones);
            }

            // Consulta médica
            html += '<div class="at-seccion-titulo"><i class="fas fa-notes-medical"></i> Consulta médica</div>';
            if (!consulta) {
                html += bloque('Estado', 'Consulta no iniciada');
            } else {
                html += '<div class="at-detalle-grid">' +
                    '<div class="at-detalle-item"><dt>Estado</dt><dd>' + escapeHtml(consulta.estado || '—') + '</dd></div>' +
                    '<div class="at-detalle-item"><dt>Inicio</dt><dd>' + escapeHtml(String(consulta.fecha_inicio || '').substr(0, 16)) + '</dd></div>' +
                    '<div class="at-detalle-item"><dt>Fin</dt><dd>' + escapeHtml(String(consulta.fecha_fin || '').substr(0, 16)) + '</dd></div>' +
                    '</div>';
                html += bloque('Anamnesis', consulta.anamnesis);
                html += bloque('Examen físico', consulta.examen_fisico);
                html += bloque('Evolución', consulta.evolucion);
                html += bloque('Plan de tratamiento', consulta.plan_tratamiento);
                html += bloque('Observaciones de consulta', consulta.observaciones);
            }

            // Diagnósticos
            if (diagnosticos.length) {
                html += '<div class="at-seccion-titulo"><i class="fas fa-stethoscope"></i> Diagnósticos</div>';
                diagnosticos.forEach(function (d) {
                    html += '<div class="at-lista-item ' + (d.tipo === 'PRINCIPAL' ? 'at-item-principal' : 'at-item-secundario') + '">' +
                        '<div class="at-lista-cabecera"><span class="at-lista-texto">' + escapeHtml(d.descripcion) + '</span>' +
                        '<span class="badge ' + (d.tipo === 'PRINCIPAL' ? 'badge-success' : 'badge-secondary') + '">' + escapeHtml(d.tipo) + '</span></div>' +
                        (d.observaciones ? '<div class="small text-muted mt-1">' + escapeHtml(d.observaciones) + '</div>' : '') +
                        '</div>';
                });
            }

            // Estudios solicitados
            if (estudios.length) {
                html += '<div class="at-seccion-titulo"><i class="fas fa-vial"></i> Estudios solicitados</div>';
                estudios.forEach(function (e) {
                    html += '<div class="at-lista-estudio">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                        '<strong>' + escapeHtml(e.tipo_estudio || 'Estudio') + '</strong>' +
                        '<span class="badge badge-info at-estado-badge">' + escapeHtml(e.estado || '—') + '</span>' +
                        '</div>' +
                        '<div class="small text-muted">' + escapeHtml(e.indicacion || 'Sin indicación') + '</div>' +
                        (e.resultado ? '<div class="small mt-1"><strong>Resultado:</strong> ' + escapeHtml(e.resultado) + '</div>' : '') +
                        '</div>';
                });
            }

            // Expediente clínico
            html += '<div class="at-seccion-titulo"><i class="fas fa-folder-open"></i> Expediente clínico</div>';
            if (!expediente) {
                html += bloque('Estado', 'Sin expediente clínico registrado');
            } else {
                html += '<div class="alert alert-light border">' +
                    'Expediente #<strong>' + escapeHtml(expediente.id) + '</strong> · Paciente ID <strong>' + escapeHtml(expediente.id_paciente) + '</strong>' +
                    '</div>';
            }

            $('#detalle-contenido').html(html);
            $('#detalle-footer').show();

            var recepcionEstado = recepcion ? recepcion.estado : '';
            var puedeEvaluar = vista === 'recepcion' && (recepcionEstado === 'EN_ESPERA' || recepcionEstado === 'EN_EVALUACION');
            var sinEvaluacion = vista === 'recepcion' && recepcionEstado === 'EN_ESPERA';
            var habilitado = recepcionEstado === 'LISTO_PARA_CONSULTA';
            var idCita = parseInt(cita.id, 10) || 0;

            $('#detalle-btn-evaluacion').toggle(puedeEvaluar).data('id', idCita);
            $('#detalle-btn-sin-evaluacion').toggle(sinEvaluacion).data('id', idCita);
            $('#detalle-btn-consulta').toggle(vista === 'medico' && habilitado && !consulta).data('id', idCita);

            var $linkConsulta = $('#detalle-link-consulta');
            if (vista === 'medico' && consulta) {
                $linkConsulta.attr('href', urlConsulta.replace('{id}', idCita)).show();
            } else {
                $linkConsulta.hide();
            }

            var $linkExpediente = $('#detalle-link-expediente');
            if (vista === 'medico') {
                $linkExpediente.attr('href', urlExpediente.replace('{id}', parseInt(cita.id_paciente, 10) || 0)).show();
            } else {
                $linkExpediente.hide();
            }
        }

        // ---------- Detalle ----------
        $(document).on('click', '.js-detalle-atencion', function () {
            var id = parseInt($(this).data('id'), 10);
            if (!id) return;

            detalleActual = null;
            ocultarError($('#detalle-alerta'));
            $('#detalle-contenido').html('Cargando…').show();
            $('#detalle-footer').hide();

            cargarDetalle(id).done(function (respuesta) {
                console.log(respuesta);
                if (respuesta && respuesta.success) {
                    detalleActual = respuesta;
                    pintarDetalle(respuesta);
                    return;
                }
                mostrarError($('#detalle-alerta'), (respuesta && respuesta.message) || 'No se pudo cargar el detalle.');
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                mostrarError($('#detalle-alerta'), (r && r.message) || 'No se pudo cargar el detalle.');
            });

            $('#modal-detalle-atencion').modal('show');
        });

        $('#detalle-btn-evaluacion').on('click', function () {
            var id = parseInt($(this).data('id'), 10);
            $('#modal-detalle-atencion').modal('hide');
            abrirEvaluacion(id);
        });

        $('#detalle-btn-sin-evaluacion').on('click', function () {
            var id = parseInt($(this).data('id'), 10);
            var nombre = detalleActual && detalleActual.cita ? detalleActual.cita.paciente : '';
            $('#modal-detalle-atencion').modal('hide');
            abrirSinEvaluacion(id, nombre);
        });

        $('#detalle-btn-consulta').on('click', function () {
            var id = parseInt($(this).data('id'), 10);
            var $btn = $(this).prop('disabled', true);
            abrirConsulta(id, $btn, $('#detalle-alerta'));
        });

        // ---------- Evaluación previa ----------
        function limpiarFormularioEvaluacion() {
            $('#form-evaluacion')[0].reset();
            $('#evaluacion-id-cita').val('');

            // Resetear perfil y cuestionario
            $('input[name="evaluacion_perfil"]').first().prop('checked', true).trigger('change');
            $('.anamnesis-campo').not('[type="checkbox"]').val('');
            $('.anamnesis-campo[type="checkbox"]').prop('checked', false);
            $('#evaluacion-anamnesis').val('');
            $('#evaluacion-imc').html('<i class="fas fa-calculator mr-1"></i> Complete peso y talla para calcular el IMC.').removeClass('alert-success alert-warning alert-danger').addClass('alert-light border text-muted');
            $('.at-signo-grupo .form-control').removeClass('at-signo-normal at-signo-alerta at-signo-peligro');
        }

        // Rangos de referencia para la evaluación previa según perfil
        var rangosReferencia = {
            'adulto-hombre': {
                sistolica: { min: 90, max: 139, texto: 'Normal: 90-139 mmHg' },
                diastolica: { min: 60, max: 89, texto: 'Normal: 60-89 mmHg' },
                fc: { min: 60, max: 100, texto: 'Normal: 60-100 lpm' },
                fr: { min: 12, max: 20, texto: 'Normal: 12-20 rpm' },
                temperatura: { min: 36.1, max: 37.2, texto: 'Normal: 36.1-37.2 °C' },
                spo2: { min: 95, max: 100, texto: 'Normal: ≥95%' }
            },
            'adulto-mujer': {
                sistolica: { min: 90, max: 139, texto: 'Normal: 90-139 mmHg' },
                diastolica: { min: 60, max: 89, texto: 'Normal: 60-89 mmHg' },
                fc: { min: 60, max: 100, texto: 'Normal: 60-100 lpm' },
                fr: { min: 12, max: 20, texto: 'Normal: 12-20 rpm' },
                temperatura: { min: 36.1, max: 37.2, texto: 'Normal: 36.1-37.2 °C' },
                spo2: { min: 95, max: 100, texto: 'Normal: ≥95%' }
            },
            'nino': {
                sistolica: { min: 90, max: 110, texto: 'Normal: 90-110 mmHg' },
                diastolica: { min: 55, max: 75, texto: 'Normal: 55-75 mmHg' },
                fc: { min: 80, max: 130, texto: 'Normal: 80-130 lpm' },
                fr: { min: 20, max: 30, texto: 'Normal: 20-30 rpm' },
                temperatura: { min: 36.5, max: 37.5, texto: 'Normal: 36.5-37.5 °C' },
                spo2: { min: 95, max: 100, texto: 'Normal: ≥95%' }
            },
            'nina': {
                sistolica: { min: 90, max: 110, texto: 'Normal: 90-110 mmHg' },
                diastolica: { min: 55, max: 75, texto: 'Normal: 55-75 mmHg' },
                fc: { min: 85, max: 135, texto: 'Normal: 85-135 lpm' },
                fr: { min: 20, max: 30, texto: 'Normal: 20-30 rpm' },
                temperatura: { min: 36.5, max: 37.5, texto: 'Normal: 36.5-37.5 °C' },
                spo2: { min: 95, max: 100, texto: 'Normal: ≥95%' }
            }
        };

        function perfilActual() {
            return $('input[name="evaluacion_perfil"]:checked').val() || 'adulto-hombre';
        }

        function actualizarRangos() {
            var perfil = perfilActual();
            var rangos = rangosReferencia[perfil];

            $('.campo-pediatrico').toggle(perfil === 'nino' || perfil === 'nina');

            $('.at-rango-ayuda').each(function () {
                var signo = $(this).data('signo');

                if (rangos && rangos[signo]) {
                    $(this).text(rangos[signo].texto);
                }
            });

            validarSignos();
        }

        function validarSignos() {
            var perfil = perfilActual();
            var rangos = rangosReferencia[perfil];

            $('.at-signo-grupo').each(function () {
                var $grupo = $(this);
                var $input = $grupo.find('.form-control');
                var signo = $grupo.find('.at-rango-ayuda').data('signo');
                var valor = parseFloat($input.val());

                $input.removeClass('at-signo-normal at-signo-alerta at-signo-peligro');

                if (isNaN(valor) || !rangos || !rangos[signo]) {
                    return;
                }

                var rango = rangos[signo];
                var min = parseFloat($input.attr('min'));
                var max = parseFloat($input.attr('max'));

                if (valor < min || valor > max) {
                    $input.addClass('at-signo-peligro');
                } else if (valor < rango.min || valor > rango.max) {
                    $input.addClass('at-signo-alerta');
                } else {
                    $input.addClass('at-signo-normal');
                }
            });
        }

        function actualizarImc() {
            var peso = parseFloat($('#evaluacion-peso').val());
            var talla = parseFloat($('#evaluacion-talla').val());
            var $imc = $('#evaluacion-imc');

            if (!peso || !talla || talla <= 0) {
                $imc.html('<i class="fas fa-calculator mr-1"></i> Complete peso y talla para calcular el IMC.')
                    .removeClass('alert-success alert-warning alert-danger alert-info')
                    .addClass('alert-light border text-muted');
                return;
            }

            var imc = peso / Math.pow(talla / 100, 2);
            var clase = 'alert-info';
            var mensaje = '';

            if (imc < 18.5) {
                clase = 'alert-warning';
                mensaje = 'Bajo peso';
            } else if (imc < 25) {
                clase = 'alert-success';
                mensaje = 'Peso normal';
            } else if (imc < 30) {
                clase = 'alert-warning';
                mensaje = 'Sobrepeso';
            } else {
                clase = 'alert-danger';
                mensaje = 'Obesidad';
            }

            $imc.html('<strong>IMC: ' + imc.toFixed(1) + '</strong> — ' + mensaje)
                .removeClass('alert-light border text-muted alert-success alert-warning alert-danger alert-info')
                .addClass(clase);
        }

        function construirAnamnesis() {
            var lineas = [];

            function agregar(titulo, selector, esCheckbox) {
                if (esCheckbox) {
                    var seleccionados = [];
                    $(selector + ':checked').each(function () {
                        seleccionados.push($(this).val());
                    });

                    if (seleccionados.length) {
                        lineas.push(titulo + ' ' + seleccionados.join(', ') + '.');
                    }
                    return;
                }

                var valor = $.trim($(selector).val());

                if (valor) {
                    lineas.push(titulo + ' ' + valor + '.');
                }
            }

            agregar('Motivo principal:', '#anamnesis-motivo');
            agregar('Inicio y evolución:', '#anamnesis-inicio');
            agregar('Características del síntoma:', '#anamnesis-caracteristicas');
            agregar('Síntomas asociados:', '.anamnesis-campo[type="checkbox"]', true);
            agregar('Medicamentos actuales:', '#anamnesis-medicamentos');
            agregar('Alergias conocidas:', '#anamnesis-alergias');

            if ($('.campo-pediatrico').is(':visible')) {
                agregar('Última ingesta/hidratación:', '#anamnesis-alimentacion');
            }

            agregar('Otras observaciones:', '#anamnesis-adicionales');

            var resumen = $.trim($('#evaluacion-anamnesis').val());

            if (lineas.length === 0 && resumen === '') {
                window.espNotificar('warning', 'Responde al menos una pregunta para generar el resumen.');
                return;
            }

            var nuevo = lineas.join('\n');

            if (resumen) {
                $('#evaluacion-anamnesis').val(resumen + '\n\n' + nuevo);
            } else {
                $('#evaluacion-anamnesis').val(nuevo);
            }

            window.espNotificar('success', 'Resumen de anamnesis generado. Puedes editarlo antes de guardar.');
        }

        // Eventos de la evaluación previa interactiva
        $('input[name="evaluacion_perfil"]').on('change', actualizarRangos);
        $('#evaluacion-peso, #evaluacion-talla').on('input', actualizarImc);
        $('#form-evaluacion .at-signo-grupo input').on('input', validarSignos);
        $('#btn-construir-anamnesis').on('click', construirAnamnesis);

        function pintarFormularioEvaluacion(datos) {
            var evaluacion = datos.evaluacion || {};
            var cita = datos.cita || {};
            var recepcion = datos.recepcion || {};

            $('#evaluacion-id-cita').val(parseInt(cita.id, 10) || 0);
            $('#evaluacion-motivo').val(evaluacion.motivo_referido || '');
            $('#evaluacion-anamnesis').val(evaluacion.anamnesis_preliminar || '');
            $('#evaluacion-sistolica').val(evaluacion.presion_sistolica || '');
            $('#evaluacion-diastolica').val(evaluacion.presion_diastolica || '');
            $('#evaluacion-fc').val(evaluacion.frecuencia_cardiaca || '');
            $('#evaluacion-fr').val(evaluacion.frecuencia_respiratoria || '');
            $('#evaluacion-temperatura').val(evaluacion.temperatura || '');
            $('#evaluacion-spo2').val(evaluacion.saturacion_oxigeno || '');
            $('#evaluacion-peso').val(evaluacion.peso_kg || '');
            $('#evaluacion-talla').val(evaluacion.talla_cm || '');
            $('#evaluacion-observaciones').val(evaluacion.observaciones || '');

            $('#evaluacion-info').html(
                '<div class="alert alert-info mb-0 py-2">' +
                '<i class="fas fa-user-injured mr-1"></i> ' + escapeHtml(cita.paciente || '') +
                ' · <code>' + escapeHtml(cita.codigo || '') + '</code>' +
                ' · Estado: <strong>' + escapeHtml(etiquetaRecepcion(recepcion.estado || '')) + '</strong></div>'
            );

            var editable = recepcion.estado === 'EN_EVALUACION';
            $('#form-evaluacion').find('input, textarea').prop('disabled', !editable);
            $('#btn-guardar-evaluacion, #btn-construir-anamnesis').prop('disabled', !editable);

            actualizarRangos();
            validarSignos();
            actualizarImc();

            if (recepcion.estado === 'EN_ESPERA') {
                $('#evaluacion-info').append('<div class="alert alert-warning mb-0 py-2 mt-2"><i class="fas fa-spinner fa-spin mr-1"></i> Iniciando evaluación…</div>');
            } else if (!editable) {
                $('#evaluacion-info').append('<div class="alert alert-secondary mb-0 py-2 mt-2"><i class="fas fa-lock mr-1"></i> La evaluación ya fue cerrada; solo se muestra la información registrada.</div>');
            }
        }

        function abrirEvaluacion(idCita) {
            if (!idCita) return;

            limpiarFormularioEvaluacion();
            ocultarError($('#evaluacion-alerta'));
            $('#evaluacion-info').html('<div class="text-center text-muted py-3">Cargando…</div>');
            $('#form-evaluacion').find('input, textarea').prop('disabled', true);
            $('#btn-guardar-evaluacion').prop('disabled', true);
            $('#modal-evaluacion').modal('show');

            cargarDetalle(idCita).done(function (respuesta) {
                if (!respuesta || !respuesta.success) {
                    mostrarError($('#evaluacion-alerta'), (respuesta && respuesta.message) || 'No se pudo cargar la cita.');
                    return;
                }

                var recepcion = respuesta.recepcion || {};

                if (recepcion.estado === 'EN_ESPERA') {
                    $.ajax({
                        url: urlIniciarEvaluacion,
                        method: 'POST',
                        dataType: 'json',
                        data: { _token: token, id_cita: idCita },
                        headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function (iniciada) {
                        if (iniciada && iniciada.success) {
                            cargarDetalle(idCita).done(function (actualizada) {
                                if (actualizada && actualizada.success) {
                                    pintarFormularioEvaluacion(actualizada);
                                }
                            });
                            return;
                        }
                        mostrarError($('#evaluacion-alerta'), (iniciada && iniciada.message) || 'No se pudo iniciar la evaluación.');
                    }).fail(function (xhr) {
                        var r = xhr && xhr.responseJSON;
                        mostrarError($('#evaluacion-alerta'), (r && r.message) || 'No se pudo iniciar la evaluación.');
                    });
                    return;
                }

                pintarFormularioEvaluacion(respuesta);
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                mostrarError($('#evaluacion-alerta'), (r && r.message) || 'No se pudo cargar la cita.');
            });
        }

        $(document).on('click', '.js-abrir-evaluacion', function () {
            abrirEvaluacion(parseInt($(this).data('id'), 10));
        });

        $('#btn-guardar-evaluacion').on('click', function () {
            var idCita = parseInt($('#evaluacion-id-cita').val(), 10);
            if (!idCita) {
                mostrarError($('#evaluacion-alerta'), 'No se pudo identificar la cita.');
                return;
            }

            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: urlGuardarEvaluacion,
                method: 'POST',
                dataType: 'json',
                data: $('#form-evaluacion').serialize(),
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                $btn.prop('disabled', false);
                if (respuesta && respuesta.success) {
                    $('#modal-evaluacion').modal('hide');
                    window.espNotificar('success', respuesta.message || 'Evaluación guardada.');
                    setTimeout(function () { window.location.reload(); }, 1200);
                    return;
                }
                mostrarError($('#evaluacion-alerta'), (respuesta && respuesta.message) || 'No se pudo guardar la evaluación.');
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($('#evaluacion-alerta'), (r && r.message) || 'No se pudo guardar la evaluación.');
            });
        });

        // ---------- Sin evaluación previa ----------
        function abrirSinEvaluacion(idCita, nombre) {
            $('#form-sin-evaluacion')[0].reset();
            $('#sin-evaluacion-id-cita').val(idCita || 0);
            $('#sin-evaluacion-paciente').text(nombre || '');
            ocultarError($('#sin-evaluacion-alerta'));
            $('#modal-sin-evaluacion').modal('show');
        }

        $(document).on('click', '.js-sin-evaluacion', function () {
            abrirSinEvaluacion(parseInt($(this).data('id'), 10),String($(this).data('paciente') || ''));
        });

        $('#btn-confirmar-sin-evaluacion').on('click', function () {
            var idCita = parseInt($('#sin-evaluacion-id-cita').val(), 10);
            var motivo = $.trim($('#sin-evaluacion-motivo').val());

            if (!idCita) {
                mostrarError($('#sin-evaluacion-alerta'), 'No se pudo identificar la cita.');
                return;
            }

            if (!motivo) {
                mostrarError($('#sin-evaluacion-alerta'), 'Indica el motivo por el que no se realiza la evaluación previa.');
                return;
            }

            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: urlSinEvaluacion,
                method: 'POST',
                dataType: 'json',
                data: { _token: token, id_cita: idCita, motivo: motivo },
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                $btn.prop('disabled', false);
                if (respuesta && respuesta.success) {
                    $('#modal-sin-evaluacion').modal('hide');
                    window.espNotificar('success', respuesta.message || 'Paciente habilitado para consulta.');
                    setTimeout(function () { window.location.reload(); }, 1200);
                    return;
                }
                mostrarError($('#sin-evaluacion-alerta'), (respuesta && respuesta.message) || 'No se pudo habilitar al paciente.');
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($('#sin-evaluacion-alerta'), (r && r.message) || 'No se pudo habilitar al paciente.');
            });
        });

        // ---------- Apertura de consulta desde el detalle ----------
        function abrirConsulta(idCita, $btn, $alerta) {
            if (!idCita) return;
            if ($btn) $btn.prop('disabled', true);
            if ($alerta) ocultarError($alerta);

            $.ajax({
                url: urlAbrirConsulta,
                method: 'POST',
                dataType: 'json',
                data: { _token: token, id_cita: idCita },
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (respuesta && respuesta.success) {
                    window.location.href = respuesta.url || urlConsulta.replace('{id}', idCita);
                    return;
                }
                if ($btn) $btn.prop('disabled', false);
                if ($alerta) mostrarError($alerta, (respuesta && respuesta.message) || 'No se pudo iniciar la consulta.');
                else window.espNotificar('error', (respuesta && respuesta.message) || 'No se pudo iniciar la consulta.');
            }).fail(function (xhr) {
                if ($btn) $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                var mensaje = (r && r.message) || 'No se pudo iniciar la consulta.';
                if ($alerta) mostrarError($alerta, mensaje);
                else window.espNotificar('error', mensaje);
            });
        }

        $(document).on('click', '.js-abrir-consulta', function () {
            var $btn = $(this).prop('disabled', true);
            abrirConsulta(parseInt($(this).data('id'), 10), $btn, null);
        });
    });
</script>
