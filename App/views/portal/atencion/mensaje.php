<?php
/**
 * Mensaje simple del módulo de atención (cita inexistente, paciente no
 * encontrado, paciente no habilitado, etc.).
 */
$titulo = $titulo ?? 'Información';
$mensaje = $mensaje ?? '';
$urlVolver = $urlVolver ?? (route('dashboard_atencion') ?? '/portal/dashboard/atencion');
?>

<div class="at-ficha" style="max-width:640px;margin:40px auto;text-align:center;">
    <i class="fas fa-info-circle text-secondary" style="font-size:2.2rem;"></i>
    <h4 class="mt-3 mb-2"><?= e($titulo) ?></h4>
    <p class="text-muted mb-4"><?= e($mensaje) ?></p>
    <a href="<?= e($urlVolver) ?>" class="btn btn-primary">
        <i class="fas fa-arrow-left mr-1"></i> Volver a la bandeja
    </a>
</div>
