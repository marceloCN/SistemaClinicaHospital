<?php
/**
 * Pestaña Resumen del gestor de especialidades médicas.
 *
 * Tarjetas con los conteos globales del módulo, especialidades con más
 * médicos acreditados, últimos servicios médicos registrados y últimos
 * bloqueos de agenda activos.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'activas' => 0,
    'inactivas' => 0,
    'acreditaciones' => 0,
    'medicos' => 0,
    'servicios_activos' => 0,
    'bloqueos_activos' => 0,
];
$topMedicos = $topMedicos ?? [];
$serviciosRecientes = $serviciosRecientes ?? [];
$bloqueosRecientes = $bloqueosRecientes ?? [];

$statTotal = (int) ($estadisticas['total'] ?? 0);
$statActivas = (int) ($estadisticas['activas'] ?? 0);
$statInactivas = (int) ($estadisticas['inactivas'] ?? 0);
$statAcreditaciones = (int) ($estadisticas['acreditaciones'] ?? 0);
$statMedicos = (int) ($estadisticas['medicos'] ?? 0);
$statServicios = (int) ($estadisticas['servicios_activos'] ?? 0);
$statBloqueos = (int) ($estadisticas['bloqueos_activos'] ?? 0);

/** Máximo para normalizar las barras del top de especialidades. */
$topMaximo = 0;

foreach ($topMedicos as $especialidad) {
    $topMaximo = max($topMaximo, (int) ($especialidad['total_medicos'] ?? 0));
}
?>

<div class="es-stats">
    <div class="es-stat">
        <span class="es-stat-icon total"><i class="fas fa-book-medical"></i></span>
        <div>
            <div class="es-stat-num"><?= $statTotal ?></div>
            <div class="es-stat-label">Especialidades</div>
        </div>
    </div>
    <div class="es-stat">
        <span class="es-stat-icon activas"><i class="fas fa-check-circle"></i></span>
        <div>
            <div class="es-stat-num"><?= $statActivas ?></div>
            <div class="es-stat-label">Activas</div>
        </div>
    </div>
    <div class="es-stat">
        <span class="es-stat-icon inactivas"><i class="fas fa-times-circle"></i></span>
        <div>
            <div class="es-stat-num"><?= $statInactivas ?></div>
            <div class="es-stat-label">Inactivas</div>
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
        <span class="es-stat-icon medicos"><i class="fas fa-user-md"></i></span>
        <div>
            <div class="es-stat-num"><?= $statMedicos ?></div>
            <div class="es-stat-label">Médicos acreditados</div>
        </div>
    </div>
    <div class="es-stat">
        <span class="es-stat-icon servicios"><i class="fas fa-hospital-user"></i></span>
        <div>
            <div class="es-stat-num"><?= $statServicios ?></div>
            <div class="es-stat-label">Servicios activos</div>
        </div>
    </div>
    <div class="es-stat">
        <span class="es-stat-icon bloqueos"><i class="fas fa-ban"></i></span>
        <div>
            <div class="es-stat-num"><?= $statBloqueos ?></div>
            <div class="es-stat-label">Bloqueos de agenda</div>
        </div>
    </div>
</div>

<div class="es-grid">
    <div class="es-card">
        <h5><i class="fas fa-medal mr-1"></i> Especialidades con más médicos</h5>

        <?php if ($topMedicos === []) { ?>
            <p class="text-muted mb-0">Aún no hay especialidades registradas.</p>
        <?php } else { ?>
            <?php foreach ($topMedicos as $especialidad) {
                $totalMedicos = (int) ($especialidad['total_medicos'] ?? 0);
                $porcentaje = $topMaximo > 0 ? round($totalMedicos * 100 / $topMaximo) : 0;
            ?>
                <div class="es-fila">
                    <div class="es-fila-main">
                        <strong><?= e((string) $especialidad['nombre']) ?></strong>
                        <small>
                            <?= $totalMedicos ?> médico(s) acreditado(s)
                            <?php if (!(bool) ($especialidad['estado'] ?? true)) { ?>
                                · <span class="text-danger">Inactiva</span>
                            <?php } ?>
                        </small>
                        <div class="es-barra"><span style="width:<?= $porcentaje ?>%;"></span></div>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <div class="es-card">
        <h5><i class="fas fa-stethoscope mr-1"></i> Últimos servicios médicos</h5>

        <?php if ($serviciosRecientes === []) { ?>
            <p class="text-muted mb-0">Todavía no hay médicos incorporados al servicio.</p>
        <?php } else { ?>
            <?php foreach ($serviciosRecientes as $servicio) {
                $badgeEstado = $servicio['estado'] === 'ACTIVO'
                    ? 'badge-success'
                    : ($servicio['estado'] === 'SUSPENDIDO' ? 'badge-warning' : 'badge-secondary');
            ?>
                <div class="es-fila">
                    <div class="es-fila-main">
                        <strong><?= e($servicio['nombre_completo']) ?></strong>
                        <small>
                            <?= e($servicio['especialidad']) ?>
                            · CI <?= e($servicio['ci']) ?>
                            · <?= e($servicio['fecha_inicio']) ?>
                        </small>
                    </div>
                    <span class="badge <?= $badgeEstado ?>"><?= e($servicio['estado']) ?></span>
                </div>
            <?php } ?>
        <?php } ?>
    </div>

    <div class="es-card">
        <h5><i class="fas fa-ban mr-1"></i> Bloqueos de agenda activos</h5>

        <?php if ($bloqueosRecientes === []) { ?>
            <p class="text-muted mb-0">No hay bloqueos de agenda registrados.</p>
        <?php } else { ?>
            <?php foreach ($bloqueosRecientes as $bloqueo) { ?>
                <div class="es-fila">
                    <div class="es-fila-main">
                        <strong><?= e($bloqueo['nombre_completo']) ?></strong>
                        <small>
                            <?= e($bloqueo['especialidad']) ?>
                            · Colegiado <?= e($bloqueo['nro_colegiado']) ?>
                        </small>
                        <small class="es-fecha">
                            <?= e($bloqueo['fecha_hora_inicio']) ?> → <?= e($bloqueo['fecha_hora_fin']) ?>
                        </small>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<div class="es-grid">
    
<!--
    <div class="es-card">
        <h5><i class="fas fa-info-circle mr-1"></i> Cómo leer este resumen</h5>
        <p class="text-muted mb-0" style="font-size:0.93rem;">
            <strong>Especialidades</strong> es el catálogo completo de la clínica (activas e inactivas);
            <strong>acreditaciones</strong> cuenta cada vínculo médico-especialidad vigente en
            <code>medico_especialidad</code> y <strong>médicos acreditados</strong> cuenta personas
            distintas. <strong>Servicios activos</strong> son las incorporaciones vigentes en
            <code>servicio_medico</code>, la rama de la que dependen los horarios y las citas.
            Los <strong>bloqueos de agenda</strong> indican impedimentos registrados dentro del
            horario de trabajo de un médico. Usa la página <em>Especialidades</em> para crear o
            editar el catálogo, <em>Médicos</em> para asignar especialidades y
            <em>Servicio médico</em> para ver acreditaciones y registrar bloqueos.
        </p>
    </div>
-->
</div>
