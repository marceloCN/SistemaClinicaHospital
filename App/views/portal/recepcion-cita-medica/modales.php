<?php

/**
 * Modales de recepción: check-in, detalle, registro de cita y
 * reprogramación por bloqueo.
 */
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
?>

<!-- Modal: check-in / detalle de cita por código -->
<div class="modal fade" id="modal-checkin" tabindex="-1" role="dialog" aria-labelledby="modal-checkin-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-checkin-title"><i class="fas fa-sign-in-alt mr-2"></i> Recepción de cita</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="checkin-alerta" class="alert alert-danger rc-alerta-error"></div>
                <div id="checkin-contenido" class="text-center text-muted py-4">
                    Busca una cita por su código.
                </div>
            </div>
            <div class="modal-footer" id="checkin-footer" style="display:none;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning" id="btn-inasistencia-checkin" style="display:none;">
                    <i class="fas fa-user-clock mr-1"></i> Marcar inasistencia
                </button>
                <button type="button" class="btn btn-success" id="btn-confirmar-checkin" style="display:none;">
                    <i class="fas fa-check mr-1"></i> Confirmar recepción
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .rc-detalle .modal-header {
        background: #0f8b84;
        color: #fff;
        padding: 14px 18px;
    }

    .rc-detalle .modal-header .close {
        color: #fff;
        opacity: .8;
    }

    .rc-detalle .modal-body {
        padding: 18px;
    }

    .rc-detalle-linea {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }

    .rc-detalle-linea:last-child {
        margin-bottom: 0;
    }

    .rc-detalle-paciente {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0b2a38;
    }

    .rc-detalle-paciente small {
        display: block;
        font-size: .82rem;
        color: #6b7c83;
        font-weight: 400;
    }

    .rc-detalle-fecha {
        color: #4a5c63;
        font-size: .95rem;
    }

    .rc-detalle-fecha strong {
        color: #0b2a38;
    }

    .rc-detalle-separador {
        border-top: 1px solid #e3eceb;
        margin: 14px 0;
    }

    .rc-detalle-datos {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 24px;
        font-size: .9rem;
    }

    @media (max-width: 576px) {
        .rc-detalle-datos {
            grid-template-columns: 1fr;
        }
    }

    .rc-detalle-datos dt {
        color: #6b7c83;
        font-weight: 600;
        font-size: .78rem;
        text-transform: uppercase;
        margin-bottom: 1px;
    }

    .rc-detalle-datos dd {
        color: #0b2a38;
        font-weight: 500;
        margin-bottom: 0;
    }

    .rc-detalle-bloque {
        margin-top: 14px;
    }

    .rc-detalle-bloque-label {
        color: #6b7c83;
        font-weight: 600;
        font-size: .78rem;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .rc-detalle-bloque-texto {
        color: #0b2a38;
        font-size: .9rem;
        background: #f8fafb;
        border-radius: 6px;
        padding: 10px 12px;
        min-height: 38px;
    }

    .rc-detalle-bloque-texto.rc-detalle-vacio {
        color: #7d8f96;
        font-style: italic;
    }

    /* Check-in / recepción por código */
    .rc-checkin-info {
        background: #fff;
        border: 1px solid #e3eceb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 139, 132, .06);
    }

    .rc-checkin-cabecera {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: #f8fafb;
        border-bottom: 1px solid #e3eceb;
        flex-wrap: wrap;
    }

    .rc-checkin-cabecera small {
        display: block;
        color: #6b7c83;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
    }

    .rc-checkin-cabecera strong {
        color: #0b2a38;
        font-size: 1.05rem;
    }

    .rc-checkin-cabecera code {
        font-size: 1rem;
        background: #e8f4f3;
        color: #0f8b84;
        padding: 2px 8px;
        border-radius: 6px;
    }

    .rc-checkin-datos {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px 16px;
        padding: 14px 16px;
        font-size: .9rem;
    }

    @media (max-width: 576px) {
        .rc-checkin-datos {
            grid-template-columns: 1fr;
        }
    }

    .rc-checkin-datos>div {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .rc-checkin-datos span {
        color: #6b7c83;
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .rc-checkin-datos strong {
        color: #0b2a38;
        font-weight: 600;
    }

    .rc-checkin-datos strong small {
        color: #6b7c83;
        font-weight: 500;
        font-size: .82rem;
    }

    .rc-checkin-advertencia {
        margin-bottom: 14px;
        border-left: 4px solid #e0a800;
    }

    /* Reprogramación por bloqueo */
    .rc-reprogramar-info {
        background: #f8fafb;
        border: 1px solid #e3eceb;
        border-radius: 12px;
        padding: 14px 16px;
        font-size: .95rem;
    }

    .rc-reprogramar-info strong {
        color: #0b2a38;
        font-size: 1.05rem;
    }

    .rc-reprogramar-info small {
        color: #6b7c83;
    }

    .rc-reprogramar-fecha-bloqueo {
        font-size: .82rem;
        color: #856404;
        background: #fff3cd;
        border-left: 4px solid #e0a800;
        padding: 10px 12px;
        border-radius: 6px;
        margin-top: 10px;
    }

    .rc-reprogramar-sin-turnos {
        background: #f8d7da;
        color: #721c24;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: .85rem;
        margin-bottom: 12px;
    }

    .rc-reprogramar-acciones {
        white-space: nowrap;
    }
</style>

<!-- Modal: detalle de cita -->
<div class="modal fade rc-detalle" id="modal-detalle-cita" tabindex="-1" role="dialog" aria-labelledby="modal-detalle-cita-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-detalle-cita-title"><i class="fas fa-file-medical mr-2"></i> Detalle de la cita</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="detalle-alerta" class="alert alert-danger rc-alerta-error"></div>
                <div id="detalle-contenido" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    .cm-buscador-voz {
        position: relative;
    }

    .cm-buscador-voz .cm-mic {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #6b7c83;
        cursor: pointer;
        padding: 6px 8px;
    }

    .cm-buscador-voz .cm-mic:hover {
        color: var(--portal-primary);
    }

    .cm-buscador-voz .cm-mic.escuchando {
        color: #c0392b;
    }

    .cm-lista-opciones {
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid #e0e6e3;
        border-radius: 10px;
        background: #f7f9f8;
    }

    .cm-opcion {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        border-bottom: 1px solid #eef2f1;
        background: transparent;
        padding: 12px 16px;
        cursor: pointer;
    }

    .cm-opcion:last-child {
        border-bottom: 0;
    }

    .cm-opcion:hover,
    .cm-opcion.activa {
        background: #e8f4f3;
    }

    .cm-opcion small {
        display: block;
        color: #6b7c83;
        font-size: 0.82rem;
    }

    .cm-paso-indicador {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .cm-paso-indicador span {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e0e6e3;
        color: #44545c;
        font-weight: 700;
        font-size: 0.85rem;
    }

    .cm-paso-indicador span.activo {
        background: var(--portal-primary);
        color: #fff;
    }

    .cm-paso-titulo {
        text-align: center;
        margin-bottom: 18px;
    }

    .cm-paso-titulo h6 {
        font-weight: 700;
        margin: 0;
    }

    .cm-ficha-card {
        border: 1px solid #e0e6e3;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 10px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .cm-ficha-card:hover,
    .cm-ficha-card.seleccionada {
        border-color: var(--portal-primary);
        background: #f4faf9;
    }

    .cm-alerta-error {
        display: none;
    }

    /* Paso 3: selector de turnos */
    .cm-resumen-seleccion {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        background: #f7f9f8;
        border: 1px solid #e4e9e7;
        border-radius: 14px;
        padding: 14px;
    }

    .cm-resumen-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }

    .cm-resumen-item i {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #fff;
        color: var(--portal-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e4e9e7;
    }

    .cm-resumen-item small {
        display: block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
        font-weight: 700;
    }

    .cm-resumen-item strong {
        display: block;
        color: #0b2a38;
        font-weight: 700;
        line-height: 1.2;
    }

    .cm-fecha-selector {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 14px;
        padding: 14px 16px;
    }

    .cm-fecha-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #44545c;
        margin-bottom: 8px;
    }

    .cm-fecha-input-wrap {
        position: relative;
    }

    .cm-fecha-input-wrap input[type="date"] {
        padding-left: 40px;
        background: #f7f9f8;
        border-color: #e0e6e3;
    }

    .cm-fecha-input-wrap::before {
        content: "\f073";
        font-family: "Font Awesome 5 Free";
        font-weight: 400;
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7c83;
    }

    .cm-fichas-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #44545c;
        margin-bottom: 10px;
    }

    .cm-fichas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 12px;
    }

    .cm-ficha-slot {
        position: relative;
        background: #fff;
        border: 2px solid #e4e9e7;
        border-radius: 14px;
        padding: 14px 10px;
        text-align: center;
        cursor: pointer;
        transition: all .15s ease;
    }

    .cm-ficha-slot:hover {
        border-color: #b8d6d2;
        box-shadow: 0 4px 12px rgba(15, 139, 132, .08);
    }

    .cm-ficha-slot.seleccionada {
        border-color: var(--portal-primary);
        background: #f0f9f8;
        box-shadow: 0 4px 14px rgba(15, 139, 132, .12);
    }

    .cm-ficha-slot .hora {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1;
    }

    .cm-ficha-slot .duracion {
        font-size: 0.72rem;
        color: #6b7c83;
        margin-top: 4px;
    }

    .cm-ficha-slot .badge-disponible {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background: #e7f7ed;
        color: #1e8e3e;
        padding: 3px 8px;
        border-radius: 20px;
        margin-top: 8px;
    }

    .cm-ficha-slot.seleccionada .badge-disponible {
        background: var(--portal-primary);
        color: #fff;
    }

    .cm-ficha-slot .check {
        position: absolute;
        top: 6px;
        right: 8px;
        color: var(--portal-primary);
        font-size: 0.9rem;
        opacity: 0;
        transition: opacity .15s ease;
    }

    .cm-ficha-slot.seleccionada .check {
        opacity: 1;
    }

    .cm-fichas-vacio,
    .cm-fichas-cargando {
        grid-column: 1 / -1;
        text-align: center;
        padding: 32px 20px;
        color: #6b7c83;
        background: #f7f9f8;
        border: 1px dashed #d0d9d5;
        border-radius: 14px;
    }

    .cm-fichas-vacio i,
    .cm-fichas-cargando i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
        color: #a8b5b0;
    }

    .cm-fichas-vacio p {
        margin: 0;
        font-size: 0.92rem;
    }

    @media (max-width: 575.98px) {
        .cm-fichas-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .cm-resumen-seleccion {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Modal: registrar cita -->
<div class="modal fade" id="modal-registrar-cita" tabindex="-1" role="dialog" aria-labelledby="modal-registrar-cita-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-registrar-cita-title"><i class="fas fa-plus mr-2"></i> Registrar cita para paciente</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="registrar-alerta" class="alert alert-danger cm-alerta-error"></div>

                <div class="cm-paso-indicador" id="rc-paso-indicador">
                    <span class="activo" data-paso="1">1</span>
                    <span data-paso="2">2</span>
                    <span data-paso="3">3</span>
                    <span data-paso="4">4</span>
                </div>

                <div class="cm-paso-titulo">
                    <h6 id="rc-paso-titulo">Paso 1: Selecciona al paciente</h6>
                    <small class="text-muted" id="rc-paso-ayuda">Busca por nombre o CI y elige el paciente.</small>
                </div>

                <!-- Paso 1: Paciente -->
                <div id="rc-paso-1" class="cm-paso">
                    <div class="cm-buscador-voz mb-3">
                        <input type="text" class="form-control pr-5" id="buscar-paciente" placeholder="Nombre o CI del paciente" autocomplete="off">
                    </div>
                    <div class="cm-lista-opciones mb-3" id="resultados-paciente" style="display:none;"></div>

                    <div id="paciente-seleccionado" class="alert alert-light border" style="display:none;">
                        <strong id="paciente-nombre"></strong>
                        <small id="paciente-ci"></small>
                        <button type="button" class="close" id="btn-limpiar-paciente"><span aria-hidden="true">&times;</span></button>
                    </div>

                    <button type="button" class="btn btn-primary" id="btn-ir-especialidad" disabled>Siguiente</button>
                </div>

                <!-- Paso 2: Especialidad -->
                <div id="rc-paso-2" class="cm-paso" style="display:none;">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-link p-0 js-rc-volver" data-paso="1"><i class="fas fa-arrow-left"></i> Volver al paciente</button>
                    </div>
                    <div class="cm-buscador-voz mb-3">
                        <input type="text" class="form-control pr-5" id="rc-buscar-especialidad" placeholder="Buscar especialidad..." autocomplete="off">
                        <button type="button" class="cm-mic js-mic" data-target="rc-buscar-especialidad"><i class="fas fa-microphone"></i></button>
                    </div>
                    <div class="cm-lista-opciones mb-3" id="rc-lista-especialidades">
                        <?php foreach ($catalogoEspecialidades as $esp) { ?>
                            <button type="button" class="cm-opcion js-rc-elegir-especialidad"
                                data-id="<?= (int) $esp['id'] ?>"
                                data-nombre="<?= e((string) $esp['nombre']) ?>">
                                <strong><?= e((string) $esp['nombre']) ?></strong>
                            </button>
                        <?php } ?>
                    </div>
                    <button type="button" class="btn btn-primary" id="btn-ir-medico" disabled>Siguiente</button>
                </div>

                <!-- Paso 3: Médico -->
                <div id="rc-paso-3" class="cm-paso" style="display:none;">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-link p-0 js-rc-volver" data-paso="2"><i class="fas fa-arrow-left"></i> Volver a especialidades</button>
                    </div>
                    <div class="cm-buscador-voz mb-3">
                        <input type="text" class="form-control pr-5" id="rc-buscar-medico" placeholder="Buscar médico..." autocomplete="off">
                        <button type="button" class="cm-mic js-mic" data-target="rc-buscar-medico"><i class="fas fa-microphone"></i></button>
                    </div>
                    <div class="cm-lista-opciones mb-3" id="rc-lista-medicos"></div>
                    <button type="button" class="btn btn-primary" id="btn-ir-ficha" disabled>Siguiente</button>
                </div>

                <!-- Paso 4: Turno -->
                <div id="rc-paso-4" class="cm-paso" style="display:none;">
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-link p-0 js-rc-volver" data-paso="3"><i class="fas fa-arrow-left"></i> Volver a médicos</button>
                    </div>

                    <div class="cm-resumen-seleccion mb-3">
                        <div class="cm-resumen-item">
                            <i class="fas fa-stethoscope"></i>
                            <div>
                                <small>Especialidad</small>
                                <strong id="rc-resumen-especialidad">—</strong>
                            </div>
                        </div>
                        <div class="cm-resumen-item">
                            <i class="fas fa-user-md"></i>
                            <div>
                                <small>Médico</small>
                                <strong id="rc-resumen-medico">—</strong>
                            </div>
                        </div>
                        <div class="cm-resumen-item">
                            <i class="fas fa-calendar-day"></i>
                            <div>
                                <small>Fecha</small>
                                <strong id="rc-resumen-fecha">—</strong>
                            </div>
                        </div>
                    </div>

                    <div class="cm-fecha-selector mb-3">
                        <label for="rc-cita-fecha" class="cm-fecha-label"><i class="far fa-calendar-alt mr-1"></i> Selecciona la fecha</label>
                        <div class="cm-fecha-input-wrap">
                            <input type="date" class="form-control" id="rc-cita-fecha" min="">
                        </div>
                    </div>

                    <label class="cm-fichas-label"><i class="far fa-clock mr-1"></i> Turnos disponibles</label>
                    <div id="rc-lista-fichas" class="cm-fichas-grid mb-3">
                        <div class="cm-fichas-vacio">
                            <i class="far fa-calendar"></i>
                            <p>Selecciona una fecha para ver los turnos disponibles.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="rc-cita-motivo">Motivo de la consulta *</label>
                        <textarea class="form-control" id="rc-cita-motivo" rows="2" maxlength="1000" placeholder="Describe brevemente el motivo de la consulta..."></textarea>
                        <small class="form-text text-muted text-right"><span id="rc-contador-motivo">0</span>/1000</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-registro" style="display:none;">
                    <i class="fas fa-check mr-1"></i> Registrar cita
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: reprogramar por bloqueo -->
<div class="modal fade" id="modal-reprogramar" tabindex="-1" role="dialog" aria-labelledby="modal-reprogramar-title" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-reprogramar-title"><i class="fas fa-calendar-alt mr-2"></i> Reprogramar citas afectadas</h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="reprogramar-alerta" class="alert alert-danger rc-alerta-error"></div>
                <div id="reprogramar-info" class="rc-reprogramar-info mb-3"></div>

                <div class="row mb-3 align-items-end">
                    <div class="col-md-4">
                        <label for="reprogramar-fecha">Fecha de destino</label>
                        <input type="date" class="form-control" id="reprogramar-fecha">
                        <small class="form-text text-muted">Debe ser posterior al día del bloqueo.</small>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary" id="btn-cargar-destinos">
                            <i class="fas fa-search mr-1"></i> Cargar turnos libres
                        </button>
                    </div>
                </div>

                <div id="reprogramar-sin-turnos" class="rc-reprogramar-sin-turnos" style="display:none;">
                    <i class="fas fa-info-circle mr-1"></i> No hay turnos disponibles para este médico en la fecha seleccionada.
                    Puedes <strong>dar de baja</strong> las citas afectadas usando el botón <span class="badge badge-danger">Cancelar</span>.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Hora afectada</th>
                                <th>Paciente</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Nuevo turno</th>
                                <th>Motivo</th>
                                <th class="rc-reprogramar-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-reprogramar"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        var token = '<?= csrf_token() ?>';
        var urlBuscarCodigo = '<?= route('portal_recepcion_cita_buscar') ?>';
        var urlRecepcionar = '<?= route('portal_recepcion_cita_recepcionar') ?>';
        var urlCambiarEstado = '<?= route('portal_recepcion_cita_cambiar_estado') ?>';
        var urlDetalle = '<?= route('portal_cita_detalle', ['id' => '{id}']) ?>';
        var urlPacientes = '<?= route('portal_recepcion_cita_pacientes') ?>';
        // El wizard de registro reutiliza los endpoints del portal del paciente
        // porque devuelven el id de servicio_medico necesario para buscar fichas.
        var urlMedicos = '<?= route('portal_cita_medicos') ?>';
        var urlFichasPortal = '<?= route('portal_cita_fichas') ?>';
        var urlGenerarCita = '<?= route('portal_cita_generar') ?>';
        var urlBloqueosAfectadas = '<?= route('portal_recepcion_cita_afectadas', ['id' => '{id}']) ?>';
        var urlReprogramar = '<?= route('portal_recepcion_cita_reprogramar') ?>';

        function mostrarError($el, mensaje) {
            $el.text(mensaje || 'Ocurrió un error.').show();
        }

        function ocultarError($el) {
            $el.hide().text('');
        }

        function formatearFechaCruda(valor) {
            if (!valor) return '—';
            return valor.substr(8, 2) + '/' + valor.substr(5, 2) + '/' + valor.substr(0, 4) + ' ' + valor.substr(11, 5);
        }

        function escapeHtml(texto) {
            if (texto === null || texto === undefined) return '';
            return String(texto).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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

        function rangoHoras(inicio, fin) {
            var h1 = inicio ? inicio.substr(11, 5) : '';
            var h2 = fin ? fin.substr(11, 5) : '';
            if (!h1) return '—';
            return h2 ? h1 + ' a ' + h2 : h1;
        }

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

        function bloqueTexto(etiqueta, valor) {
            var vacio = (valor === null || valor === undefined || valor === '');
            return '<div class="rc-detalle-bloque">' +
                '<div class="rc-detalle-bloque-label">' + escapeHtml(etiqueta) + '</div>' +
                '<div class="rc-detalle-bloque-texto' + (vacio ? ' rc-detalle-vacio' : '') + '">' + escapeHtml(vacio ? 'Sin información' : String(valor)) + '</div>' +
                '</div>';
        }

        // ---------- Check-in ----------
        var $modalCheckin = $('#modal-checkin');
        var $alertaCheckin = $('#checkin-alerta');
        var $contenidoCheckin = $('#checkin-contenido');
        var $footerCheckin = $('#checkin-footer');
        var $btnConfirmarCheckin = $('#btn-confirmar-checkin');
        var $btnInasistenciaCheckin = $('#btn-inasistencia-checkin');
        var codigoCheckin = '';
        var idCitaCheckin = 0;

        $btnConfirmarCheckin.on('click', function() {
            if (!codigoCheckin) return;
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: urlRecepcionar,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    codigo: codigoCheckin
                },
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                $btn.prop('disabled', false);
                if (respuesta && respuesta.success) {
                    $modalCheckin.modal('hide');
                    window.espNotificar('success', respuesta.message || 'Cita recepcionada correctamente.');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                    return;
                }
                mostrarError($alertaCheckin, (respuesta && respuesta.message) || 'No se pudo recepcionar la cita.');
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaCheckin, (r && r.message) || 'No se pudo recepcionar la cita.');
            });
        });

        $btnInasistenciaCheckin.on('click', function() {
            if (idCitaCheckin <= 0) return;
            console.log(idCitaCheckin);
            if (!confirm('¿Marcar esta cita como INASISTENCIA? La ficha/turno conservará su estado actual.')) return;
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: urlCambiarEstado,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id_cita: idCitaCheckin,
                    estado: 'INASISTENCIA'
                },
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                $btn.prop('disabled', false);
                if (respuesta && respuesta.success) {
                    $modalCheckin.modal('hide');
                    window.espNotificar('success', respuesta.message || 'Estado actualizado correctamente.');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                    return;
                }
                mostrarError($alertaCheckin, (respuesta && respuesta.message) || 'No se pudo cambiar el estado.');
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaCheckin, (r && r.message) || 'No se pudo cambiar el estado.');
            });
        });

        function abrirCheckin(codigo) {
            codigo = $.trim(codigo || '');
            ocultarError($alertaCheckin);
            $footerCheckin.hide();
            $btnConfirmarCheckin.hide();
            $btnInasistenciaCheckin.hide();
            idCitaCheckin = 0;
            $modalCheckin.modal('show');

            if (!codigo) {
                $contenidoCheckin.html('<p class="text-muted">Ingresa el código de la cita.</p>');
                return;
            }

            $contenidoCheckin.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>');

            $.ajax({
                url: urlBuscarCodigo,
                method: 'GET',
                data: {
                    codigo: codigo
                },
                dataType: 'json'
            }).done(function(respuesta) {
                if (!respuesta || !respuesta.success) {
                    $contenidoCheckin.html('<div class="alert alert-warning">' + escapeHtml((respuesta && respuesta.message) || 'No se encontró la cita.') + '</div>');
                    return;
                }

                var c = respuesta.cita;

                codigoCheckin = c.codigo;
                idCitaCheckin = parseInt(c.id, 10) || 0;

                var fechaCita = (c.fecha || '').substr(0, 10);
                var ahora = new Date();
                var fechaHoy = ahora.getFullYear() + '-' + ('0' + (ahora.getMonth() + 1)).slice(-2) + '-' + ('0' + ahora.getDate()).slice(-2);
                var esHoy = fechaCita === fechaHoy;
                var advertencias = '';
                if (!esHoy) {
                    advertencias += '<div class="alert alert-warning alert-dismissible rc-checkin-advertencia">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>' +
                        '<strong><i class="fas fa-exclamation-triangle mr-1"></i> Fecha diferente a hoy.</strong> ' +
                        'La cita corresponde al <strong>' + escapeHtml(formatearFechaCruda(c.fecha_hora_inicio).split(' ')[0]) + '</strong>. ' +
                        'Puedes recepcionarla solo en caso de emergencia.' +
                        '</div>';
                }

                var esTarde = false;
                if (esHoy && c.fecha_hora_fin) {
                    var fechaFin = String(c.fecha_hora_fin).replace(' ', 'T').replace(/([+-]\d{2})$/, '$1:00');
                    var fin = new Date(fechaFin);
                    esTarde = !isNaN(fin.getTime()) && fin.getTime() < ahora.getTime();
                }


                if (esTarde) {
                    advertencias += '<div class="alert alert-warning alert-dismissible rc-checkin-advertencia">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>' +
                        '<strong><i class="fas fa-clock mr-1"></i> Horario de cita finalizado.</strong> ' +
                        'El turno terminó a las <strong>' + escapeHtml(String(c.fecha_hora_fin).substr(11, 5)) + '</strong>. ' +
                        'Puedes recepcionar de todos modos o marcar inasistencia.' +
                        '</div>';
                }

                $contenidoCheckin.html(
                    advertencias +
                    '<div class="rc-checkin-info">' +
                    '<div class="rc-checkin-cabecera">' +
                    '<div>' +
                    '<small>Código de cita</small>' +
                    '<strong><code>' + escapeHtml(c.codigo) + '</code></strong>' +
                    '</div>' +
                    '<span class="badge ' + claseEstadoCss(c.estado) + '">' + escapeHtml(c.estado) + '</span>' +
                    '</div>' +
                    '<div class="rc-checkin-datos">' +
                    '<div><span>Paciente</span><strong>' + escapeHtml(c.paciente) + ' <small>CI ' + escapeHtml(c.paciente_ci) + '</small></strong></div>' +
                    '<div><span>Fecha y hora</span><strong>' + escapeHtml(formatearFechaCruda(c.fecha_hora_inicio)) + '</strong></div>' +
                    '<div><span>Médico</span><strong>' + escapeHtml(c.medico) + '</strong></div>' +
                    '<div><span>Especialidad</span><strong>' + escapeHtml(c.especialidad) + '</strong></div>' +
                    '</div>' +
                    '</div>'
                );

                $footerCheckin.show();

                if (respuesta.puede_recepcionar) {
                    $btnConfirmarCheckin.show();
                    $btnInasistenciaCheckin.toggle(esTarde);
                } else {
                    $btnConfirmarCheckin.hide();
                    $btnInasistenciaCheckin.hide();
                    if (respuesta.recepcionada) {
                        $contenidoCheckin.append('<div class="alert alert-info mt-3 mb-0">Esta cita ya fue recepcionada.</div>');
                    }
                }
            }).fail(function(xhr) {
                var r = xhr && xhr.responseJSON;
                $contenidoCheckin.html('<div class="alert alert-warning">' + escapeHtml((r && r.message) || 'No se pudo buscar la cita.') + '</div>');
            });
        }

        $('#btn-buscar-codigo').on('click', function() {
            abrirCheckin($('#checkin-codigo').val());
        });
        $('#checkin-codigo').on('keypress', function(e) {
            if (e.which === 13) {
                abrirCheckin($(this).val());
            }
        });
        $(document).on('click', '.js-recepcionar-cita', function() {
            abrirCheckin($(this).attr('data-codigo'));
        });



        // ---------- Detalle ----------
        var $modalDetalle = $('#modal-detalle-cita');
        var $alertaDetalle = $('#detalle-alerta');
        var $contenidoDetalle = $('#detalle-contenido');

        $(document).on('click', '.js-detalle-cita', function() {
            var id = parseInt($(this).attr('data-id'), 10) || 0;
            if (id <= 0) return;
            ocultarError($alertaDetalle);
            $contenidoDetalle.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
            $modalDetalle.modal('show');

            $.ajax({
                url: urlDetalle.replace('{id}', id),
                method: 'POST',
                data: {
                    _token: token
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                if (!respuesta || !respuesta.success || !respuesta.cita) {
                    $('#modal-detalle-cita-title').html('<i class="fas fa-file-medical mr-2"></i> Detalle de la cita');
                    $contenidoDetalle.html('<div class="alert alert-warning mb-0">' +
                        escapeHtml((respuesta && respuesta.message) || 'No se pudo cargar el detalle.') + '</div>');
                    return;
                }
                var c = respuesta.cita;
                $('#modal-detalle-cita-title').html('<i class="fas fa-file-medical mr-2"></i> ' + escapeHtml(c.codigo ? 'Código ' + c.codigo : 'Detalle de la cita'));

                var html = '<div class="rc-detalle-linea">' +
                    '<div class="rc-detalle-paciente">' +
                    escapeHtml(c.paciente || 'Paciente no registrado') +
                    '<small>CI ' + escapeHtml(c.paciente_ci || '—') + '</small>' +
                    '</div>' +
                    '<span class="badge ' + claseEstadoCss(c.estado) + '">' + escapeHtml(etiquetaEstado(c.estado)) + '</span>' +
                    '</div>'

                    +
                    '<div class="rc-detalle-linea">' +
                    '<div class="rc-detalle-fecha">' +
                    '<i class="far fa-calendar-alt mr-1 text-secondary"></i> <strong>' + escapeHtml(formatearFechaCruda(c.fecha_hora_inicio)) + '</strong>' +
                    ' <span class="text-muted">(' + escapeHtml(rangoHoras(c.fecha_hora_inicio, c.fecha_hora_fin)) + escapeHtml(duracionTexto(c.fecha_hora_inicio, c.fecha_hora_fin)) + ')</span>' +
                    '</div>' +
                    '</div>'
                    +
                    '<div class="rc-detalle-separador"></div>'
                    +
                    '<dl class="rc-detalle-datos">' +
                    '<div><dt>Médico</dt><dd>' + escapeHtml(c.medico || '—') + '</dd></div>' +
                    '<div><dt>Especialidad</dt><dd>' + escapeHtml(c.especialidad || '—') + '</dd></div>' +
                    '<div><dt>Código</dt><dd>' + escapeHtml(c.codigo || '—') + '</dd></div>' +
                    '<div><dt>Registrado</dt><dd>' + escapeHtml(formatearFechaCruda(c.fecha_registro)) + '</dd></div>' +
                    '</dl>'

                    +
                    bloqueTexto('Motivo de la consulta', c.motivo_consulta) +
                    bloqueTexto('Observaciones', c.observaciones);

                $contenidoDetalle.html(html);
            }).fail(function(xhr) {
                var r = xhr && xhr.responseJSON;
                $contenidoDetalle.html('<div class="alert alert-warning">' + escapeHtml((r && r.message) || 'No se pudo cargar el detalle.') + '</div>');
            });
        });

        // ---------- Registro de cita ----------
        var $modalRegistrar = $('#modal-registrar-cita');
        var $alertaRegistrar = $('#registrar-alerta');

        var registro = {
            paso: 1,
            idPaciente: 0,
            idEspecialidad: 0,
            especialidad: '',
            idMedico: 0,
            medico: '',
            idFicha: 0,
            fichaTexto: ''
        };

        function formatearFicha(ficha) {
            return (ficha.fecha_hora_inicio || '').substr(11, 5);
        }

        function setMinFecha() {
            var hoy = new Date();
            var mes = ('0' + (hoy.getMonth() + 1)).slice(-2);
            var dia = ('0' + hoy.getDate()).slice(-2);
            $('#rc-cita-fecha').attr('min', hoy.getFullYear() + '-' + mes + '-' + dia);
        }

        function cambiarPaso(nuevo) {
            registro.paso = nuevo;
            $('.cm-paso').hide();
            $('#rc-paso-' + nuevo).show();
            $('#rc-paso-indicador span').removeClass('activo').filter('[data-paso="' + nuevo + '"]').addClass('activo');

            var titulos = {
                1: ['Paso 1: Selecciona al paciente', 'Busca por nombre o CI y elige el paciente.'],
                2: ['Paso 2: Elige la especialidad', 'Busca o selecciona la especialidad en la que necesita atención.'],
                3: ['Paso 3: Elige el médico', 'Médicos disponibles para ' + registro.especialidad],
                4: ['Paso 4: Elige fecha y hora', 'Turnos disponibles con ' + registro.medico]
            };

            $('#rc-paso-titulo').text(titulos[nuevo][0]);
            $('#rc-paso-ayuda').text(titulos[nuevo][1]);

            if (nuevo === 4) {
                $('#btn-confirmar-registro').show();
                $('#rc-resumen-especialidad').text(registro.especialidad || '—');
                $('#rc-resumen-medico').text(registro.medico || '—');
                actualizarResumenFecha();
            } else {
                $('#btn-confirmar-registro').hide();
            }
        }

        function actualizarResumenFecha() {
            var fecha = $('#rc-cita-fecha').val();
            if (!fecha) {
                $('#rc-resumen-fecha').text('—');
                return;
            }
            var p = fecha.split('-');
            $('#rc-resumen-fecha').text(p[2] + '/' + p[1] + '/' + p[0]);
        }

        function filtrarLista(inputId, listaId, selectorItems) {
            var texto = $.trim($('#' + inputId).val()).toLowerCase();
            $('#' + listaId).find(selectorItems).each(function() {
                var $item = $(this);
                $item.toggle(($item.text() || '').toLowerCase().indexOf(texto) !== -1);
            });
        }

        function resetRegistro() {
            registro = {
                paso: 1,
                idPaciente: 0,
                idEspecialidad: 0,
                especialidad: '',
                idMedico: 0,
                medico: '',
                idFicha: 0,
                fichaTexto: ''
            };
            $('#buscar-paciente, #rc-buscar-especialidad, #rc-buscar-medico, #rc-cita-fecha, #rc-cita-motivo').val('');
            $('#resultados-paciente').hide().empty();
            $('#paciente-seleccionado').hide();
            $('#btn-ir-especialidad, #btn-ir-medico, #btn-ir-ficha').prop('disabled', true);
            $('#rc-lista-medicos, #rc-lista-fichas').empty();
            $('.js-rc-elegir-especialidad, .js-rc-elegir-medico').removeClass('activa');
            $('#rc-contador-motivo').text('0');
            cambiarPaso(1);
            filtrarLista('rc-buscar-especialidad', 'rc-lista-especialidades', '.js-rc-elegir-especialidad');
        }

        $('#btn-registrar-cita').on('click', function() {
            ocultarError($alertaRegistrar);
            resetRegistro();
            setMinFecha();
            $modalRegistrar.modal('show');
        });

        var timeoutPaciente = null;
        $('#buscar-paciente').on('input', function() {
            clearTimeout(timeoutPaciente);
            var texto = $.trim($(this).val());
            if (texto.length < 2) {
                $('#resultados-paciente').hide().empty();
                return;
            }
            timeoutPaciente = setTimeout(function() {
                buscarPacientes(texto);
            }, 300);
        });

        function buscarPacientes(texto) {
            $.ajax({
                url: urlPacientes,
                method: 'GET',
                data: {
                    texto: texto
                },
                dataType: 'json'
            }).done(function(respuesta) {
                if (!respuesta || !respuesta.success || !respuesta.pacientes || respuesta.pacientes.length === 0) {
                    $('#resultados-paciente').hide().empty();
                    return;
                }
                var html = '';
                $.each(respuesta.pacientes, function(_, p) {
                    html += '<button type="button" class="cm-opcion js-elegir-paciente" data-id="' + p.id + '" data-nombre="' + escapeHtml(p.nombre_completo) + '" data-ci="' + escapeHtml(p.ci) + '">' +
                        '<strong>' + escapeHtml(p.nombre_completo) + '</strong>' +
                        '<small>CI ' + escapeHtml(p.ci) + '</small>' +
                        '</button>';
                });
                $('#resultados-paciente').html(html).show();
            }).fail(function() {
                $('#resultados-paciente').hide().empty();
            });
        }

        $(document).on('click', '.js-elegir-paciente', function() {
            var $btn = $(this);
            registro.idPaciente = parseInt($btn.attr('data-id'), 10) || 0;
            $('#paciente-nombre').text($btn.attr('data-nombre'));
            $('#paciente-ci').text('CI ' + $btn.attr('data-ci'));
            $('#paciente-seleccionado').show();
            $('#resultados-paciente').hide().empty();
            $('#buscar-paciente').val('');
            $('#btn-ir-especialidad').prop('disabled', registro.idPaciente <= 0);
        });

        $('#btn-limpiar-paciente').on('click', function() {
            registro.idPaciente = 0;
            $('#paciente-seleccionado').hide();
            $('#btn-ir-especialidad').prop('disabled', true);
        });

        $('#btn-ir-especialidad').on('click', function() {
            if (registro.idPaciente <= 0) return;
            cambiarPaso(2);
        });

        $('#rc-buscar-especialidad').on('input', function() {
            filtrarLista('rc-buscar-especialidad', 'rc-lista-especialidades', '.js-rc-elegir-especialidad');
        });

        $(document).on('click', '.js-rc-elegir-especialidad', function() {
            var $btn = $(this);
            registro.idEspecialidad = parseInt($btn.attr('data-id'), 10) || 0;
            registro.especialidad = $.trim($btn.attr('data-nombre'));
            $('.js-rc-elegir-especialidad').removeClass('activa');
            $btn.addClass('activa');
            $('#btn-ir-medico').prop('disabled', registro.idEspecialidad <= 0);
        });

        $('#btn-ir-medico').on('click', function() {
            if (registro.idEspecialidad <= 0) return;
            cambiarPaso(3);
            cargarMedicosRegistro();
        });

        function cargarMedicosRegistro() {
            $('#rc-lista-medicos').html('<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Cargando médicos...</div>');
            $.ajax({
                url: urlMedicos,
                method: 'GET',
                data: {
                    id_especialidad: registro.idEspecialidad
                },
                dataType: 'json'
            }).done(function(respuesta) {
                if (!respuesta || !respuesta.success || !respuesta.medicos || respuesta.medicos.length === 0) {
                    $('#rc-lista-medicos').html('<div class="text-center text-muted py-3">No hay médicos disponibles para esta especialidad.</div>');
                    return;
                }
                var html = '';
                $.each(respuesta.medicos, function(_, m) {
                    html += '<button type="button" class="cm-opcion js-rc-elegir-medico" data-id="' + m.id + '" data-nombre="' + escapeHtml(m.nombre_completo) + '">' +
                        '<strong>' + escapeHtml(m.nombre_completo) + '</strong>' +
                        '<small>CI ' + escapeHtml(m.ci) + ' · Colegiado ' + escapeHtml(m.nro_colegiado) + '</small>' +
                        '</button>';
                });
                $('#rc-lista-medicos').html(html);
                filtrarLista('rc-buscar-medico', 'rc-lista-medicos', '.js-rc-elegir-medico');
            }).fail(function() {
                $('#rc-lista-medicos').html('<div class="text-center text-danger py-3">No se pudieron cargar los médicos.</div>');
            });
        }

        $('#rc-buscar-medico').on('input', function() {
            filtrarLista('rc-buscar-medico', 'rc-lista-medicos', '.js-rc-elegir-medico');
        });

        $(document).on('click', '.js-rc-elegir-medico', function() {
            var $btn = $(this);
            registro.idMedico = parseInt($btn.attr('data-id'), 10) || 0;
            registro.medico = $.trim($btn.attr('data-nombre'));
            $('.js-rc-elegir-medico').removeClass('activa');
            $btn.addClass('activa');
            $('#btn-ir-ficha').prop('disabled', registro.idMedico <= 0);
        });

        $('#btn-ir-ficha').on('click', function() {
            if (registro.idMedico <= 0) return;
            cambiarPaso(4);
            $('#rc-cita-fecha').val('').trigger('change');
            $('#rc-lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar"></i><p>Selecciona una fecha para ver los turnos disponibles.</p></div>');
            registro.idFicha = 0;
            registro.fichaTexto = '';
        });

        $(document).on('click', '.js-rc-volver', function() {
            var paso = parseInt($(this).attr('data-paso'), 10) || 1;
            cambiarPaso(paso);
            if (paso === 3 && $('#rc-lista-medicos .js-rc-elegir-medico').length === 0) {
                cargarMedicosRegistro();
            }
        });

        $('#rc-cita-fecha').on('change', function() {
            var fecha = $.trim($(this).val());
            ocultarError($alertaRegistrar);
            actualizarResumenFecha();
            registro.idFicha = 0;
            registro.fichaTexto = '';
            $('.cm-ficha-slot').removeClass('seleccionada');

            if (!fecha) {
                $('#rc-lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar"></i><p>Selecciona una fecha para ver los turnos disponibles.</p></div>');
                return;
            }

            $('#rc-lista-fichas').html('<div class="cm-fichas-cargando"><i class="fas fa-spinner fa-spin"></i><p>Cargando turnos...</p></div>');

            $.ajax({
                url: urlFichasPortal,
                method: 'GET',
                data: {
                    id_medico: registro.idMedico,
                    id_especialidad: registro.idEspecialidad,
                    fecha: fecha
                },
                dataType: 'json'
            }).done(function(respuesta) {
                if (!respuesta || !respuesta.success) {
                    $('#rc-lista-fichas').html('<div class="cm-fichas-vacio"><i class="fas fa-exclamation-circle text-danger"></i><p>' + escapeHtml(respuesta && respuesta.message ? respuesta.message : 'No se pudieron cargar los turnos.') + '</p></div>');
                    return;
                }
                if (!respuesta.fichas || respuesta.fichas.length === 0) {
                    $('#rc-lista-fichas').html('<div class="cm-fichas-vacio"><i class="far fa-calendar-times"></i><p>No hay turnos disponibles para esa fecha.</p></div>');
                    return;
                }
                var html = '';
                $.each(respuesta.fichas, function(_, f) {
                    var hora = formatearFicha(f);
                    var fin = (f.fecha_hora_fin || '').substr(11, 5);
                    html += '<div class="cm-ficha-slot" data-id="' + f.id + '" data-texto="' + escapeHtml(hora) + '">' +
                        '<i class="fas fa-check-circle check"></i>' +
                        '<div class="hora">' + escapeHtml(hora) + '</div>' +
                        '<div class="duracion">hasta ' + escapeHtml(fin) + '</div>' +
                        '<span class="badge-disponible">Disponible</span>' +
                        '</div>';
                });
                $('#rc-lista-fichas').html(html);
            }).fail(function() {
                $('#rc-lista-fichas').html('<div class="cm-fichas-vacio"><i class="fas fa-exclamation-circle text-danger"></i><p>No se pudieron cargar los turnos.</p></div>');
            });
        });

        $(document).on('click', '.cm-ficha-slot', function() {
            $('.cm-ficha-slot').removeClass('seleccionada');
            var $slot = $(this).addClass('seleccionada');
            registro.idFicha = parseInt($slot.attr('data-id'), 10) || 0;
            registro.fichaTexto = $slot.attr('data-texto') || '';
        });

        $('#rc-cita-motivo').on('input', function() {
            $('#rc-contador-motivo').text($(this).val().length);
        });

        $('#btn-confirmar-registro').on('click', function() {
            ocultarError($alertaRegistrar);

            if (registro.idFicha <= 0) {
                mostrarError($alertaRegistrar, 'Selecciona un turno disponible.');
                return;
            }

            var motivo = $.trim($('#rc-cita-motivo').val());
            if (!motivo) {
                mostrarError($alertaRegistrar, 'Describe el motivo de la consulta.');
                return;
            }

            var $btn = $(this).prop('disabled', true);
            var datos = {
                id_ficha_agenda: registro.idFicha,
                motivo_consulta: motivo,
                id_paciente: registro.idPaciente
            }
            console.log(datos);
            $.ajax({
                url: urlGenerarCita,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id_ficha_agenda: registro.idFicha,
                    motivo_consulta: motivo,
                    id_paciente: registro.idPaciente
                },
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                $btn.prop('disabled', false);
                if (respuesta && respuesta.success) {
                    $modalRegistrar.modal('hide');
                    window.espNotificar('success', respuesta.message || 'Cita registrada correctamente.');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                    return;
                }
                mostrarError($alertaRegistrar, (respuesta && respuesta.message) || 'No se pudo registrar la cita.');
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaRegistrar, (r && r.message) || 'No se pudo registrar la cita.');
            });
        });

        // ---------- Reprogramación por bloqueo ----------
        var $modalReprogramar = $('#modal-reprogramar');
        var $alertaReprogramar = $('#reprogramar-alerta');
        var bloqueoActual = null;
        var citasAfectadas = [];
        var fichasDestino = [];

        $(document).on('click', '.js-ver-bloqueo', function() {
            var id = parseInt($(this).attr('data-id'), 10) || 0;
            if (id <= 0) return;
            ocultarError($alertaReprogramar);
            $('#reprogramar-fecha').val('').removeAttr('min');
            $('#reprogramar-sin-turnos').hide();
            $('#tabla-reprogramar').html('<tr><td colspan="7" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
            $modalReprogramar.modal('show');

            $.ajax({
                url: urlBloqueosAfectadas.replace('{id}', id),
                method: 'GET',
                dataType: 'json'
            }).done(function(respuesta) {
                if (!respuesta || !respuesta.success) {
                    $('#tabla-reprogramar').html('<tr><td colspan="7" class="text-center text-danger">' + escapeHtml((respuesta && respuesta.message) || 'No se pudieron cargar las citas.') + '</td></tr>');
                    return;
                }
                bloqueoActual = respuesta.bloqueo;
                citasAfectadas = respuesta.citas || [];

                var fechaInicioBloqueo = (bloqueoActual && bloqueoActual.inicio_crudo) ? bloqueoActual.inicio_crudo.substr(0, 10) : '';
                var fechaMinDestino = '';
                if (fechaInicioBloqueo && /^\d{4}-\d{2}-\d{2}$/.test(fechaInicioBloqueo)) {
                    var d = new Date(fechaInicioBloqueo + 'T00:00:00');
                    d.setDate(d.getDate() + 1);
                    fechaMinDestino = d.toISOString().substr(0, 10);
                }
                $('#reprogramar-fecha').attr('min', fechaMinDestino);

                $('#reprogramar-info').html(
                    '<strong>' + escapeHtml(bloqueoActual.nombre_completo) + '</strong> — ' + escapeHtml(bloqueoActual.especialidad) +
                    '<br><small>' + escapeHtml(bloqueoActual.fecha_hora_inicio) + ' → ' + escapeHtml(bloqueoActual.fecha_hora_fin) + ' · ' + escapeHtml(bloqueoActual.motivo) + '</small>' +
                    (fechaMinDestino ? '<div class="rc-reprogramar-fecha-bloqueo"><i class="fas fa-ban mr-1"></i> La fecha de destino debe ser <strong>a partir del ' + escapeHtml(fechaMinDestino) + '</strong> (día siguiente al bloqueo).</div>' : '')
                );
                renderCitasAfectadas();
            }).fail(function(xhr) {
                var r = xhr && xhr.responseJSON;
                $('#tabla-reprogramar').html('<tr><td colspan="7" class="text-center text-danger">' + escapeHtml((r && r.message) || 'No se pudieron cargar las citas.') + '</td></tr>');
            });
        });

        function renderCitasAfectadas() {
            if (citasAfectadas.length === 0) {
                $('#tabla-reprogramar').html('<tr><td colspan="7" class="text-center text-muted">No hay citas afectadas por este bloqueo.</td></tr>');
                return;
            }
            var html = '';
            $.each(citasAfectadas, function(i, c) {
                var estadoClase = (c.estado === 'REPROGRAMADA') ? 'badge badge-warning' : 'badge badge-info';
                html += '<tr data-id-cita="' + c.id + '" data-id-ficha="' + c.id_ficha + '">' +
                    '<td class="rc-hora">' + escapeHtml(formatearFechaCruda(c.fecha_hora_inicio)) + '</td>' +
                    '<td>' + escapeHtml(c.paciente) + ' <small>CI ' + escapeHtml(c.paciente_ci) + '</small></td>' +
                    '<td><code>' + escapeHtml(c.codigo) + '</code></td>' +
                    '<td><span class="' + estadoClase + '">' + escapeHtml(c.estado || 'PROGRAMADA') + '</span></td>' +
                    '<td><select class="form-control form-control-sm select-destino" data-index="' + i + '"><option value="">Cargar turnos primero</option></select></td>' +
                    '<td><input type="text" class="form-control form-control-sm input-motivo" placeholder="Motivo de reprogramación" maxlength="250"></td>' +
                    '<td class="rc-reprogramar-acciones">' +
                        '<button type="button" class="btn btn-sm btn-primary btn-reprogramar-una mr-1" data-index="' + i + '" title="Reprogramar"><i class="fas fa-exchange-alt"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-danger btn-dar-baja-una" data-index="' + i + '" title="Dar de baja (cancelar)"><i class="fas fa-times"></i></button>' +
                    '</td>' +
                    '</tr>';
            });
            $('#tabla-reprogramar').html(html);
        }

        $('#btn-cargar-destinos').on('click', function() {
            if (!bloqueoActual) return;
            var fecha = $.trim($('#reprogramar-fecha').val());
            var fechaMin = $('#reprogramar-fecha').attr('min');
            if (!fecha) {
                mostrarError($alertaReprogramar, 'Selecciona una fecha de destino.');
                return;
            }
            if (fechaMin && fecha < fechaMin) {
                mostrarError($alertaReprogramar, 'La fecha de destino debe ser posterior al día del bloqueo (mínimo ' + fechaMin + ').');
                return;
            }
            ocultarError($alertaReprogramar);
            $('#reprogramar-sin-turnos').hide();
            $(this).prop('disabled', true);

            $.ajax({
                url: urlFichasPortal,
                method: 'GET',
                data: {
                    id_medico: bloqueoActual.id_servicio_medico || 0,
                    fecha: fecha
                },
                dataType: 'json'
            }).done(function(respuesta) {
                $('#btn-cargar-destinos').prop('disabled', false);
                if (!respuesta || !respuesta.success) {
                    mostrarError($alertaReprogramar, (respuesta && respuesta.message) || 'No se pudieron cargar los turnos.');
                    $('.select-destino').html('<option value="">— Sin turnos —</option>');
                    $('#reprogramar-sin-turnos').show();
                    return;
                }
                fichasDestino = respuesta.fichas || [];
                if (fichasDestino.length === 0) {
                    $('.select-destino').html('<option value="">— Sin turnos disponibles —</option>');
                    $('#reprogramar-sin-turnos').show();
                    return;
                }
                var opciones = '<option value="">— Seleccionar —</option>';
                $.each(fichasDestino, function(_, f) {
                    opciones += '<option value="' + f.id + '">' + escapeHtml(f.fecha_hora_inicio.substr(11, 5)) + '</option>';
                });
                $('.select-destino').html(opciones);
            }).fail(function(xhr) {
                $('#btn-cargar-destinos').prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaReprogramar, (r && r.message) || 'No se pudieron cargar los turnos.');
                $('.select-destino').html('<option value="">— Sin turnos —</option>');
                $('#reprogramar-sin-turnos').show();
            });
        });

        $(document).on('click', '.btn-reprogramar-una', function() {
            var idx = parseInt($(this).attr('data-index'), 10);
            var cita = citasAfectadas[idx];
            if (!cita) return;
            var $fila = $(this).closest('tr');
            var idFichaNueva = parseInt($fila.find('.select-destino').val(), 10) || 0;
            var motivo = $.trim($fila.find('.input-motivo').val());

            if (idFichaNueva <= 0) {
                mostrarError($alertaReprogramar, 'Selecciona el nuevo turno.');
                return;
            }
            if (!motivo) {
                mostrarError($alertaReprogramar, 'Indica el motivo de la reprogramación.');
                return;
            }

            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: urlReprogramar,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id_cita: cita.id,
                    id_ficha_nueva: idFichaNueva,
                    motivo: motivo
                },
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                if (respuesta && respuesta.success) {
                    $fila.fadeOut(function() {
                        $(this).remove();
                    });
                    window.espNotificar('success', respuesta.message || 'Cita reprogramada.');
                    return;
                }
                $btn.prop('disabled', false);
                mostrarError($alertaReprogramar, (respuesta && respuesta.message) || 'No se pudo reprogramar.');
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaReprogramar, (r && r.message) || 'No se pudo reprogramar.');
            });
        });

        $(document).on('click', '.btn-dar-baja-una', function() {
            var idx = parseInt($(this).attr('data-index'), 10);
            var cita = citasAfectadas[idx];
            if (!cita) return;
            var $fila = $(this).closest('tr');
            var paciente = escapeHtml(cita.paciente || 'el paciente');

            if (!confirm('¿Dar de baja la cita de ' + paciente + ' (' + escapeHtml(cita.codigo || '') + ')? La cita pasará a estado CANCELADA.')) {
                return;
            }

            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: urlCambiarEstado,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id_cita: cita.id,
                    estado: 'CANCELADA'
                },
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                if (respuesta && respuesta.success) {
                    $fila.fadeOut(function() {
                        $(this).remove();
                    });
                    window.espNotificar('success', respuesta.message || 'Cita dada de baja.');
                    return;
                }
                $btn.prop('disabled', false);
                mostrarError($alertaReprogramar, (respuesta && respuesta.message) || 'No se pudo dar de baja la cita.');
            }).fail(function(xhr) {
                $btn.prop('disabled', false);
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaReprogramar, (r && r.message) || 'No se pudo dar de baja la cita.');
            });
        });

        // Voz (reutiliza el estilo del módulo de citas)
        $(document).on('click', '.js-mic', function() {
            var $mic = $(this);
            var targetId = $mic.attr('data-target');
            if (!targetId) return;
            var $input = $('#' + targetId);
            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                window.espNotificar('error', 'Tu navegador no soporta búsqueda por voz.');
                return;
            }
            if ($mic.hasClass('escuchando')) return;
            var reconocimiento = new SpeechRecognition();
            reconocimiento.lang = 'es-ES';
            reconocimiento.continuous = false;
            reconocimiento.interimResults = false;
            $mic.addClass('escuchando').find('i').removeClass('fa-microphone').addClass('fa-stop-circle');
            reconocimiento.onresult = function(e) {
                $input.val(e.results[0][0].transcript).trigger('input');
                $mic.removeClass('escuchando').find('i').addClass('fa-microphone').removeClass('fa-stop-circle');
            };
            reconocimiento.onerror = reconocimiento.onend = function() {
                $mic.removeClass('escuchando').find('i').addClass('fa-microphone').removeClass('fa-stop-circle');
            };
            reconocimiento.start();
        });
    });
</script>