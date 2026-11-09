<?php
/**
 * Header del módulo de citas médicas del paciente.
 *
 * Incluye el bloque de estilos, título, estadísticas y botón para
 * abrir el asistente de generación de cita.
 */
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'pendientes' => 0,
    'en_clinica' => 0,
    'atendidas' => 0,
    'canceladas' => 0,
    'inasistencias' => 0,
];

$urlMisCitas = route('dashboard_mis_citas') ?? '/portal/dashboard/citas-medicas/mis-citas';
?>

<style>
    .cm-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }
    .cm-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .cm-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .cm-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .cm-stat-icon.total { background: #e8f4f3; color: var(--portal-primary); }
    .cm-stat-icon.pendientes { background: #fef7e0; color: #b8860b; }
    .cm-stat-icon.clinica { background: #e8f0fe; color: #1a73e8; }
    .cm-stat-icon.atendidas { background: #e7f7ed; color: #1e8e3e; }
    .cm-stat-icon.canceladas { background: #fdecea; color: #c0392b; }
    .cm-stat-icon.inasistencias { background: #f3e8ff; color: #7c3aed; }
    .cm-stat-num { font-size: 1.5rem; font-weight: 800; color: #0b2a38; line-height: 1.1; }
    .cm-stat-label { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7c83; }
    .cm-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }
    .cm-tabla table { margin-bottom: 0; }
    .cm-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }
    .cm-tabla td { vertical-align: middle; border-color: #eef2f1; font-size: 0.92rem; }
    .cm-hora { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .cm-paginacion { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
    .cm-paginacion .page-link { color: #44545c; border-color: #e0e6e3; }
    .cm-paginacion .page-item.active .page-link { background: var(--portal-primary); border-color: var(--portal-primary); }
    .cm-nombre { font-weight: 600; color: #0b2a38; line-height: 1.25; }
    .cm-nombre small { display: block; font-weight: 500; color: #6b7c83; font-size: 0.8rem; }
    .cm-buscador-voz { position: relative; }
    .cm-buscador-voz .cm-mic {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #6b7c83;
        cursor: pointer;
        padding: 6px 8px;
    }
    .cm-buscador-voz .cm-mic:hover { color: var(--portal-primary); }
    .cm-buscador-voz .cm-mic.escuchando { color: #c0392b; }
    .cm-lista-opciones {
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid #e0e6e3;
        border-radius: 10px;
        background: #f7f9f8;
    }
    .cm-opcion {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        border-bottom: 1px solid #eef2f1;
        background: transparent;
        padding: 12px 16px;
        cursor: pointer;
    }
    .cm-opcion:last-child { border-bottom: 0; }
    .cm-opcion:hover, .cm-opcion.activa { background: #e8f4f3; }
    .cm-opcion small { display: block; color: #6b7c83; font-size: 0.82rem; }
    .cm-paso-indicador { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; }
    .cm-paso-indicador span {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e0e6e3;
        color: #44545c;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .cm-paso-indicador span.activo { background: var(--portal-primary); color: #fff; }
    .cm-paso-titulo { text-align: center; margin-bottom: 18px; }
    .cm-paso-titulo h6 { font-weight: 700; margin: 0; }
    .cm-ficha-card {
        border: 1px solid #e0e6e3;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 10px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cm-ficha-card:hover, .cm-ficha-card.seleccionada { border-color: var(--portal-primary); background: #f4faf9; }
    .cm-alerta-error { display: none; }

    /* Paso 3: selector de turnos */
    .cm-resumen-seleccion {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        background: #f7f9f8;
        border: 1px solid #e4e9e7;
        border-radius: 14px;
        padding: 14px;
    }
    .cm-resumen-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }
    .cm-resumen-item i {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #fff;
        color: var(--portal-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e4e9e7;
    }
    .cm-resumen-item small { display: block; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7c83; font-weight: 700; }
    .cm-resumen-item strong { display: block; color: #0b2a38; font-weight: 700; line-height: 1.2; }

    .cm-fecha-selector { background: #fff; border: 1px solid #e4e9e7; border-radius: 14px; padding: 14px 16px; }
    .cm-fecha-label { display: block; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #44545c; margin-bottom: 8px; }
    .cm-fecha-input-wrap { position: relative; }
    .cm-fecha-input-wrap input[type="date"] { padding-left: 40px; background: #f7f9f8; border-color: #e0e6e3; }
    .cm-fecha-input-wrap::before {
        content: "\f073";
        font-family: "Font Awesome 5 Free";
        font-weight: 400;
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7c83;
    }

    .cm-fichas-label { display: block; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #44545c; margin-bottom: 10px; }
    .cm-fichas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 12px;
    }
    .cm-ficha-slot {
        position: relative;
        background: #fff;
        border: 2px solid #e4e9e7;
        border-radius: 14px;
        padding: 14px 10px;
        text-align: center;
        cursor: pointer;
        transition: all .15s ease;
    }
    .cm-ficha-slot:hover { border-color: #b8d6d2; box-shadow: 0 4px 12px rgba(15,139,132,.08); }
    .cm-ficha-slot.seleccionada { border-color: var(--portal-primary); background: #f0f9f8; box-shadow: 0 4px 14px rgba(15,139,132,.12); }
    .cm-ficha-slot .hora { font-size: 1.25rem; font-weight: 800; color: #0b2a38; line-height: 1; }
    .cm-ficha-slot .duracion { font-size: 0.72rem; color: #6b7c83; margin-top: 4px; }
    .cm-ficha-slot .badge-disponible {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background: #e7f7ed;
        color: #1e8e3e;
        padding: 3px 8px;
        border-radius: 20px;
        margin-top: 8px;
    }
    .cm-ficha-slot.seleccionada .badge-disponible { background: var(--portal-primary); color: #fff; }
    .cm-ficha-slot .check {
        position: absolute;
        top: 6px;
        right: 8px;
        color: var(--portal-primary);
        font-size: 0.9rem;
        opacity: 0;
        transition: opacity .15s ease;
    }
    .cm-ficha-slot.seleccionada .check { opacity: 1; }

    .cm-fichas-vacio, .cm-fichas-cargando {
        grid-column: 1 / -1;
        text-align: center;
        padding: 32px 20px;
        color: #6b7c83;
        background: #f7f9f8;
        border: 1px dashed #d0d9d5;
        border-radius: 14px;
    }
    .cm-fichas-vacio i, .cm-fichas-cargando i { font-size: 2rem; margin-bottom: 10px; display: block; color: #a8b5b0; }
    .cm-fichas-vacio p { margin: 0; font-size: 0.92rem; }

    @media (max-width: 575.98px) {
        .cm-stats { grid-template-columns: repeat(2, 1fr); }
        .cm-fichas-grid { grid-template-columns: repeat(2, 1fr); }
        .cm-resumen-seleccion { grid-template-columns: 1fr; }
        .cm-tabla thead { display: none; }
        .cm-tabla, .cm-tabla tbody, .cm-tabla tr, .cm-tabla td { display: block; width: 100%; }
        .cm-tabla tr { border-bottom: 1px solid #eef2f1; padding: 10px 0; }
        .cm-tabla td { border: 0; padding: 4px 16px; }
    }
</style>
<div class="cm-header">
    <div>
        <h3 class="m-0"><i class="fas fa-calendar-check text-secondary"></i> Citas médicas</h3>
        <p class="text-muted mb-0">Genera tus citas con los especialistas y revisa tu historial.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e($urlMisCitas) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-history mr-1"></i> Mis citas
        </a>
        <button type="button" class="btn btn-outline-primary" id="btn-cita-voz" title="Crear una cita hablando">
            <i class="fas fa-microphone mr-1"></i> Cita por voz
        </button>
        <button type="button" class="btn btn-primary" id="btn-nueva-cita">
            <i class="fas fa-plus mr-1"></i> Nueva cita
        </button>
    </div>
</div>

<div class="cm-stats">
    <div class="cm-stat">
        <span class="cm-stat-icon total"><i class="fas fa-calendar"></i></span>
        <div><div class="cm-stat-num"><?= (int) ($estadisticas['total'] ?? 0) ?></div><div class="cm-stat-label">Total</div></div>
    </div>
    <div class="cm-stat">
        <span class="cm-stat-icon pendientes"><i class="fas fa-clock"></i></span>
        <div><div class="cm-stat-num"><?= (int) ($estadisticas['pendientes'] ?? 0) ?></div><div class="cm-stat-label">Pendientes</div></div>
    </div>
    <div class="cm-stat">
        <span class="cm-stat-icon clinica"><i class="fas fa-hospital"></i></span>
        <div><div class="cm-stat-num"><?= (int) ($estadisticas['en_clinica'] ?? 0) ?></div><div class="cm-stat-label">En clínica</div></div>
    </div>
    <div class="cm-stat">
        <span class="cm-stat-icon atendidas"><i class="fas fa-check-circle"></i></span>
        <div><div class="cm-stat-num"><?= (int) ($estadisticas['atendidas'] ?? 0) ?></div><div class="cm-stat-label">Atendidas</div></div>
    </div>
    <div class="cm-stat">
        <span class="cm-stat-icon canceladas"><i class="fas fa-times-circle"></i></span>
        <div><div class="cm-stat-num"><?= (int) ($estadisticas['canceladas'] ?? 0) ?></div><div class="cm-stat-label">Canceladas</div></div>
    </div>
    <div class="cm-stat">
        <span class="cm-stat-icon inasistencias"><i class="fas fa-user-slash"></i></span>
        <div><div class="cm-stat-num"><?= (int) ($estadisticas['inasistencias'] ?? 0) ?></div><div class="cm-stat-label">Inasistencias</div></div>
    </div>
</div>

<?php require __DIR__ . '/../base/confirmar.php'; ?>
