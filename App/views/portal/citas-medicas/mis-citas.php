<?php
/**
 * Historial de citas del paciente.
 *
 * Orquesta: filtros, tabla paginada y modales de detalle/cancelar.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'pendientes' => 0,
    'en_clinica' => 0,
    'atendidas' => 0,
    'canceladas' => 0,
    'inasistencias' => 0,
];
$citas = $citas ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$numerosPagina = $numerosPagina ?? [];
$filtroEstado = (string) ($filtroEstado ?? '');
$filtroDesde = (string) ($filtroDesde ?? '');
$filtroHasta = (string) ($filtroHasta ?? '');
$filtroTexto = (string) ($filtroTexto ?? '');
$estadosDisponibles = $estadosDisponibles ?? [];
$urlLimpia = route('dashboard_mis_citas') ?? '/portal/dashboard/citas-medicas/mis-citas';

$urlPagina = static function (int $nuevaPagina) use (
    $filtroEstado, $filtroDesde, $filtroHasta, $filtroTexto, $urlLimpia
): string {
    $params = array_filter([
        'estado' => $filtroEstado,
        'desde' => $filtroDesde,
        'hasta' => $filtroHasta,
        'texto' => $filtroTexto,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn ($v) => $v !== '' && $v !== null);

    return $params === [] ? $urlLimpia : $urlLimpia . '?' . http_build_query($params);
};

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$formatoFecha = static function (string $valor): string {
    if ($valor === '') {
        return '—';
    }

    return substr($valor, 8, 2) . '/' . substr($valor, 5, 2) . '/' . substr($valor, 0, 4)
        . ' ' . substr($valor, 11, 5);
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

<style>
    .cm-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
    .cm-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px; margin-bottom: 20px; }
    .cm-stat { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; }
    .cm-stat-icon { width: 48px; height: 48px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
    .cm-stat-icon.total { background: #e8f4f3; color: var(--portal-primary); }
    .cm-stat-icon.pendientes { background: #fef7e0; color: #b8860b; }
    .cm-stat-icon.clinica { background: #e8f0fe; color: #1a73e8; }
    .cm-stat-icon.atendidas { background: #e7f7ed; color: #1e8e3e; }
    .cm-stat-icon.canceladas { background: #fdecea; color: #c0392b; }
    .cm-stat-icon.inasistencias { background: #f3e8ff; color: #7c3aed; }
    .cm-stat-num { font-size: 1.5rem; font-weight: 800; color: #0b2a38; line-height: 1.1; }
    .cm-stat-label { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7c83; }
    .cm-filtros { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; padding: 20px 22px; margin-bottom: 20px; }
    .cm-tabla { background: #fff; border: 1px solid #e4e9e7; border-radius: 16px; overflow: hidden; }
    .cm-tabla table { margin-bottom: 0; }
    .cm-tabla thead th { background: #f4f7f6; border-bottom: 1px solid #e4e9e7; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: #44545c; white-space: nowrap; }
    .cm-tabla td { vertical-align: middle; border-color: #eef2f1; font-size: 0.92rem; }
    .cm-hora { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .cm-nombre { font-weight: 600; color: #0b2a38; line-height: 1.25; }
    .cm-nombre small { display: block; font-weight: 500; color: #6b7c83; font-size: 0.8rem; }
    .cm-paginacion { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
    .cm-paginacion .page-link { color: #44545c; border-color: #e0e6e3; }
    .cm-paginacion .page-item.active .page-link { background: var(--portal-primary); border-color: var(--portal-primary); }
    @media (max-width: 575.98px) {
        .cm-stats { grid-template-columns: repeat(2, 1fr); }
        .cm-tabla thead { display: none; }
        .cm-tabla, .cm-tabla tbody, .cm-tabla tr, .cm-tabla td { display: block; width: 100%; }
        .cm-tabla tr { border-bottom: 1px solid #eef2f1; padding: 10px 0; }
        .cm-tabla td { border: 0; padding: 4px 16px; }
    }
</style>

<div class="cm-header">
    <div>
        <h3 class="m-0"><i class="fas fa-history text-secondary"></i> Mis citas</h3>
        <p class="text-muted mb-0">Historial completo de tus citas médicas.</p>
    </div>
    <a href="<?= route('dashboard_citas_medicas') ?? '/portal/dashboard/citas-medicas' ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver a citas
    </a>
</div>

<div class="cm-stats">
    <div class="cm-stat"><span class="cm-stat-icon total"><i class="fas fa-calendar"></i></span><div><div class="cm-stat-num"><?= (int) ($estadisticas['total'] ?? 0) ?></div><div class="cm-stat-label">Total</div></div></div>
    <div class="cm-stat"><span class="cm-stat-icon pendientes"><i class="fas fa-clock"></i></span><div><div class="cm-stat-num"><?= (int) ($estadisticas['pendientes'] ?? 0) ?></div><div class="cm-stat-label">Pendientes</div></div></div>
    <div class="cm-stat"><span class="cm-stat-icon clinica"><i class="fas fa-hospital"></i></span><div><div class="cm-stat-num"><?= (int) ($estadisticas['en_clinica'] ?? 0) ?></div><div class="cm-stat-label">En clínica</div></div></div>
    <div class="cm-stat"><span class="cm-stat-icon atendidas"><i class="fas fa-check-circle"></i></span><div><div class="cm-stat-num"><?= (int) ($estadisticas['atendidas'] ?? 0) ?></div><div class="cm-stat-label">Atendidas</div></div></div>
    <div class="cm-stat"><span class="cm-stat-icon canceladas"><i class="fas fa-times-circle"></i></span><div><div class="cm-stat-num"><?= (int) ($estadisticas['canceladas'] ?? 0) ?></div><div class="cm-stat-label">Canceladas</div></div></div>
    <div class="cm-stat"><span class="cm-stat-icon inasistencias"><i class="fas fa-user-slash"></i></span><div><div class="cm-stat-num"><?= (int) ($estadisticas['inasistencias'] ?? 0) ?></div><div class="cm-stat-label">Inasistencias</div></div></div>
</div>

<form class="cm-filtros" method="GET" action="<?= e($urlLimpia) ?>">
    <div class="row align-items-end">
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-estado">Estado</label>
            <select class="form-control" id="filtro-estado" name="estado">
                <option value="">Todos</option>
                <?php foreach ($estadosDisponibles as $clave => $etiqueta) { ?>
                    <option value="<?= e($clave) ?>" <?= $filtroEstado === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-lg-2 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-desde">Desde</label>
            <input type="date" class="form-control" id="filtro-desde" name="desde" value="<?= e($filtroDesde) ?>">
        </div>
        <div class="col-lg-2 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-hasta">Hasta</label>
            <input type="date" class="form-control" id="filtro-hasta" name="hasta" value="<?= e($filtroHasta) ?>">
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-texto">Búsqueda</label>
            <input type="text" class="form-control" id="filtro-texto" name="texto"
                value="<?= e($filtroTexto) ?>" maxlength="100" placeholder="Código, médico o motivo">
        </div>
        <div class="col-lg-2 col-md-6 mb-3 mb-md-0 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            <a href="<?= e($urlLimpia) ?>" class="btn btn-outline-secondary"><i class="fas fa-eraser"></i></a>
        </div>
    </div>
</form>

<div class="cm-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Fecha y hora</th>
                    <th scope="col">Médico</th>
                    <th scope="col">Especialidad</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($citas === []) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay citas que coincidan con los filtros.
                        </td>
                    </tr>
                <?php } ?>
                <?php foreach ($citas as $cita) { ?>
                    <tr>
                        <td class="cm-hora"><?= e($formatoFecha((string) $cita['fecha_hora_inicio'])) ?></td>
                        <td>
                            <div class="cm-nombre">
                                <?= e((string) $cita['medico']) ?>
                                <small>Colegiado <?= e((string) $cita['nro_colegiado']) ?></small>
                            </div>
                        </td>
                        <td><?= e((string) $cita['especialidad']) ?></td>
                        <td>
                            <span class="badge <?= e($claseEstado((string) $cita['estado'])) ?>">
                                <?= e((string) $cita['estado']) ?>
                            </span>
                        </td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary js-detalle-cita"
                                data-id="<?= (int) $cita['id'] ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="cm-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> cita(s) · <?= $porPagina ?> por página
    </small>
    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de citas">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($urlPagina(max(1, $pagina - 1))) ?>"><i class="fas fa-chevron-left"></i></a>
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
                    <a class="page-link" href="<?= e($urlPagina(min($paginas, $pagina + 1))) ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php } ?>
</div>

<?php require __DIR__ . '/../base/confirmar.php'; ?>
<?php require __DIR__ . '/modales.php'; ?>
