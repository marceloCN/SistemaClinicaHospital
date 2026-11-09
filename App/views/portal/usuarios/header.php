<?php
/**
 * Header del módulo Usuarios (empleados): título, badge total y pestañas.
 * Al cambiar de pestaña no se conservan filtros (solo aplican en Datos).
 */
$tab = (string) ($tab ?? 'resumen');
$statTotal = (int) ($statTotal ?? 0);

/** URL de pestaña sin arrastrar filtros. */
$urlTab = static function (string $nuevaTab): string {
    $base = route('dashboard_usuarios') ?? '/portal/dashboard/Usuarios';

    return $nuevaTab === 'resumen' ? $base : $base . '?' . http_build_query(['tab' => $nuevaTab]);
};
?>

<div class="us-header">
    <div>
        <h3 class="m-0"><i class="fas fa-user-shield text-secondary"></i> Empleados del sistema</h3>
        <p class="text-muted mb-0">Personas con rol administrador, recepcionista o médico: cuenta de acceso, rol y ficha médica.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= $statTotal ?> empleado(s)</span>
</div>

<ul class="nav nav-tabs us-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'resumen' ? 'active' : '' ?>"
            href="<?= e($urlTab('resumen')) ?>">
            <i class="fas fa-chart-pie mr-1"></i> Resumen
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'datos' ? 'active' : '' ?>"
            href="<?= e($urlTab('datos')) ?>">
            <i class="fas fa-id-badge mr-1"></i> Empleados
        </a>
    </li>
</ul>
