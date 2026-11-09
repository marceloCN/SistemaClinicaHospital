<?php

/**
 * Filtros del módulo de horarios de atención.
 *
 * Botón para crear un nuevo horario y formulario de filtros.
 */
$filtroMedico = (string) ($filtroMedico ?? '');
$filtroEspecialidad = (string) ($filtroEspecialidad ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');
$catalogoEspecialidades = $catalogoEspecialidades ?? [];

$urlLimpia = route('dashboard_horario_atencion') ?? '/portal/dashboard/horario-atencion';

/** URLs de los reportes conservando los filtros activos (sin paginación). */
$urlPdf = route('portal_horario_atencion_pdf') ?? '/portal/dashboard/horario-atencion/pdf';
$urlCsv = route('portal_horario_atencion_csv') ?? '/portal/dashboard/horario-atencion/csv';
$paramsReporte = array_filter([
    'medico' => $filtroMedico,
    'especialidad' => $filtroEspecialidad,
    'estado' => $filtroEstado,
], static fn($v) => $v !== '' && $v !== null);

if ($paramsReporte !== []) {
    $urlPdf .= '?' . http_build_query($paramsReporte);
    $urlCsv .= '?' . http_build_query($paramsReporte);
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
    <button type="button" class="btn btn-primary" id="btn-nuevo-horario">
        <i class="fas fa-plus mr-1"></i> Nuevo horario
    </button>
    <small class="text-muted">Los filtros solo aplican en esta página.</small>
</div>

<form class="ha-filtros" method="GET" action="<?= route('dashboard_horario_atencion') ?>">
    <div class="row align-items-end">
        <div class="col-lg-4 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-medico">Médico</label>
            <input type="text" class="form-control" id="filtro-medico" name="medico" maxlength="100"
                value="<?= e($filtroMedico) ?>" placeholder="Nombre, CI o colegiado">
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-especialidad">Especialidad</label>
            <select class="form-control" id="filtro-especialidad" name="especialidad">
                <option value="">Todas</option>
                <?php foreach ($catalogoEspecialidades as $especialidad) { ?>
                    <option value="<?= (int) $especialidad['id'] ?>"
                        <?= $filtroEspecialidad === (string) (int) $especialidad['id'] ? 'selected' : '' ?>>
                        <?= e((string) $especialidad['nombre']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-estado">Estado</label>
            <select class="form-control" id="filtro-estado" name="estado">
                <option value="">Todos</option>
                <option value="1" <?= $filtroEstado === '1' ? 'selected' : '' ?>>Activos</option>
                <option value="0" <?= $filtroEstado === '0' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-6 mb-3 mb-md-0 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
            </button>
            <a href="<?= e($urlLimpia) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-eraser"></i>
            </a>
            <a href="<?= e($urlPdf) ?>" class="btn btn-outline-danger" target="_blank" rel="noopener" title="Generar PDF">
                <i class="fas fa-file-pdf"></i>
            </a>
            <a href="<?= e($urlCsv) ?>" class="btn btn-outline-success" title="Generar CSV">
                <i class="fas fa-file-csv"></i>
            </a>
        </div>
    </div>
</form>