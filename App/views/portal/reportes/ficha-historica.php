<?php
/**
 * Reporte de fichas históricas.
 *
 * Listado paginado de fichas de agenda de horarios que ya cumplieron su
 * vigencia (fecha_hasta < hoy), con filtros y totales. Los horarios
 * vigentes siguen gestionándose en el módulo de horarios de atención.
 */
$fichas = $fichas ?? [];
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'abiertas' => 0,
    'cerradas' => 0,
    'bloqueadas' => 0,
    'con_cita' => 0,
    'sin_cita' => 0,
    'atendidas' => 0,
];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 20);
$numerosPagina = $numerosPagina ?? [];
$filtros = $filtros ?? [
    'desde' => '',
    'hasta' => '',
    'medico' => '',
    'especialidad' => '',
    'estado' => '',
    'paciente' => '',
    'vigencia' => '',
];
$catalogoEspecialidades = $catalogoEspecialidades ?? [];

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$urlPagina = static function (int $nuevaPagina) use ($filtros): string {
    $params = array_filter([
        'desde' => (string) $filtros['desde'],
        'hasta' => (string) $filtros['hasta'],
        'medico' => (string) $filtros['medico'],
        'especialidad' => (string) $filtros['especialidad'],
        'estado' => (string) $filtros['estado'],
        'paciente' => (string) $filtros['paciente'],
        'vigencia' => (string) $filtros['vigencia'],
        'page' => $nuevaPagina > 1 ? (string) $nuevaPagina : null,
    ], static fn ($v): bool => $v !== '' && $v !== null);

    $base = route('portal_reporte_ficha_historica') ?? '/portal/dashboard/reportes/ficha-historica';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$urlLimpia = route('portal_reporte_ficha_historica') ?? '/portal/dashboard/reportes/ficha-historica';

$claseEstadoFicha = [
    'ABIERTA' => 'badge-success',
    'CERRADA' => 'badge-primary',
    'BLOQUEADA' => 'badge-warning',
];

$claseEstadoCita = [
    'PROGRAMADA' => 'badge-info',
    'CONFIRMADA' => 'badge-success',
    'REPROGRAMADA' => 'badge-info',
    'EN_RECEPCION' => 'badge-info',
    'EN_ATENCION' => 'badge-warning',
    'ATENDIDA' => 'badge-success',
    'CANCELADA' => 'badge-danger',
    'INASISTENCIA' => 'badge-danger',
];
?>

<style>
    .rp-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .rp-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .rp-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .rp-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
        background: #e8f4f3;
        color: var(--portal-primary);
    }

    .rp-stat-num {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1.1;
    }

    .rp-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .rp-filtros {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }

    .rp-filtros .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .rp-filtros .form-control,
    .rp-filtros .form-control:focus {
        background: #f7f9f8;
        border-color: #e0e6e3;
        box-shadow: none;
    }

    .rp-ayuda {
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.86rem;
        color: #3d6a8f;
        margin-bottom: 20px;
    }

    .rp-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .rp-tabla table { margin-bottom: 0; }

    .rp-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }

    .rp-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.92rem;
    }

    .rp-nombre {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .rp-nombre small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.8rem;
    }

    .rp-hora {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .rp-paginacion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .rp-paginacion .page-link {
        color: #44545c;
        border-color: #e0e6e3;
    }

    .rp-paginacion .page-item.active .page-link {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
    }

    @media (max-width: 575.98px) {
        .rp-filtros { padding: 16px; }

        .rp-tabla thead { display: none; }
        .rp-tabla,
        .rp-tabla tbody,
        .rp-tabla tr,
        .rp-tabla td { display: block; width: 100%; }
        .rp-tabla tr { border-bottom: 1px solid #eef2f1; padding: 10px 0; }
        .rp-tabla td { border: 0; padding: 4px 16px; }
    }
</style>

<div class="rp-header">
    <div>
        <h3 class="m-0"><i class="fas fa-calendar-day text-secondary"></i> Fichas de agenda</h3>
        <p class="text-muted mb-0">Todas las fichas generadas por los horarios de atención.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= $total ?> ficha(s)</span>
</div>

<div class="rp-ayuda">
    <i class="fas fa-info-circle"></i>
    Aquí puedes consultar todas las fichas (disponibles, cerradas o bloqueadas) filtrando por fecha,
    médico, especialidad, estado de la ficha, vigencia del horario y paciente. Los filtros solo aplican en esta página.
</div>

<div class="rp-stats">
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-calendar-check"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) $estadisticas['total'] ?></div>
            <div class="rp-stat-label">Fichas</div>
        </div>
    </div>
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-door-open"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) $estadisticas['abiertas'] ?></div>
            <div class="rp-stat-label">Abiertas</div>
        </div>
    </div>
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-lock"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) $estadisticas['cerradas'] ?></div>
            <div class="rp-stat-label">Cerradas</div>
        </div>
    </div>
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-ban"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) $estadisticas['bloqueadas'] ?></div>
            <div class="rp-stat-label">Bloqueadas</div>
        </div>
    </div>
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-link"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) $estadisticas['con_cita'] ?></div>
            <div class="rp-stat-label">Con cita</div>
        </div>
    </div>
    <div class="rp-stat">
        <span class="rp-stat-icon"><i class="fas fa-check-double"></i></span>
        <div>
            <div class="rp-stat-num"><?= (int) $estadisticas['atendidas'] ?></div>
            <div class="rp-stat-label">Atendidas</div>
        </div>
    </div>
</div>

<form class="rp-filtros" method="GET" action="<?= route('portal_reporte_ficha_historica') ?>">
    <div class="row align-items-end">
        <div class="col-lg-2 col-md-4 col-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-desde">Fecha desde</label>
            <input type="date" class="form-control" id="filtro-desde" name="desde"
                value="<?= e((string) $filtros['desde']) ?>">
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-hasta">Fecha hasta</label>
            <input type="date" class="form-control" id="filtro-hasta" name="hasta"
                value="<?= e((string) $filtros['hasta']) ?>">
        </div>
        <div class="col-lg-3 col-md-4 col-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-medico">Médico</label>
            <input type="text" class="form-control" id="filtro-medico" name="medico" maxlength="100"
                value="<?= e((string) $filtros['medico']) ?>" placeholder="Nombre, CI o colegiado">
        </div>
        <div class="col-lg-3 col-md-4 col-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-especialidad">Especialidad</label>
            <select class="form-control" id="filtro-especialidad" name="especialidad">
                <option value="">Todas</option>
                <?php foreach ($catalogoEspecialidades as $especialidad) { ?>
                    <option value="<?= (int) $especialidad['id'] ?>"
                        <?= (string) $filtros['especialidad'] === (string) (int) $especialidad['id'] ? 'selected' : '' ?>>
                        <?= e((string) $especialidad['nombre']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-estado">Estado ficha</label>
            <select class="form-control" id="filtro-estado" name="estado">
                <option value="">Todos</option>
                <option value="ABIERTA" <?= $filtros['estado'] === 'ABIERTA' ? 'selected' : '' ?>>Abierta</option>
                <option value="CERRADA" <?= $filtros['estado'] === 'CERRADA' ? 'selected' : '' ?>>Cerrada</option>
                <option value="BLOQUEADA" <?= $filtros['estado'] === 'BLOQUEADA' ? 'selected' : '' ?>>Bloqueada</option>
            </select>
        </div>
    </div>
    <div class="row align-items-end">
        <div class="col-lg-2 col-md-4 col-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-vigencia">Vigencia horario</label>
            <select class="form-control" id="filtro-vigencia" name="vigencia">
                <option value="">Todas</option>
                <option value="vigente" <?= $filtros['vigencia'] === 'vigente' ? 'selected' : '' ?>>Vigente</option>
                <option value="vencido" <?= $filtros['vigencia'] === 'vencido' ? 'selected' : '' ?>>Vencido</option>
            </select>
        </div>
        <div class="col-lg-4 col-md-5 col-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-paciente">Paciente</label>
            <input type="text" class="form-control" id="filtro-paciente" name="paciente" maxlength="100"
                value="<?= e((string) $filtros['paciente']) ?>" placeholder="Nombre o CI (cuando la ficha tiene cita)">
        </div>
        <div class="col-lg-1 col-md-3 col-6 mb-3 mb-md-0 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
            </button>
            <a href="<?= e($urlLimpia) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-eraser"></i>
            </a>
        </div>
    </div>
</form>

<div class="rp-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Fecha</th>
                    <th scope="col" class="text-center">Horario</th>
                    <th scope="col">Médico</th>
                    <th scope="col">Especialidad</th>
                    <th scope="col" class="text-center">Estado ficha</th>
                    <th scope="col">Paciente</th>
                    <th scope="col">Cita</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($fichas === []) { ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay fichas históricas que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($fichas as $ficha) { ?>
                    <tr>
                        <td class="rp-hora"><?= e((string) $ficha['fecha']) ?></td>
                        <td class="text-center rp-hora">
                            <?= e(substr((string) $ficha['fecha_hora_inicio'], 11, 5)) ?>
                            – <?= e(substr((string) $ficha['fecha_hora_fin'], 11, 5)) ?>
                        </td>
                        <td>
                            <div class="rp-nombre">
                                <?= e((string) $ficha['nombre_completo']) ?>
                                <small>
                                    CI <?= e((string) $ficha['ci']) ?>
                                    · Colegiado <?= e($ficha['nro_colegiado'] !== '' ? (string) $ficha['nro_colegiado'] : '—') ?>
                                </small>
                            </div>
                        </td>
                        <td><?= e((string) $ficha['especialidad']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= $claseEstadoFicha[$ficha['estado_ficha']] ?? 'badge-secondary' ?>">
                                <?= e((string) $ficha['estado_ficha']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($ficha['paciente_nombre'] !== null) { ?>
                                <div class="rp-nombre">
                                    <?= e((string) $ficha['paciente_nombre']) ?>
                                    <small>CI <?= e((string) $ficha['paciente_ci']) ?></small>
                                </div>
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($ficha['cita_codigo'] !== null) { ?>
                                <span class="badge <?= $claseEstadoCita[$ficha['cita_estado']] ?? 'badge-secondary' ?>">
                                    <?= e((string) $ficha['cita_codigo']) ?> · <?= e((string) $ficha['cita_estado']) ?>
                                </span>
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="rp-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> ficha(s) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de fichas históricas">
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
