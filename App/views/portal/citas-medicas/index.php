<?php
/**
 * Dashboard del paciente para citas médicas.
 *
 * Orquesta: header (estilos + estadísticas), próximas citas y
 * modal del asistente de generación.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'pendientes' => 0,
    'en_clinica' => 0,
    'atendidas' => 0,
    'canceladas' => 0,
    'inasistencias' => 0,
];
$proximas = $proximas ?? [];
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
?>

<?= \Lib\Core\View::render('portal.citas-medicas.header', [
    'estadisticas' => $estadisticas,
]) ?>

<?= \Lib\Core\View::render('portal.citas-medicas.proximas', [
    'proximas' => $proximas,
]) ?>

<?= \Lib\Core\View::render('portal.citas-medicas.modales', [
    'catalogoEspecialidades' => $catalogoEspecialidades,
]) ?>
