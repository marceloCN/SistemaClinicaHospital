<?php
/**
 * Tabla de citas del día + bloqueos activos en recepción.
 */
$citas = $citas ?? [];
$bloqueos = $bloqueos ?? [];
$estadosDisponibles = $estadosDisponibles ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$numerosPagina = $numerosPagina ?? [];
$filtroEstado = (string) ($filtroEstado ?? '');
$filtroTexto = (string) ($filtroTexto ?? '');
$urlLimpia = route('dashboard_recepcion_citas_medicas') ?? '/portal/dashboard/recepcion-cita-medica';

$urlPagina = static function (int $nuevaPagina) use ($filtroEstado, $filtroTexto, $urlLimpia): string {
    $params = array_filter([
        'estado' => $filtroEstado,
        'texto' => $filtroTexto,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn ($v) => $v !== '' && $v !== null);

    return $params === [] ? $urlLimpia : $urlLimpia . '?' . http_build_query($params);
};

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$formatoHora = static function (?string $valor): string {
    if ($valor === null || $valor === '') return '—';
    return substr($valor, 11, 5);
};

$rangoHorario = static function (string $inicio, string $fin): string {
    $ini = substr($inicio, 11, 5);
    $finHora = substr($fin, 11, 5);
    return ($ini === '' ? '—' : $ini) . ' - ' . ($finHora === '' ? '—' : $finHora);
};

$claseEstado = static function (string $estado): string {
    return match ($estado) {
        'PROGRAMADA' => 'badge-info',
        'CONFIRMADA' => 'badge-primary',
        'REPROGRAMADA' => 'badge-warning',
        'EN_RECEPCION', 'EN_ATENCION' => 'badge-secondary',
        'ATENDIDA' => 'badge-success',
        'CANCELADA' => 'badge-danger',
        'INASISTENCIA' => 'badge-dark',
        default => 'badge-light',
    };
};
?>

<div class="row">
    <div class="col-xl-8">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
            <h5 class="m-0">Citas del día</h5>
            <button type="button" class="btn btn-primary" id="btn-registrar-cita">
                <i class="fas fa-plus mr-1"></i> Registrar cita
            </button>
        </div>

        <form class="rc-filtros" method="GET" action="<?= e($urlLimpia) ?>">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label" for="filtro-estado">Estado</label>
                    <select class="form-control" id="filtro-estado" name="estado">
                        <option value="">Todos</option>
                        <?php foreach ($estadosDisponibles as $clave => $etiqueta) { ?>
                            <option value="<?= e($clave) ?>" <?= $filtroEstado === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-5 mb-3 mb-md-0">
                    <label class="form-label" for="filtro-texto">Paciente / código</label>
                    <input type="text" class="form-control" id="filtro-texto" name="texto" maxlength="100" value="<?= e($filtroTexto) ?>">
                </div>
                <div class="col-md-3 mb-3 mb-md-0 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    <a href="<?= e($urlLimpia) ?>" class="btn btn-outline-secondary"><i class="fas fa-eraser"></i></a>
                </div>
            </div>
        </form>

        <div class="rc-tabla">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Hora</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Médico / Especialidad</th>
                            <th scope="col">Código</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($citas === []) { ?>
                            <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox"></i> No hay citas para hoy con los filtros aplicados.</td></tr>
                        <?php } ?>
                        <?php foreach ($citas as $cita) { ?>
                            <tr>
                                <td class="rc-hora"><?= e($rangoHorario((string) $cita['fecha_hora_inicio'], (string) $cita['fecha_hora_fin'])) ?></td>
                                <td>
                                    <div class="rc-nombre">
                                        <?= e((string) $cita['paciente']) ?>
                                        <small>CI <?= e((string) $cita['paciente_ci']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="rc-nombre">
                                        <?= e((string) $cita['medico']) ?>
                                        <small><?= e((string) $cita['especialidad']) ?></small>
                                    </div>
                                </td>
                                <td><code><?= e((string) $cita['codigo']) ?></code></td>
                                <td>
                                    <span class="badge <?= e($claseEstado((string) $cita['estado'])) ?>"><?= e((string) $cita['estado']) ?></span>
                                    <?php if (!empty($cita['id_recepcion'])) { ?>
                                        <span class="badge badge-light border" title="Recepcionada"><i class="fas fa-sign-in-alt"></i></span>
                                    <?php } ?>
                                </td>
                                <td class="text-right text-nowrap">
                                    <?php if (empty($cita['id_recepcion']) && in_array($cita['estado'], ['PROGRAMADA'], true)) { ?>
                                        <button type="button" class="btn btn-sm btn-success js-recepcionar-cita"
                                            data-codigo="<?= e((string) $cita['codigo']) ?>" title="Recepcionar">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </button>
                                    <?php } ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary js-detalle-cita"
                                        data-id="<?= (int) $cita['id'] ?>" title="Detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rc-paginacion">
            <small class="text-muted">Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> cita(s) · <?= $porPagina ?> por página</small>
            <?php if ($paginas > 1) { ?>
                <nav aria-label="Paginación de citas">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($urlPagina(max(1, $pagina - 1))) ?>"><i class="fas fa-chevron-left"></i></a></li>
                        <?php foreach ($numerosPagina as $numero) { ?>
                            <?php if ($numero === '...') { ?><li class="page-item disabled"><span class="page-link">…</span></li><?php } else { ?>
                                <li class="page-item <?= (int) $numero === $pagina ? 'active' : '' ?>"><a class="page-link" href="<?= e($urlPagina((int) $numero)) ?>"><?= (int) $numero ?></a></li>
                            <?php } ?>
                        <?php } ?>
                        <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($urlPagina(min($paginas, $pagina + 1))) ?>"><i class="fas fa-chevron-right"></i></a></li>
                    </ul>
                </nav>
            <?php } ?>
        </div>
    </div>

    <div class="col-xl-4 mt-4 mt-xl-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0">Check-in</h5>
        </div>
        <div class="rc-filtros mb-4">
            <label class="form-label" for="checkin-codigo">Código de la cita</label>
            <div class="input-group">
                <input type="text" class="form-control" id="checkin-codigo" placeholder="Ej. C1A2B3C4" maxlength="20">
                <div class="input-group-append">
                    <button type="button" class="btn btn-primary" id="btn-buscar-codigo"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0">Bloqueos activos</h5>
        </div>
        <div class="rc-bloqueos">
            <?php if ($bloqueos === []) { ?>
                <p class="text-muted mb-0"><i class="fas fa-inbox"></i> No hay bloqueos activos.</p>
            <?php } ?>
            <?php foreach ($bloqueos as $bloqueo) { ?>
                <div class="d-flex justify-content-between align-items-start mb-2 pb-2" style="border-bottom:1px solid #eef2f1;">
                    <div>
                        <div class="rc-nombre"><?= e((string) $bloqueo['nombre_completo']) ?></div>
                        <small class="d-block text-muted"><?= e((string) $bloqueo['especialidad']) ?> · <?= e((string) $bloqueo['motivo']) ?></small>
                        <small class="d-block text-muted"><?= e((string) $bloqueo['fecha_hora_inicio']) ?> → <?= e((string) $bloqueo['fecha_hora_fin']) ?></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-warning js-ver-bloqueo"
                        data-id="<?= (int) $bloqueo['id'] ?>"
                        data-afectadas="<?= (int) $bloqueo['citas_afectadas'] ?>">
                        <?= (int) $bloqueo['citas_afectadas'] ?> <i class="fas fa-calendar-check"></i>
                    </button>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
