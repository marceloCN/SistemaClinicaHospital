<?php
/**
 * Dashboard de pacientes — orquestador.
 *
 * Divide la interfaz en header, pestaña Resumen (dashboard con info
 * adicional) y pestaña Datos personales (filtros solo aquí, tabla con
 * acciones y modales de detalle / edición / contacto / registro).
 */
$pacientes = $pacientes ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$numerosPagina = $numerosPagina ?? [];
$tab = (string) ($tab ?? 'resumen');

$estadisticas = $estadisticas ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'regulares' => 0, 'activos_sin_ficha' => 0, 'inactivos_sin_ficha' => 0];
$recientes = $recientes ?? [];

$statTotal = (int) ($estadisticas['total'] ?? 0);

/** Los filtros solo aplican y se conservan en la pestaña de datos. */
$tabContent = $tab === 'resumen'
    ? \Lib\Core\View::render('portal.pacientes.resumen', [
        'estadisticas' => $estadisticas,
        'recientes' => $recientes,
    ])
    : \Lib\Core\View::render('portal.pacientes.datos', [
        'pacientes' => $pacientes,
        'total' => $total,
        'pagina' => $pagina,
        'paginas' => $paginas,
        'porPagina' => $porPagina,
        'numerosPagina' => $numerosPagina,
        'filtroNombre' => (string) ($filtroNombre ?? ''),
        'filtroCi' => (string) ($filtroCi ?? ''),
        'filtroSexo' => (string) ($filtroSexo ?? ''),
        'filtroEstado' => (string) ($filtroEstado ?? ''),
        'filtroRegular' => (string) ($filtroRegular ?? ''),
        'filtroDesde' => (string) ($filtroDesde ?? ''),
        'filtroHasta' => (string) ($filtroHasta ?? ''),
    ]);
?>

<style>
    .pac-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .pac-tabs {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 6px 8px;
        margin-bottom: 20px;
    }

    .pac-tabs .nav-link {
        color: #44545c;
        font-weight: 600;
        font-size: 0.92rem;
        border-radius: 10px;
        padding: 10px 18px;
    }

    .pac-tabs .nav-link.active {
        background: var(--portal-primary);
        color: #fff;
    }

    .pac-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .pac-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .pac-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .pac-stat-icon.total {
        background: #e8f4f3;
        color: var(--portal-primary);
    }

    .pac-stat-icon.activos {
        background: #e7f7ed;
        color: #1e8e3e;
    }

    .pac-stat-icon.inactivos {
        background: #fdecea;
        color: #c0392b;
    }

    .pac-stat-icon.regulares {
        background: #e8f0fe;
        color: #1a73e8;
    }

    .pac-stat-icon.sin-cuenta {
        background: #fef7e0;
        color: #b8860b;
    }

    .pac-stat-icon.bloqueados {
        background: #f3e8fd;
        color: #7b1fa2;
    }

    .pac-stat-num {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1.1;
    }

    .pac-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .pac-resumen-grid {
        display: grid;
        grid-template-columns: minmax(240px, 320px) 1fr;
        gap: 20px;
        align-items: stretch;
        margin-bottom: 20px;
    }

    @media (max-width: 767.98px) {
        .pac-resumen-grid {
            grid-template-columns: 1fr;
        }
    }

    .pac-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 24px;
    }

    .pac-card h5 {
        font-weight: 700;
        color: #0b2a38;
        font-size: 1rem;
        margin-bottom: 18px;
    }

    .pac-donut-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 18px;
    }

    .pac-donut {
        position: relative;
        width: 190px;
        height: 190px;
    }

    .pac-donut svg {
        transform: rotate(-90deg);
        width: 190px;
        height: 190px;
    }

    .pac-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .pac-donut-center strong {
        font-size: 1.9rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1;
    }

    .pac-donut-center span {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7c83;
        margin-top: 4px;
    }

    .pac-leyenda {
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }

    .pac-leyenda-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
        color: #44545c;
    }

    .pac-leyenda-item .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .pac-leyenda-item strong {
        margin-left: auto;
        color: #0b2a38;
        font-variant-numeric: tabular-nums;
    }

    .pac-reciente {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #eef2f1;
    }

    .pac-reciente:last-child {
        border-bottom: 0;
    }

    .pac-reciente-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #e8f4f3;
        color: var(--portal-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }

    .pac-filtros {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }

    .pac-filtros .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .pac-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .pac-tabla table {
        margin-bottom: 0;
    }

    .pac-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }

    .pac-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.92rem;
    }

    .pac-nombre {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .pac-nombre small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.8rem;
    }

    .pac-fecha {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        color: #0b2a38;
    }

    .pac-paginacion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .pac-paginacion .page-link {
        color: #44545c;
        border-color: #e0e6e3;
    }

    .pac-paginacion .page-item.active .page-link {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
    }

    .pac-detalle-form .form-control,
    .pac-detalle-form .form-control:focus,
    .pac-form .form-control,
    .pac-form .form-control:focus {
        background: #f7f9f8;
        border-color: #e0e6e3;
        box-shadow: none;
    }

    .pac-detalle-form label,
    .pac-form label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .pac-seccion {
        border-top: 1px solid #e8eeec;
        margin-top: 16px;
        padding-top: 14px;
    }

    .pac-seccion h6 {
        font-weight: 700;
        color: var(--portal-primary);
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 12px;
    }

    /* Modal de detalle: diseño de ficha */
    .pac-detalle-seccion {
        background: #fff;
        border: 1px solid #e8eeec;
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    .pac-detalle-titulo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--portal-primary);
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #e0e6e3;
    }

    .pac-detalle-titulo i {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        background: color-mix(in srgb, var(--portal-primary) 12%, white);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .pac-campo-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
    }

    @media (max-width: 575.98px) {
        .pac-campo-grid {
            grid-template-columns: 1fr;
        }

        .pac-campo[style*="span 2"] {
            grid-column: span 1 !important;
        }
    }

    .pac-campo {
        min-width: 0;
        background: #f7faf9;
        border: 1px solid #eef2f1;
        border-radius: 10px;
        padding: 9px 12px;
    }

    .pac-campo-label {
        display: block;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #7d8f96;
        margin-bottom: 3px;
    }

    .pac-campo-valor {
        display: block;
        font-size: 0.92rem;
        font-weight: 600;
        color: #0b2a38;
        word-break: break-word;
        line-height: 1.35;
    }

    .pac-detalle-aviso {
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.86rem;
        color: #3d6a8f;
        margin-top: 12px;
    }

    .pac-contacto {
        border: 1px solid #e8eeec;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 10px;
    }

    .pac-contacto strong {
        color: #0b2a38;
        display: block;
    }

    .pac-contacto small {
        color: #6b7c83;
    }

    /* Wizard de registro */
    .pac-wizard-pasos {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 22px;
    }

    .pac-wizard-paso {
        flex: 1;
        max-width: 220px;
        text-align: center;
        padding: 10px 12px;
        border-radius: 12px;
        border: 2px solid #e0e6e3;
        font-size: 0.82rem;
        font-weight: 700;
        color: #6b7c83;
        background: #fff;
    }

    .pac-wizard-paso.activo {
        border-color: var(--portal-primary);
        background: color-mix(in srgb, var(--portal-primary) 10%, white);
        color: var(--portal-primary);
    }

    .pac-wizard-paso.completado {
        border-color: #2fae60;
        background: #e7f7ed;
        color: #1e8e3e;
    }

    .pac-check {
        font-size: 0.8rem;
        margin-top: 4px;
    }

    .pac-check.ok { color: #1e8e3e; }
    .pac-check.error { color: #c0392b; }
    .pac-check.info { color: #6b7c83; }

    .pac-alerta-error {
        display: none;
    }

    @media (max-width: 575.98px) {
        .pac-filtros {
            padding: 16px;
        }

        .pac-tabla thead {
            display: none;
        }

        .pac-tabla,
        .pac-tabla tbody,
        .pac-tabla tr,
        .pac-tabla td {
            display: block;
            width: 100%;
        }

        .pac-tabla tr {
            border-bottom: 1px solid #eef2f1;
            padding: 10px 0;
        }

        .pac-tabla td {
            border: 0;
            padding: 4px 16px;
        }
    }
</style>

<?= \Lib\Core\View::render('portal.pacientes.header', [
    'tab' => $tab,
    'statTotal' => $statTotal,
]) ?>

<?= $tabContent ?>
