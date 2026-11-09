<?php
/**
 * Panel del flujo de atención integral.
 *
 * Orquesta: header con estadísticas, bandeja (recepción o médica) y
 * modales de detalle, evaluación previa y habilitación sin evaluación.
 */
$vista = $vista ?? 'recepcion';
$pacientes = $pacientes ?? [];
$estadisticas = $estadisticas ?? [];
?>

<?= \Lib\Core\View::render('portal.atencion.header', [
    'vista' => $vista,
    'estadisticas' => $estadisticas,
]) ?>

<?= \Lib\Core\View::render('portal.atencion.bandeja', [
    'vista' => $vista,
    'pacientes' => $pacientes,
    'total' => $total ?? 0,
    'pagina' => $pagina ?? 1,
    'paginas' => $paginas ?? 1,
    'porPagina' => $porPagina ?? 10,
    'numerosPagina' => $numerosPagina ?? [],
    'filtroEstado' => $filtroEstado ?? '',
    'filtroTexto' => $filtroTexto ?? '',
]) ?>

<?= \Lib\Core\View::render('portal.atencion.modales', [
    'vista' => $vista,
]) ?>
