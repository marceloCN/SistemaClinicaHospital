<?php
/**
 * Bandeja del flujo de atención: filtros, tabla de pacientes y paginación.
 *
 * La vista "recepción" gestiona la espera y la evaluación previa; la
 * vista "médica" abre la consulta de los pacientes habilitados.
 */
$pacientes = $pacientes ?? [];
$vista = $vista ?? 'recepcion';
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$numerosPagina = $numerosPagina ?? [];
$filtroEstado = (string) ($filtroEstado ?? '');
$filtroTexto = (string) ($filtroTexto ?? '');
$urlLimpia = route('dashboard_atencion') ?? '/portal/dashboard/atencion';

$estadosRecepcion = [
    'EN_ESPERA' => 'En espera',
    'EN_EVALUACION' => 'En evaluación',
    'LISTO_PARA_CONSULTA' => 'Listo para consulta',
    'FINALIZADO' => 'Finalizado',
];

$urlPagina = static function (int $nuevaPagina) use ($filtroEstado, $filtroTexto, $urlLimpia, $vista): string {
    $params = array_filter([
        'estado' => $vista === 'recepcion' ? $filtroEstado : null,
        'texto' => $filtroTexto,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn ($valor) => $valor !== '' && $valor !== null);

    return $params === [] ? $urlLimpia : $urlLimpia . '?' . http_build_query($params);
};

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$rangoHorario = static function (string $inicio, string $fin): string {
    $ini = substr($inicio, 11, 5);
    $finHora = substr($fin, 11, 5);
    return ($ini === '' ? '—' : $ini) . ' - ' . ($finHora === '' ? '—' : $finHora);
};

$claseRecepcion = static function (string $estado): string {
    return match ($estado) {
        'EN_ESPERA' => 'badge-warning',
        'EN_EVALUACION' => 'badge-info',
        'LISTO_PARA_CONSULTA' => 'badge-success',
        'FINALIZADO' => 'badge-secondary',
        default => 'badge-light',
    };
};

$etiquetaRecepcion = static function (string $estado) use ($estadosRecepcion): string {
    return $estadosRecepcion[$estado] ?? $estado;
};
?>

<div class="row">
    <div class="col-12">
        <form class="at-filtros" method="GET" action="<?= e($urlLimpia) ?>">
            <div class="row align-items-end">
                <?php if ($vista === 'recepcion') { ?>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label" for="filtro-estado">Estado de recepción</label>
                        <select class="form-control" id="filtro-estado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estadosRecepcion as $clave => $etiqueta) { ?>
                                <option value="<?= e($clave) ?>" <?= $filtroEstado === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label" for="filtro-texto">Paciente / CI / código</label>
                    <input type="text" class="form-control" id="filtro-texto" name="texto" maxlength="100" value="<?= e($filtroTexto) ?>">
                </div>
                <div class="col-md-3 mb-3 mb-md-0 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    <a href="<?= e($urlLimpia) ?>" class="btn btn-outline-secondary"><i class="fas fa-eraser"></i></a>
                </div>
            </div>
        </form>

        <div class="at-tabla">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Hora</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Médico / Especialidad</th>
                            <th scope="col">Código</th>
                            <th scope="col">Recepción</th>
                            <th scope="col">Evaluación</th>
                            <th scope="col">Consulta</th>
                            <th scope="col" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pacientes === []) { ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox"></i> No hay pacientes en el flujo con los filtros aplicados.</td></tr>
                        <?php } ?>
                        <?php foreach ($pacientes as $fila) { ?>
                            <tr>
                                <td class="at-hora"><?= e($rangoHorario((string) $fila['fecha_hora_inicio'], (string) $fila['fecha_hora_fin'])) ?></td>
                                <td>
                                    <div class="at-nombre">
                                        <?= e((string) $fila['paciente']) ?>
                                        <small>CI <?= e((string) $fila['paciente_ci']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="at-nombre">
                                        <?= e((string) $fila['medico']) ?>
                                        <small><?= e((string) $fila['especialidad']) ?></small>
                                    </div>
                                </td>
                                <td><code><?= e((string) $fila['codigo']) ?></code></td>
                                <td>
                                    <span class="badge <?= e($claseRecepcion((string) $fila['recepcion_estado'])) ?>">
                                        <?= e($etiquetaRecepcion((string) $fila['recepcion_estado'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($fila['id_evaluacion'] !== null) { ?>
                                        <span class="badge <?= (string) $fila['evaluacion_estado'] === 'COMPLETADA' ? 'badge-success' : ((string) $fila['evaluacion_estado'] === 'ANULADA' ? 'badge-dark' : 'badge-info') ?>">
                                            <?= e((string) $fila['evaluacion_estado']) ?>
                                        </span>
                                    <?php } else { ?>
                                        <span class="badge badge-light border">Sin evaluación</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if ($fila['id_consulta'] !== null) { ?>
                                        <span class="badge <?= (string) $fila['consulta_estado'] === 'FINALIZADA' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= (string) $fila['consulta_estado'] === 'FINALIZADA' ? 'Finalizada' : 'En curso' ?>
                                        </span>
                                    <?php } else { ?>
                                        <span class="badge badge-light border">Sin iniciar</span>
                                    <?php } ?>
                                </td>
                                <td class="text-right text-nowrap">
                                    <?php if ($vista === 'recepcion') { ?>
                                        <?php if (in_array((string) $fila['recepcion_estado'], ['EN_ESPERA', 'EN_EVALUACION'], true)) { ?>
                                            <button type="button" class="btn btn-sm btn-primary js-abrir-evaluacion"
                                                data-id="<?= (int) $fila['id'] ?>"
                                                title="<?= (string) $fila['recepcion_estado'] === 'EN_ESPERA' ? 'Iniciar evaluación' : 'Continuar evaluación' ?>">
                                                <i class="fas fa-clipboard-list"></i>
                                            </button>
                                        <?php } ?>
                                        <?php if ((string) $fila['recepcion_estado'] === 'EN_ESPERA') { ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning js-sin-evaluacion"
                                                data-id="<?= (int) $fila['id'] ?>"
                                                data-paciente="<?= e((string) $fila['paciente']) ?>"
                                                title="Habilitar sin evaluación previa">
                                                <i class="fas fa-forward"></i>
                                            </button>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ((string) $fila['recepcion_estado'] === 'LISTO_PARA_CONSULTA' && $fila['id_consulta'] === null) { ?>
                                            <button type="button" class="btn btn-sm btn-success js-abrir-consulta"
                                                data-id="<?= (int) $fila['id'] ?>"
                                                title="Iniciar consulta">
                                                <i class="fas fa-notes-medical"></i>
                                            </button>
                                        <?php } ?>
                                        <?php if ($fila['id_consulta'] !== null) { ?>
                                            <a class="btn btn-sm <?= (string) $fila['consulta_estado'] === 'EN_CURSO' ? 'btn-warning' : 'btn-outline-primary' ?>"
                                                href="<?= e((route('portal_atencion_consulta', ['id' => (int) $fila['id']])) ?? ('/portal/dashboard/atencion/consulta/' . (int) $fila['id'])) ?>"
                                                title="<?= (string) $fila['consulta_estado'] === 'EN_CURSO' ? 'Continuar consulta' : 'Ver consulta' ?>">
                                                <i class="fas fa-file-medical"></i>
                                            </a>
                                        <?php } ?>
                                    <?php } ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary js-detalle-atencion"
                                        data-id="<?= (int) $fila['id'] ?>" title="Detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="at-paginacion">
            <small class="text-muted">Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> paciente(s) · <?= $porPagina ?> por página</small>
            <?php if ($paginas > 1) { ?>
                <nav aria-label="Paginación de la bandeja">
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
</div>
