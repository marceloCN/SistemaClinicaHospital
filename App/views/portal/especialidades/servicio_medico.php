<?php
/**
 * Página servicio_medico del gestor de especialidades médicas.
 *
 * Filtros (especialidad, estado y médico) con sus reportes PDF y CSV, tabla
 * paginada de las acreditaciones vigentes en servicio_medico y los
 * botones para registrar un bloqueo de agenda o revisar los bloqueos
 * ya registrados de cada médico.
 */
$servicios = $servicios ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
$catalogoMedicos = $catalogoMedicos ?? [];
$acreditacionesPorMedico = $acreditacionesPorMedico ?? [];
$filtroEspecialidad = (string) ($filtroEspecialidad ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');
$filtroMedico = (string) ($filtroMedico ?? '');

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$numerosPagina = $numerosPagina ?? [];

/** URL de página conservando filtros. */
$urlPagina = static function (int $nuevaPagina) use ($filtroEspecialidad, $filtroEstado, $filtroMedico): string {
    $params = array_filter([
        'especialidad' => $filtroEspecialidad,
        'estado_serv' => $filtroEstado,
        'medico' => $filtroMedico,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn ($v) => $v !== '' && $v !== null);

    $base = route('dashboard_especialidades_servicio') ?? '/portal/dashboard/Especialidades/servicio_medico';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$urlLimpia = route('dashboard_especialidades_servicio') ?? '/portal/dashboard/Especialidades/servicio_medico';

/** URLs de los reportes conservando los filtros activos (sin paginación). */
$urlPdf = route('portal_especialidad_pdf') ?? '/portal/dashboard/Especialidades/pdf';
$urlCsv = route('portal_especialidad_csv') ?? '/portal/dashboard/Especialidades/csv';
$paramsReporte = array_filter([
    'tipo' => 'servicio',
    'especialidad' => $filtroEspecialidad,
    'estado_serv' => $filtroEstado,
    'medico' => $filtroMedico,
], static fn ($v) => $v !== '' && $v !== null);

if ($paramsReporte !== []) {
    $urlPdf .= '?' . http_build_query($paramsReporte);
    $urlCsv .= '?' . http_build_query($paramsReporte);
}

/** Badges según el estado del servicio médico. */
$badgesServicio = [
    'ACTIVO' => 'badge-success',
    'SUSPENDIDO' => 'badge-warning',
    'FINALIZADO' => 'badge-secondary',
];
?>

<?= \Lib\Core\View::render('portal.especialidades.header', [
    'tab' => 'servicio',
    'statTotal' => (int) ($statTotal ?? 0),
    'statMedicos' => (int) ($statMedicos ?? 0),
]) ?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
    <button type="button" class="btn btn-primary" id="btn-nuevo-servicio">
        <i class="fas fa-plus mr-1"></i> Nuevo servicio médico
    </button>
    <small class="text-muted">Los filtros solo aplican en esta página.</small>
</div>

<div class="es-ayuda mb-3">
    <i class="fas fa-info-circle"></i>
    El médico debe tener la especialidad asignada en la página
    <strong>Médicos</strong> para poder sumarse al servicio; de este servicio se
    generan los horarios de atención y las fichas.
</div>

<form class="es-filtros" method="GET" action="<?= route('dashboard_especialidades_servicio') ?>">
    <div class="row align-items-end">
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-especialidad">Especialidad</label>
            <select class="form-control" id="filtro-especialidad" name="especialidad">
                <option value="">Todas</option>
                <?php foreach ($catalogoEspecialidades as $catalogo) { ?>
                    <option value="<?= (int) $catalogo['id'] ?>"
                        <?= $filtroEspecialidad === (string) (int) $catalogo['id'] ? 'selected' : '' ?>>
                        <?= e((string) $catalogo['nombre']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-estado-serv">Estado</label>
            <select class="form-control" id="filtro-estado-serv" name="estado_serv">
                <option value="">Todos</option>
                <option value="ACTIVO" <?= $filtroEstado === 'ACTIVO' ? 'selected' : '' ?>>Activo</option>
                <option value="SUSPENDIDO" <?= $filtroEstado === 'SUSPENDIDO' ? 'selected' : '' ?>>Suspendido</option>
                <option value="FINALIZADO" <?= $filtroEstado === 'FINALIZADO' ? 'selected' : '' ?>>Finalizado</option>
            </select>
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-medico">Médico</label>
            <input type="text" class="form-control" id="filtro-medico" name="medico" maxlength="100"
                value="<?= e($filtroMedico) ?>" placeholder="Nombre, CI o colegiado">
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
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
                    <th scope="col">Especialidad</th>
                    <th scope="col">Vigencia</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-center">Bloqueos</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servicios === []) { ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay servicios médicos que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($servicios as $servicio) {
                    $idServicio = (int) $servicio['id'];
                    $badgeEstado = $badgesServicio[$servicio['estado']] ?? 'badge-secondary';
                    $bloqueosActivos = (int) $servicio['bloqueos_activos'];
                ?>
                    <tr>
                        <td>
                            <div class="es-nombre">
                                <?= e($servicio['nombre_completo']) ?>
                                <small>
                                    CI <?= e($servicio['ci']) ?>
                                    · Colegiado <?= e($servicio['nro_colegiado'] !== '' ? $servicio['nro_colegiado'] : '—') ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <?= e($servicio['especialidad']) ?>
                            <?php if (trim((string) $servicio['institucion_formadora']) !== '') { ?>
                                <small class="d-block text-muted"><?= e($servicio['institucion_formadora']) ?></small>
                            <?php } ?>
                        </td>
                        <td class="es-fecha">
                            <?= e($servicio['fecha_inicio']) ?>
                            <?php if (trim((string) $servicio['fecha_fin']) !== '') { ?>
                                → <?= e($servicio['fecha_fin']) ?>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="badge <?= $badgeEstado ?>"><?= e($servicio['estado']) ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($bloqueosActivos > 0) { ?>
                                <span class="badge badge-warning"><?= $bloqueosActivos ?> activo(s)</span>
                            <?php } else { ?>
                                <span class="text-muted">0</span>
                            <?php } ?>
                        </td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary js-editar-servicio"
                                title="Editar servicio médico"
                                data-id="<?= $idServicio ?>"
                                data-url="<?= e(route('portal_especialidad_bloqueos', ['id' => '{id}']) ?? '') ?>">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger js-bloquear"
                                title="Añadir bloqueo de agenda"
                                data-id="<?= $idServicio ?>"
                                data-medico="<?= e($servicio['nombre_completo']) ?>"
                                data-especialidad="<?= e($servicio['especialidad']) ?>">
                                <i class="fas fa-ban"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary js-ver-bloqueos"
                                title="Ver bloqueos de agenda"
                                data-id="<?= $idServicio ?>"
                                data-medico="<?= e($servicio['nombre_completo']) ?>"
                                data-especialidad="<?= e($servicio['especialidad']) ?>"
                                data-url="<?= e(route('portal_especialidad_bloqueos', ['id' => '{id}']) ?? '') ?>">
                                <i class="fas fa-list"></i>
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="es-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> servicio(s) médico(s) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de servicios médicos">
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

<!-- Modal: crear / editar servicio médico -->
<div class="modal fade" id="modal-servicio" tabindex="-1" role="dialog" aria-labelledby="modal-servicio-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-servicio-title">
                    <i class="fas fa-stethoscope mr-2"></i> <span id="servicio-titulo">Nuevo servicio médico</span>
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="servicio-alerta" class="alert alert-danger es-alerta-error"></div>

                <form id="form-servicio" class="es-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" id="servicio-id" value="0">

                    <!-- Datos de solo lectura al editar (médico y especialidad son inmutables) -->
                    <div id="servicio-solo-lectura" class="es-ayuda mb-3" style="display:none;">
                        <i class="fas fa-user-md"></i>
                        <strong id="servicio-info-medico">—</strong>
                        <span id="servicio-info-especialidad"></span>
                    </div>

                    <div id="servicio-seleccion" class="row">
                        <div class="col-md-6 mb-3">
                            <label for="servicio-medico">Médico *</label>
                            <select class="form-control" id="servicio-medico">
                                <option value="0">— Selecciona un médico —</option>
                                <?php foreach ($catalogoMedicos as $medicoCatalogo) {
                                    $puede = isset($acreditacionesPorMedico[$medicoCatalogo['id']]);
                                ?>
                                    <option value="<?= $medicoCatalogo['id'] ?>" data-acreditado="<?= $puede ? '1' : '0' ?>">
                                        <?= e($medicoCatalogo['nombre_completo']) ?>
                                        <?php if (!$puede) { ?> (sin especialidad asignada)<?php } ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="servicio-especialidad">Especialidad *</label>
                            <select class="form-control" id="servicio-especialidad">
                                <option value="0">— Selecciona el médico —</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="servicio-fecha-inicio">Fecha de inicio *</label>
                            <input type="date" class="form-control" id="servicio-fecha-inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="servicio-fecha-fin">Fecha de fin</label>
                            <input type="date" class="form-control" id="servicio-fecha-fin" name="fecha_fin">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="servicio-estado">Estado *</label>
                            <select class="form-control" id="servicio-estado" name="estado">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="SUSPENDIDO">SUSPENDIDO</option>
                                <option value="FINALIZADO">FINALIZADO</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="servicio-observaciones">Observaciones</label>
                        <textarea class="form-control" id="servicio-observaciones" name="observaciones" rows="3"
                            maxlength="2000" placeholder="Motivo de la incorporación, suspensiones, etc."></textarea>
                    </div>

                    <div class="es-ayuda">
                        <i class="fas fa-info-circle"></i>
                        El servicio es lo que habilita los <strong>horarios de atención</strong> y las
                        <strong>fichas</strong> del médico. Solo puede haber un servicio ACTIVO por
                        especialidad y médico.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-servicio">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: registrar bloqueo de agenda -->
<div class="modal fade" id="modal-bloqueo" tabindex="-1" role="dialog" aria-labelledby="modal-bloqueo-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#c0392b;color:#fff;">
                <h5 class="modal-title" id="modal-bloqueo-title">
                    <i class="fas fa-ban mr-2"></i> Bloqueo de agenda
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="bloqueo-alerta" class="alert alert-danger es-alerta-error"></div>

                <div class="es-ayuda mb-3">
                    <i class="fas fa-user-md"></i>
                    <strong id="bloqueo-medico">—</strong>
                    <span id="bloqueo-especialidad"></span>
                </div>

                <form id="form-bloqueo" class="es-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id_servicio" id="bloqueo-id-servicio" value="0">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bloqueo-inicio">Inicio *</label>
                            <input type="datetime-local" class="form-control" id="bloqueo-inicio"
                                name="fecha_hora_inicio" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bloqueo-fin">Fin *</label>
                            <input type="datetime-local" class="form-control" id="bloqueo-fin"
                                name="fecha_hora_fin" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="bloqueo-motivo">Motivo</label>
                        <textarea class="form-control" id="bloqueo-motivo" name="motivo" rows="3"
                            maxlength="200" placeholder="Ej. Permiso médico, capacitación, ausencia..."></textarea>
                    </div>

                    <div class="es-ayuda">
                        <i class="fas fa-info-circle"></i>
                        El bloqueo impide atender dentro de ese rango y no puede solaparse con otro
                        bloqueo activo del mismo médico.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-guardar-bloqueo">
                    <i class="fas fa-save mr-1"></i> Registrar bloqueo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: bloqueos registrados del médico -->
<div class="modal fade" id="modal-bloqueos" tabindex="-1" role="dialog" aria-labelledby="modal-bloqueos-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-bloqueos-title">
                    <i class="fas fa-list mr-2"></i> Bloqueos de agenda
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="es-ayuda mb-3">
                    <i class="fas fa-user-md"></i>
                    <strong id="ver-bloqueos-medico">—</strong>
                    <span id="ver-bloqueos-especialidad"></span>
                </div>

                <div id="ver-bloqueos-contenido"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var token = '<?= csrf_token() ?>';
        var urlBloqueo = '<?= route('portal_especialidad_bloqueo') ?>';
        var urlAnular = '<?= route('portal_especialidad_bloqueo_anular') ?>';
        var urlGuardarServicio = '<?= route('portal_especialidad_servicio_guardar') ?>';
        var urlBaseBloqueos = '<?= e(route('portal_especialidad_bloqueos', ['id' => '{id}']) ?? '') ?>';

        var acreditacionesPorMedico = <?= json_encode($acreditacionesPorMedico, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

        var $modalServicio = $('#modal-servicio');
        var $alertaServicio = $('#servicio-alerta');
        var $btnGuardarServicio = $('#btn-guardar-servicio');

        var $modalBloqueo = $('#modal-bloqueo');
        var $alertaBloqueo = $('#bloqueo-alerta');
        var $btnGuardarBloqueo = $('#btn-guardar-bloqueo');

        var $modalBloqueos = $('#modal-bloqueos');
        var $contenidoBloqueos = $('#ver-bloqueos-contenido');

        var servicioActual = 0;

        function escapar(texto) {
            if (typeof window.escapeHtml === 'function') {
                return window.escapeHtml(texto);
            }
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(texto === null || texto === undefined ? '' : String(texto)));
            return div.innerHTML;
        }

        function mostrarError($alerta, mensaje) {
            $alerta.text(mensaje || 'Ocurrió un error.').show();
        }

        function ocultarError($alerta) {
            $alerta.hide().text('');
        }

        function fechaLegible(valor) {
            if (!valor) {
                return '—';
            }
            var texto = String(valor);
            if (texto.indexOf('/') !== -1) {
                return texto;
            }
            var partes = texto.replace('T', ' ').split(/[- :]/);
            if (partes.length < 5) {
                return valor;
            }
            return partes[2] + '/' + partes[1] + '/' + partes[0] + ' ' + partes[3] + ':' + partes[4];
        }

        /* ------------------------------------------------------------------
         * Alta y edición de servicios médicos
         * ---------------------------------------------------------------- */
        function pintarEspecialidades(idMedico) {
            var $select = $('#servicio-especialidad');
            var lista = acreditacionesPorMedico[String(idMedico)] || [];

            $select.empty();

            if (!lista.length) {
                $select.append($('<option>').val('0').text('— Sin especialidades asignadas —'));
                return;
            }

            $select.append($('<option>').val('0').text('— Selecciona una especialidad —'));

            lista.forEach(function (especialidad) {
                $select.append($('<option>').val(String(especialidad.id)).text(especialidad.nombre));
            });
        }

        function fechaHoy() {
            var hoy = new Date();
            var mes = ('0' + (hoy.getMonth() + 1)).slice(-2);
            var dia = ('0' + hoy.getDate()).slice(-2);

            return hoy.getFullYear() + '-' + mes + '-' + dia;
        }

        function abrirServicioNuevo() {
            ocultarError($alertaServicio);
            $('#servicio-id').val('0');
            $('#servicio-titulo').text('Nuevo servicio médico');
            $('#servicio-seleccion').show();
            $('#servicio-solo-lectura').hide();
            $('#servicio-medico').val('0');
            pintarEspecialidades(0);
            $('#servicio-fecha-inicio').val(fechaHoy());
            $('#servicio-fecha-fin').val('');
            $('#servicio-estado').val('ACTIVO');
            $('#servicio-observaciones').val('');
            $modalServicio.modal('show');
        }

        $('#btn-nuevo-servicio').on('click', abrirServicioNuevo);

        $('#servicio-medico').on('change', function () {
            pintarEspecialidades($(this).val());
        });

        $(document).on('click', '.js-editar-servicio', function () {
            var id = parseInt($(this).attr('data-id'), 10) || 0;
            var url = $(this).attr('data-url').replace('{id}', id);

            ocultarError($alertaServicio);
            $('#servicio-id').val(String(id));
            $('#servicio-titulo').text('Editar servicio médico');
            $('#servicio-solo-lectura').show();
            $('#servicio-seleccion').hide();
            $('#servicio-info-medico').text('—');
            $('#servicio-info-especialidad').text('');
            $('#servicio-fecha-inicio').val('');
            $('#servicio-fecha-fin').val('');
            $('#servicio-estado').val('ACTIVO');
            $('#servicio-observaciones').val('');
            $modalServicio.modal('show');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success || !respuesta.servicio) {
                    mostrarError($alertaServicio, (respuesta && respuesta.message) || 'No se pudo cargar el servicio.');
                    return;
                }

                var servicio = respuesta.servicio;

                $('#servicio-info-medico').text(servicio.nombre_completo || '—');
                $('#servicio-info-especialidad').text(' · ' + (servicio.especialidad || ''));
                $('#servicio-fecha-inicio').val(String(servicio.fecha_inicio || '').slice(0, 10));
                $('#servicio-fecha-fin').val(String(servicio.fecha_fin || '').slice(0, 10));
                $('#servicio-estado').val(servicio.estado || 'ACTIVO');
                $('#servicio-observaciones').val(servicio.observaciones || '');
            }).fail(function () {
                mostrarError($alertaServicio, 'No se pudo cargar el servicio médico. Cierra y vuelve a intentar.');
            });
        });

        $btnGuardarServicio.on('click', function () {
            ocultarError($alertaServicio);

            var id = parseInt($('#servicio-id').val(), 10) || 0;
            var fechaInicio = $('#servicio-fecha-inicio').val();
            var fechaFin = $('#servicio-fecha-fin').val();

            if (!fechaInicio) {
                mostrarError($alertaServicio, 'Indica la fecha de inicio del servicio.');
                return;
            }

            if (fechaFin && fechaFin < fechaInicio) {
                mostrarError($alertaServicio, 'La fecha de fin no puede ser anterior a la de inicio.');
                return;
            }

            var datos = {
                _token: token,
                id: id,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                estado: $('#servicio-estado').val(),
                observaciones: $('#servicio-observaciones').val()
            };

            if (id === 0) {
                var idMedico = parseInt($('#servicio-medico').val(), 10) || 0;
                var idEspecialidad = parseInt($('#servicio-especialidad').val(), 10) || 0;

                if (idMedico <= 0) {
                    mostrarError($alertaServicio, 'Selecciona el médico.');
                    return;
                }

                if (idEspecialidad <= 0) {
                    mostrarError($alertaServicio, 'Selecciona la especialidad. Si el médico no la tiene '
                        + 'asignada, primero hazlo desde la página Médicos.');
                    return;
                }

                datos.id_medico = idMedico;
                datos.especialidad = idEspecialidad;
            }

            var $btn = $(this).prop('disabled', true);

            $.ajax({
                url: urlGuardarServicio,
                method: 'POST',
                dataType: 'json',
                data: datos,
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (respuesta && respuesta.success) {
                    window.location.reload();
                    return;
                }
                mostrarError($alertaServicio, (respuesta && respuesta.message) || 'No se pudo guardar el servicio médico.');
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaServicio, (r && r.message) || 'No se pudo guardar el servicio médico. Revisa los datos.');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        /* ------------------------------------------------------------------
         * Registrar bloqueo de agenda
         * ---------------------------------------------------------------- */
        $(document).on('click', '.js-bloquear', function () {
            servicioActual = parseInt($(this).attr('data-id'), 10) || 0;

            ocultarError($alertaBloqueo);
            $('#bloqueo-id-servicio').val(servicioActual);
            $('#bloqueo-medico').text($(this).attr('data-medico') || '—');
            $('#bloqueo-especialidad').text(' · ' + ($(this).attr('data-especialidad') || ''));
            $('#bloqueo-inicio').val('');
            $('#bloqueo-fin').val('');
            $('#bloqueo-motivo').val('');

            $modalBloqueo.modal('show');
        });

        $btnGuardarBloqueo.on('click', function () {
            ocultarError($alertaBloqueo);

            var inicio = $('#bloqueo-inicio').val();
            var fin = $('#bloqueo-fin').val();

            if (!inicio || !fin) {
                mostrarError($alertaBloqueo, 'Indica la fecha y hora de inicio y de fin del bloqueo.');
                return;
            }

            if (fin <= inicio) {
                mostrarError($alertaBloqueo, 'La fecha y hora de fin deben ser posteriores al inicio.');
                return;
            }

            var $btn = $(this).prop('disabled', true);

            $.ajax({
                url: urlBloqueo,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: token,
                    id_servicio: $('#bloqueo-id-servicio').val(),
                    fecha_hora_inicio: inicio,
                    fecha_hora_fin: fin,
                    motivo: $('#bloqueo-motivo').val()
                },
                headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (respuesta && respuesta.success) {
                    window.location.reload();
                    return;
                }
                mostrarError($alertaBloqueo, (respuesta && respuesta.message) || 'No se pudo registrar el bloqueo.');
            }).fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                mostrarError($alertaBloqueo, (r && r.message) || 'No se pudo registrar el bloqueo. Revisa los datos.');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        /* ------------------------------------------------------------------
         * Listado de bloqueos por médico
         * ---------------------------------------------------------------- */
        function renderBloqueos(bloqueos) {
            if (!bloqueos || bloqueos.length === 0) {
                $contenidoBloqueos.html('<p class="text-muted mb-0">'
                    + '<i class="fas fa-info-circle"></i> Este médico no tiene bloqueos de agenda registrados.</p>');
                return;
            }

            var html = '';

            bloqueos.forEach(function (bloqueo) {
                html += '<div class="es-bloqueo-item' + (bloqueo.activo ? '' : ' anulado') + '">'
                    + '<strong>' + escapar(fechaLegible(bloqueo.fecha_hora_inicio))
                    + ' → ' + escapar(fechaLegible(bloqueo.fecha_hora_fin)) + '</strong>'
                    + '<small>'
                    + (bloqueo.motivo ? escapar(bloqueo.motivo) : 'Sin motivo registrado')
                    + ' · Registrado por ' + escapar(bloqueo.registrado_por)
                    + '</small>'
                    + '<div class="mt-2">';

                if (bloqueo.activo) {
                    html += '<button type="button" class="btn btn-sm btn-outline-danger js-anular-bloqueo" '
                        + 'data-id="' + bloqueo.id + '">'
                        + '<i class="fas fa-times mr-1"></i> Anular bloqueo</button>';
                } else {
                    html += '<span class="badge badge-secondary">Anulado</span>';
                }

                html += '</div></div>';
            });

            $contenidoBloqueos.html(html);
        }

        function cargarBloqueos(id) {
            $contenidoBloqueos.html('<div class="text-center py-4">'
                + '<i class="fas fa-circle-notch fa-spin fa-2x text-muted"></i>'
                + '<p class="text-muted mt-3 mb-0">Cargando bloqueos de agenda...</p></div>');

            $.ajax({
                url: urlBaseBloqueos.replace('{id}', id),
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success) {
                    $contenidoBloqueos.html('<div class="alert alert-danger mb-0">'
                        + escapar((respuesta && respuesta.message) || 'No se pudieron cargar los bloqueos.') + '</div>');
                    return;
                }
                renderBloqueos(respuesta.bloqueos);
            }).fail(function () {
                $contenidoBloqueos.html('<div class="alert alert-danger mb-0">'
                    + 'No se pudieron cargar los bloqueos de agenda.</div>');
            });
        }

        $(document).on('click', '.js-ver-bloqueos', function () {
            servicioActual = parseInt($(this).attr('data-id'), 10) || 0;

            $('#ver-bloqueos-medico').text($(this).attr('data-medico') || '—');
            $('#ver-bloqueos-especialidad').text(' · ' + ($(this).attr('data-especialidad') || ''));

            $modalBloqueos.modal('show');
            cargarBloqueos(servicioActual);
        });

        $(document).on('click', '.js-anular-bloqueo', function () {
            var $btn = $(this);
            var id = $btn.attr('data-id');

            window.espConfirmar({
                titulo: 'Anular bloqueo de agenda',
                mensaje: '¿Deseas anular este bloqueo? El médico podrá atender en ese rango de tiempo.',
                btn: 'Sí, anular'
            }, function () {
                $btn.prop('disabled', true);

                $.ajax({
                    url: urlAnular,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: token,
                        id: id
                    },
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                }).done(function (respuesta) {
                    if (respuesta && respuesta.success) {
                        cargarBloqueos(servicioActual);
                        return;
                    }
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (respuesta && respuesta.message) || 'No se pudo anular el bloqueo.');
                }).fail(function (xhr) {
                    var r = xhr && xhr.responseJSON;
                    $btn.prop('disabled', false);
                    window.espNotificar('error', (r && r.message) || 'No se pudo anular el bloqueo.');
                });
            });
        });
    });
</script>
