<?php

/**
 * Tabla paginada de horarios de atención.
 *
 * Muestra médico, especialidad, día, horario, vigencia, estado, fichas y acciones.
 */
$horarios = $horarios ?? [];
$conteos = $conteos ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$numerosPagina = $numerosPagina ?? [];
$filtroMedico = (string) ($filtroMedico ?? '');
$filtroEspecialidad = (string) ($filtroEspecialidad ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$urlPagina = static function (int $nuevaPagina) use ($filtroMedico, $filtroEspecialidad, $filtroEstado): string {
    $params = array_filter([
        'medico' => $filtroMedico,
        'especialidad' => $filtroEspecialidad,
        'estado' => $filtroEstado,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn($v) => $v !== '' && $v !== null);

    $base = route('dashboard_horario_atencion') ?? '/portal/dashboard/horario-atencion';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

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

<div class="ha-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Médico</th>
                    <th scope="col">Especialidad</th>
                    <th scope="col">Día</th>
                    <th scope="col" class="text-center">Atencion y Duración</th>
                    <th scope="col">Vigencia</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-center">Fichas</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($horarios === []) { ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay horarios que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($horarios as $horario) {
                    $idHorario = (int) $horario['id'];
                    $conteo = $conteos[$idHorario] ?? ['total_fichas' => 0, 'cerradas' => 0, 'libres' => 0];
                ?>
                    <tr>
                        <td>
                            <div class="ha-nombre">
                                <?= e($horario['nombre_completo']) ?>
                                <small>
                                    CI <?= e($horario['ci']) ?>
                                    · Colegiado <?= e($horario['nro_colegiado'] !== '' ? $horario['nro_colegiado'] : '—') ?>
                                </small>
                            </div>
                        </td>
                        <td><?= e($horario['especialidad']) ?></td>
                        <td><?= e($diasSemana[$horario['dia_semana']] ?? '—') ?></td>
                        <td class="text-center ha-hora">De <?= e(substr((string) $horario['hora_inicio'], 0, 5)) ?>
                            a <?= e(substr((string) $horario['hora_fin'], 0, 5)) ?><br>
                            Duracion: <?= (int) $horario['duracion_ficha'] ?> min
                        </td>
                        <td class="ha-hora">
                            <?= e((string) $horario['fecha_desde']) ?>
                            <?php if (trim((string) $horario['fecha_hasta']) !== '') { ?>
                                → <?= e((string) $horario['fecha_hasta']) ?>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="badge <?= $horario['estado'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $horario['estado'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-light border" title="Total de fichas generadas">
                                <?= (int) $conteo['total_fichas'] ?> ficha<?= (int) $conteo['total_fichas'] === 1 ? '' : 's' ?>
                            </span>
                            <?php if ((int) $conteo['cerradas'] > 0) { ?>
                                <span class="badge badge-primary" title="Fichas cerradas u ocupadas (estado distinto de ABIERTA)">
                                    <?= (int) $conteo['cerradas'] ?> cerrada<?= (int) $conteo['cerradas'] === 1 ? '' : 's' ?>
                                </span>
                            <?php } ?>
                            <?php if ((int) $conteo['libres'] > 0) { ?>
                                <span class="badge badge-success" title="Fichas libres (estado ABIERTA)">
                                    <?= (int) $conteo['libres'] ?> libre<?= (int) $conteo['libres'] === 1 ? '' : 's' ?>
                                </span>
                            <?php } ?>
                        </td>
                        <td class="text-right text-nowrap">

                            <?php if ($horario['estado']) { ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-editar"
                                    title="Editar horario"
                                    data-id="<?= $idHorario ?>"
                                    data-servicio="<?= (int) $horario['id_servicio_medico'] ?>"
                                    data-dia="<?= (int) $horario['dia_semana'] ?>"
                                    data-inicio="<?= e((string) $horario['hora_inicio']) ?>"
                                    data-fin="<?= e((string) $horario['hora_fin']) ?>"
                                    data-duracion="<?= (int) $horario['duracion_ficha'] ?>"
                                    data-desde="<?= e((string) $horario['fecha_desde']) ?>"
                                    data-hasta="<?= e((string) $horario['fecha_hasta']) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning js-desactivar"
                                    title="Desactivar horario"
                                    data-id="<?= $idHorario ?>"
                                    data-medico="<?= e($horario['nombre_completo']) ?>">
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                 <button type="button" class="btn btn-sm btn-outline-primary js-generar-fichas"
                                    title="Generar fichas"
                                    data-id="<?= $idHorario ?>"
                                    data-medico="<?= e($horario['nombre_completo']) ?>"
                                    data-hora-inicio="<?= e((string) $horario['hora_inicio']) ?>"
                                    data-hora-fin="<?= e((string) $horario['hora_fin']) ?>"
                                    data-duracion="<?= (int) $horario['duracion_ficha'] ?>"
                                    data-dia="<?= (int) $horario['dia_semana'] ?>">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-outline-success js-activar"
                                    title="Reactivar horario"
                                    data-id="<?= $idHorario ?>"
                                    data-medico="<?= e($horario['nombre_completo']) ?>">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                            <?php } ?>
                            <?php if ((int) $conteo['libres'] > 0) { ?>
                                <button type="button" class="btn btn-sm btn-outline-danger js-eliminar-fichas"
                                    title="Eliminar fichas restantes"
                                    data-id="<?= $idHorario ?>"
                                    data-medico="<?= e($horario['nombre_completo']) ?>"
                                    data-restantes="<?= (int) $conteo['libres'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ha-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> horario(s) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de horarios">
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