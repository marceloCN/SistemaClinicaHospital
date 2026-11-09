<?php
/**
 * Header del módulo Pacientes: título, badge total y pestañas.
 * Al cambiar de pestaña no se conservan filtros (solo aplican en Datos).
 */
$tab = (string) ($tab ?? 'resumen');
$statTotal = (int) ($statTotal ?? 0);

/** URL de pestaña sin arrastrar filtros. */
$urlTab = static function (string $nuevaTab): string {
    $base = route('dashboard_pacientes') ?? '/portal/dashboard/Pacientes';

    return $nuevaTab === 'resumen' ? $base : $base . '?' . http_build_query(['tab' => $nuevaTab]);
};
?>

<div class="pac-header">
    <div>
        <h3 class="m-0"><i class="fas fa-users text-secondary"></i> Pacientes</h3>
        <p class="text-muted mb-0">Cuentas de personas con rol PAC, estado general y contactos de emergencia.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= $statTotal ?> paciente(s)</span>
</div>

<ul class="nav nav-tabs pac-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'resumen' ? 'active' : '' ?>"
            href="<?= e($urlTab('resumen')) ?>">
            <i class="fas fa-chart-pie mr-1"></i> Resumen
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'datos' ? 'active' : '' ?>"
            href="<?= e($urlTab('datos')) ?>">
            <i class="fas fa-id-card mr-1"></i> Datos personales
        </a>
    </li>
</ul>
