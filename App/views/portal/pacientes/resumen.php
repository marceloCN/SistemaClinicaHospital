<?php
/**
 * Pestaña Resumen del dashboard de pacientes.
 *
 * Tarjetas de estadísticas (registrados, activos, inactivos y regulares),
 * donut con 3 segmentos excluyentes (regulares, activos sin ficha e
 * inactivos sin ficha) y últimos registros.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'activos' => 0,
    'inactivos' => 0,
    'regulares' => 0,
    'activos_sin_ficha' => 0,
    'inactivos_sin_ficha' => 0,
];
$recientes = $recientes ?? [];

$statTotal = (int) ($estadisticas['total'] ?? 0);
$statActivos = (int) ($estadisticas['activos'] ?? 0);
$statInactivos = (int) ($estadisticas['inactivos'] ?? 0);
$statRegulares = (int) ($estadisticas['regulares'] ?? 0);
$activosSinFicha = (int) ($estadisticas['activos_sin_ficha'] ?? 0);
$inactivosSinFicha = (int) ($estadisticas['inactivos_sin_ficha'] ?? 0);

/** Segmentos mutuamente excluyentes del donut (suman el total). */
$segmentos = [
    ['etiqueta' => 'Regulares', 'valor' => $statRegulares, 'color' => '#1a73e8'],
    ['etiqueta' => 'Activos sin ficha', 'valor' => $activosSinFicha, 'color' => '#2fae60'],
    ['etiqueta' => 'Inactivos sin ficha', 'valor' => $inactivosSinFicha, 'color' => '#e5929a'],
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

<div class="pac-stats">
    <div class="pac-stat">
        <span class="pac-stat-icon total"><i class="fas fa-users"></i></span>
        <div>
            <div class="pac-stat-num"><?= $statTotal ?></div>
            <div class="pac-stat-label">Registrados</div>
        </div>
    </div>
    <div class="pac-stat">
        <span class="pac-stat-icon activos"><i class="fas fa-user-check"></i></span>
        <div>
            <div class="pac-stat-num"><?= $statActivos ?></div>
            <div class="pac-stat-label">Activos</div>
        </div>
    </div>
    <div class="pac-stat">
        <span class="pac-stat-icon inactivos"><i class="fas fa-user-slash"></i></span>
        <div>
            <div class="pac-stat-num"><?= $statInactivos ?></div>
            <div class="pac-stat-label">Inactivos</div>
        </div>
    </div>
    <div class="pac-stat">
        <span class="pac-stat-icon regulares"><i class="fas fa-folder-open"></i></span>
        <div>
            <div class="pac-stat-num"><?= $statRegulares ?></div>
            <div class="pac-stat-label">Regulares (con ficha)</div>
        </div>
    </div>
</div>

<div class="pac-resumen-grid">
    <div class="pac-card pac-donut-wrap">
        <div class="pac-donut">
            <svg viewBox="0 0 160 160" role="img" aria-label="Distribución de pacientes: regulares, activos sin ficha e inactivos sin ficha">
                <?php foreach ($segmentos as $i => $segmento) { ?>
                    <?php if ($segmento['valor'] <= 0) { continue; } ?>
                    <circle cx="80" cy="80" r="<?= $radio ?>" fill="none"
                        stroke="<?= e($segmento['color']) ?>" stroke-width="18"
                        stroke-dasharray="<?= number_format($circunferencia, 2, '.', '') ?>"
                        stroke-dashoffset="<?= number_format($offsets[$i], 2, '.', '') ?>"></circle>
                <?php } ?>
            </svg>
            <div class="pac-donut-center">
                <strong><?= $statTotal ?></strong>
                <span>Total</span>
            </div>
        </div>
        <div class="pac-leyenda">
            <?php foreach ($segmentos as $segmento) { ?>
                <?php $pct = $statTotal > 0 ? round($segmento['valor'] * 100 / $statTotal, 1) : 0; ?>
                <div class="pac-leyenda-item">
                    <span class="dot" style="background:<?= e($segmento['color']) ?>;"></span>
                    <?= e($segmento['etiqueta']) ?> (<?= number_format($pct, 1) ?>%) <strong><?= (int) $segmento['valor'] ?></strong>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="pac-card">
        <h5><i class="fas fa-clock mr-1"></i> Últimos pacientes registrados</h5>

        <?php if ($recientes === []) { ?>
            <p class="text-muted mb-0">Aún no hay pacientes registrados.</p>
        <?php } else { ?>
            <?php foreach ($recientes as $reciente) { ?>
                <?php
                $ts = $reciente['fecha_registro'] !== '' ? strtotime((string) $reciente['fecha_registro']) : false;
                $iniciales = mb_strtoupper(mb_substr((string) $reciente['nombre'], 0, 1))
                    . mb_strtoupper(mb_substr((string) $reciente['apellido'], 0, 1));
                ?>
                <div class="pac-reciente">
                    <span class="pac-reciente-avatar"><?= e($iniciales) ?></span>
                    <div style="min-width:0;">
                        <strong style="color:#0b2a38; display:block; line-height:1.25;">
                            <?= e((string) $reciente['nombre_completo']) ?>
                        </strong>
                        <small class="text-muted">
                            CI <?= e((string) $reciente['ci']) ?>
                            · <?= $ts !== false ? date('d/m/Y H:i', $ts) : e((string) $reciente['fecha_registro']) ?>
                            <?= $reciente['tiene_registro'] ? '· Regular' : '· Sin ficha' ?>
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
<div class="pac-card">
    <h5><i class="fas fa-info-circle mr-1"></i> Cómo leer este resumen</h5>
    <p class="text-muted mb-0" style="font-size:0.93rem;">
        <strong>Registrados</strong> son todas las personas con rol PAC (activas e inactivas).
        <strong>Activos</strong> e <strong>inactivos</strong> se cuentan por <code>persona.estado</code>.
        El gráfico divide el total en tres grupos sin solape: <strong>regulares</strong> (con ficha en la tabla
        <code>paciente</code>), <strong>activos sin ficha</strong> e <strong>inactivos sin ficha</strong>.
        Usa la pestaña <em>Datos personales</em> para buscar, editar, agregar contactos de emergencia
        o registrar nuevos pacientes.
    </p>
</div>
-->