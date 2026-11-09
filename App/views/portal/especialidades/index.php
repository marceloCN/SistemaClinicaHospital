<?php
/**
 * Gestor de especialidades médicas — resumen del módulo.
 *
 * Renderiza el header con las cuatro páginas del módulo y la vista de
 * resumen con tarjetas de estadísticas, top de especialidades, últimos
 * servicios médicos y últimos bloqueos de agenda.
 */
$estadisticas = $estadisticas ?? [];
$topMedicos = $topMedicos ?? [];
$serviciosRecientes = $serviciosRecientes ?? [];
$bloqueosRecientes = $bloqueosRecientes ?? [];
?>

<?= \Lib\Core\View::render('portal.especialidades.header', [
    'tab' => 'resumen',
    'statTotal' => (int) ($estadisticas['total'] ?? 0),
    'statMedicos' => (int) ($estadisticas['medicos'] ?? 0),
]) ?>

<?= \Lib\Core\View::render('portal.especialidades.resumen', [
    'estadisticas' => $estadisticas,
    'topMedicos' => $topMedicos,
    'serviciosRecientes' => $serviciosRecientes,
    'bloqueosRecientes' => $bloqueosRecientes,
]) ?>
