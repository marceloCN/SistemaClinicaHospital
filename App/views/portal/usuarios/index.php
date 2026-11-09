<?php
/**
 * Dashboard de empleados (Usuarios del sistema) — orquestador.
 *
 * Divide la interfaz en header, pestaña Resumen (tarjetas, donut por rol
 * y últimos registros) y pestaña Empleados (filtros, tabla paginada con
 * acciones y modales de detalle / edición / wizard de registro en 2
 * pasos con ficha de médico condicional).
 */
$personal = $personal ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$numerosPagina = $numerosPagina ?? [];
$tab = (string) ($tab ?? 'resumen');

$estadisticas = $estadisticas ?? [
    'total' => 0,
    'activos' => 0,
    'inactivos' => 0,
    'administradores' => 0,
    'recepcionistas' => 0,
    'medicos' => 0,
    'sin_cuenta' => 0,
    'bloqueados' => 0,
    'fichas_medico' => 0,
];
$recientes = $recientes ?? [];
$roles = $roles ?? [];

$statTotal = (int) ($estadisticas['total'] ?? 0);

/** Los filtros solo aplican y se conservan en la pestaña de datos. */
$tabContent = $tab === 'resumen'
    ? \Lib\Core\View::render('portal.usuarios.resumen', [
        'estadisticas' => $estadisticas,
        'recientes' => $recientes,
    ])
    : \Lib\Core\View::render('portal.usuarios.datos', [
        'personal' => $personal,
        'total' => $total,
        'pagina' => $pagina,
        'paginas' => $paginas,
        'porPagina' => $porPagina,
        'numerosPagina' => $numerosPagina,
        'roles' => $roles,
        'idActual' => (int) ($idActual ?? 0),
        'filtroNombre' => (string) ($filtroNombre ?? ''),
        'filtroCi' => (string) ($filtroCi ?? ''),
        'filtroRol' => (string) ($filtroRol ?? ''),
        'filtroEstado' => (string) ($filtroEstado ?? ''),
        'filtroDesde' => (string) ($filtroDesde ?? ''),
        'filtroHasta' => (string) ($filtroHasta ?? ''),
    ]);
?>

<style>
    .us-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .us-tabs {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 6px 8px;
        margin-bottom: 20px;
    }

    .us-tabs .nav-link {
        color: #44545c;
        font-weight: 600;
        font-size: 0.92rem;
        border-radius: 10px;
        padding: 10px 18px;
    }

    .us-tabs .nav-link.active {
        background: var(--portal-primary);
        color: #fff;
    }

    .us-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .us-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .us-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .us-stat-icon.total {
        background: #e8f4f3;
        color: var(--portal-primary);
    }

    .us-stat-icon.activos {
        background: #e7f7ed;
        color: #1e8e3e;
    }

    .us-stat-icon.inactivos {
        background: #fdecea;
        color: #c0392b;
    }

    .us-stat-icon.roles {
        background: #e8f0fe;
        color: #1a73e8;
    }

    .us-stat-icon.cuentas {
        background: #fef7e0;
        color: #b8860b;
    }

    .us-stat-icon.bloqueados {
        background: #f3e8fd;
        color: #7b1fa2;
    }

    .us-stat-num {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1.1;
    }

    .us-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .us-resumen-grid {
        display: grid;
        grid-template-columns: minmax(240px, 320px) 1fr;
        gap: 20px;
        align-items: stretch;
        margin-bottom: 20px;
    }

    @media (max-width: 767.98px) {
        .us-resumen-grid {
            grid-template-columns: 1fr;
        }
    }

    .us-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 24px;
    }

    .us-card h5 {
        font-weight: 700;
        color: #0b2a38;
        font-size: 1rem;
        margin-bottom: 18px;
    }

    .us-donut-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 18px;
    }

    .us-donut {
        position: relative;
        width: 190px;
        height: 190px;
    }

    .us-donut svg {
        transform: rotate(-90deg);
        width: 190px;
        height: 190px;
    }

    .us-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .us-donut-center strong {
        font-size: 1.9rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1;
    }

    .us-donut-center span {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7c83;
        margin-top: 4px;
    }

    .us-leyenda {
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }

    .us-leyenda-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
        color: #44545c;
    }

    .us-leyenda-item .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .us-leyenda-item strong {
        margin-left: auto;
        color: #0b2a38;
        font-variant-numeric: tabular-nums;
    }

    .us-reciente {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #eef2f1;
    }

    .us-reciente:last-child {
        border-bottom: 0;
    }

    .us-reciente-avatar {
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

    .us-filtros {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }

    .us-filtros .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .us-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .us-tabla table {
        margin-bottom: 0;
    }

    .us-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }

    .us-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.92rem;
    }

    .us-nombre {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .us-nombre small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.8rem;
    }

    .us-fecha {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        color: #0b2a38;
    }

    .us-paginacion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .us-paginacion .page-link {
        color: #44545c;
        border-color: #e0e6e3;
    }

    .us-paginacion .page-item.active .page-link {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
    }

    .us-detalle-form .form-control,
    .us-detalle-form .form-control:focus,
    .us-form .form-control,
    .us-form .form-control:focus {
        background: #f7f9f8;
        border-color: #e0e6e3;
        box-shadow: none;
    }

    .us-detalle-form label,
    .us-form label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .us-detalle-seccion {
        background: #fff;
        border: 1px solid #e8eeec;
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    .us-detalle-titulo {
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

    .us-detalle-titulo i {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        background: color-mix(in srgb, var(--portal-primary) 12%, white);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .us-campo-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
    }

    @media (max-width: 575.98px) {
        .us-campo-grid {
            grid-template-columns: 1fr;
        }

        .us-campo[style*="span 2"] {
            grid-column: span 1 !important;
        }
    }

    .us-campo {
        min-width: 0;
        background: #f7faf9;
        border: 1px solid #eef2f1;
        border-radius: 10px;
        padding: 9px 12px;
    }

    .us-campo-label {
        display: block;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #7d8f96;
        margin-bottom: 3px;
    }

    .us-campo-valor {
        display: block;
        font-size: 0.92rem;
        font-weight: 600;
        color: #0b2a38;
        word-break: break-word;
        line-height: 1.35;
    }

    .us-detalle-aviso {
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.86rem;
        color: #3d6a8f;
        margin-top: 12px;
    }

    /* Wizard de registro */
    .us-wizard-pasos {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-bottom: 22px;
    }

    .us-wizard-paso {
        flex: 1;
        max-width: 240px;
        text-align: center;
        padding: 10px 12px;
        border-radius: 12px;
        border: 2px solid #e0e6e3;
        font-size: 0.82rem;
        font-weight: 700;
        color: #6b7c83;
        background: #fff;
    }

    .us-wizard-paso.activo {
        border-color: var(--portal-primary);
        background: color-mix(in srgb, var(--portal-primary) 10%, white);
        color: var(--portal-primary);
    }

    .us-wizard-paso.completado {
        border-color: #2fae60;
        background: #e7f7ed;
        color: #1e8e3e;
    }

    .us-check {
        font-size: 0.8rem;
        margin-top: 4px;
    }

    .us-check.ok { color: #1e8e3e; }
    .us-check.error { color: #c0392b; }
    .us-check.info { color: #6b7c83; }

    .us-alerta-error {
        display: none;
    }

    /* Bloque de ficha médica condicional (paso 2) */
    .us-seccion-medico {
        border: 1px dashed #b9d4f3;
        background: #f4f9ff;
        border-radius: 12px;
        padding: 14px 16px;
        margin-top: 4px;
    }

    .us-seccion-medico h6 {
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #3d6a8f;
        margin-bottom: 12px;
    }

    /* Grupos del wizard (paso 2) */
    .us-grupo {
        background: #f7faf9;
        border: 1px solid #eef2f1;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 14px;
    }

    .us-grupo-titulo {
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--portal-primary);
        margin-bottom: 12px;
    }

    /* Componente de logros del médico */
    .us-logros {
        background: #ffffff;
        border: 1px solid #d9e6f5;
        border-radius: 12px;
        padding: 14px 16px;
    }

    .us-logros > label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: block;
        margin-bottom: 8px;
    }

    .us-logros-add {
        display: flex;
        gap: 8px;
        align-items: stretch;
    }

    .us-logros-add .form-control {
        flex: 1 1 auto;
        min-width: 0;
    }

    .us-logros-add .btn {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .us-logros-lista {
        list-style: none;
        margin: 12px 0 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 240px;
        overflow-y: auto;
    }

    .us-logros-item {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f4f9ff;
        border: 1px solid #d9e6f5;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.9rem;
        color: #0b2a38;
    }

    .us-logros-item > i {
        color: #1a73e8;
        flex-shrink: 0;
    }

    .us-logros-item > span {
        flex: 1 1 auto;
        min-width: 0;
        word-break: break-word;
        line-height: 1.35;
    }

    .us-logros-quitar {
        border: 0;
        background: transparent;
        color: #c0392b;
        font-size: 0.9rem;
        line-height: 1;
        padding: 4px 6px;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .us-logros-quitar:hover {
        background: #fdecea;
    }

    .us-logros-vacio {
        font-size: 0.85rem;
        color: #6b7c83;
        background: #f7f9f8;
        border: 1px dashed #e0e6e3;
        border-radius: 10px;
        padding: 12px 14px;
    }

    @media (max-width: 575.98px) {
        .us-filtros {
            padding: 16px;
        }

        .us-tabla thead {
            display: none;
        }

        .us-tabla,
        .us-tabla tbody,
        .us-tabla tr,
        .us-tabla td {
            display: block;
            width: 100%;
        }

        .us-tabla tr {
            border-bottom: 1px solid #eef2f1;
            padding: 10px 0;
        }

        .us-tabla td {
            border: 0;
            padding: 4px 16px;
        }
    }
</style>

<?= \Lib\Core\View::render('portal.usuarios.header', [
    'tab' => $tab,
    'statTotal' => $statTotal,
]) ?>

<?= $tabContent ?>
