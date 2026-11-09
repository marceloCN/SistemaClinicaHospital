<?php
/**
 * Header del módulo Permisos: título, badge de total y descripción.
 */
$totalPermisos = (int) ($totalPermisos ?? 0);
?>

<div class="per-header">
    <div>
        <h3 class="m-0"><i class="fas fa-key text-secondary"></i> Permisos por rol</h3>
        <p class="text-muted mb-0">
            Matriz de acceso: tilda los permisos que tendrá cada rol y guarda los cambios en una sola acción.
        </p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= $totalPermisos ?> permiso(s) en el sistema</span>
</div>
