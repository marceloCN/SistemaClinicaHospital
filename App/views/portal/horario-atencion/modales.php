<?php
/**
 * Modales y JavaScript del módulo de horarios de atención.
 *
 * Modal de creación/edición de horario, modal de generación de fichas y
 * toda la lógica AJAX para guardar, desactivar, generar y eliminar fichas.
 */
$catalogoServicios = $catalogoServicios ?? [];

$diasSemana = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo',
];
?>

<!-- Modal de creación / edición de horario -->
<div class="modal fade" id="modal-horario" tabindex="-1" role="dialog" aria-labelledby="modal-horario-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-horario-title">
                    <i class="fas fa-clock mr-2"></i> <span id="horario-titulo">Nuevo horario</span>
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="horario-alerta" class="alert alert-danger ha-alerta-error"></div>

                <form id="form-horario" class="ha-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" id="horario-id" value="0">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="horario-servicio">Servicio médico *</label>
                            <select class="form-control" id="horario-servicio" name="id_servicio_medico" required>
                                <option value="0">— Selecciona un servicio médico —</option>
                                <?php foreach ($catalogoServicios as $servicio) { ?>
                                    <option value="<?= (int) $servicio['id'] ?>">
                                        <?= e($servicio['nombre_completo']) ?> · <?= e($servicio['especialidad']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="horario-dia">Día de la semana *</label>
                            <select class="form-control" id="horario-dia" name="dia_semana" required>
                                <option value="0">— Selecciona un día —</option>
                                <?php foreach ($diasSemana as $numero => $nombre) { ?>
                                    <option value="<?= $numero ?>"><?= e($nombre) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="horario-hora-inicio">Hora inicio *</label>
                            <input type="time" class="form-control" id="horario-hora-inicio" name="hora_inicio" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="horario-hora-fin">Hora fin *</label>
                            <input type="time" class="form-control" id="horario-hora-fin" name="hora_fin" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="horario-duracion">Duración ficha (min) *</label>
                            <input type="number" class="form-control" id="horario-duracion" name="duracion_ficha"
                                min="1" step="1" placeholder="Ej. 20" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="horario-fecha-desde">Vigencia desde *</label>
                            <input type="date" class="form-control" id="horario-fecha-desde" name="fecha_desde" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="horario-fecha-hasta">Vigencia hasta</label>
                            <input type="date" class="form-control" id="horario-fecha-hasta" name="fecha_hasta">
                        </div>
                    </div>

                    <div class="ha-ayuda">
                        <i class="fas fa-info-circle"></i>
                        La vigencia permite guardar el histórico de horarios. Las fichas de agenda solo se generan
                        dentro del rango de vigencia activo.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-horario">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de generación de fichas -->
<div class="modal fade" id="modal-fichas" tabindex="-1" role="dialog" aria-labelledby="modal-fichas-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-fichas-title">
                    <i class="fas fa-calendar-plus mr-2"></i> Generar fichas de agenda
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="fichas-alerta" class="alert alert-danger ha-alerta-error"></div>

                <div class="ha-ayuda mb-3">
                    <i class="fas fa-user-md"></i>
                    <strong id="fichas-medico">—</strong>
                    <span id="fichas-info"></span>
                </div>

                <div class="ha-ayuda mb-3" style="background:#e8f6f3;border-color:#b8e0d6;">
                    <i class="fas fa-calculator"></i>
                    Estimado: <strong id="fichas-estimado">—</strong>
                    <small class="d-block text-muted mt-1" id="fichas-estimado-detalle"></small>
                </div>

                <form id="form-fichas" class="ha-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" id="fichas-id" value="0">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fichas-fecha-inicio">Fecha inicio *</label>
                            <input type="date" class="form-control" id="fichas-fecha-inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fichas-fecha-fin">Fecha fin *</label>
                            <input type="date" class="form-control" id="fichas-fecha-fin" name="fecha_fin" required>
                        </div>
                    </div>

                    <div class="ha-ayuda">
                        <i class="fas fa-info-circle"></i>
                        Se generarán fichas solo en las fechas que coincidan con el día de la semana del horario,
                        respetando su vigencia y evitando duplicados.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-generar-fichas">
                    <i class="fas fa-cogs mr-1"></i> Generar fichas
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var token = '<?= csrf_token() ?>';
        var urlStore = '<?= route('portal_horario_atencion_store') ?>';
        var urlUpdate = '<?= route('portal_horario_atencion_update', ['id' => '{id}']) ?>';
        var urlDestroy = '<?= route('portal_horario_atencion_destroy', ['id' => '{id}']) ?>';
        var urlGenerarFichas = '<?= route('portal_horario_atencion_generar_fichas', ['id' => '{id}']) ?>';
        var urlEliminarFichas = '<?= route('portal_horario_atencion_eliminar_fichas', ['id' => '{id}']) ?>';

        var $modalHorario = $('#modal-horario');
        var $alertaHorario = $('#horario-alerta');
        var $tituloHorario = $('#horario-titulo');
        var $btnGuardarHorario = $('#btn-guardar-horario');

        var $modalFichas = $('#modal-fichas');
        var $alertaFichas = $('#fichas-alerta');
        var $btnGenerarFichas = $('#btn-generar-fichas');

        function mostrarError($alerta, mensaje) {
            $alerta.text(mensaje || 'Ocurrió un error.').show();
        }

        function ocultarError($alerta) {
            $alerta.hide().text('');
        }

        function fechaHoy() {
            var hoy = new Date();
            var mes = ('0' + (hoy.getMonth() + 1)).slice(-2);
            var dia = ('0' + hoy.getDate()).slice(-2);

            return hoy.getFullYear() + '-' + mes + '-' + dia;
        }

        function abrirNuevoHorario() {
            ocultarError($alertaHorario);
            $tituloHorario.text('Nuevo horario');
            $('#horario-id').val('0');
            $('#horario-servicio').val('0').prop('disabled', false);
            $('#horario-dia').val('0');
            $('#horario-hora-inicio').val('');
            $('#horario-hora-fin').val('');
            $('#horario-duracion').val('');
            $('#horario-fecha-desde').val(fechaHoy());
            $('#horario-fecha-hasta').val('');
            //$('#horario-estado').val('1');
            $modalHorario.modal('show');
        }

        $('#btn-nuevo-horario').on('click', abrirNuevoHorario);

        // referencia para editar un horario de atencion de un doctor
        $(document).on('click', '.js-editar', function () {
            var $btn = $(this);
            var id = parseInt($btn.attr('data-id'), 10) || 0;

            ocultarError($alertaHorario);
            $tituloHorario.text('Editar horario');
            $('#horario-id').val(id);
            $('#horario-servicio').val($btn.attr('data-servicio')).prop('disabled', true);
            $('#horario-dia').val($btn.attr('data-dia'));
            $('#horario-hora-inicio').val(String($btn.attr('data-inicio')).slice(0, 5));
            $('#horario-hora-fin').val(String($btn.attr('data-fin')).slice(0, 5));
            $('#horario-duracion').val($btn.attr('data-duracion'));
            $('#horario-fecha-desde').val(String($btn.attr('data-desde')).slice(0, 10));
            $('#horario-fecha-hasta').val(String($btn.attr('data-hasta')).slice(0, 10) || '');
            //$('#horario-estado').val($btn.attr('data-estado'));
            $modalHorario.modal('show');
        });

        $btnGuardarHorario.on('click', function () {
            ocultarError($alertaHorario);

            var id = parseInt($('#horario-id').val(), 10) || 0;
            var idServicio = parseInt($('#horario-servicio').val(), 10) || 0;
            var dia = parseInt($('#horario-dia').val(), 10) || 0;
            var horaInicio = $.trim($('#horario-hora-inicio').val());
            var horaFin = $.trim($('#horario-hora-fin').val());
            var duracion = parseInt($('#horario-duracion').val(), 10) || 0;
            var fechaDesde = $.trim($('#horario-fecha-desde').val());
            var fechaHasta = $.trim($('#horario-fecha-hasta').val());

            if (idServicio <= 0) {
                mostrarError($alertaHorario, 'Selecciona el servicio médico.');
                return;
            }

            if (dia < 1 || dia > 7) {
                mostrarError($alertaHorario, 'Selecciona el día de la semana.');
                return;
            }

            if (!horaInicio || !horaFin) {
                mostrarError($alertaHorario, 'Indica la hora de inicio y la hora de fin.');
                return;
            }

            if (horaFin <= horaInicio) {
                mostrarError($alertaHorario, 'La hora de fin debe ser posterior a la de inicio.');
                return;
            }

            if (duracion <= 0) {
                mostrarError($alertaHorario, 'La duración de la ficha debe ser mayor a 0 minutos.');
                return;
            }

            if (!fechaDesde) {
                mostrarError($alertaHorario, 'La fecha de inicio de vigencia es obligatoria.');
                return;
            }

            if (fechaHasta && fechaHasta < fechaDesde) {
                mostrarError($alertaHorario, 'La fecha de fin no puede ser anterior a la de inicio.');
                return;
            }

            var datos = {
                _token: token,
                id_servicio_medico: idServicio,
                dia_semana: dia,
                hora_inicio: horaInicio,
                hora_fin: horaFin,
                duracion_ficha: duracion,
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                //estado: $('#horario-estado').val()
            };


            var url = id > 0 ? urlUpdate.replace('{id}', id) : urlStore;

            var $btn = $(this).prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: datos,
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (respuesta && respuesta.success) {
                    window.espNotificar('success', respuesta.message || 'Horario guardado correctamente.');
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                    return;
                }
                $btn.prop('disabled', false);
                mostrarError($alertaHorario, (respuesta && respuesta.message) || 'No se pudo guardar el horario.');
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                $btn.prop('disabled', false);
                mostrarError($alertaHorario, (r && r.message) || 'No se pudo guardar el horario. Revisa los datos.');
            });
        });

        $(document).on('click', '.js-desactivar, .js-activar', function () {
            var $btn = $(this);
            var id = $btn.attr('data-id');
            var medico = $btn.attr('data-medico') || 'este médico';
            var reactivar = $btn.hasClass('js-activar');

            window.espConfirmar({
                titulo: reactivar ? 'Reactivar horario' : 'Desactivar horario',
                mensaje: reactivar
                    ? '¿Deseas reactivar el horario de ' + medico + '?'
                    : '¿Deseas desactivar el horario de ' + medico + '?',
                ayuda: reactivar
                    ? 'El horario volverá a estar activo y podrás generar fichas dentro de su vigencia.'
                    : 'No se puede desactivar si el horario ya tiene citas asociadas. Al desactivar se eliminan las fichas libres.',
                btn: reactivar ? 'Sí, reactivar' : 'Sí, desactivar',
                destructivo: !reactivar
            }, function () {
                $btn.prop('disabled', true);

                $.ajax({
                    url: urlDestroy.replace('{id}', id),
                    method: 'POST',
                    dataType: 'json',
                    data: { _token: token },
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                }).done(function (respuesta) {
                    if (respuesta && respuesta.success) {
                        window.espNotificar('success', respuesta.message
                            || (reactivar ? 'Horario reactivado correctamente.' : 'Horario desactivado correctamente.'));
                        setTimeout(function () {
                            window.location.reload();
                        }, 1200);
                        return;
                    }
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (respuesta && respuesta.message)
                        || (reactivar ? 'No se pudo reactivar el horario.' : 'No se pudo desactivar el horario.'));
                }).fail(function (xhr) {
                    var r = xhr && xhr.responseJSON;
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (r && r.message)
                        || (reactivar ? 'No se pudo reactivar el horario.' : 'No se pudo desactivar el horario.'));
                });
            });
        });

        var datosGenerarFichas = {
            horaInicio: '',
            horaFin: '',
            duracion: 0,
            diaSemana: 0
        };

        function horaAMinutos(hora) {
            var partes = String(hora).split(':');
            return (parseInt(partes[0], 10) || 0) * 60 + (parseInt(partes[1], 10) || 0);
        }

        function contarDiasSemana(fechaInicio, fechaFin, diaSemanaPhp) {
            var inicio = new Date(fechaInicio + 'T00:00:00');
            var fin = new Date(fechaFin + 'T00:00:00');
            var count = 0;
            var jsDia = diaSemanaPhp % 7;

            while (inicio <= fin) {
                if (inicio.getDay() === jsDia) {
                    count++;
                }
                inicio.setDate(inicio.getDate() + 1);
            }

            return count;
        }

        function calcularEstimadoFichas() {
            var fechaInicio = $.trim($('#fichas-fecha-inicio').val());
            var fechaFin = $.trim($('#fichas-fecha-fin').val());

            if (!datosGenerarFichas.horaInicio || !datosGenerarFichas.horaFin
                || datosGenerarFichas.duracion <= 0 || datosGenerarFichas.diaSemana < 1
                || !fechaInicio || !fechaFin || fechaFin < fechaInicio) {
                $('#fichas-estimado').text('—');
                $('#fichas-estimado-detalle').text('');
                return;
            }

            var minutosTotales = horaAMinutos(datosGenerarFichas.horaFin) - horaAMinutos(datosGenerarFichas.horaInicio);
            var fichasPorDia = Math.max(0, Math.floor(minutosTotales / datosGenerarFichas.duracion));
            var diasCoinciden = contarDiasSemana(fechaInicio, fechaFin, datosGenerarFichas.diaSemana);
            var total = fichasPorDia * diasCoinciden;

            $('#fichas-estimado').text(total + ' ficha' + (total === 1 ? '' : 's'));
            $('#fichas-estimado-detalle').text(
                fichasPorDia + ' ficha(s) por día · ' + diasCoinciden + ' día(s) coincidente(s)'
            );
        }

        $(document).on('click', '.js-generar-fichas', function () {
            var $btn = $(this);
            var id = parseInt($btn.attr('data-id'), 10) || 0;
            var medico = $btn.attr('data-medico') || '—';

            ocultarError($alertaFichas);
            $('#fichas-id').val(id);
            $('#fichas-medico').text(medico);
            $('#fichas-info').text('');

            datosGenerarFichas = {
                horaInicio: $btn.attr('data-hora-inicio') || '',
                horaFin: $btn.attr('data-hora-fin') || '',
                duracion: parseInt($btn.attr('data-duracion'), 10) || 0,
                diaSemana: parseInt($btn.attr('data-dia'), 10) || 0
            };

            $('#fichas-fecha-inicio').val(fechaHoy());
            $('#fichas-fecha-fin').val(fechaHoy());
            calcularEstimadoFichas();
            $modalFichas.modal('show');
        });

        $('#fichas-fecha-inicio, #fichas-fecha-fin').on('change', calcularEstimadoFichas);

        $btnGenerarFichas.on('click', function () {
            ocultarError($alertaFichas);

            var id = parseInt($('#fichas-id').val(), 10) || 0;
            var fechaInicio = $.trim($('#fichas-fecha-inicio').val());
            var fechaFin = $.trim($('#fichas-fecha-fin').val());

            if (!fechaInicio || !fechaFin) {
                mostrarError($alertaFichas, 'Indica la fecha de inicio y la fecha de fin.');
                return;
            }

            if (fechaFin < fechaInicio) {
                mostrarError($alertaFichas, 'La fecha de fin no puede ser anterior a la de inicio.');
                return;
            }

            var $btn = $(this).prop('disabled', true);

            $.ajax({
                url: urlGenerarFichas.replace('{id}', id),
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin
                },
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (respuesta && respuesta.success) {
                    $modalFichas.modal('hide');
                    window.espNotificar('success', respuesta.message || 'Fichas generadas correctamente.');
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                    return;
                }
                $btn.prop('disabled', false);
                mostrarError($alertaFichas, (respuesta && respuesta.message) || 'No se pudieron generar las fichas.');
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                var mensaje = 'No se pudieron generar las fichas.';

                if (r && r.message) {
                    mensaje = r.message;
                } else if (xhr && xhr.status) {
                    mensaje += ' (Error ' + xhr.status + ')';
                    if (xhr.statusText) {
                        mensaje += ' ' + xhr.statusText;
                    }
                    if (xhr.responseText && xhr.responseText.length < 500) {
                        mensaje += ': ' + xhr.responseText;
                    }
                }

                $btn.prop('disabled', false);
                mostrarError($alertaFichas, mensaje);
            });
        });

        $(document).on('click', '.js-eliminar-fichas', function () {
            var $btn = $(this);
            var id = $btn.attr('data-id');
            var medico = $btn.attr('data-medico') || 'este médico';
            var restantes = parseInt($btn.attr('data-restantes'), 10) || 0;

            window.espConfirmar({
                titulo: 'Eliminar fichas restantes',
                mensaje: '¿Deseas eliminar ' + restantes + ' ficha' + (restantes === 1 ? '' : 's') + ' libre' + (restantes === 1 ? '' : 's') + ' del horario de ' + medico + '?',
                ayuda: 'Solo se eliminarán las fichas libres (estado ABIERTA). Esta acción no se puede deshacer.',
                btn: 'Sí, eliminar',
                destructivo: true
            }, function () {
                $btn.prop('disabled', true);

                $.ajax({
                    url: urlEliminarFichas.replace('{id}', id),
                    method: 'POST',
                    dataType: 'json',
                    data: { _token: token },
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                }).done(function (respuesta) {
                    if (respuesta && respuesta.success) {
                        window.espNotificar('success', respuesta.message || 'Fichas eliminadas correctamente.');
                        setTimeout(function () {
                            window.location.reload();
                        }, 1200);
                        return;
                    }
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (respuesta && respuesta.message) || 'No se pudieron eliminar las fichas restantes.');
                }).fail(function (xhr) {
                    var r = xhr && xhr.responseJSON;
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (r && r.message) || 'No se pudieron eliminar las fichas restantes.');
                });
            });
        });
    });
</script>
