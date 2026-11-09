<?php
/**
 * Pestaña Resumen del dashboard de empleados.
 *
 * Tarjetas de estadísticas (total, activos, inactivos y cuentas sin
 * acceso), donut con los tres roles de sistema y últimos registros.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'activos' => 0,
    'inactivos' => 0,
    'administradores' => 0,
    'recepcionistas' => 0,
    'medicos' => 0,
    'sin_cuenta' => 0,
    'bloqueados' => 0,
    'fichas_medico' => 0,
];
$recientes = $recientes ?? [];

$statTotal = (int) ($estadisticas['total'] ?? 0);
$statActivos = (int) ($estadisticas['activos'] ?? 0);
$statInactivos = (int) ($estadisticas['inactivos'] ?? 0);
$statAdministradores = (int) ($estadisticas['administradores'] ?? 0);
$statRecepcionistas = (int) ($estadisticas['recepcionistas'] ?? 0);
$statMedicos = (int) ($estadisticas['medicos'] ?? 0);
$statSinCuenta = (int) ($estadisticas['sin_cuenta'] ?? 0);
$statBloqueados = (int) ($estadisticas['bloqueados'] ?? 0);
$statFichasMedico = (int) ($estadisticas['fichas_medico'] ?? 0);

/** Segmentos mutuamente excluyentes del donut (suman el total). */
$segmentos = [
    ['etiqueta' => 'Administradores', 'valor' => $statAdministradores, 'color' => '#1a73e8'],
    ['etiqueta' => 'Recepcionistas', 'valor' => $statRecepcionistas, 'color' => '#2fae60'],
    ['etiqueta' => 'Médicos', 'valor' => $statMedicos, 'color' => '#0f8b84'],
];

/** Circunferencia del círculo SVG (r = 70) y offsets acumulados. */
$radio = 70;
$circunferencia = 2 * M_PI * $radio;
$offsets = [];
$acumulado = 0;

foreach ($segmentos as $segmento) {
    $fraccion = $statTotal > 0 ? $segmento['valor'] / $statTotal : 0;
    $offsets[] = $circunferencia * (1 - $acumulado);
    $acumulado += $fraccion;
}
?>

<div class="us-stats">
    <div class="us-stat">
        <span class="us-stat-icon total"><i class="fas fa-users-cog"></i></span>
        <div>
            <div class="us-stat-num"><?= $statTotal ?></div>
            <div class="us-stat-label">Empleados</div>
        </div>
    </div>
    <div class="us-stat">
        <span class="us-stat-icon activos"><i class="fas fa-user-check"></i></span>
        <div>
            <div class="us-stat-num"><?= $statActivos ?></div>
            <div class="us-stat-label">Activos</div>
        </div>
    </div>
    <div class="us-stat">
        <span class="us-stat-icon inactivos"><i class="fas fa-user-slash"></i></span>
        <div>
            <div class="us-stat-num"><?= $statInactivos ?></div>
            <div class="us-stat-label">Inactivos</div>
        </div>
    </div>
    <div class="us-stat">
        <span class="us-stat-icon cuentas"><i class="fas fa-key"></i></span>
        <div>
            <div class="us-stat-num"><?= $statSinCuenta ?></div>
            <div class="us-stat-label">Sin cuenta de acceso</div>
        </div>
    </div>
</div>

<div class="us-resumen-grid">
    <div class="us-card us-donut-wrap">
        <div class="us-donut">
            <svg viewBox="0 0 160 160" role="img" aria-label="Distribución de empleados: administradores, recepcionistas y médicos">
                <?php foreach ($segmentos as $i => $segmento) { ?>
                    <?php if ($segmento['valor'] <= 0) { continue; } ?>
                    <circle cx="80" cy="80" r="<?= $radio ?>" fill="none"
                        stroke="<?= e($segmento['color']) ?>" stroke-width="18"
                        stroke-dasharray="<?= number_format($circunferencia, 2, '.', '') ?>"
                        stroke-dashoffset="<?= number_format($offsets[$i], 2, '.', '') ?>"></circle>
                <?php } ?>
            </svg>
            <div class="us-donut-center">
                <strong><?= $statTotal ?></strong>
                <span>Total</span>
            </div>
        </div>
        <div class="us-leyenda">
            <?php foreach ($segmentos as $segmento) { ?>
                <?php $pct = $statTotal > 0 ? round($segmento['valor'] * 100 / $statTotal, 1) : 0; ?>
                <div class="us-leyenda-item">
                    <span class="dot" style="background:<?= e($segmento['color']) ?>;"></span>
                    <?= e($segmento['etiqueta']) ?> (<?= number_format($pct, 1) ?>%) <strong><?= (int) $segmento['valor'] ?></strong>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="us-card">
        <h5><i class="fas fa-clock mr-1"></i> Últimos empleados registrados</h5>

        <?php if ($recientes === []) { ?>
            <p class="text-muted mb-0">Aún no hay empleados registrados.</p>
        <?php } else { ?>
            <?php foreach ($recientes as $reciente) { ?>
                <?php
                $ts = $reciente['fecha_registro'] !== '' ? strtotime((string) $reciente['fecha_registro']) : false;
                $iniciales = mb_strtoupper(mb_substr((string) $reciente['nombre'], 0, 1))
                    . mb_strtoupper(mb_substr((string) $reciente['apellido'], 0, 1));
                ?>
                <div class="us-reciente">
                    <span class="us-reciente-avatar"><?= e($iniciales) ?></span>
                    <div style="min-width:0;">
                        <strong style="color:#0b2a38; display:block; line-height:1.25;">
                            <?= e((string) $reciente['nombre_completo']) ?>
                        </strong>
                        <small class="text-muted">
                            CI <?= e((string) $reciente['ci']) ?>
                            · <?= $ts !== false ? date('d/m/Y H:i', $ts) : e((string) $reciente['fecha_registro']) ?>
                            · <?= e((string) $reciente['rol_nombre']) ?>
                        </small>
                    </div>
                    <span class="badge <?= $reciente['estado'] ? 'badge-success' : 'badge-danger' ?> ml-auto align-self-center">
                        <?= $reciente['estado'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<!--
<div class="us-card">
    <h5><i class="fas fa-info-circle mr-1"></i> Cómo leer este resumen</h5>
    <p class="text-muted mb-0" style="font-size:0.93rem;">
        <strong>Empleados</strong> son todas las personas con rol distinto de PAC: administradores,
        recepcionistas y médicos (activos e inactivos).
        <strong>Activos</strong> e <strong>inactivos</strong> se cuentan por <code>persona.estado</code>.
        El gráfico divide el total entre los tres roles de sistema.
        Actualmente <strong><?= $statSinCuenta ?></strong> empleado(s) no tienen cuenta de acceso y
        <strong><?= $statBloqueados ?></strong> tienen la cuenta bloqueada por intentos fallidos;
        hay <strong><?= $statFichasMedico ?></strong> ficha(s) en la tabla <code>medico</code>.
        Usa la pestaña <em>Empleados</em> para buscar, editar, dar de baja o registrar nuevos empleados.
    </p>
</div>
-->