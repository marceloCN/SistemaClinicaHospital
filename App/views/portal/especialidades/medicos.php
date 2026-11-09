<?php
/**
 * Página Medicos del gestor de especialidades médicas.
 *
 * Control de las especialidades que tiene asignadas cada médico
 * (medico_especialidad): es el paso previo que habilita sumarlo a
 * Servicio médico (servicio_medico) y, desde ahí, a los horarios de
 * atención y las fichas.
 *
 * Acciones: asignar una o varias especialidades y quitarlas (bloqueado
 * mientras exista algún servicio médico en la especialidad).
 */
$medicos = $medicos ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$filtroTextoMed = (string) ($filtroTextoMed ?? '');
$filtroAcredit = (string) ($filtroAcredit ?? '');
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
$catalogoMedicos = $catalogoMedicos ?? [];
$estadisticas = $estadisticas ?? [];

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$numerosPagina = $numerosPagina ?? [];

/** URL de página conservando filtros. */
$urlPagina = static function (int $nuevaPagina) use ($filtroTextoMed, $filtroAcredit): string {
    $params = array_filter([
        'medico' => $filtroTextoMed,
        'acredit' => $filtroAcredit,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn ($v) => $v !== '' && $v !== null);

    $base = route('dashboard_especialidades_medicos') ?? '/portal/dashboard/Especialidades/Medicos';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$urlLimpia = route('dashboard_especialidades_medicos') ?? '/portal/dashboard/Especialidades/Medicos';

/** URLs de los reportes conservando los filtros activos (sin paginación). */
$urlPdf = route('portal_especialidad_pdf') ?? '/portal/dashboard/Especialidades/pdf';
$urlCsv = route('portal_especialidad_csv') ?? '/portal/dashboard/Especialidades/csv';
$paramsReporte = array_filter([
    'tipo' => 'medicos',
    'medico' => $filtroTextoMed,
    'acredit' => $filtroAcredit,
], static fn ($v) => $v !== '' && $v !== null);

if ($paramsReporte !== []) {
    $urlPdf .= '?' . http_build_query($paramsReporte);
    $urlCsv .= '?' . http_build_query($paramsReporte);
}

$statMedicosAcreditados = (int) ($estadisticas['medicos'] ?? 0);
$statAcreditaciones = (int) ($estadisticas['acreditaciones'] ?? 0);
$statServicios = (int) ($estadisticas['servicios_activos'] ?? 0);
?>

<?= \Lib\Core\View::render('portal.especialidades.header', [
    'tab' => 'medicos',
    'statTotal' => (int) ($statTotal ?? 0),
    'statMedicos' => (int) ($statMedicos ?? 0),
]) ?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
    <button type="button" class="btn btn-primary" id="btn-asignar-especialidad">
        <i class="fas fa-plus mr-1"></i> Asignar especialidad a un médico
    </button>
    <small class="text-muted">Flujo: Especialidades → Médicos → Servicio médico.</small>
</div>

<div class="es-stats mb-3">
    <div class="es-stat">
        <span class="es-stat-icon medicos"><i class="fas fa-user-md"></i></span>
        <div>
            <div class="es-stat-num"><?= $statMedicosAcreditados ?></div>
            <div class="es-stat-label">Médicos con especialidad</div>
        </div>
    </div>
    <div class="es-stat">
        <span class="es-stat-icon acred"><i class="fas fa-certificate"></i></span>
        <div>
            <div class="es-stat-num"><?= $statAcreditaciones ?></div>
            <div class="es-stat-label">Acreditaciones</div>
        </div>
    </div>
    <div class="es-stat">
        <span class="es-stat-icon servicios"><i class="fas fa-stethoscope"></i></span>
        <div>
            <div class="es-stat-num"><?= $statServicios ?></div>
            <div class="es-stat-label">Servicios activos</div>
        </div>
    </div>
</div>

<form class="es-filtros" method="GET" action="<?= route('dashboard_especialidades_medicos') ?>">
    <div class="row align-items-end">
        <div class="col-lg-5 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-medico-texto">Médico</label>
            <input type="text" class="form-control" id="filtro-medico-texto" name="medico" maxlength="100"
                value="<?= e($filtroTextoMed) ?>" placeholder="Nombre, CI o nro. de colegiado">
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-acredit">Especialidades asignadas</label>
            <select class="form-control" id="filtro-acredit" name="acredit">
                <option value="">Todos</option>
                <option value="con" <?= $filtroAcredit === 'con' ? 'selected' : '' ?>>Con especialidad</option>
                <option value="sin" <?= $filtroAcredit === 'sin' ? 'selected' : '' ?>>Sin especialidad</option>
            </select>
        </div>
        <div class="col-lg-4 col-md-12 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
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
                    <th scope="col">Médico</th>
                    <th scope="col">Especialidades asignadas</th>
                    <th scope="col" class="text-center">Acredit.</th>
                    <th scope="col" class="text-center">Servicios activos</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($medicos === []) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay médicos que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($medicos as $medico) {
                    $idMedico = (int) $medico['id'];
                    $especialidadesMedico = $medico['especialidades'] ?? [];
                ?>
                    <tr>
                        <td>
                            <div class="es-nombre">
                                <?= e($medico['nombre_completo']) ?>
                                <small>
                                    CI <?= e($medico['ci']) ?>
                                    · Colegiado <?= e($medico['nro_colegiado'] !== '' ? $medico['nro_colegiado'] : '—') ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <?php if ($especialidadesMedico === []) { ?>
                                <span class="badge badge-light border">Sin especialidades</span>
                                <small class="d-block text-muted mt-1">
                                    Aún no puede sumarse a Servicio médico.
                                </small>
                            <?php } else { ?>
                                <?php foreach ($especialidadesMedico as $acreditacion) { ?>
                                    <?php $sinServicio = (int) $acreditacion['total_servicios'] === 0; ?>
                                    <span class="badge badge-<?= $acreditacion['especialidad_activa'] ? 'primary' : 'secondary' ?> mr-1 mb-1"
                                        title="<?= (int) $acreditacion['servicios_activos'] ?> servicio(s) activo(s)">
                                        <?= e($acreditacion['nombre']) ?>
                                        <?php if ($sinServicio) { ?>
                                            <button type="button"
                                                class="btn btn-sm border-0 p-0 ml-1 text-white js-quitar-especialidad"
                                                title="Quitar especialidad"
                                                data-id-medico="<?= $idMedico ?>"
                                                data-id-especialidad="<?= (int) $acreditacion['id_especialidad'] ?>"
                                                data-nombre="<?= e($acreditacion['nombre']) ?>"
                                                data-medico="<?= e($medico['nombre_completo']) ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php } else { ?>
                                            <i class="fas fa-lock ml-1" title="Tiene servicios médicos: no puede quitarse"></i>
                                        <?php } ?>
                                    </span>
                                <?php } ?>
                            <?php } ?>
                        </td>
                        <td class="text-center"><?= (int) $medico['total_especialidades'] ?></td>
                        <td class="text-center"><?= (int) $medico['servicios_activos'] ?></td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary js-asignar"
                                title="Asignar especialidades"
                                data-id-medico="<?= $idMedico ?>">
                                <i class="fas fa-plus"></i>
                            </button>
                            <a class="btn btn-sm btn-outline-secondary"
                                title="Ver en Servicio médico"
                                href="<?= e((route('dashboard_especialidades_servicio') ?? '/portal/dashboard/Especialidades/servicio_medico') . '?' . http_build_query(['medico' => $medico['nombre_completo']])) ?>">
                                <i class="fas fa-stethoscope"></i>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="es-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> médico(s) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de médicos">
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

<!-- Modal: asignar especialidades al médico -->
<div class="modal fade" id="modal-asignar" tabindex="-1" role="dialog" aria-labelledby="modal-asignar-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-asignar-title">
                    <i class="fas fa-user-md mr-2"></i> Asignar especialidades al médico
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="asignar-alerta" class="alert alert-danger es-alerta-error"></div>

                <form id="form-asignar" class="es-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id_medico" id="asignar-id-medico" value="0">

                    <div class="mb-3">
                        <label for="asignar-medico">Médico *</label>
                        <select class="form-control" id="asignar-medico">
                            <option value="0">— Selecciona un médico —</option>
                            <?php foreach ($catalogoMedicos as $medicoCatalogo) { ?>
                                <option value="<?= $medicoCatalogo['id'] ?>">
                                    <?= e($medicoCatalogo['nombre_completo']) ?>
                                    (CI <?= e($medicoCatalogo['ci']) ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <label class="d-block">Especialidades a asignar *</label>
                    <div class="border rounded p-3" style="max-height:260px;overflow:auto;background:#f7f9f8;">
                        <?php if ($catalogoEspecialidades === []) { ?>
                            <p class="text-muted mb-0">
                                No hay especialidades activas. Crea la especialidad primero en la página Especialidades.
                            </p>
                        <?php } else { ?>
                            <div class="row">
                                <?php foreach ($catalogoEspecialidades as $especialidadCatalogo) { ?>
                                    <div class="col-md-6">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input js-especialidad-check"
                                                id="esp-check-<?= (int) $especialidadCatalogo['id'] ?>"
                                                name="especialidades[]"
                                                value="<?= (int) $especialidadCatalogo['id'] ?>">
                                            <label class="custom-control-label" for="esp-check-<?= (int) $especialidadCatalogo['id'] ?>">
                                                <?= e($especialidadCatalogo['nombre']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="es-ayuda mt-3">
                        <i class="fas fa-info-circle"></i>
                        Con la especialidad asignada el médico podrá sumarse en
                        <strong>Servicio médico</strong>, y desde ahí recibir horarios de
                        atención y fichas. Las especialidades ya asignadas se contabilizan
                        y no se duplican.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-asignacion">
                    <i class="fas fa-save mr-1"></i> Asignar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var token = '<?= csrf_token() ?>';
        var urlAsignar = '<?= route('portal_especialidad_asignar') ?>';
        var urlQuitar = '<?= route('portal_especialidad_quitar') ?>';

        var $modal = $('#modal-asignar');
        var $alerta = $('#asignar-alerta');
        var $btnGuardar = $('#btn-guardar-asignacion');

        function mostrarError(mensaje) {
            $alerta.text(mensaje || 'Ocurrió un error.').show();
        }

        function ocultarError() {
            $alerta.hide().text('');
        }

        function abrirModal(idMedico) {
            ocultarError();
            $('#asignar-id-medico').val(idMedico || 0);
            $('#asignar-medico').val(String(idMedico || 0));
            $('.js-especialidad-check').prop('checked', false);
            $modal.modal('show');
        }

        $('#btn-asignar-especialidad').on('click', function () {
            abrirModal(0);
        });

        $(document).on('click', '.js-asignar', function () {
            abrirModal(parseInt($(this).attr('data-id-medico'), 10) || 0);
        });

        $btnGuardar.on('click', function () {
            ocultarError();

            var idMedico = parseInt($('#asignar-medico').val(), 10) || 0;
            var seleccionadas = [];

            $('.js-especialidad-check:checked').each(function () {
                seleccionadas.push($(this).val());
            });

            if (idMedico <= 0) {
                mostrarError('Selecciona el médico.');
                return;
            }

            if (seleccionadas.length === 0) {
                mostrarError('Selecciona al menos una especialidad.');
                return;
            }

            var $btn = $(this).prop('disabled', true);

            $.ajax({
                url: urlAsignar,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id_medico: idMedico,
                    especialidades: seleccionadas
                },
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (respuesta && respuesta.success) {
                    window.location.reload();
                    return;
                }
                mostrarError((respuesta && respuesta.message) || 'No se pudieron asignar las especialidades.');
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                mostrarError((r && r.message) || 'No se pudieron asignar las especialidades. Revisa los datos.');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '.js-quitar-especialidad', function () {
            var $btn = $(this);
            var idMedico = $btn.attr('data-id-medico');
            var idEspecialidad = $btn.attr('data-id-especialidad');
            var nombre = $btn.attr('data-nombre') || 'la especialidad';
            var medico = $btn.attr('data-medico') || 'el médico';

            window.espConfirmar({
                titulo: 'Quitar especialidad',
                mensaje: '¿Quitar "' + nombre + '" de ' + medico + '?\n'
                    + 'El médico dejará de estar acreditado en esa especialidad.',
                ayuda: 'Mientras el médico tenga servicios médicos en la especialidad no se '
                    + 'podrá quitar, porque de ahí dependen sus horarios y fichas.',
                btn: 'Sí, quitar'
            }, function () {
                $btn.prop('disabled', true);

                $.ajax({
                    url: urlQuitar,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: token,
                        id_medico: idMedico,
                        id_especialidad: idEspecialidad
                    },
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                }).done(function (respuesta) {
                    if (respuesta && respuesta.success) {
                        window.location.reload();
                        return;
                    }
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (respuesta && respuesta.message) || 'No se pudo quitar la especialidad.');
                }).fail(function (xhr) {
                    var r = xhr && xhr.responseJSON;
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (r && r.message) || 'No se pudo quitar la especialidad.');
                });
            });
        });
    });
</script>
