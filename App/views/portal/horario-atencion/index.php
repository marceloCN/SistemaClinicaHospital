<?php
/**
 * Dashboard de horarios de atención médica.
 *
 * Orquesta las vistas parciales: header, filtros, tabla y modales.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'activos' => 0,
    'inactivos' => 0,
    'medicos' => 0,
    'fichas' => 0,
];
$horarios = $horarios ?? [];
$conteos = $conteos ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$numerosPagina = $numerosPagina ?? [];
$filtroMedico = (string) ($filtroMedico ?? '');
$filtroEspecialidad = (string) ($filtroEspecialidad ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
$catalogoServicios = $catalogoServicios ?? [];
?>

<!-- es la session que solo muestra la estadistica de los horarios de atencion -->
<?= \Lib\Core\View::render('portal.horario-atencion.header', [
    'estadisticas' => $estadisticas,
]) ?>

<!-- es la session donde muestra el modal del boton + Nuevo horario y el formulario de filtros -->
<?= \Lib\Core\View::render('portal.horario-atencion.filtros', [
    'filtroMedico' => $filtroMedico,
    'filtroEspecialidad' => $filtroEspecialidad,
    'filtroEstado' => $filtroEstado,
    'catalogoEspecialidades' => $catalogoEspecialidades,
]) ?>

<!-- es la session donde muestra la tabla de horarios de atencion y el paginador -->
<?= \Lib\Core\View::render('portal.horario-atencion.tabla', [
    'horarios' => $horarios,
    'conteos' => $conteos,
    'total' => $total,
    'pagina' => $pagina,
    'paginas' => $paginas,
    'porPagina' => $porPagina,
    'numerosPagina' => $numerosPagina,
    'filtroMedico' => $filtroMedico,
    'filtroEspecialidad' => $filtroEspecialidad,
    'filtroEstado' => $filtroEstado,
]) ?>

<!-- es la session donde muestra el modal para crear un horario de atencion o generar fichas medicas -->
<?= \Lib\Core\View::render('portal.horario-atencion.modales', [
    'catalogoServicios' => $catalogoServicios,
]) ?>
