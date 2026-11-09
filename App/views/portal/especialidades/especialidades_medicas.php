<?php

/**
 * Página Especialidades_medicas del gestor de especialidades médicas.
 *
 * Filtros (nombre y estado) con su reporte PDF, tabla paginada del
 * catálogo con conteos de médicos y servicios, y modal para crear o
 * editar una especialidad. El estado arranca en activo al crearla.
 */
$especialidades = $especialidades ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$filtroNombre = (string) ($filtroNombre ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$numerosPagina = $numerosPagina ?? [];

/** URL de página conservando filtros. */
$urlPagina = static function (int $nuevaPagina) use ($filtroNombre, $filtroEstado): string {
    $params = array_filter([
        'nombre' => $filtroNombre,
        'estado' => $filtroEstado,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn($v) => $v !== '' && $v !== null);

    $base = route('dashboard_especialidades_medicas') ?? '/portal/dashboard/Especialidades/Especialidades_medicas';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$urlLimpia = route('dashboard_especialidades_medicas') ?? '/portal/dashboard/Especialidades/Especialidades_medicas';

/** URLs de los reportes conservando los filtros activos (sin paginación). */
$urlPdf = route('portal_especialidad_pdf') ?? '/portal/dashboard/Especialidades/pdf';
$urlCsv = route('portal_especialidad_csv') ?? '/portal/dashboard/Especialidades/csv';
$paramsReporte = array_filter([
    'tipo' => 'especialidades',
    'nombre' => $filtroNombre,
    'estado' => $filtroEstado,
], static fn($v) => $v !== '' && $v !== null);

if ($paramsReporte !== []) {
    $urlPdf .= '?' . http_build_query($paramsReporte);
    $urlCsv .= '?' . http_build_query($paramsReporte);
}
?>

<?= \Lib\Core\View::render('portal.especialidades.header', [
    'tab' => 'especialidades',
    'statTotal' => (int) ($statTotal ?? 0),
    'statMedicos' => (int) ($statMedicos ?? 0),
]) ?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
    <button type="button" class="btn btn-primary" id="btn-nueva-especialidad">
        <i class="fas fa-plus mr-1"></i> Nueva especialidad
    </button>
    <small class="text-muted">Los filtros solo aplican en esta página.</small>
</div>

<form class="es-filtros" method="GET" action="<?= route('dashboard_especialidades_medicas') ?>">
    <div class="row align-items-end">
        <div class="col-lg-4 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-nombre">Nombre de la especialidad</label>
            <input type="text" class="form-control" id="filtro-nombre" name="nombre" maxlength="100"
                value="<?= e($filtroNombre) ?>" placeholder="Ej. Cardiología">
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-estado">Estado</label>
            <select class="form-control" id="filtro-estado" name="estado">
                <option value="">Todas</option>
                <option value="1" <?= $filtroEstado === '1' ? 'selected' : '' ?>>Activas</option>
                <option value="0" <?= $filtroEstado === '0' ? 'selected' : '' ?>>Inactivas</option>
            </select>
        </div>
        <div class="col-lg-5 col-md-12 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Buscar
            </button>
            <a href="<?= e($urlLimpia) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-eraser"></i> Limpiar
            </a>
            <a href="<?= e($urlPdf) ?>" class="btn btn-outline-danger" target="_blank" rel="noopener">
                <i class="fas fa-file-pdf"></i> Generar PDF
            </a>
            <a href="<?= e($urlCsv) ?>" class="btn btn-outline-success">
                <i class="fas fa-file-csv"></i> Generar CSV
            </a>
        </div>
    </div>
</form>

<div class="es-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Especialidad</th>
                    <th scope="col" class="text-center">Médicos</th>
                    <th scope="col" class="text-center">Servicios activos</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($especialidades === []) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay especialidades que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($especialidades as $especialidad) {
                    $idEspecialidad = (int) $especialidad['id'];
                    $descripcion = trim((string) $especialidad['descripcion']);
                ?>
                    <tr>
                        <td>
                            <div class="es-nombre">
                                <?= e((string) $especialidad['nombre']) ?>
                                <small>
                                    <?php if ($descripcion !== '') { ?>
                                        <?= e(mb_strlen($descripcion) > 90 ? mb_substr($descripcion, 0, 90) . '…' : $descripcion) ?>
                                    <?php } else { ?>
                                        <span class="text-muted">Sin descripción</span>
                                    <?php } ?>
                                </small>
                            </div>
                        </td>
                        <td class="text-center"><?= (int) $especialidad['total_medicos'] ?></td>
                        <td class="text-center"><?= (int) $especialidad['servicios_activos'] ?></td>
                        <td>
                            <span class="badge <?= $especialidad['estado'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $especialidad['estado'] ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </td>
                        <td class="text-right text-nowrap">

                            <?php if ($especialidad['estado']) { ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-editar"
                                    title="Editar especialidad"
                                    data-id="<?= $idEspecialidad ?>"
                                    data-url="<?= e(route('portal_especialidad_detalle', ['id' => '{id}']) ?? '') ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning js-cambiar-estado"
                                    title="Desactivar especialidad"
                                    data-id="<?= $idEspecialidad ?>"
                                    data-estado="0"
                                    data-nombre="<?= e((string) $especialidad['nombre']) ?>">
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-outline-info js-cambiar-estado"
                                    title="Activar especialidad"
                                    data-id="<?= $idEspecialidad ?>"
                                    data-estado="1"
                                    data-nombre="<?= e((string) $especialidad['nombre']) ?>">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="es-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> especialidad(es) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de especialidades">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($urlPagina(max(1, $pagina - 1))) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>

                <?php foreach ($numerosPagina as $numero) { ?>
                    <?php if ($numero === '...') { ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php } else { ?>
                        <li class="page-item <?= (int) $numero === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e($urlPagina((int) $numero)) ?>"><?= (int) $numero ?></a>
                        </li>
                    <?php } ?>
                <?php } ?>

                <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($urlPagina(min($paginas, $pagina + 1))) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    <?php } ?>
</div>

<!-- Modal de creación / edición de especialidad -->
<div class="modal fade" id="modal-especialidad" tabindex="-1" role="dialog" aria-labelledby="modal-especialidad-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-especialidad-title">
                    <i class="fas fa-book-medical mr-2"></i> <span id="especialidad-titulo">Nueva especialidad</span>
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="especialidad-alerta" class="alert alert-danger es-alerta-error"></div>

                <form id="form-especialidad" class="es-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" id="especialidad-id" value="0">

                    <div class="mb-3">
                        <label for="especialidad-nombre">Nombre de la especialidad *</label>
                        <input type="text" class="form-control" id="especialidad-nombre" name="nombre"
                            maxlength="100" placeholder="Ej. Cardiología" required>
                    </div>

                    <div class="mb-2">
                        <label for="especialidad-descripcion">Descripción</label>
                        <textarea class="form-control" id="especialidad-descripcion" name="descripcion"
                            rows="4" maxlength="2000"
                            placeholder="Ámbito de atención, requerimientos o cualquier dato de utilidad."></textarea>
                    </div>

                    <div class="es-ayuda">
                        <i class="fas fa-info-circle"></i>
                        Al crearla la especialidad queda <strong>activa</strong>; puedes desactivarla
                        desde la tabla para retirarla del catálogo sin perder sus acreditaciones.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-especialidad">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        var token = '<?= csrf_token() ?>';
        var urlGuardar = '<?= route('portal_especialidad_guardar') ?>';
        var urlEstado = '<?= route('portal_especialidad_estado') ?>';
        var urlBaseDetalle = '<?= e(route('portal_especialidad_detalle', ['id' => '{id}']) ?? '') ?>';

        var $modal = $('#modal-especialidad');
        var $alerta = $('#especialidad-alerta');
        var $titulo = $('#especialidad-titulo');
        var $btnGuardar = $('#btn-guardar-especialidad');

        function mostrarError(mensaje) {
            $alerta.text(mensaje || 'Ocurrió un error.').show();
        }

        function ocultarError() {
            $alerta.hide().text('');
        }

        function abrirModal(titulo) {
            ocultarError();
            $titulo.text(titulo);
            $modal.modal('show');
        }

        $('#btn-nueva-especialidad').on('click', function() {
            $('#especialidad-id').val('0');
            $('#especialidad-nombre').val('');
            $('#especialidad-descripcion').val('');
            abrirModal('Nueva especialidad');
            $('#especialidad-nombre').trigger('focus');
        });

        $(document).on('click', '.js-editar', function() {
            var id = $(this).attr('data-id');
            var url = $(this).attr('data-url').replace('{id}', id);

            ocultarError();
            $titulo.text('Editar especialidad');
            $('#especialidad-id').val(id);
            $('#especialidad-nombre').val('');
            $('#especialidad-descripcion').val('');
            $modal.modal('show');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                if (!respuesta || !respuesta.success) {
                    mostrarError((respuesta && respuesta.message) || 'No se pudo cargar la especialidad.');
                    return;
                }
                $('#especialidad-nombre').val(respuesta.especialidad.nombre);
                $('#especialidad-descripcion').val(respuesta.especialidad.descripcion);
            }).fail(function() {
                mostrarError('No se pudo cargar la especialidad. Cierra y vuelve a intentar.');
            });
        });

        $btnGuardar.on('click', function() {
            ocultarError();

            var nombre = $.trim($('#especialidad-nombre').val());

            if (nombre === '') {
                mostrarError('El nombre de la especialidad es obligatorio.');
                return;
            }

            var $btn = $(this).prop('disabled', true);

            $.ajax({
                url: urlGuardar,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id: $('#especialidad-id').val(),
                    nombre: nombre,
                    descripcion: $('#especialidad-descripcion').val()
                },
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function(respuesta) {
                if (respuesta && respuesta.success) {
                    window.location.reload();
                    return;
                }
                mostrarError((respuesta && respuesta.message) || 'No se pudo guardar la especialidad.');
            }).fail(function(xhr) {
                var r = xhr && xhr.responseJSON;
                mostrarError((r && r.message) || 'No se pudo guardar la especialidad. Revisa los datos.');
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '.js-cambiar-estado', function() {
            var $btn = $(this);
            var id = $btn.attr('data-id');
            var estado = $btn.attr('data-estado');
            var nombre = $btn.attr('data-nombre') || 'esta especialidad';
            var baja = estado !== '1';

            window.espConfirmar({
                titulo: baja ? 'Dar de baja la especialidad' : 'Reactivar la especialidad',
                mensaje: baja ?
                    '¿Deseas dar de baja "' + nombre + '"? Se ocultará del catálogo de especialidades.' :
                    '¿Deseas reactivar "' + nombre + '"? Volverá a estar disponible en el catálogo.',
                ayuda: baja ?
                    'No se puede dar de baja mientras la especialidad tenga servicios médicos ' +
                    'registrados: de esos servicios se generan los horarios de atención y las fichas.' :
                    '',
                btn: baja ? 'Sí, dar de baja' : 'Sí, reactivar',
                destructivo: baja
            }, function() {
                $btn.prop('disabled', true);

                $.ajax({
                    url: urlEstado,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: token,
                        id: id,
                        estado: estado
                    },
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).done(function(respuesta) {
                    if (respuesta && respuesta.success) {
                        window.location.reload();
                        return;
                    }
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (respuesta && respuesta.message) || 'No se pudo cambiar el estado.');
                }).fail(function(xhr) {
                    var r = xhr && xhr.responseJSON;
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (r && r.message) || 'No se pudo cambiar el estado de la especialidad.');
                });
            });
        });
    });
</script>