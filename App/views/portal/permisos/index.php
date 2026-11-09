<?php
/**
 * Dashboard de permisos por rol — orquestador.
 *
 * Tarjetas por rol (n/total de permisos), matriz agrupada por módulo con
 * columnas ADMIN / RECEP / MEDICO / PAC y barra de "cambios sin guardar"
 * con guardado en una sola petición.
 */
$roles = $roles ?? [];
$permisos = $permisos ?? [];
$modulos = $modulos ?? [];
$matriz = $matriz ?? [];
$conteos = $conteos ?? [];
$totalPermisos = (int) ($totalPermisos ?? count($permisos));
?>

<style>
    .per-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .per-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .per-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 18px 20px;
    }

    .per-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .per-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .per-stat-icon.admin {
        background: #e8f0fe;
        color: #1a73e8;
    }

    .per-stat-icon.recep {
        background: #e7f7ed;
        color: #1e8e3e;
    }

    .per-stat-icon.medico {
        background: #e0f2f1;
        color: #0f8b84;
    }

    .per-stat-icon.pac {
        background: #fef7e0;
        color: #b8860b;
    }

    .per-stat-num {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1;
    }

    .per-stat-num small {
        font-size: 0.85rem;
        font-weight: 700;
        color: #6b7c83;
    }

    .per-stat-label {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .per-progress {
        height: 8px;
        border-radius: 8px;
        background: #eef2f1;
        overflow: hidden;
    }

    .per-progress > span {
        display: block;
        height: 100%;
        border-radius: 8px;
        background: var(--portal-primary);
        transition: width 0.3s ease;
    }

    .per-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .per-card h5 {
        font-weight: 700;
        color: #0b2a38;
        font-size: 1rem;
        margin-bottom: 6px;
    }

    .per-card .per-sub {
        font-size: 0.88rem;
        color: #6b7c83;
        margin-bottom: 18px;
    }

    .per-modulo {
        border-top: 1px solid #e8eeec;
        padding-top: 16px;
        margin-top: 16px;
    }

    .per-modulo:first-of-type {
        border-top: 0;
        padding-top: 0;
        margin-top: 0;
    }

    .per-modulo-titulo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--portal-primary);
        margin-bottom: 10px;
    }

    .per-modulo-titulo i {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: color-mix(in srgb, var(--portal-primary) 12%, white);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
    }

    .per-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .per-tabla table {
        margin-bottom: 0;
    }

    .per-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
        text-align: center;
        padding: 12px 10px;
    }

    .per-tabla thead th.per-col-permiso {
        text-align: left;
        width: 55%;
    }

    .per-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.9rem;
        text-align: center;
        padding: 10px;
    }

    .per-tabla td.per-celda-permiso {
        text-align: left;
    }

    .per-permiso-nombre {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .per-permiso-nombre small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.78rem;
    }

    .per-tabla .form-check {
        margin: 0;
        min-height: 0;
        display: inline-block;
    }

    .per-tabla .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
        position: relative;
    }

    .per-tabla tbody tr:hover td {
        background: #f8fbfa;
    }

    /* Barra de cambios sin guardar */
    .per-barra {
        position: sticky;
        bottom: 14px;
        z-index: 30;
        display: none;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        background: #0b2a38;
        color: #fff;
        border-radius: 14px;
        padding: 14px 18px;
        box-shadow: 0 12px 30px rgba(11, 42, 56, 0.35);
        margin-top: 18px;
    }

    .per-barra.visible {
        display: flex;
    }

    .per-barra small {
        color: #c8d6dc;
    }

    @media (max-width: 575.98px) {
        .per-card {
            padding: 18px;
        }

        .per-tabla thead th.per-col-permiso {
            width: auto;
        }

        .per-tabla thead th {
            font-size: 0.68rem;
            padding: 10px 6px;
        }
    }
</style>

<?= \Lib\Core\View::render('portal.permisos.header', [
    'totalPermisos' => $totalPermisos,
]) ?>

<?= \Lib\Core\View::render('portal.permisos.resumen', [
    'roles' => $roles,
    'conteos' => $conteos,
    'totalPermisos' => $totalPermisos,
]) ?>

<?= \Lib\Core\View::render('portal.permisos.matriz', [
    'roles' => $roles,
    'modulos' => $modulos,
    'matriz' => $matriz,
    'totalPermisos' => $totalPermisos,
]) ?>
