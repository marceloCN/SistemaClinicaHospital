<?php
/**
 * Modales del módulo de citas médicas del paciente.
 *
 * - Asistente de 3 pasos (especialidad → médico → fecha/hora) con voz.
 * - Detalle de cita con cancelación.
 */
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
?>

<!-- Modal: generar cita (asistente) -->
<div class="modal fade" id="modal-nueva-cita" tabindex="-1" role="dialog" aria-labelledby="modal-nueva-cita-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-nueva-cita-title"><i class="fas fa-calendar-plus mr-2"></i> Nueva cita médica</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="cita-alerta" class="alert alert-danger cm-alerta-error"></div>

                <div class="cm-paso-indicador" id="paso-indicador">
                    <span class="activo" data-paso="1">1</span>
                    <span data-paso="2">2</span>
                    <span data-paso="3">3</span>
                </div>

                <div class="cm-paso-titulo">
                    <h6 id="paso-titulo">Paso 1: Elige la especialidad</h6>
                    <small class="text-muted" id="paso-ayuda">Busca o selecciona la especialidad en la que necesitas atención.</small>
                </div>

                <!-- Paso 1 -->
                <div id="paso-1" class="cm-paso">
                    <div class="cm-buscador-voz mb-3">
                        <input type="text" class="form-control pr-5" id="buscar-especialidad" placeholder="Escribe o usa el micrófono..." autocomplete="off">
                        <button type="button" class="cm-mic js-mic" data-target="buscar-especialidad" title="Buscar por voz"><i class="fas fa-microphone"></i></button>
                    </div>
                    <div class="cm-lista-opciones" id="lista-especialidades">
                        <?php foreach ($catalogoEspecialidades as $esp) { ?>
                            <button type="button" class="cm-opcion js-elegir-especialidad"
                                data-id="<?= (int) $esp['id'] ?>"
                                data-nombre="<?= e((string) $esp['nombre']) ?>">
                                <strong><?= e((string) $esp['nombre']) ?></strong>
                            </button>
                        <?php } ?>
                    </div>
                </div>

                <!-- Paso 2 -->
                <div id="paso-2" class="cm-paso" style="display:none;">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-link p-0 js-volver-paso" data-paso="1"><i class="fas fa-arrow-left"></i> Volver a especialidades</button>
                    </div>
                    <div class="cm-buscador-voz mb-3">
                        <input type="text" class="form-control pr-5" id="buscar-medico" placeholder="Buscar médico..." autocomplete="off">
                        <button type="button" class="cm-mic js-mic" data-target="buscar-medico" title="Buscar por voz"><i class="fas fa-microphone"></i></button>
                    </div>
                    <div class="cm-lista-opciones" id="lista-medicos">
                        <div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Cargando médicos...</div>
                    </div>
                </div>

                <!-- Paso 3 -->
                <div id="paso-3" class="cm-paso" style="display:none;">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-link p-0 js-volver-paso" data-paso="2"><i class="fas fa-arrow-left"></i> Volver a médicos</button>
                    </div>

                    <!-- Resumen de la selección -->
                    <div class="cm-resumen-seleccion mb-3">
                        <div class="cm-resumen-item">
                            <i class="fas fa-stethoscope"></i>
                            <div>
                                <small>Especialidad</small>
                                <strong id="resumen-especialidad">—</strong>
                            </div>
                        </div>
                        <div class="cm-resumen-item">
                            <i class="fas fa-user-md"></i>
                            <div>
                                <small>Médico</small>
                                <strong id="resumen-medico">—</strong>
                            </div>
                        </div>
                        <div class="cm-resumen-item">
                            <i class="fas fa-calendar-day"></i>
                            <div>
                                <small>Fecha</small>
                                <strong id="resumen-fecha">—</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Selector de fecha -->
                    <div class="cm-fecha-selector mb-3">
                        <label for="cita-fecha" class="cm-fecha-label"><i class="far fa-calendar-alt mr-1"></i> Selecciona la fecha</label>
                        <div class="cm-fecha-input-wrap">
                            <input type="date" class="form-control" id="cita-fecha" min="">
                        </div>
                    </div>

                    <!-- Turnos disponibles -->
                    <label class="cm-fichas-label"><i class="far fa-clock mr-1"></i> Turnos disponibles</label>
                    <div id="lista-fichas" class="cm-fichas-grid mb-3">
                        <div class="cm-fichas-vacio">
                            <i class="far fa-calendar"></i>
                            <p>Selecciona una fecha para ver los turnos disponibles.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cita-motivo">Motivo de la consulta *</label>
                        <textarea class="form-control" id="cita-motivo" rows="2" maxlength="1000" placeholder="Describe brevemente el motivo de tu consulta..." required></textarea>
                        <small class="form-text text-muted text-right"><span id="contador-motivo">0</span>/1000</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-cita" style="display:none;">
                    <i class="fas fa-check mr-1"></i> Confirmar cita
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: cita por voz (dictado completo: especialidad + fecha) -->
<style>
    .cm-voz-mic {
        width: 92px;
        height: 92px;
        border-radius: 50%;
        border: 0;
        background: var(--portal-primary, #0f8b84);
        color: #fff;
        font-size: 2rem;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(15, 139, 132, .28);
        transition: transform .15s ease, box-shadow .15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .cm-voz-mic:hover { transform: scale(1.04); }
    .cm-voz-mic.escuchando {
        animation: cm-voz-pulso 1.4s ease-in-out infinite;
        background: #c0392b;
        box-shadow: 0 8px 24px rgba(192, 57, 43, .3);
    }
    @keyframes cm-voz-pulso {
        0%, 100% { box-shadow: 0 0 0 0 rgba(192, 57, 43, .45); }
        50% { box-shadow: 0 0 0 18px rgba(192, 57, 43, 0); }
    }
    .cm-voz-estado {
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6b7c83;
        margin-top: 14px;
    }
    .cm-voz-transcripcion {
        min-height: 54px;
        background: #f7f9f8;
        border: 1px solid #e4e9e7;
        border-radius: 12px;
        padding: 10px 14px;
        margin-top: 12px;
        font-size: .95rem;
        color: #0b2a38;
        text-align: left;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .cm-voz-transcripcion:empty::before { content: "Tu frase aparecerá aquí…"; color: #9aa8ae; font-style: italic; }
    .cm-voz-respuesta {
        display: none;
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: .88rem;
        color: #3d6a8f;
        text-align: left;
        margin-top: 12px;
        line-height: 1.5;
    }
    .cm-voz-respuesta.error { background: #fdecea; border-color: #f5b7b1; color: #922b21; }
    .cm-voz-respuesta .cm-voz-lista { margin: 8px 0 4px; padding-left: 18px; text-align: left; }
    .cm-voz-respuesta .cm-voz-lista li { margin-bottom: 3px; font-size: .85rem; }
    .cm-ficha-slot .medico-slot { font-size: .7rem; color: #6b7c83; margin-top: 5px; line-height: 1.2; }
</style>
<div class="modal fade" id="modal-cita-voz" tabindex="-1" role="dialog" aria-labelledby="modal-cita-voz-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-cita-voz-title"><i class="fas fa-microphone mr-2"></i> Nueva cita por voz</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <button type="button" class="cm-voz-mic" id="voz-mic" title="Hablar"><i class="fas fa-microphone"></i></button>
                <div class="cm-voz-estado" id="voz-estado">Preparando el micrófono…</div>
                <div class="cm-voz-transcripcion" id="voz-transcripcion"></div>
                <div class="cm-voz-respuesta" id="voz-respuesta"></div>
                <small class="text-muted d-block mt-3">
                    Ejemplo: <em>“¿Tienes alguna cita disponible el lunes que viene de Medicina General?”</em>
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="voz-ver-disponibles" style="display:none;">
                    <i class="fas fa-calendar-check mr-1"></i> Ver esos horarios
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-outline-primary" id="voz-reintentar" style="display:none;">
                    <i class="fas fa-redo mr-1"></i> Hablar de nuevo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: detalle de cita -->
<style>
    .cm-detalle .modal-header {
        background: var(--portal-primary, #0f8b84);
        color: #fff;
        border: 0;
        align-items: center;
    }
    .cm-detalle-header-main { display: flex; align-items: center; gap: 12px; }
    .cm-detalle-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .cm-detalle .modal-title { font-weight: 700; line-height: 1.2; }
    .cm-detalle-header-main small { display: block; opacity: .85; font-size: .76rem; letter-spacing: .03em; }
    .cm-detalle .close { color: #fff; text-shadow: none; opacity: .9; }
    .cm-detalle .close:hover { color: #fff; opacity: 1; }

    .cm-detalle-cargando { text-align: center; padding: 36px 16px; color: #6b7c83; }
    .cm-detalle-cargando i { color: var(--portal-primary, #0f8b84); }
    .cm-detalle-cargando p { margin: 12px 0 0; font-size: .9rem; }
    #detalle-alerta.cm-alerta-error { display: none; }

    .cm-detalle-hero {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: center;
        justify-content: space-between;
        background: #f7f9f8;
        border: 1px solid #e4e9e7;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 16px;
    }
    .cm-detalle-hero-fecha { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .cm-detalle-hero-fecha > i {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e4e9e7;
        color: var(--portal-primary, #0f8b84);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .cm-detalle-hero-fecha small,
    .cm-detalle-hero-estado small {
        display: block;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #7d8f96;
    }
    .cm-detalle-hero-fecha strong { display: block; color: #0b2a38; font-size: 1.02rem; font-weight: 800; line-height: 1.25; }
    .cm-detalle-horas {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 4px;
        font-size: .84rem;
        color: #44545c;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }
    .cm-detalle-hero-estado { text-align: right; }
    .cm-detalle-hero-estado .badge {
        font-size: .76rem;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .cm-detalle-seccion { margin-bottom: 14px; }
    .cm-detalle-seccion:last-child { margin-bottom: 0; }
    .cm-detalle-titulo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #44545c;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px dashed #e0e6e3;
    }
    .cm-detalle-titulo i {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: color-mix(in srgb, var(--portal-primary, #0f8b84) 12%, white);
        color: var(--portal-primary, #0f8b84);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        flex-shrink: 0;
    }

    .cm-campo-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 14px; }
    .cm-campo {
        min-width: 0;
        background: #f7faf9;
        border: 1px solid #eef2f1;
        border-radius: 10px;
        padding: 9px 12px;
    }
    .cm-campo-label {
        display: block;
        font-size: .68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #7d8f96;
        margin-bottom: 3px;
    }
    .cm-campo-valor { display: block; font-size: .92rem; font-weight: 600; color: #0b2a38; word-break: break-word; line-height: 1.35; }
    .cm-campo-valor small { display: block; font-weight: 500; color: #6b7c83; font-size: .78rem; }

    .cm-detalle-nota {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: .86rem;
        color: #3d6a8f;
        margin-top: 14px;
        line-height: 1.45;
    }
    .cm-detalle-nota i { margin-top: 2px; flex-shrink: 0; }

    @media (max-width: 575.98px) {
        .cm-campo-grid { grid-template-columns: 1fr; }
        .cm-detalle-hero-estado { text-align: left; }
    }
</style>
<div class="modal fade" id="modal-detalle-cita" tabindex="-1" role="dialog" aria-labelledby="modal-detalle-cita-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content cm-detalle">
            <div class="modal-header">
                <div class="cm-detalle-header-main">
                    <span class="cm-detalle-header-icon"><i class="fas fa-file-medical"></i></span>
                    <div>
                        <h5 class="modal-title" id="modal-detalle-cita-title">Detalle de la cita</h5>
                        <small id="detalle-codigo-header">Cargando…</small>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="detalle-alerta" class="alert alert-danger cm-alerta-error"></div>
                <div id="detalle-contenido" class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>
            </div>
            <div class="modal-footer" id="detalle-footer" style="display:none;">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cerrar</button>
                <button type="button" class="btn btn-danger" id="btn-cancelar-cita" style="display:none;">
                    <i class="fas fa-times-circle mr-1"></i> Cancelar cita
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var token = '<?= csrf_token() ?>';
        var urlGenerar = '<?= route('portal_cita_generar') ?>';
        var urlMedicos = '<?= route('portal_cita_medicos') ?>';
        var urlFichas = '<?= route('portal_cita_fichas') ?>';
        var urlDetalle = '<?= route('portal_cita_detalle', ['id' => '{id}']) ?>';
        var urlCancelar = '<?= route('portal_cita_cancelar', ['id' => '{id}']) ?>';

        var $modalNueva = $('#modal-nueva-cita');
        var $alerta = $('#cita-alerta');
        var $btnConfirmar = $('#btn-confirmar-cita');

        var wizard = {
            paso: 1,
            idEspecialidad: 0,
            especialidad: '',
            idMedico: 0,
            medico: '',
            idFicha: 0,
            fichaTexto: '',
            multiMedico: false
        };

        function mostrarError($el, mensaje) { $el.text(mensaje || 'Ocurrió un error.').show(); }
        function ocultarError($el) { $el.hide().text(''); }

        function setMinFecha() {
            var hoy = new Date();
            var mes = ('0' + (hoy.getMonth() + 1)).slice(-2);
            var dia = ('0' + hoy.getDate()).slice(-2);
            $('#cita-fecha').attr('min', hoy.getFullYear() + '-' + mes + '-' + dia);
        }

        function cambiarPaso(nuevo) {
            wizard.paso = nuevo;
            $('.cm-paso').hide();
            $('#paso-' + nuevo).show();
            $('#paso-indicador span').removeClass('activo').filter('[data-paso="' + nuevo + '"]').addClass('activo');

            var titulos = {
                1: ['Paso 1: Elige la especialidad', 'Busca o selecciona la especialidad en la que necesitas atención.'],
                2: ['Paso 2: Elige el médico', 'Médicos disponibles para ' + wizard.especialidad],
                3: ['Paso 3: Elige fecha y hora', wizard.multiMedico
                    ? ('Turnos disponibles en ' + wizard.especialidad)
                    : ('Turnos disponibles con ' + wizard.medico)]
            };

            $('#paso-titulo').text(titulos[nuevo][0]);
            $('#paso-ayuda').text(titulos[nuevo][1]);

            if (nuevo === 3) {
                $btnConfirmar.show();
                $('#resumen-especialidad').text(wizard.especialidad || '—');
                $('#resumen-medico').text(wizard.multiMedico ? 'Varios médicos' : (wizard.medico || '—'));
                actualizarResumenFecha();
            } else {
                $btnConfirmar.hide();
            }
        }

        function actualizarResumenFecha() {
            var fecha = $('#cita-fecha').val();
            if (!fecha) {
                $('#resumen-fecha').text('—');
                return;
            }
            var partes = fecha.split('-');
            $('#resumen-fecha').text(partes[2] + '/' + partes[1] + '/' + partes[0]);
        }

        function resetWizard() {
            wizard = { paso: 1, idEspecialidad: 0, especialidad: '', idMedico: 0, medico: '', idFicha: 0, fichaTexto: '', multiMedico: false };
            $('#buscar-especialidad, #buscar-medico, #cita-fecha, #cita-motivo').val('');
            $('#lista-medicos').html('<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Cargando médicos...</div>');
            $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar"></i><p>Selecciona una fecha para ver los turnos disponibles.</p></div>');
            $('.cm-ficha-slot').removeClass('seleccionada');
            $('#contador-motivo').text('0');
            cambiarPaso(1);
            filtrarLista('buscar-especialidad', 'lista-especialidades', '.js-elegir-especialidad');
        }

        function abrirNuevaCita() {
            ocultarError($alerta);
            resetWizard();
            setMinFecha();
            $modalNueva.modal('show');
        }

        $('#btn-nueva-cita, .js-nueva-cita').on('click', abrirNuevaCita);

        function filtrarLista(inputId, listaId, selectorItems) {
            var texto = $.trim($('#' + inputId).val()).toLowerCase();
            $('#' + listaId).find(selectorItems).each(function () {
                var $item = $(this);
                var nombre = ($item.text() || '').toLowerCase();
                $item.toggle(nombre.indexOf(texto) !== -1);
            });
        }

        $('#buscar-especialidad').on('input', function () {
            filtrarLista('buscar-especialidad', 'lista-especialidades', '.js-elegir-especialidad');
        });

        $('#buscar-medico').on('input', function () {
            filtrarLista('buscar-medico', 'lista-medicos', '.js-elegir-medico');
        });

        $(document).on('click', '.js-elegir-especialidad', function () {
            var $btn = $(this);
            wizard.idEspecialidad = parseInt($btn.attr('data-id'), 10) || 0;
            wizard.especialidad = $.trim($btn.attr('data-nombre'));
            ocultarError($alerta);
            cambiarPaso(2);
            cargarMedicos();
        });

        $(document).on('click', '.js-volver-paso', function () {
            var paso = parseInt($(this).attr('data-paso'), 10) || 1;
            cambiarPaso(paso);
            if (paso === 2 && $('#lista-medicos .js-elegir-medico').length === 0) {
                cargarMedicos();
            }
        });

        function cargarMedicos() {
            $('#lista-medicos').html('<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Cargando médicos...</div>');


            $.ajax({
                url: urlMedicos,
                method: 'GET',
                data: { id_especialidad: wizard.idEspecialidad },
                dataType: 'json'
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success || !respuesta.medicos || respuesta.medicos.length === 0) {
                    $('#lista-medicos').html('<div class="text-center text-muted py-3">No hay médicos disponibles para esta especialidad.</div>');
                    return;
                }
                var html = '';
                $.each(respuesta.medicos, function (_, medico) {
                    html += '<button type="button" class="cm-opcion js-elegir-medico" data-id="' + medico.id + '" data-nombre="' + escapeHtml(medico.nombre_completo) + '">' +
                        '<strong>' + escapeHtml(medico.nombre_completo) + '</strong>' +
                        '<small>CI ' + escapeHtml(medico.ci) + ' · Colegiado ' + escapeHtml(medico.nro_colegiado) + '</small>' +
                        '</button>';
                });

                $('#lista-medicos').html(html);
                filtrarLista('buscar-medico', 'lista-medicos', '.js-elegir-medico');
            }).fail(function () {
                $('#lista-medicos').html('<div class="text-center text-danger py-3">No se pudieron cargar los médicos.</div>');
            });
        }

        $(document).on('click', '.js-elegir-medico', function () {
            var $btn = $(this);
            wizard.idMedico = parseInt($btn.attr('data-id'), 10) || 0;
            wizard.medico = $.trim($btn.attr('data-nombre'));
            wizard.multiMedico = false;
            ocultarError($alerta);
            cambiarPaso(3);
            $('#cita-fecha').val('');
            $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar"></i><p>Selecciona una fecha para ver los turnos disponibles.</p></div>');
            wizard.idFicha = 0;
        });

        function htmlSlotFicha(ficha, medico) {
            var hora = formatearFicha(ficha);
            var fin = (ficha.fecha_hora_fin || '').substr(11, 5);
            var html = '<div class="cm-ficha-slot" data-id="' + ficha.id + '" data-texto="' + escapeHtml(hora) + '"';
            if (medico) {
                html += ' data-id-medico="' + parseInt(medico.id, 10) + '" data-medico="' + escapeHtml(medico.nombre_completo) + '"';
            }
            html += '>' +
                '<i class="fas fa-check-circle check"></i>' +
                '<div class="hora">' + escapeHtml(hora) + '</div>' +
                '<div class="duracion">hasta ' + escapeHtml(fin) + '</div>';
            if (medico) {
                html += '<div class="medico-slot"><i class="fas fa-user-md mr-1"></i>' + escapeHtml(medico.nombre_completo) + '</div>';
            }
            html += '<span class="badge-disponible">Disponible</span></div>';
            return html;
        }

        function buscarHorariosEspecialidad(idEspecialidad, fecha, callback) {
            $.ajax({
                url: urlMedicos,
                method: 'GET',
                data: { id_especialidad: idEspecialidad },
                dataType: 'json'
            }).done(function (respuesta) {
                var medicos = (respuesta && respuesta.success && respuesta.medicos) ? respuesta.medicos : [];
                if (medicos.length === 0) { callback([], 'sin-medicos'); return; }

                var pendientes = medicos.length;
                var slots = [];

                $.each(medicos, function (_, medico) {
                    $.ajax({
                        url: urlFichas,
                        method: 'GET',
                        data: { id_medico: medico.id, fecha: fecha },
                        dataType: 'json'
                    }).done(function (r) {
                        if (r && r.success && r.fichas) {
                            $.each(r.fichas, function (_, ficha) {
                                slots.push({ ficha: ficha, medico: medico });
                            });
                        }
                    }).always(function () {
                        pendientes--;
                        if (pendientes > 0) return;
                        slots.sort(function (a, b) {
                            return (a.ficha.fecha_hora_inicio || '').localeCompare(b.ficha.fecha_hora_inicio || '');
                        });
                        callback(slots, null);
                    });
                });
            }).fail(function () { callback(null, 'error'); });
        }

        function cargarFichasEspecialidad(fecha) {
            $('#lista-fichas').html('<div class="cm-fichas-cargando"><i class="fas fa-spinner fa-spin"></i><p>Cargando turnos...</p></div>');

            buscarHorariosEspecialidad(wizard.idEspecialidad, fecha, function (slots, error) {
                if (error === 'sin-medicos') {
                    $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar-times"></i><p>No hay médicos asignados a ' + escapeHtml(wizard.especialidad) + '.</p></div>');
                    return;
                }
                if (error || !slots) {
                    $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="fas fa-exclamation-circle text-danger"></i><p>No se pudieron cargar los turnos.</p></div>');
                    return;
                }
                if (slots.length === 0) {
                    var partes = fecha.split('-');
                    $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar-times"></i><p>No hay horarios disponibles el '
                        + escapeHtml(partes[2] + '/' + partes[1] + '/' + partes[0])
                        + ' para la especialidad ' + escapeHtml(wizard.especialidad) + '.</p></div>');
                    return;
                }

                var html = '';
                $.each(slots, function (_, s) {
                    html += htmlSlotFicha(s.ficha, s.medico);
                });
                $('#lista-fichas').html(html);
            });
        }

        $('#cita-fecha').on('change', function () {
            var fecha = $.trim($(this).val());
            ocultarError($alerta);
            actualizarResumenFecha();
            wizard.idFicha = 0;
            $('.cm-ficha-slot').removeClass('seleccionada');

            if (!fecha) {
                $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar"></i><p>Selecciona una fecha para ver los turnos disponibles.</p></div>');
                return;
            }

            if (wizard.multiMedico) {
                cargarFichasEspecialidad(fecha);
                return;
            }

            $('#lista-fichas').html('<div class="cm-fichas-cargando"><i class="fas fa-spinner fa-spin"></i><p>Cargando turnos...</p></div>');
            
            $.ajax({
                url: urlFichas,
                method: 'GET',
                data: {
                    id_medico: wizard.idMedico,
                    fecha: fecha
                },
                dataType: 'json'
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success) {
                    $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="fas fa-exclamation-circle text-danger"></i><p>' + escapeHtml(respuesta && respuesta.message ? respuesta.message : 'No se pudieron cargar los turnos.') + '</p></div>');
                    return;
                }

                if (!respuesta.fichas || respuesta.fichas.length === 0) {
                    $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar-times"></i><p>No hay turnos disponibles para esa fecha.</p></div>');
                    return;
                }

                var html = '';
                $.each(respuesta.fichas, function (_, ficha) {
                    html += htmlSlotFicha(ficha, null);
                });

                $('#lista-fichas').html(html);
            }).fail(function () {
                $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="fas fa-exclamation-circle text-danger"></i><p>No se pudieron cargar los turnos.</p></div>');
            });
        });

        $(document).on('click', '.cm-ficha-slot', function () {
            $('.cm-ficha-slot').removeClass('seleccionada');
            var $slot = $(this).addClass('seleccionada');
            wizard.idFicha = parseInt($slot.attr('data-id'), 10) || 0;
            wizard.fichaTexto = $slot.attr('data-texto') || '';
            var idMedico = parseInt($slot.attr('data-id-medico'), 10) || 0;
            if (idMedico > 0) {
                wizard.idMedico = idMedico;
                wizard.medico = $.trim($slot.attr('data-medico')) || wizard.medico;
                $('#resumen-medico').text(wizard.medico);
            }
        });

        $('#cita-motivo').on('input', function () {
            $('#contador-motivo').text($(this).val().length);
        });

        // verifica este paso que este correcto y no te olvides de modificarlo cuando este listo
        $btnConfirmar.on('click', function () {
            ocultarError($alerta);

            if (wizard.idFicha <= 0) {
                mostrarError($alerta, 'Selecciona un turno disponible.');
                return;
            }

            var motivo = $.trim($('#cita-motivo').val());
            if (!motivo) {
                mostrarError($alerta, 'Describe el motivo de la consulta.');
                return;
            }

            var $btn = $(this).prop('disabled', true);
            var datos =  {
                    _token: token,
                    id_ficha_agenda: wizard.idFicha,
                    motivo_consulta: motivo
                };
                console.log(datos);
            $.ajax({
                url: urlGenerar,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id_ficha_agenda: wizard.idFicha,
                    motivo_consulta: motivo
                },
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                $btn.prop('disabled', false);

                if (respuesta && respuesta.success) {
                    $modalNueva.modal('hide');
                    window.espNotificar('success', respuesta.message || 'Cita generada correctamente.');
                    setTimeout(function () { window.location.reload(); }, 1200);
                    return;
                }
                mostrarError($alerta, (respuesta && respuesta.message) || 'No se pudo generar la cita.');
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($alerta, (r && r.message) || 'No se pudo generar la cita.');
            });
        });

        // ----- Cita por voz -----
        var $modalVoz = $('#modal-cita-voz');
        var $vozEstado = $('#voz-estado');
        var $vozTranscripcion = $('#voz-transcripcion');
        var $vozRespuesta = $('#voz-respuesta');
        var $vozMic = $('#voz-mic');
        var $vozReintentar = $('#voz-reintentar');
        var $vozVer = $('#voz-ver-disponibles');
        var vozContexto = null;
        var reconocimientoVoz = null;
        var vozProcesando = false;
        var vozAbierto = false;

        function vozMensaje(msg, esError) {
            $vozRespuesta.html(msg || '').toggleClass('error', !!esError).show();
        }

        function vozOcultarMensaje() {
            $vozRespuesta.hide().html('').removeClass('error');
        }

        function normalizarVoz(t) {
            return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim();
        }

        function fechaISO(d) {
            var mes = ('0' + (d.getMonth() + 1)).slice(-2);
            var dia = ('0' + d.getDate()).slice(-2);
            return d.getFullYear() + '-' + mes + '-' + dia;
        }

        /** Devuelve {desde, hasta} en ISO; si es un solo día, desde === hasta. */
        function rangoDesdeVoz(texto) {
            var t = normalizarVoz(texto);
            var hoy = new Date();
            hoy.setHours(0, 0, 0, 0);

            function sumarDias(base, n) {
                var d = new Date(base.getTime());
                d.setDate(d.getDate() + n);
                return d;
            }

            function unDia(iso) { return { desde: iso, hasta: iso }; }

            if (/\bpasado\s+manana\b/.test(t)) return unDia(fechaISO(sumarDias(hoy, 2)));
            if (/\bmanana\b/.test(t) && !/\b(de|por|a)\s+la\s+manana\b/.test(t)) return unDia(fechaISO(sumarDias(hoy, 1)));
            if (/\bhoy\b/.test(t)) return unDia(fechaISO(hoy));

            // "10/11", "10-11", "10/11/2026"
            var m = t.match(/\b(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{2,4}))?\b/);
            if (m) {
                var anio = m[3] ? parseInt(m[3], 10) : hoy.getFullYear();
                if (m[3] && m[3].length === 2) anio += 2000;
                var d1 = new Date(anio, parseInt(m[2], 10) - 1, parseInt(m[1], 10));
                if (!isNaN(d1.getTime())) {
                    if (d1 < hoy && !m[3]) d1.setFullYear(hoy.getFullYear() + 1);
                    return unDia(fechaISO(d1));
                }
            }

            // "10 de noviembre"
            var meses = { enero: 0, febrero: 1, marzo: 2, abril: 3, mayo: 4, junio: 5, julio: 6, agosto: 7, septiembre: 8, setiembre: 8, octubre: 9, noviembre: 10, diciembre: 11 };
            m = t.match(/\b(\d{1,2})\s+de\s+([a-z]+)\b/);
            if (m && meses[m[2]] !== undefined) {
                var d2 = new Date(hoy.getFullYear(), meses[m[2]], parseInt(m[1], 10));
                if (!isNaN(d2.getTime())) {
                    if (d2 < hoy) d2.setFullYear(hoy.getFullYear() + 1);
                    return unDia(fechaISO(d2));
                }
            }

            // "el lunes que viene", "el martes"
            var dias = { domingo: 0, lunes: 1, martes: 2, miercoles: 3, jueves: 4, viernes: 5, sabado: 6 };
            var relativo = /(que\s+viene|proxim|siguiente|semana\s+entrante)/.test(t) && !/esta\s+semana/.test(t);
            for (var nombre in dias) {
                if (!new RegExp('\\b' + nombre + 's?\\b').test(t)) continue;
                var delta = (dias[nombre] - hoy.getDay() + 7) % 7;
                if (delta === 0) delta = 7;
                // "que viene/próximo" → la ocurrencia debe caer en una semana posterior a hoy
                var diasHastaProximoLunes = (8 - hoy.getDay()) % 7 || 7;
                if (relativo && delta < diasHastaProximoLunes) delta += 7;
                return unDia(fechaISO(sumarDias(hoy, delta)));
            }

            // "esta semana" → de hoy al domingo
            if (/\besta\s+semana\b/.test(t)) {
                var lunesEsta = sumarDias(hoy, -((hoy.getDay() + 6) % 7));
                return { desde: fechaISO(hoy), hasta: fechaISO(sumarDias(lunesEsta, 6)) };
            }

            // "la semana que viene", "la próxima semana", "la semana entrante/siguiente"
            if (/(?:la\s+)?(?:proxima|siguiente|entrante)\s+semana|semana\s+(?:que\s+viene|proxima|siguiente|entrante)/.test(t)) {
                var lunesProx = sumarDias(sumarDias(hoy, -((hoy.getDay() + 6) % 7)), 7);
                return { desde: fechaISO(lunesProx), hasta: fechaISO(sumarDias(lunesProx, 6)) };
            }

            return null;
        }

        function etiquetaRango(rango) {
            if (!rango) return '';
            if (rango.desde === rango.hasta) {
                return 'el ' + rango.desde.split('-').reverse().join('/');
            }
            var a = rango.desde.split('-').reverse().join('/');
            var b = rango.hasta.split('-').reverse().join('/');
            return 'del ' + a + ' al ' + b;
        }

        function fechasDelRango(rango) {
            var out = [];
            var d = new Date(rango.desde + 'T00:00:00');
            var fin = new Date(rango.hasta + 'T00:00:00');
            while (d <= fin && out.length < 7) {
                out.push(fechaISO(d));
                d.setDate(d.getDate() + 1);
            }
            if (out.length === 0) out.push(rango.desde);
            return out;
        }

        function mencionaDoctor(texto) {
            return /\b(doctor|doctores|doctora|doctoras|dr|dra|medico|medica)\b/.test(normalizarVoz(texto));
        }

        /** Devuelve el nombre de pila/apellido que acompaña a "doctor/dra/..." si lo hay ('' si es genérico). */
        function nombreDoctorCandidato(texto) {
            var t = normalizarVoz(texto);
            var stop = {
                de: 1, del: 1, la: 1, el: 1, los: 1, las: 1, en: 1, un: 1, una: 1, con: 1, para: 1,
                por: 1, y: 1, o: 1, mi: 1, su: 1, al: 1, lo: 1, le: 1, se: 1, que: 1, como: 1,
                este: 1, esta: 1, hoy: 1, manana: 1, semana: 1, cita: 1, citas: 1, horario: 1,
                horarios: 1, turno: 1, turnos: 1, dia: 1, dias: 1, tiempo: 1, fecha: 1, fechas: 1,
                disponible: 1, disponibles: 1, necesito: 1, quiero: 1, puedo: 1, tiene: 1, general: 1
            };
            var re = /\b(?:doctoras|doctores|doctora|doctor|dra|dr|medicas|medicos|medica|medico)\.?\s+(?:([a-z]{2,})\s+)?([a-z]{3,})/g;
            var m;
            while ((m = re.exec(t)) !== null) {
                var cands = [m[2], m[1]];
                for (var i = 0; i < cands.length; i++) {
                    var p = cands[i];
                    if (p && !stop[p]) return p;
                }
            }
            return '';
        }

        function doctorDesdeVoz(texto, medicos) {
            var t = normalizarVoz(texto);
            var ignorar = { dr: 1, dra: 1, doctor: 1, doctors: 1, doctora: 1, doctoras: 1, medico: 1, medica: 1, medicos: 1, medicas: 1 };
            var mejor = null;
            var mejorLargo = 0;

            $.each(medicos, function (_, medico) {
                var nombre = normalizarVoz(medico.nombre_completo || '');
                if (!nombre) return;
                var largo = 0;
                if (t.indexOf(nombre) !== -1) {
                    largo = nombre.length;
                } else {
                    var palabras = nombre.split(' ');
                    for (var i = 0; i < palabras.length; i++) {
                        var p = palabras[i];
                        if (p.length >= 4 && !ignorar[p] && t.indexOf(p) !== -1) {
                            largo = Math.max(largo, p.length);
                        }
                    }
                }
                if (largo > mejorLargo) {
                    mejorLargo = largo;
                    mejor = medico;
                }
            });

            return mejor;
        }

        /** Itera los días del rango hasta encontrar el primero con slots (o null). */
        function primerDiaConDisponibilidad(fechas, obtener, callback) {
            var i = 0;
            (function siguiente() {
                if (i >= fechas.length) { callback(null, []); return; }
                var fecha = fechas[i++];
                obtener(fecha, function (slots) {
                    if (slots && slots.length > 0) { callback(fecha, slots); return; }
                    siguiente();
                });
            })();
        }

        function fichasDeMedico(idMedico, fecha, callback) {
            $.ajax({
                url: urlFichas,
                method: 'GET',
                data: { id_medico: idMedico, fecha: fecha },
                dataType: 'json'
            }).done(function (r) {
                callback((r && r.success && r.fichas) ? r.fichas : []);
            }).fail(function () { callback([]); });
        }

        function htmlListaDoctores(slots) {
            var grupos = {};
            var orden = [];
            $.each(slots, function (_, s) {
                var k = s.medico.id;
                if (!grupos[k]) {
                    grupos[k] = { nombre: s.medico.nombre_completo, horas: [] };
                    orden.push(k);
                }
                if (grupos[k].horas.length < 4) grupos[k].horas.push(formatearFicha(s.ficha));
            });
            var html = '<ul class="cm-voz-lista">';
            $.each(orden.slice(0, 6), function (_, k) {
                html += '<li><strong>' + escapeHtml(grupos[k].nombre) + '</strong>: ' + escapeHtml(grupos[k].horas.join(', ')) + '</li>';
            });
            if (orden.length > 6) html += '<li>…y otros médicos más.</li>';
            html += '</ul>';
            return html;
        }

        function especialidadDesdeVoz(texto) {
            var t = normalizarVoz(texto);
            var mejor = null;
            var mejorLargo = 0;

            $('#lista-especialidades .js-elegir-especialidad').each(function () {
                var nombre = normalizarVoz($(this).attr('data-nombre') || '');
                if (!nombre) return;
                var largo = 0;
                if (t.indexOf(nombre) !== -1) {
                    largo = nombre.length;
                } else {
                    // coincide por palabras sueltas (ej: "ginecologia" dentro de "ginecologia y obstetricia")
                    var palabras = nombre.split(' ');
                    for (var i = 0; i < palabras.length; i++) {
                        if (palabras[i].length >= 4 && t.indexOf(palabras[i]) !== -1) {
                            largo = Math.max(largo, palabras[i].length);
                        }
                    }
                }
                if (largo > mejorLargo) {
                    mejorLargo = largo;
                    mejor = {
                        id: parseInt($(this).attr('data-id'), 10) || 0,
                        nombre: $.trim($(this).attr('data-nombre'))
                    };
                }
            });

            return mejor;
        }

        function fechaLegible(iso) {
            var p = (iso || '').split('-');
            return p[2] + '/' + p[1] + '/' + p[0];
        }

        function abrirWizardDesdeVoz(especialidad, fecha, medico) {
            ocultarError($alerta);
            resetWizard();
            setMinFecha();

            wizard.idEspecialidad = especialidad.id;
            wizard.especialidad = especialidad.nombre;

            if (medico) {
                wizard.idMedico = medico.id;
                wizard.medico = medico.nombre_completo;
                wizard.multiMedico = false;
            } else {
                wizard.multiMedico = true;
            }

            $modalNueva.modal('show');
            cambiarPaso(3);

            if (fecha) {
                $('#cita-fecha').val(fecha);
                $('#cita-fecha').trigger('change');
            } else {
                $('#lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar"></i><p>Selecciona una fecha para ver los turnos disponibles.</p></div>');
                mostrarError($alerta, 'No detecté la fecha en tu frase. Elígela aquí para ver los horarios.');
            }
        }

        function irAWizard(especialidad, fecha, medico) {
            $modalVoz.modal('hide');
            setTimeout(function () {
                abrirWizardDesdeVoz(especialidad, fecha, medico);
            }, 400);
        }

        function buscarSlotsRango(idEspecialidad, fechas, callback) {
            primerDiaConDisponibilidad(fechas, function (fecha, cb) {
                buscarHorariosEspecialidad(idEspecialidad, fecha, function (slots, error) {
                    cb((error || !slots) ? [] : slots);
                });
            }, callback);
        }

        function procesarSolicitudVoz(texto) {
            vozProcesando = false;
            $vozMic.removeClass('escuchando');
            vozContexto = null;
            $vozVer.hide();
            $vozEstado.text('Analizando tu solicitud…');

            var especialidad = especialidadDesdeVoz(texto);
            if (!especialidad) {
                $vozEstado.text(mencionaDoctor(texto) ? 'Falta la especialidad' : 'No se reconoció la especialidad');
                vozMensaje(mencionaDoctor(texto)
                    ? 'Entiendo que quieres cita con un doctor, pero necesito la especialidad. Di algo como: '
                      + '<em>“cita con el Dr. García de Cardiología la semana que viene”</em>.'
                    : 'No reconocí la especialidad en tu frase. Puedes decir, por ejemplo: '
                      + '<em>“cita el lunes que viene de Ginecología”</em>.', true);
                $vozReintentar.show();
                return;
            }

            var rango = rangoDesdeVoz(texto);
            if (!rango) {
                $vozEstado.text('No se reconoció la fecha');
                vozMensaje('Reconocí <strong>' + escapeHtml(especialidad.nombre) + '</strong>, pero no la fecha. '
                    + 'Indica el día, por ejemplo: <em>“mañana”</em>, <em>“el lunes que viene”</em>, '
                    + '<em>“la próxima semana”</em> o <em>“10/11”</em>.', true);
                $vozReintentar.show();
                return;
            }

            $vozEstado.text('Buscando médicos de ' + especialidad.nombre + '…');

            $.ajax({
                url: urlMedicos,
                method: 'GET',
                data: { id_especialidad: especialidad.id },
                dataType: 'json'
            }).done(function (respuesta) {
                if (!vozAbierto) return;

                var medicos = (respuesta && respuesta.success && respuesta.medicos) ? respuesta.medicos : [];
                if (medicos.length === 0) {
                    $vozEstado.text('Sin disponibilidad');
                    vozMensaje('No hay médicos asignados a la especialidad <strong>' + escapeHtml(especialidad.nombre) + '</strong>.');
                    $vozReintentar.show();
                    return;
                }

                var doctor = doctorDesdeVoz(texto, medicos);
                var candidato = nombreDoctorCandidato(texto);
                // "doctor de ginecología" / "doctor pediatra" → la palabra pertenece a la especialidad
                if (candidato) {
                    var palabrasEsp = normalizarVoz(especialidad.nombre).split(' ');
                    for (var pe = 0; pe < palabrasEsp.length; pe++) {
                        var pw = palabrasEsp[pe];
                        if (pw === candidato || (pw.length >= 6 && candidato.length >= 6 && pw.substr(0, 6) === candidato.substr(0, 6))) {
                            candidato = '';
                            break;
                        }
                    }
                }
                var pideDoctor = !doctor && !!candidato;
                var fechas = fechasDelRango(rango);
                var etiqueta = etiquetaRango(rango);

                function sinAgendaDelDoctor() {
                    $vozEstado.text('Buscando horarios con otros médicos…');
                    buscarSlotsRango(especialidad.id, fechas, function (fechaAlt, slotsAlt) {
                        if (!vozAbierto) return;
                        if (!fechaAlt) {
                            $vozEstado.text('Sin disponibilidad');
                            vozMensaje('No hay agenda con <strong>' + escapeHtml(doctor.nombre_completo) + '</strong> ' + etiqueta
                                + ' ni con otros médicos de <strong>' + escapeHtml(especialidad.nombre) + '</strong>. '
                                + 'Prueba con otra fecha.');
                            $vozReintentar.show();
                            return;
                        }
                        $vozEstado.text('Sin agenda con ese médico');
                        vozContexto = { especialidad: especialidad, fecha: fechaAlt };
                        vozMensaje('No hay agenda con <strong>' + escapeHtml(doctor.nombre_completo) + '</strong> ' + etiqueta + '. '
                            + 'En el rango indicado sí están disponibles otros médicos'
                            + (fechaAlt !== fechas[0] ? ' (primeros horarios el <strong>' + fechaLegible(fechaAlt) + '</strong>)' : '') + ':'
                            + htmlListaDoctores(slotsAlt), false);
                        $vozVer.show();
                        $vozReintentar.show();
                    });
                }

                if (doctor) {
                    $vozEstado.text('Buscando agenda de ' + doctor.nombre_completo + '…');
                    primerDiaConDisponibilidad(fechas, function (fecha, cb) {
                        fichasDeMedico(doctor.id, fecha, cb);
                    }, function (fechaDoc, slotsDoc) {
                        if (!vozAbierto) return;
                        if (fechaDoc) {
                            irAWizard(especialidad, fechaDoc, doctor);
                            return;
                        }
                        sinAgendaDelDoctor();
                    });
                    return;
                }

                if (pideDoctor) {
                    $vozEstado.text('Buscando horarios disponibles…');
                    buscarSlotsRango(especialidad.id, fechas, function (fechaAlt, slotsAlt) {
                        if (!vozAbierto) return;
                        if (!fechaAlt) {
                            $vozEstado.text('Sin disponibilidad');
                            vozMensaje('No encontré al doctor <strong>' + escapeHtml(candidato) + '</strong> ni horarios disponibles ' + etiqueta
                                + ' para <strong>' + escapeHtml(especialidad.nombre) + '</strong>.');
                            $vozReintentar.show();
                            return;
                        }
                        $vozEstado.text('Doctor no encontrado');
                        vozContexto = { especialidad: especialidad, fecha: fechaAlt };
                        vozMensaje('No encontré al doctor <strong>' + escapeHtml(candidato) + '</strong> en <strong>' + escapeHtml(especialidad.nombre) + '</strong>. '
                            + 'Sí hay horarios ' + etiqueta
                            + (fechaAlt !== fechas[0] ? ' (primeros el <strong>' + fechaLegible(fechaAlt) + '</strong>)' : '') + ':'
                            + htmlListaDoctores(slotsAlt), false);
                        $vozVer.show();
                        $vozReintentar.show();
                    });
                    return;
                }

                $vozEstado.text('Buscando horarios disponibles…');
                buscarSlotsRango(especialidad.id, fechas, function (fechaGen, slotsGen) {
                    if (!vozAbierto) return;
                    if (!fechaGen) {
                        $vozEstado.text('Sin disponibilidad');
                        vozMensaje('No hay horarios disponibles ' + etiqueta
                            + ' para la especialidad <strong>' + escapeHtml(especialidad.nombre) + '</strong>. '
                            + 'Prueba con otra fecha.');
                        $vozReintentar.show();
                        return;
                    }
                    irAWizard(especialidad, fechaGen, null);
                });
            }).fail(function () {
                if (!vozAbierto) return;
                $vozEstado.text('No se pudo consultar');
                vozMensaje('No pude consultar los horarios en este momento. Intenta de nuevo.', true);
                $vozReintentar.show();
            });
        }

        function escucharVoz() {
            var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) {
                $vozEstado.text('Dictado no disponible');
                vozMensaje('Tu navegador no permite dictado por voz. Usa Chrome, Edge o escribe la solicitud en el asistente.', true);
                return;
            }

            vozOcultarMensaje();
            $vozReintentar.hide();
            $vozVer.hide();
            vozContexto = null;
            $vozTranscripcion.text('');
            vozProcesando = false;
            $vozEstado.text('Escuchando… háblame');
            $vozMic.addClass('escuchando');

            if (reconocimientoVoz) {
                reconocimientoVoz.onend = null;
                reconocimientoVoz.onerror = null;
                reconocimientoVoz.onresult = null;
                try { reconocimientoVoz.abort(); } catch (e) {}
            }

            var rec = new SR();
            reconocimientoVoz = rec;
            rec.lang = 'es-ES';
            rec.interimResults = true;
            rec.continuous = false;
            rec.maxAlternatives = 1;

            var textoFinal = '';

            rec.onresult = function (event) {
                var parcial = '';
                for (var i = event.resultIndex; i < event.results.length; i++) {
                    var t = event.results[i][0].transcript;
                    if (event.results[i].isFinal) textoFinal += t;
                    else parcial += t;
                }
                $vozTranscripcion.text((textoFinal + ' ' + parcial).trim());
            };

            rec.onerror = function (event) {
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    vozProcesando = true;
                    $vozMic.removeClass('escuchando');
                    $vozEstado.text('Permiso de micrófono denegado');
                    vozMensaje('Habilita el permiso del micrófono para usar la cita por voz.', true);
                } else if (event.error !== 'no-speech' && event.error !== 'aborted') {
                    vozProcesando = true;
                    $vozMic.removeClass('escuchando');
                    $vozEstado.text('No se pudo escuchar');
                    vozMensaje('Ocurrió un problema con el micrófono. Intenta de nuevo.', true);
                    $vozReintentar.show();
                }
            };

            rec.onend = function () {
                $vozMic.removeClass('escuchando');
                if (vozProcesando) return;

                var texto = $.trim(textoFinal);
                if (!texto) {
                    $vozEstado.text('No te escuché');
                    vozMensaje('No detecté ninguna frase. Toca el micrófono para intentar de nuevo.', true);
                    $vozReintentar.show();
                    return;
                }

                procesarSolicitudVoz(texto);
            };

            try {
                rec.start();
            } catch (e) {
                $vozEstado.text('No se pudo iniciar');
                vozMensaje('No se pudo iniciar el micrófono. Intenta de nuevo.', true);
                $vozReintentar.show();
            }
        }

        function abrirCitaPorVoz() {
            vozOcultarMensaje();
            $vozReintentar.hide();
            $vozVer.hide();
            vozContexto = null;
            $vozTranscripcion.text('');
            vozProcesando = false;
            $vozEstado.text('Preparando el micrófono…');
            $modalVoz.modal('show');
        }

        $('#btn-cita-voz, .js-cita-voz').on('click', abrirCitaPorVoz);

        $modalVoz.on('shown.bs.modal', function () {
            vozAbierto = true;
            escucharVoz();
        });

        $modalVoz.on('hidden.bs.modal', function () {
            vozAbierto = false;
            if (reconocimientoVoz) {
                reconocimientoVoz.onend = null;
                reconocimientoVoz.onerror = null;
                reconocimientoVoz.onresult = null;
                try { reconocimientoVoz.abort(); } catch (e) {}
                reconocimientoVoz = null;
            }
            $vozMic.removeClass('escuchando');
        });

        $vozMic.on('click', function () {
            escucharVoz();
        });

        $vozReintentar.on('click', function () {
            escucharVoz();
        });

        $vozVer.on('click', function () {
            if (!vozContexto) return;
            var ctx = vozContexto;
            vozContexto = null;
            $modalVoz.modal('hide');
            setTimeout(function () {
                abrirWizardDesdeVoz(ctx.especialidad, ctx.fecha, null);
            }, 400);
        });

        // ----- Detalle / cancelar -----
        var $modalDetalle = $('#modal-detalle-cita');
        var $alertaDetalle = $('#detalle-alerta');
        var $contenidoDetalle = $('#detalle-contenido');
        var $footerDetalle = $('#detalle-footer');
        var citaActualId = 0;
        var citaActualEstado = '';

        $(document).on('click', '.js-detalle-cita', function () {
            var id = parseInt($(this).attr('data-id'), 10) || 0;
            if (id <= 0) return;

            citaActualId = id;
            citaActualEstado = '';
            ocultarError($alertaDetalle);
            $('#detalle-codigo-header').text('Cargando…');
            $contenidoDetalle.html('<div class="cm-detalle-cargando"><i class="fas fa-circle-notch fa-spin fa-2x"></i>'
                + '<p>Cargando información de la cita…</p></div>');
            $footerDetalle.hide();
            $('#btn-cancelar-cita').hide();
            $modalDetalle.modal('show');

            $.ajax({
                url: urlDetalle.replace('{id}', id),
                method: 'POST',
                dataType: 'json',
                data: { _token: token },
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success || !respuesta.cita) {
                    $('#detalle-codigo-header').text('Detalle de la cita');
                    $contenidoDetalle.html('<div class="alert alert-warning mb-0">'
                        + escapeHtml((respuesta && respuesta.message) || 'No se pudo cargar el detalle.') + '</div>');
                    return;
                }

                var c = respuesta.cita;
                // El paciente solo cancela citas PROGRAMADA; el resto de estados
                // los gestionan recepción/otros roles del sistema.
                var cancelable = c.estado === 'PROGRAMADA';
                citaActualEstado = c.estado || '';

                $('#detalle-codigo-header').text(c.codigo ? 'Código ' + c.codigo : 'Detalle de la cita');

                var html = '<div class="cm-detalle-hero">'
                    + '<div class="cm-detalle-hero-fecha">'
                    + '<i class="far fa-calendar-alt"></i>'
                    + '<div>'
                    + '<small>Fecha y hora de la cita</small>'
                    + '<strong>' + escapeHtml(formatearFechaCruda(c.fecha_hora_inicio)) + '</strong>'
                    + '<span class="cm-detalle-horas"><i class="far fa-clock"></i> '
                    + escapeHtml(rangoHoras(c.fecha_hora_inicio, c.fecha_hora_fin))
                    + escapeHtml(duracionTexto(c.fecha_hora_inicio, c.fecha_hora_fin)) + '</span>'
                    + '</div>'
                    + '</div>'
                    + '<div class="cm-detalle-hero-estado">'
                    + '<small>Estado actual</small>'
                    + '<span class="badge ' + claseEstadoCss(c.estado) + '">' + escapeHtml(etiquetaEstado(c.estado)) + '</span>'
                    + '</div>'
                    + '</div>'

                    + '<div class="cm-detalle-seccion">'
                    + '<div class="cm-detalle-titulo"><i class="fas fa-user-md"></i> Datos de la atención</div>'
                    + '<div class="cm-campo-grid">'
                    + campoDetalle('Médico tratante', c.medico, 'Colegiado N.º ' + (c.nro_colegiado || '—'))
                    + campoDetalle('Especialidad', c.especialidad)
                    + campoDetalle('Código de la cita', c.codigo)
                    + campoDetalle('Fecha de registro', formatearFechaCruda(c.fecha_registro))
                    + '</div>'
                    + '</div>'

                    + '<div class="cm-detalle-seccion">'
                    + '<div class="cm-detalle-titulo"><i class="fas fa-stethoscope"></i> Motivo de la consulta</div>'
                    + '<div class="cm-campo-grid">'
                    + campoDetalle('Detalle', c.motivo_consulta || 'El paciente no registró un motivo.', '', true)
                    + campoDetalle('Observaciones del médico', c.observaciones || 'Sin observaciones registradas.', '', true)
                    + '</div>'
                    + '</div>';

                if (!cancelable) {
                    html += '<div class="cm-detalle-nota"><i class="fas fa-info-circle"></i> '
                        + 'Esta cita ya no puede cancelarse desde tu portal; '
                        + 'solo recepción puede reprogramarla o cancelarla.</div>';
                }

                $contenidoDetalle.html(html);
                $footerDetalle.show();

                if (cancelable) {
                    $('#btn-cancelar-cita').show();
                } else {
                    $('#btn-cancelar-cita').hide();
                }
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                $('#detalle-codigo-header').text('Detalle de la cita');
                $contenidoDetalle.html('<div class="alert alert-warning mb-0">'
                    + escapeHtml((r && r.message) || 'No se pudo cargar el detalle.') + '</div>');
            });
        });

        $('#btn-cancelar-cita').on('click', function () {
            if (citaActualId <= 0) return;

            // Solo las citas PROGRAMADA se cancelan desde el portal del paciente.
            if (citaActualEstado !== 'PROGRAMADA') {
                mostrarError($alertaDetalle, 'Solo puedes cancelar citas programadas. Los demás estados los gestiona recepción.');
                return;
            }

            // Cambia un modal por otro: primero cierra el detalle y al terminar
            // abre la confirmación (evita backdrops apilados de Bootstrap).
            $modalDetalle.one('hidden.bs.modal', function () {
                var confirmado = false;

                window.espConfirmar({
                    titulo: 'Cancelar cita programada',
                    mensaje: '¿Deseas cancelar esta cita programada? El turno volverá a estar disponible para otros pacientes.',
                    ayuda: 'Esta acción no se puede deshacer.',
                    btn: 'Sí, cancelar',
                    destructivo: true
                }, function () {
                    confirmado = true;

                    $.ajax({
                        url: urlCancelar.replace('{id}', citaActualId),
                        method: 'POST',
                        dataType: 'json',
                        data: { _token: token },
                        headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function (respuesta) {
                        if (respuesta && respuesta.success) {
                            window.espNotificar('success', respuesta.message || 'Cita cancelada correctamente.');
                            setTimeout(function () { window.location.reload(); }, 1200);
                            return;
                        }
                        volverAlDetalle((respuesta && respuesta.message) || 'No se pudo cancelar la cita.');
                    }).fail(function (xhr) {
                        var r = xhr && xhr.responseJSON;
                        volverAlDetalle((r && r.message) || 'No se pudo cancelar la cita.');
                    });
                });

                // Si el paciente cierra la confirmación sin aceptar, regresa al detalle.
                $('#modal-confirmar').one('hidden.bs.modal', function () {
                    if (!confirmado) {
                        $modalDetalle.modal('show');
                    }
                });
            });

            $modalDetalle.modal('hide');
        });

        /** Reabre el detalle con el error de cancelación (la confirmación ya se cerró o está por cerrar). */
        function volverAlDetalle(mensaje) {
            mostrarError($alertaDetalle, mensaje);

            if ($('#modal-confirmar').is(':visible')) {
                $('#modal-confirmar').one('hidden.bs.modal', function () {
                    $modalDetalle.modal('show');
                });
                return;
            }

            $modalDetalle.modal('show');
        }

        // ----- Voz (Web Speech API) -----
        function iniciarVoz($mic, $input) {
            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                window.espNotificar('error', 'Tu navegador no soporta búsqueda por voz.');
                return;
            }

            var reconocimiento = new SpeechRecognition();
            reconocimiento.lang = 'es-ES';
            reconocimiento.continuous = false;
            reconocimiento.interimResults = false;

            $mic.addClass('escuchando').find('i').removeClass('fa-microphone').addClass('fa-stop-circle');

            reconocimiento.onresult = function (event) {
                var texto = event.results[0][0].transcript;
                $input.val(texto).trigger('input');
                detenerVoz($mic);
            };

            reconocimiento.onerror = function () {
                detenerVoz($mic);
                window.espNotificar('error', 'No se pudo capturar la voz. Intenta de nuevo.');
            };

            reconocimiento.onend = function () {
                detenerVoz($mic);
            };

            reconocimiento.start();
        }

        function detenerVoz($mic) {
            $mic.removeClass('escuchando').find('i').removeClass('fa-stop-circle').addClass('fa-microphone');
        }

        $(document).on('click', '.js-mic', function () {
            var $mic = $(this);
            var targetId = $mic.attr('data-target');

            if (!targetId) return;

            var $input = $('#' + targetId);

            if ($mic.hasClass('escuchando')) {
                detenerVoz($mic);
                return;
            }

            iniciarVoz($mic, $input);
        });

        // Helpers
        function formatearFechaCruda(valor) {
            if (!valor) return '—';
            return valor.substr(8, 2) + '/' + valor.substr(5, 2) + '/' + valor.substr(0, 4) + ' ' + valor.substr(11, 5);
        }

        function rangoHoras(inicio, fin) {
            var h1 = inicio ? inicio.substr(11, 5) : '';
            var h2 = fin ? fin.substr(11, 5) : '';
            if (!h1) return '—';
            return h2 ? h1 + ' a ' + h2 : h1;
        }

        /** Duración de la cita en minutos (p. ej. "· 30 min"). */
        function duracionTexto(inicio, fin) {
            if (!inicio || !fin) return '';
            var a = new Date(inicio.replace(' ', 'T'));
            var b = new Date(fin.replace(' ', 'T'));
            var min = Math.round((b - a) / 60000);
            if (!isFinite(min) || min <= 0) return '';
            if (min < 60) return ' · ' + min + ' min';
            var h = Math.floor(min / 60);
            var m = min % 60;
            return ' · ' + h + ' h' + (m ? ' ' + m + ' min' : '');
        }

        /** Campo visual del modal de detalle: etiqueta + valor (+ subtítulo opcional). */
        function campoDetalle(etiqueta, valor, subtitulo, ancho) {
            var v = (valor === null || valor === undefined || valor === '') ? '—' : String(valor);
            var claseValor = (v === '—') ? ' style="font-style:italic;color:#7d8f96;"' : '';
            return '<div class="cm-campo"' + (ancho ? ' style="grid-column:span 2;"' : '') + '>'
                + '<span class="cm-campo-label">' + escapeHtml(etiqueta) + '</span>'
                + '<span class="cm-campo-valor"' + claseValor + '>' + escapeHtml(v)
                + (subtitulo ? '<small>' + escapeHtml(subtitulo) + '</small>' : '')
                + '</span></div>';
        }

        /** Etiqueta legible de un estado (PROGRAMADA → Programada). */
        function etiquetaEstado(estado) {
            if (!estado) return '—';
            var mapa = {
                'PROGRAMADA': 'Programada',
                'CONFIRMADA': 'Confirmada',
                'REPROGRAMADA': 'Reprogramada',
                'EN_RECEPCION': 'En recepción',
                'EN_ATENCION': 'En atención',
                'ATENDIDA': 'Atendida',
                'CANCELADA': 'Cancelada',
                'INASISTENCIA': 'Inasistencia',
                'LIBRE': 'Libre',
                'BLOQUEADA': 'Bloqueada',
                'OCUPADA': 'Ocupada'
            };
            return mapa[estado] || estado.charAt(0) + estado.slice(1).toLowerCase();
        }

        function formatearFicha(ficha) {
            if (!ficha || !ficha.fecha_hora_inicio) return '';
            return ficha.fecha_hora_inicio.substr(11, 5);
        }

        function claseEstadoCss(estado) {
            var mapa = {
                'PROGRAMADA': 'badge-info',
                'CONFIRMADA': 'badge-primary',
                'REPROGRAMADA': 'badge-warning',
                'EN_RECEPCION': 'badge-secondary',
                'EN_ATENCION': 'badge-secondary',
                'ATENDIDA': 'badge-success',
                'CANCELADA': 'badge-danger',
                'INASISTENCIA': 'badge-dark'
            };
            return mapa[estado] || 'badge-light';
        }

        function escapeHtml(texto) {
            if (texto === null || texto === undefined) return '';
            return String(texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
</script>
