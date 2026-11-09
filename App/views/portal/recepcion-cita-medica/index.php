<?php
/**
 * Panel de recepción de citas médicas.
 *
 * Orquesta: header con estadísticas del día, filtros/tabla de citas de
 * hoy, bloqueos activos y modales de check-in, registro y reprogramación.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'pendientes' => 0,
    'en_clinica' => 0,
    'atendidas' => 0,
    'canceladas' => 0,
];
$citas = $citas ?? [];
$bloqueos = $bloqueos ?? [];
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
?>

<?= \Lib\Core\View::render('portal.recepcion-cita-medica.header', [
    'estadisticas' => $estadisticas,
]) ?>

<?= \Lib\Core\View::render('portal.recepcion-cita-medica.tabla', [
    'citas' => $citas,
    'bloqueos' => $bloqueos,
    'catalogoEspecialidades' => $catalogoEspecialidades,
    'estadosDisponibles' => $estadosDisponibles ?? [],
]) ?>

<?= \Lib\Core\View::render('portal.recepcion-cita-medica.modales', [
    'catalogoEspecialidades' => $catalogoEspecialidades,
]) ?>
