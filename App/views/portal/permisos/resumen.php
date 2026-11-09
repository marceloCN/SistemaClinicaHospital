<?php
/**
 * Tarjetas de resumen del dashboard de permisos: asignaciones activas
 * por rol con barra de progreso (n/total de permisos del sistema).
 */
$roles = $roles ?? [];
$conteos = $conteos ?? [];
$totalPermisos = (int) ($totalPermisos ?? 0);

/** Ícono y clase de color por clave de rol. */
$apariencia = [
    'ADMIN' => ['fa-user-shield', 'admin'],
    'RECEP' => ['fa-headset', 'recep'],
    'MEDICO' => ['fa-stethoscope', 'medico'],
    'PAC' => ['fa-user-injured', 'pac'],
];
?>

<div class="per-stats">
    <?php foreach ($roles as $rol) { ?>
        <?php
        $idRol = (int) $rol['id'];
        $clave = (string) $rol['clave'];
        $asignados = (int) ($conteos[$idRol] ?? 0);
        $pct = $totalPermisos > 0 ? round($asignados * 100 / $totalPermisos, 1) : 0;
        [$icono, $clase] = $apariencia[$clave] ?? ['fa-user', 'recep'];
        ?>
        <div class="per-stat">
            <div class="per-stat-top">
                <div>
                    <div class="per-stat-num">
                        <?= $asignados ?><small> / <?= $totalPermisos ?></small>
                    </div>
                    <div class="per-stat-label"><?= e((string) $rol['nombre']) ?></div>
                </div>
                <span class="per-stat-icon <?= $clase ?>"><i class="fas <?= $icono ?>"></i></span>
            </div>
            <div class="per-progress" title="<?= $pct ?>% de los permisos activos">
                <span style="width: <?= $pct ?>%;"></span>
            </div>
        </div>
    <?php } ?>
</div>
