<?php
$config = $config ?? [];
$configuraciones = $configuraciones ?? [];
$categorias = $categorias ?? [];
$servicios = $servicios ?? [];
$especialidades = $especialidades ?? [];
$medicos = $medicos ?? [];
$seleccionEspecialidades = array_map('intval', $seleccionEspecialidades ?? []);
$seleccionMedicos = array_map('intval', $seleccionMedicos ?? []);
$presets = $presets ?? [];
$presetActual = (string) ($presetActual ?? 'personalizado');
$iconos = $iconos ?? [];

$categoriasExcluidas = ['apariencia', 'pagina_inicial'];
$categoriasGenerales = array_values(array_filter(
    $categorias,
    static fn(string $categoria): bool => !in_array($categoria, $categoriasExcluidas, true)
));

$etiquetasCategoria = [
    'general' => 'Datos de la clínica',
    'citas' => 'Citas y horarios',
];

$curadas = [
    'citas' => [
        'titulo' => 'Citas y horarios',
        'ayuda' => 'Valores por defecto que usan el calendario de citas y la atención diaria.',
        'contexto' => 'Calendario de citas y horarios del centro.',
        'campos' => [
            'duracion_cita_minutos_default' => ['Duración de cada cita', 'integer', 'Minutos que dura una cita estándar.'],
            'hora_inicio_atencion_default' => ['Hora de inicio de atención', 'time', 'Primera hora disponible para agendar.'],
            'hora_fin_atencion_default' => ['Hora de fin de atención', 'time', 'Última hora disponible para agendar.'],
        ],
    ],
    'general' => [
        'titulo' => 'Datos de la clínica',
        'ayuda' => 'Aparecen en la barra superior, el pie de página y las páginas públicas.',
        'contexto' => 'Barra superior, pie de página y páginas públicas.',
        'campos' => [
            'clinica_nombre' => ['Nombre de la clínica', 'text', ''],
            'clinica_eslogan' => ['Eslogan', 'text', ''],
            'clinica_direccion' => ['Dirección', 'text', ''],
            'clinica_telefono' => ['Teléfono', 'text', ''],
            'clinica_correo' => ['Correo de contacto', 'email', ''],
            'clinica_emergencias' => ['Emergencias', 'text', ''],
            'clinica_horario' => ['Horario (lunes a viernes)', 'text', ''],
            'clinica_horario_sabado' => ['Horario (sábados)', 'text', ''],
        ],
    ],
];

$colorPrimario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($config['tema_color_primario'] ?? ''))
    ? (string) $config['tema_color_primario']
    : '#008040';
$colorSecundario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($config['tema_color_secundario'] ?? ''))
    ? (string) $config['tema_color_secundario']
    : '#0a3d2e';

$opcionesIconos = static function (string $seleccionado) use ($iconos): string {
    $html = '';

    foreach ($iconos as $icono => $etiqueta) {
        $html .= '<option value="' . e((string) $icono) . '"'
            . ($icono === $seleccionado ? ' selected' : '')
            . '>' . e((string) $etiqueta) . '</option>';
    }

    return $html;
};

$iconosJson = json_encode($iconos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$endpointJson = json_encode(route('portal_configuracion_guardar'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$endpointImagenJson = json_encode(route('portal_configuracion_imagen'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$endpointImagenQuitarJson = json_encode(route('portal_configuracion_imagen_quitar'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$urlBaseJson = json_encode(URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$imagenFondo = trim((string) ($config['pagina_inicial_imagen_fondo'] ?? ''));
$imagenFondoUrl = $imagenFondo === ''
    ? ''
    : (preg_match('#^https?://#i', $imagenFondo) ? $imagenFondo : URL . '/' . ltrim($imagenFondo, '/'));
$esOscuro = ($config['tema_modo'] ?? 'claro') === 'oscuro';
?>

<style>
    .cfg-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 8px;
        padding-bottom: 6px;
    }

    .cfg-tabs .nav-link {
        border-radius: 50px;
        white-space: nowrap;
        color: #44545c;
        font-weight: 600;
        border: 1px solid #e0e6e3;
        background: #fff;
    }

    .cfg-tabs .nav-link.active {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
        color: #fff;
    }

    .cfg-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 14px;
        padding: 22px;
    }

    .cfg-card-head {
        margin-bottom: 18px;
    }

    .cfg-card-head h5 {
        font-weight: 800;
        margin-bottom: 4px;
    }

    .cfg-card-head p {
        color: #6b7c83;
        font-size: 0.9rem;
        margin: 0;
    }

    .cfg-presets {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 10px;
    }

    .cfg-preset {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #e0e6e3;
        border-radius: 12px;
        text-align: left;
        font-weight: 600;
        color: #334155;
        transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    }

    .cfg-preset:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(11, 42, 56, 0.08);
    }

    .cfg-preset.active {
        border-color: var(--portal-primary);
        box-shadow: 0 0 0 3px rgba(15, 139, 132, 0.15);
    }

    .cfg-preset-colors {
        display: inline-flex;
        border-radius: 50px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.08);
        flex-shrink: 0;
    }

    .cfg-preset-colors span {
        width: 18px;
        height: 28px;
        display: block;
    }

    .cfg-preset .fa-check-circle {
        margin-left: auto;
        color: var(--portal-primary);
    }

    .cfg-hero {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .cfg-hero-preview {
        width: 240px;
        height: 135px;
        flex-shrink: 0;
        border: 1px solid #e4e9e7;
        border-radius: 12px;
        overflow: hidden;
        background: #f4f7f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9aa8ae;
    }

    .cfg-hero-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cfg-hero-preview span {
        font-size: 1.8rem;
    }

    .cfg-hero-preview-vacio {
        border-style: dashed;
    }

    .cfg-hero-acciones {
        flex: 1;
        min-width: 250px;
    }

    .cfg-preview {
        --cfg-primary: #008040;
        --cfg-secondary: #0a3d2e;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #e4e9e7;
        box-shadow: 0 16px 40px rgba(11, 42, 56, 0.08);
        background: linear-gradient(135deg, #fff 0%, color-mix(in srgb, var(--cfg-primary) 10%, white) 100%);
    }

    .cfg-preview-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 16px;
        background: var(--cfg-secondary);
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.75rem;
    }

    .cfg-preview-body {
        padding: 26px 24px 18px;
    }

    .cfg-preview-eyebrow {
        display: inline-block;
        color: var(--cfg-primary);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .cfg-preview-body h3 {
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.15;
        color: #0b2a38;
        margin-bottom: 8px;
    }

    .cfg-preview-body h3 span {
        color: var(--cfg-primary);
    }

    .cfg-preview-body p {
        color: #5d7078;
        font-size: 0.85rem;
        margin-bottom: 14px;
    }

    .cfg-preview-btn {
        display: inline-block;
        background: var(--cfg-primary);
        color: #fff;
        border-radius: 50px;
        padding: 8px 18px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .cfg-preview-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 0 24px 22px;
    }

    .cfg-preview-stats span {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 10px;
        padding: 8px;
        text-align: center;
        font-size: 0.7rem;
        color: #5d7078;
    }

    .cfg-preview-stats strong {
        display: block;
        color: var(--cfg-primary);
        font-size: 0.9rem;
    }

    .cfg-servicio {
        display: grid;
        grid-template-columns: 150px minmax(160px, 1fr) minmax(240px, 1.6fr) auto;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border: 1px solid #e4e9e7;
        border-radius: 12px;
        margin-bottom: 10px;
        background: #fbfcfd;
    }

    .cfg-servicio-handle {
        display: none;
    }

    .cfg-servicio-actions {
        display: flex;
        gap: 4px;
    }

    .cfg-permisos-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .cfg-permiso {
        display: inline-flex;
        flex-direction: column;
        gap: 2px;
        border: 1px solid #e0e6e3;
        background: #f8faf9;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
    }

    .cfg-permiso small {
        color: #7b8a90;
        font-weight: 500;
        font-size: 0.72rem;
    }

    .cfg-permiso i {
        color: var(--portal-primary);
        margin-right: 4px;
    }

    .cfg-savebar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 20px;
        padding: 14px 18px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #e4e9e7;
        border-radius: 14px;
        backdrop-filter: blur(8px);
        box-shadow: 0 -8px 30px rgba(11, 42, 56, 0.06);
    }

    .cfg-savebar small {
        color: #6b7c83;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }

    .cfg-card .table {
        min-width: 560px;
    }

    @media (max-width: 992px) {
        .cfg-servicio {
            grid-template-columns: 1fr;
        }

        .cfg-servicio-actions {
            justify-content: flex-end;
        }
    }

    @media (max-width: 768px) {
        .cfg-tabs {
            gap: 6px;
            -webkit-overflow-scrolling: touch;
        }

        .cfg-tabs .nav-link {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .cfg-card {
            padding: 16px;
            border-radius: 12px;
        }

        .cfg-preview-body {
            padding: 20px 18px 14px;
        }

        .cfg-preview-body h3 {
            font-size: 1.3rem;
        }

        .cfg-preview-stats {
            padding: 0 18px 18px;
        }

        .cfg-savebar {
            position: static;
            flex-direction: column;
            align-items: stretch;
            padding: 12px;
        }

        .cfg-savebar>div {
            display: flex;
            width: 100%;
            gap: 8px;
        }

        .cfg-savebar .btn {
            flex: 1 1 auto;
        }
    }

    @media (max-width: 480px) {
        .cfg-presets {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .cfg-preset {
            padding: 8px;
            font-size: 0.78rem;
        }

        .cfg-preset-colors span {
            width: 14px;
            height: 24px;
        }

        .cfg-preview-stats {
            grid-template-columns: 1fr;
        }

        .cfg-savebar .btn-lg {
            font-size: 0.95rem;
            padding: 10px 12px;
        }
    }

    /* =====================================================================
       Vista previa en vivo de la portada
       ===================================================================== */

    .cfg-layout {
        align-items: flex-start;
    }

    .cfg-contexto {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f1f7f6;
        border: 1px dashed color-mix(in srgb, var(--portal-primary) 45%, white);
        color: #33555c;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 10px;
    }

    .cfg-contexto i {
        color: var(--portal-primary);
    }

    .cfg-contexto strong {
        color: #0b2a38;
    }

    .cfg-preview-card {
        position: sticky;
        top: 16px;
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 14px 36px rgba(11, 42, 56, 0.08);
    }

    .cfg-preview-card-head {
        padding: 14px 16px;
        border-bottom: 1px solid #eef2f0;
        background: #fbfcfd;
    }

    .cfg-preview-card-head h6 {
        font-weight: 800;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cfg-preview-card-head h6 i {
        color: var(--portal-primary);
    }

    .cfg-preview-card-head p {
        margin: 0;
        color: #6b7c83;
        font-size: 0.78rem;
    }

    .cfg-frame-wrap {
        position: relative;
        height: 560px;
        background: #f4f7f6;
        overflow: hidden;
    }

    .cfg-frame-wrap.cfg-frame-cargando::after {
        content: "Actualizando vista previa...";
        position: absolute;
        inset: auto 0 0 0;
        padding: 6px 10px;
        text-align: center;
        font-size: 0.72rem;
        color: #6b7c83;
        background: rgba(255, 255, 255, 0.92);
        border-top: 1px solid #e4e9e7;
    }

    #cfg-frame {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
        background: #fff;
        transition: opacity 0.2s;
    }

    .cfg-preview-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
        padding: 10px 14px;
        border-top: 1px solid #eef2f0;
        background: #fff;
    }

    .cfg-preview-toolbar .btn {
        font-size: 0.78rem;
    }

    .cfg-preview-toolbar small {
        color: #6b7c83;
        font-size: 0.72rem;
    }

    .cfg-seccion-resaltada {
        outline: 3px solid var(--portal-primary);
        outline-offset: 6px;
        transition: outline-color 0.4s;
    }

    @media (max-width: 992px) {
        .cfg-preview-card {
            position: static;
            margin-bottom: 20px;
        }

        .cfg-frame-wrap {
            height: 420px;
        }
    }

</style>

<div class="d-flex justify-content-between align-items-start flex-wrap mb-4" style="gap: 12px;">
    <div>
        <h3 class="m-0"><i class="fas fa-cogs text-secondary"></i> Configuración del sistema</h3>
        <p class="text-muted mb-0">Administra el tema y todo el contenido que se refleja en la página de inicio pública.</p>
    </div>
    <span class="badge badge-secondary align-self-center">Solo ADMIN</span>
</div>

<form id="form-configuracion">
    <?= csrf_field() ?>

    <div class="row cfg-layout">
        <div class="col-lg-8">
    <ul class="nav nav-pills cfg-tabs mb-3" role="tablist">

        <li class="nav-item">
            <a class="nav-link active" id="tab-apariencia-link" data-toggle="tab" href="#tab-apariencia" role="tab" data-seccion="apariencia">
                <i class="fas fa-palette"></i> Apariencia
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-inicio-link" data-toggle="tab" href="#tab-inicio" role="tab" data-seccion="inicio">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-servicios-link" data-toggle="tab" href="#tab-servicios" role="tab" data-seccion="servicios">
                <i class="fas fa-th-large"></i> Servicios
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-especialidades-link" data-toggle="tab" href="#tab-especialidades" role="tab" data-seccion="especialidades">
                <i class="fas fa-stethoscope"></i> Especialidades
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-medicos-link" data-toggle="tab" href="#tab-medicos" role="tab" data-seccion="medicos">
                <i class="fas fa-user-md"></i> Médicos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tab-general-link" data-toggle="tab" href="#tab-general" role="tab" data-seccion="general">
                <i class="fas fa-sliders-h"></i> General
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-apariencia" role="tabpanel">
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="cfg-card h-100">
                        <div class="cfg-card-head">
                            <h5>Colores y tema</h5>
                            <p>Elige un preset o personaliza los colores. El cambio se aplica al inicio, especialidades, médicos y encabezado público.</p>
                            <p class="cfg-contexto"><i class="fas fa-eye"></i> Estás modificando: <strong>Colores de toda la portada</strong> — topbar, botones, enlaces y acentos.</p>
                        </div>

                        <input type="hidden" name="config[tema_preset]" id="cfg-tema-preset" value="<?= e($presetActual) ?>">

                        <div class="cfg-presets mb-4">
                            <?php foreach ($presets as $clave => $preset) { ?>
                                <button type="button"
                                    class="cfg-preset js-preset <?= $presetActual === $clave ? 'active' : '' ?>"
                                    data-preset="<?= e((string) $clave) ?>"
                                    data-primary="<?= e((string) $preset['primary']) ?>"
                                    data-secondary="<?= e((string) $preset['secondary']) ?>">
                                    <span class="cfg-preset-colors">
                                        <span style="background: <?= e((string) $preset['primary']) ?>"></span>
                                        <span style="background: <?= e((string) $preset['secondary']) ?>"></span>
                                    </span>
                                    <span><?= e((string) $preset['nombre']) ?></span>
                                    <?php if ($presetActual === $clave) { ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php } ?>
                                </button>
                            <?php } ?>
                            <button type="button"
                                class="cfg-preset js-preset <?= $presetActual === 'personalizado' ? 'active' : '' ?>"
                                data-preset="personalizado"
                                data-primary=""
                                data-secondary="">
                                <span class="cfg-preset-colors">
                                    <span style="background: <?= e($colorPrimario) ?>"></span>
                                    <span style="background: <?= e($colorSecundario) ?>"></span>
                                </span>
                                <span>Personalizado</span>
                                <?php if ($presetActual === 'personalizado') { ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php } ?>
                            </button>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="cfg-tema-modo" name="config[tema_modo]" value="oscuro" data-off="claro" <?= $esOscuro ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="cfg-tema-modo"><strong>Modo oscuro</strong></label>
                            </div>
                            <small class="form-text text-muted">Se aplica a la portada pública y a este panel. Se combina con cualquier paleta de colores.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cfg-color-primario">Color primario</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="cfg-color-primario-picker" value="<?= e($colorPrimario) ?>">
                                        <input type="text" class="form-control" id="cfg-color-primario" name="config[tema_color_primario]" value="<?= e($colorPrimario) ?>" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" required>
                                    </div>
                                    <small class="form-text text-muted">Botones, enlaces y acentos.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cfg-color-secundario">Color secundario</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="cfg-color-secundario-picker" value="<?= e($colorSecundario) ?>">
                                        <input type="text" class="form-control" id="cfg-color-secundario" name="config[tema_color_secundario]" value="<?= e($colorSecundario) ?>" maxlength="7" pattern="^#[a-fA-F0-9]{6}$" required>
                                    </div>
                                    <small class="form-text text-muted">Franjas, contacto y pie de página.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="cfg-hero-archivo">Imagen de fondo del hero</label>
                            <input type="hidden" name="config[pagina_inicial_imagen_fondo]" id="cfg-imagen-fondo" value="<?= e($imagenFondo) ?>">

                            <div class="cfg-hero">
                                <div class="cfg-hero-preview<?= $imagenFondoUrl === '' ? ' cfg-hero-preview-vacio' : '' ?>" id="cfg-hero-preview">
                                    <?php if ($imagenFondoUrl !== '') { ?>
                                        <img src="<?= e($imagenFondoUrl) ?>" alt="Imagen de fondo del hero" id="cfg-hero-img">
                                    <?php } else { ?>
                                        <span id="cfg-hero-img"><i class="fas fa-image"></i></span>
                                    <?php } ?>
                                </div>

                                <div class="cfg-hero-acciones">
                                    <input type="file" class="form-control-file" id="cfg-hero-archivo" accept="image/jpeg,image/png,image/webp">
                                    <small class="form-text text-muted mb-2">JPG, PNG o WebP, máximo 2 MB. Se guarda en <code>public/assets/img/</code> y reemplaza la imagen anterior.</small>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="btn-hero-quitar" <?= $imagenFondoUrl === '' ? 'style="display:none;"' : '' ?>>
                                        <i class="fas fa-trash"></i> Quitar imagen
                                    </button>
                                    <div class="form-text" id="cfg-hero-estado"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-inicio" role="tabpanel">
            <div class="cfg-card">
                <div class="cfg-card-head">
                    <h5>Contenido del inicio</h5>
                    <p>Estos textos se muestran en el hero de la página principal. Los datos de contacto y horarios están en la pestaña General.</p>
                    <p class="cfg-contexto"><i class="fas fa-eye"></i> Estás modificando: <strong>Hero de la portada (#inicio)</strong> — título, subtítulo, descripción y estadísticas.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cfg-titulo">Título principal</label>
                            <input type="text" class="form-control" id="cfg-titulo" name="config[pagina_inicial_titulo]" value="<?= e((string) ($config['pagina_inicial_titulo'] ?? '')) ?>" maxlength="160" required>
                            <small class="form-text text-muted">Si incluye el nombre de la clínica, se resalta automáticamente.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cfg-subtitulo">Subtítulo</label>
                            <input type="text" class="form-control" id="cfg-subtitulo" name="config[pagina_inicial_subtitulo]" value="<?= e((string) ($config['pagina_inicial_subtitulo'] ?? '')) ?>" maxlength="200" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="cfg-descripcion">Descripción</label>
                            <textarea class="form-control" id="cfg-descripcion" name="config[pagina_inicial_descripcion]" rows="3" maxlength="2000"><?= e((string) ($config['pagina_inicial_descripcion'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cfg-anios">Años de experiencia</label>
                            <input type="number" class="form-control" id="cfg-anios" name="config[inicio_anios_experiencia]" value="<?= (int) ($config['inicio_anios_experiencia'] ?? 25) ?>" min="0" max="100">
                            <small class="form-text text-muted">Se muestra en las estadísticas del inicio.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cfg-medicos-cantidad">Médicos a mostrar</label>
                            <input type="number" class="form-control" id="cfg-medicos-cantidad" name="config[inicio_medicos_cantidad]" value="<?= (int) ($config['inicio_medicos_cantidad'] ?? 3) ?>" min="1" max="12">
                            <small class="form-text text-muted">Cantidad máxima de destacados en la sección de médicos.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-servicios" role="tabpanel">
            <div class="cfg-card">
                <div class="cfg-card-head">
                    <h5>Detalle de servicios</h5>
                    <p>Cada tarjeta de la sección "Nuestros servicios" del inicio. Puedes reordenarlas y editar título, descripción e icono.</p>
                    <p class="cfg-contexto"><i class="fas fa-eye"></i> Estás modificando: <strong>Sección #servicios de la portada</strong> — las tarjetas que ve el visitante.</p>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="cfg-mostrar-servicios" name="config[inicio_mostrar_servicios]" value="1" data-off="0" <?= (bool) ($config['inicio_mostrar_servicios'] ?? true) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="cfg-mostrar-servicios">Mostrar la sección de servicios en la portada</label>
                    </div>
                    <small class="form-text text-muted">Si la ocultas, también desaparecen el botón "Conocer servicios" y los enlaces del menú.</small>
                </div>

                <div class="alert alert-info py-2">
                    <i class="fas fa-lightbulb"></i>
                    Ejemplo: <strong>Diagnóstico por imagen</strong> → "Equipos modernos y lectura especializada para una evaluación precisa."
                </div>

                <div id="servicios-lista">
                    <?php foreach ($servicios as $servicio) { ?>
                        <div class="cfg-servicio">
                            <span class="cfg-servicio-handle"><i class="fas fa-bars"></i></span>
                            <select class="form-control cfg-servicio-icon" aria-label="Icono">
                                <?= $opcionesIconos((string) ($servicio['icon'] ?? 'fa-stethoscope')) ?>
                            </select>
                            <input type="text" class="form-control cfg-servicio-title" maxlength="90" value="<?= e((string) ($servicio['title'] ?? '')) ?>" placeholder="Título del servicio">
                            <input type="text" class="form-control cfg-servicio-desc" maxlength="300" value="<?= e((string) ($servicio['desc'] ?? '')) ?>" placeholder="Descripción breve">
                            <div class="cfg-servicio-actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary js-servicio-subir" title="Subir"><i class="fas fa-arrow-up"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-servicio-bajar" title="Bajar"><i class="fas fa-arrow-down"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger js-servicio-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-servicio">
                    <i class="fas fa-plus"></i> Agregar servicio
                </button>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-especialidades" role="tabpanel">
            <div class="cfg-card">
                <div class="cfg-card-head">
                    <h5>Especialidades del inicio</h5>
                    <p>Se listan desde <code>servicio_medico</code> (acreditaciones activas). Si esa tabla aún está vacía, se usa <code>medico_especialidad</code> como respaldo.</p>
                    <p class="cfg-contexto"><i class="fas fa-eye"></i> Estás modificando: <strong>Sección #especialidades de la portada</strong> — buscador y directorio de especialidades.</p>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="cfg-mostrar-especialidades" name="config[inicio_mostrar_especialidades]" value="1" data-off="0" <?= (bool) ($config['inicio_mostrar_especialidades'] ?? true) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="cfg-mostrar-especialidades">Mostrar la sección de especialidades en la portada</label>
                    </div>
                    <small class="form-text text-muted">Si la ocultas, el enlace "Especialidades" del menú también desaparece.</small>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap: 10px;">
                    <span class="badge badge-primary"><?= count($especialidades) ?> especialidad(es) con servicio activo</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-esp-todas">Seleccionar todas</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-esp-ninguna">Quitar todas</button>
                    </div>
                </div>

                <?php if ($especialidades === []) { ?>
                    <div class="alert alert-warning mb-0">
                        No hay especialidades con médicos activos. Registra médicos y sus acreditaciones para verlas aquí.
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 44px;"></th>
                                    <th>Especialidad</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Médicos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($especialidades as $especialidad) { ?>
                                    <?php $espId = (int) $especialidad['id']; ?>
                                    <tr>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input js-esp-check" id="esp_<?= $espId ?>" value="<?= $espId ?>" <?= in_array($espId, $seleccionEspecialidades, true) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="esp_<?= $espId ?>"></label>
                                            </div>
                                        </td>
                                        <td><strong><?= e((string) $especialidad['nombre']) ?></strong></td>
                                        <td class="text-muted"><?= e((string) ($especialidad['descripcion'] ?? '')) ?></td>
                                        <td class="text-right"><span class="badge badge-light"><?= (int) ($especialidad['total_medicos'] ?? 0) ?></span></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-medicos" role="tabpanel">
            <div class="cfg-card">
                <div class="cfg-card-head">
                    <h5>Médicos del inicio</h5>
                    <p>Se listan los médicos con acreditación activa en <code>servicio_medico</code>. Elige cuáles destacar y cuántos mostrar.</p>
                    <p class="cfg-contexto"><i class="fas fa-eye"></i> Estás modificando: <strong>Sección #medicos de la portada</strong> — destacados del equipo y directorio con buscador.</p>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="cfg-mostrar-medicos" name="config[inicio_mostrar_medicos]" value="1" data-off="0" <?= (bool) ($config['inicio_mostrar_medicos'] ?? true) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="cfg-mostrar-medicos">Mostrar la sección de médicos en la portada</label>
                    </div>
                    <small class="form-text text-muted">Si la ocultas, el botón "Reservar una cita" apunta a la sección de contacto.</small>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap: 10px;">
                    <span class="badge badge-primary"><?= count($medicos) ?> médico(s) con servicio activo</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-med-todos">Seleccionar todos</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-med-ninguno">Quitar todos</button>
                    </div>
                </div>

                <?php if ($medicos === []) { ?>
                    <div class="alert alert-warning mb-0">
                        No hay médicos con servicio activo. Verifica las acreditaciones en <code>medico_especialidad</code> y <code>servicio_medico</code>.
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 44px;"></th>
                                    <th>Médico</th>
                                    <th>Especialidades</th>
                                    <th>CMP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medicos as $medico) { ?>
                                    <?php $medId = (int) $medico['id']; ?>
                                    <tr>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input js-med-check" id="med_<?= $medId ?>" value="<?= $medId ?>" <?= in_array($medId, $seleccionMedicos, true) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="med_<?= $medId ?>"></label>
                                            </div>
                                        </td>
                                        <td><strong>Dr./Dra. <?= e((string) $medico['nombre']) ?> <?= e((string) $medico['apellido']) ?></strong></td>
                                        <td class="text-muted"><?= e((string) ($medico['especialidades'] ?? '')) ?></td>
                                        <td><?= e((string) ($medico['nro_colegiado'] ?? '')) ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>


        <div class="tab-pane fade" id="tab-general" role="tabpanel">
            <div class="alert alert-light border py-2">
                <i class="fas fa-info-circle text-primary"></i>
                Estos valores alimentan el encabezado, el pie de página, las páginas públicas y los valores por defecto de las citas.
                <p class="cfg-contexto mb-0 mt-2"><i class="fas fa-eye"></i> Estás modificando: <strong>Barra superior y pie de página</strong> — contacto, horarios y datos de la clínica.</p>
            </div>

            <div class="cfg-card mb-3">
                <div class="cfg-card-head">
                    <h5>Sección de contacto de la portada</h5>
                    <p>El bloque final de la página principal: <strong>"Conversemos sobre tu salud"</strong> con teléfono, emergencias, correo y dirección.</p>
                    <p class="cfg-contexto"><i class="fas fa-eye"></i> Estás modificando: <strong>Sección #contacto de la portada</strong> — decide si se visualiza o no.</p>
                </div>

                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="cfg-mostrar-contacto" name="config[inicio_mostrar_contacto]" value="1" data-off="0" <?= (bool) ($config['inicio_mostrar_contacto'] ?? true) ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="cfg-mostrar-contacto">Mostrar la sección de contacto en la portada</label>
                </div>
                <small class="form-text text-muted mt-2">Los datos de contacto siguen visibles en la barra superior y el pie de página.</small>
            </div>

            <?php foreach ($curadas as $categoria => $definicion) { ?>
                <?php
                $itemsPorClave = [];

                foreach ($configuraciones[$categoria] ?? [] as $itemGeneral) {
                    $itemsPorClave[(string) $itemGeneral['clave']] = $itemGeneral;
                }
                ?>
                <div class="cfg-card mb-3">
                    <div class="cfg-card-head">
                        <h5><?= e($definicion['titulo']) ?></h5>
                        <p><?= e($definicion['ayuda']) ?></p>
                        <p class="cfg-contexto"><i class="fas fa-eye"></i> Estás modificando: <strong><?= e($definicion['contexto']) ?></strong></p>
                    </div>

                    <div class="row">
                        <?php foreach ($definicion['campos'] as $claveCurada => $campoCurado) { ?>
                            <?php
                            if (!isset($itemsPorClave[$claveCurada])) {
                                continue;
                            }

                            $itemCurado = $itemsPorClave[$claveCurada];
                            $valorCurado = (string) ($itemCurado['valor'] ?? '');
                            $editableCurado = (bool) ($itemCurado['editable'] ?? true);
                            $etiquetaCurada = (string) $campoCurado[0];
                            $tipoCurado = (string) $campoCurado[1];
                            $ayudaCurada = (string) ($campoCurado[2] ?? '');
                            $inputIdCurado = 'cfg_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $claveCurada);
                            ?>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="<?= e($inputIdCurado) ?>"><?= e($etiquetaCurada) ?></label>

                                    <?php if ($tipoCurado === 'time') { ?>
                                        <input type="time" class="form-control" id="<?= e($inputIdCurado) ?>" name="config[<?= e($claveCurada) ?>]" value="<?= e($valorCurado) ?>" <?= $editableCurado ? '' : 'readonly' ?>>
                                    <?php } elseif ($tipoCurado === 'integer') { ?>
                                        <input type="number" class="form-control" id="<?= e($inputIdCurado) ?>" name="config[<?= e($claveCurada) ?>]" value="<?= e($valorCurado) ?>" min="5" max="240" step="1" <?= $editableCurado ? '' : 'readonly' ?>>
                                    <?php } elseif ($tipoCurado === 'email') { ?>
                                        <input type="email" class="form-control" id="<?= e($inputIdCurado) ?>" name="config[<?= e($claveCurada) ?>]" value="<?= e($valorCurado) ?>" <?= $editableCurado ? '' : 'readonly' ?>>
                                    <?php } else { ?>
                                        <input type="text" class="form-control" id="<?= e($inputIdCurado) ?>" name="config[<?= e($claveCurada) ?>]" value="<?= e($valorCurado) ?>" <?= $editableCurado ? '' : 'readonly' ?>>
                                    <?php } ?>

                                    <?php if ($ayudaCurada !== '') { ?>
                                        <small class="form-text text-muted"><?= e($ayudaCurada) ?></small>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php foreach ($categoriasGenerales as $categoria) {
                if (isset($curadas[$categoria])) {
                    continue;
                }

                if (($configuraciones[$categoria] ?? []) === []) {
                    continue;
                } ?>
                <div class="cfg-card mb-3">
                    <div class="cfg-card-head">
                        <h5><?= e($etiquetasCategoria[$categoria] ?? ucfirst(str_replace('_', ' ', (string) $categoria))) ?></h5>
                    </div>

                    <div class="row">
                        <?php foreach ($configuraciones[$categoria] as $item) { ?>
                            <?php
                            $clave = (string) $item['clave'];
                            $valor = (string) ($item['valor'] ?? '');
                            $tipo = (string) ($item['tipo'] ?? 'string');
                            $descripcion = (string) ($item['descripcion'] ?? '');
                            $editable = (bool) ($item['editable'] ?? true);
                            $inputId = 'cfg_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $clave);
                            ?>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="<?= e($inputId) ?>"><?= e(ucfirst(str_replace('_', ' ', $clave))) ?></label>

                                    <?php if ($tipo === 'text') { ?>
                                        <textarea class="form-control" id="<?= e($inputId) ?>" name="config[<?= e($clave) ?>]" rows="2" <?= $editable ? '' : 'readonly' ?>><?= e($valor) ?></textarea>
                                    <?php } elseif ($tipo === 'boolean') { ?>
                                        <select class="form-control" id="<?= e($inputId) ?>" name="config[<?= e($clave) ?>]" <?= $editable ? '' : 'disabled' ?>>
                                            <option value="1" <?= filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? 'selected' : '' ?>>Sí</option>
                                            <option value="0" <?= !filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? 'selected' : '' ?>>No</option>
                                        </select>
                                    <?php } elseif ($tipo === 'integer') { ?>
                                        <input type="number" class="form-control" id="<?= e($inputId) ?>" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>" <?= $editable ? '' : 'readonly' ?>>
                                    <?php } elseif (str_starts_with($clave, 'hora_')) { ?>
                                        <input type="time" class="form-control" id="<?= e($inputId) ?>" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>" <?= $editable ? '' : 'readonly' ?>>
                                    <?php } elseif ($tipo === 'color' || preg_match('/^#[a-fA-F0-9]{6}$/', $valor)) { ?>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="<?= e($inputId) ?>_picker" value="<?= e($valor) ?>">
                                            <input type="text" class="form-control" id="<?= e($inputId) ?>" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>" <?= $editable ? '' : 'readonly' ?>>
                                        </div>
                                    <?php } else { ?>
                                        <input type="text" class="form-control" id="<?= e($inputId) ?>" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>" <?= $editable ? '' : 'readonly' ?>>
                                    <?php } ?>

                                    <?php if ($descripcion !== '') { ?>
                                        <small class="form-text text-muted"><?= e($descripcion) ?></small>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
        </div>

        <div class="col-lg-4">
            <div class="cfg-preview-card">
                <div class="cfg-preview-card-head">
                    <h6><i class="fas fa-eye"></i> Vista previa de la portada</h6>
                    <p>Así lo ve el visitante. Los cambios se reflejan sin guardar.</p>
                </div>

                <div class="cfg-frame-wrap" id="cfg-frame-wrap">
                    <iframe id="cfg-frame" title="Vista previa de la portada pública"></iframe>
                </div>

                <div class="cfg-preview-toolbar">
                    <small id="cfg-preview-estado"><i class="fas fa-circle-notch fa-spin"></i> Cargando...</small>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-preview-recargar">
                            <i class="fas fa-sync"></i> Recargar
                        </button>
                        <a class="btn btn-sm btn-outline-primary" href="<?= URL ?>/" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt"></i> Portada real
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="cfg-savebar">
        <small><i class="fas fa-shield-alt"></i> Los cambios se guardan en la base de datos y se reflejan en el sitio público.</small>
        <div>
            <button type="button" class="btn btn-outline-secondary" id="btn-descartar-config">Descartar</button>
            <button type="submit" class="btn btn-primary btn-lg" id="btn-guardar-config">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $ = window.jQuery;

        if (!$) {
            return;
        }

        var iconos = <?= $iconosJson ?: '{}' ?>;
        var endpoint = <?= $endpointJson ?: '""' ?>;
        var endpointImagen = <?= $endpointImagenJson ?: '""' ?>;
        var endpointImagenQuitar = <?= $endpointImagenQuitarJson ?: '""' ?>;
        var urlBase = <?= $urlBaseJson ?: '""' ?>;
        var endpointPreview = <?= json_encode(route('portal_configuracion_preview'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var $form = $('#form-configuracion');
        var $frame = $('#cfg-frame');
        var $frameWrap = $('#cfg-frame-wrap');
        var peticionPreview = null;
        var temporizadorPreview = null;
        var scrollPendiente = null;
        var seccionPendiente = null;
        var elementoResaltado = null;

        function notificar(tipo, mensaje) {
            if (typeof window.showNotification === 'function') {
                window.showNotification(tipo, mensaje);
            } else {
                window.alert(mensaje);
            }
        }

        function token() {
            return $form.find('input[name="_token"]').val() || '';
        }

        /* =====================================================================
           Vista previa en vivo de la portada (render real en el iframe)
           ===================================================================== */

        function recolectarBorrador() {
            var data = {};

            $form.find('[name^="config["]').each(function() {
                var $campo = $(this);
                var nombre = $campo.attr('name');
                var coincidencia = nombre.match(/^config\[(.+)\]$/);

                if (!coincidencia) {
                    return;
                }

                if ($campo.is(':checkbox')) {
                    var apagado = $campo.data('off');
                    data[coincidencia[1]] = $campo.prop('checked')
                        ? String($campo.val() || '1')
                        : String(apagado === undefined || apagado === null || apagado === '' ? '0' : apagado);
                } else {
                    data[coincidencia[1]] = $campo.val();
                }
            });

            data.inicio_servicios = [];

            $('#servicios-lista .cfg-servicio').each(function() {
                var titulo = $(this).find('.cfg-servicio-title').val().trim();

                if (titulo === '') {
                    return;
                }

                data.inicio_servicios.push({
                    icon: $(this).find('.cfg-servicio-icon').val(),
                    title: titulo,
                    desc: $(this).find('.cfg-servicio-desc').val().trim()
                });
            });

            data.inicio_especialidades = $('.js-esp-check:checked').map(function() {
                return parseInt($(this).val(), 10);
            }).get();

            data.inicio_medicos_destacados = $('.js-med-check:checked').map(function() {
                return parseInt($(this).val(), 10);
            }).get();

            return data;
        }

        function estadoPreview(texto, cargando) {
            $('#cfg-preview-estado').html((cargando ? '<i class="fas fa-circle-notch fa-spin"></i> ' : '') + texto);
        }

        function seccionesDeVista(seccion) {
            var mapa = {
                apariencia: ['.cl-topbar', '.cl-nav'],
                inicio: ['#inicio'],
                servicios: ['#servicios'],
                especialidades: ['#especialidades'],
                medicos: ['#medicos'],
                general: ['#contacto', '.cl-footer']
            };

            return mapa[seccion] || [];
        }

        function quitarResaltado() {
            if (elementoResaltado && elementoResaltado.style) {
                elementoResaltado.style.outline = '';
                elementoResaltado.style.outlineOffset = '';
                elementoResaltado = null;
            }
        }

        function resaltarSeccion(seccion) {
            var doc = $frame[0] ? $frame[0].contentDocument : null;

            if (!doc || !doc.body) {
                seccionPendiente = seccion;
                return;
            }

            var elemento = null;
            seccionesDeVista(seccion).some(function(selector) {
                elemento = doc.querySelector(selector);
                return !!elemento;
            });

            if (!elemento) {
                return;
            }

            quitarResaltado();

            var color = $('#cfg-color-primario').val();

            if (!/^#[0-9a-fA-F]{6}$/.test(color || '')) {
                color = '#0f8b84';
            }

            elemento.style.outline = '3px solid ' + color;
            elemento.style.outlineOffset = '6px';
            elemento.style.transition = 'outline-color 0.4s';
            elementoResaltado = elemento;

            try {
                elemento.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (error) {
                elemento.scrollIntoView();
            }

            setTimeout(function() {
                if (elementoResaltado === elemento) {
                    quitarResaltado();
                }
            }, 2600);
        }

        function cargarPreview(preservarScroll) {
            if (peticionPreview && peticionPreview.readyState !== 4) {
                peticionPreview.abort();
            }

            if (preservarScroll && $frame[0] && $frame[0].contentWindow) {
                scrollPendiente = $frame[0].contentWindow.pageYOffset || 0;
            } else {
                scrollPendiente = null;
            }

            $frameWrap.addClass('cfg-frame-cargando');
            estadoPreview('Actualizando vista previa...', true);

            peticionPreview = $.ajax({
                url: endpointPreview,
                method: 'POST',
                contentType: 'application/json',
                dataType: 'html',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token()
                },
                data: JSON.stringify({ config: recolectarBorrador() })
            }).done(function(html) {
                $frame.one('load', function() {
                    $frameWrap.removeClass('cfg-frame-cargando');
                    estadoPreview('Vista previa al día', false);

                    if (seccionPendiente !== null) {
                        var pendiente = seccionPendiente;
                        seccionPendiente = null;
                        scrollPendiente = null;
                        resaltarSeccion(pendiente);
                    } else if (scrollPendiente !== null) {
                        try {
                            $frame[0].contentWindow.scrollTo(0, scrollPendiente);
                        } catch (error) {
                            // El navegador puede bloquear el acceso al documento.
                        }
                        scrollPendiente = null;
                    }
                });

                $frame.prop('srcdoc', html);
            }).fail(function(xhr, estado) {
                if (estado === 'abort') {
                    return;
                }

                $frameWrap.removeClass('cfg-frame-cargando');
                estadoPreview('No se pudo actualizar la vista previa', false);
            });
        }

        function programarPreview() {
            clearTimeout(temporizadorPreview);
            temporizadorPreview = setTimeout(function() {
                cargarPreview(true);
            }, 400);
        }

        $form.on('input change', ':input', function() {
            if (this.id === 'btn-guardar-config' || this.id === 'btn-descartar-config' || this.id === 'cfg-hero-archivo') {
                return;
            }

            programarPreview();
        });

        $form.on('click', 'button', function() {
            if (this.id === 'btn-guardar-config' || this.id === 'btn-descartar-config') {
                return;
            }

            programarPreview();
        });

        $('.cfg-tabs a[data-toggle="tab"]').on('shown.bs.tab', function() {
            resaltarSeccion(String($(this).data('seccion') || ''));
        });

        $('#btn-preview-recargar').on('click', function() {
            cargarPreview(false);
        });

        function marcarPersonalizado() {
            $('#cfg-tema-preset').val('personalizado');
            $('.js-preset').removeClass('active').find('.fa-check-circle').remove();
            $('.js-preset[data-preset="personalizado"]').addClass('active').append('<i class="fas fa-check-circle"></i>');
        }

        $('#cfg-color-primario-picker').on('input change', function() {
            $('#cfg-color-primario').val($(this).val());
            marcarPersonalizado();
            programarPreview();
        });

        $('#cfg-color-secundario-picker').on('input change', function() {
            $('#cfg-color-secundario').val($(this).val());
            marcarPersonalizado();
            programarPreview();
        });

        $('#cfg-color-primario, #cfg-color-secundario').on('input', function() {
            var valor = $(this).val();
            var picker = $(this).attr('id') === 'cfg-color-primario' ?
                $('#cfg-color-primario-picker') :
                $('#cfg-color-secundario-picker');

            if (/^#[0-9a-fA-F]{6}$/.test(valor)) {
                picker.val(valor);
            }

            marcarPersonalizado();
            programarPreview();
        });

        $('.js-preset').on('click', function() {
            var clave = $(this).data('preset');
            var primario = $(this).data('primary');
            var secundario = $(this).data('secondary');

            $('#cfg-tema-preset').val(clave);
            $('.js-preset').removeClass('active').find('.fa-check-circle').remove();
            $(this).addClass('active').append('<i class="fas fa-check-circle"></i>');

            if (clave !== 'personalizado') {
                $('#cfg-color-primario').val(primario);
                $('#cfg-color-primario-picker').val(primario);
                $('#cfg-color-secundario').val(secundario);
                $('#cfg-color-secundario-picker').val(secundario);
            }

            programarPreview();
        });

        $('#cfg-titulo, #cfg-subtitulo').on('input', programarPreview);

        function crearSelectIcono(seleccionado) {
            var $select = $('<select class="form-control cfg-servicio-icon" aria-label="Icono">');

            Object.keys(iconos).forEach(function(icono) {
                $('<option>')
                    .val(icono)
                    .text(iconos[icono])
                    .prop('selected', icono === seleccionado)
                    .appendTo($select);
            });

            return $select;
        }

        function crearFilaServicio(datos) {
            datos = datos || {};

            var $fila = $('<div class="cfg-servicio">');
            var $acciones = $('<div class="cfg-servicio-actions">');

            $('<button type="button" class="btn btn-sm btn-outline-secondary js-servicio-subir" title="Subir"><i class="fas fa-arrow-up"></i></button>').appendTo($acciones);
            $('<button type="button" class="btn btn-sm btn-outline-secondary js-servicio-bajar" title="Bajar"><i class="fas fa-arrow-down"></i></button>').appendTo($acciones);
            $('<button type="button" class="btn btn-sm btn-outline-danger js-servicio-eliminar" title="Eliminar"><i class="fas fa-trash"></i></button>').appendTo($acciones);

            $fila.append(
                $('<span class="cfg-servicio-handle"><i class="fas fa-bars"></i></span>'),
                crearSelectIcono(datos.icon || 'fa-stethoscope'),
                $('<input type="text" class="form-control cfg-servicio-title" maxlength="90" placeholder="Título del servicio">').val(datos.title || ''),
                $('<input type="text" class="form-control cfg-servicio-desc" maxlength="300" placeholder="Descripción breve">').val(datos.desc || ''),
                $acciones
            );

            return $fila;
        }

        $('#btn-add-servicio').on('click', function() {
            $('#servicios-lista').append(crearFilaServicio({}));
        });

        $('#servicios-lista').on('click', '.js-servicio-eliminar', function() {
            $(this).closest('.cfg-servicio').remove();
        });

        $('#servicios-lista').on('click', '.js-servicio-subir', function() {
            var $fila = $(this).closest('.cfg-servicio');

            if ($fila.prev('.cfg-servicio').length) {
                $fila.prev('.cfg-servicio').before($fila);
            }
        });

        $('#servicios-lista').on('click', '.js-servicio-bajar', function() {
            var $fila = $(this).closest('.cfg-servicio');

            if ($fila.next('.cfg-servicio').length) {
                $fila.next('.cfg-servicio').after($fila);
            }
        });

        $('#btn-esp-todas').on('click', function() {
            $('.js-esp-check').prop('checked', true);
        });

        $('#btn-esp-ninguna').on('click', function() {
            $('.js-esp-check').prop('checked', false);
        });

        $('#btn-med-todos').on('click', function() {
            $('.js-med-check').prop('checked', true);
        });

        $('#btn-med-ninguno').on('click', function() {
            $('.js-med-check').prop('checked', false);
        });

        $('#btn-descartar-config').on('click', function() {
            window.location.reload();
        });

        /* =====================================================================
           Imagen de fondo del hero (subida y eliminación)
           ===================================================================== */

        function urlImagenHero(ruta) {
            if (!ruta) {
                return '';
            }

            return /^https?:\/\//i.test(ruta) ? String(ruta) : urlBase + '/' + String(ruta).replace(/^\/+/, '');
        }

        function pintarImagenHero(ruta) {
            var url = urlImagenHero(ruta);
            var $preview = $('#cfg-hero-preview');

            $preview.toggleClass('cfg-hero-preview-vacio', url === '');

            if (url === '') {
                $preview.html('<span id="cfg-hero-img"><i class="fas fa-image"></i></span>');
                $('#btn-hero-quitar').hide();
                return;
            }

            if ($preview.find('img').length) {
                $preview.find('img').attr('src', url);
            } else {
                $preview.html('<img src="' + url + '" alt="Imagen de fondo del hero" id="cfg-hero-img">');
            }

            $('#btn-hero-quitar').show();
        }

        $('#cfg-hero-archivo').on('change', function() {
            var archivo = this.files && this.files[0];
            var $archivo = $(this);

            if (!archivo) {
                return;
            }

            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(archivo.type) === -1) {
                notificar('error', 'Formato no permitido. Usa una imagen JPG, PNG o WebP.');
                $archivo.val('');
                return;
            }

            if (archivo.size > 2097152) {
                notificar('error', 'La imagen no puede superar los 2 MB.');
                $archivo.val('');
                return;
            }

            var datos = new FormData();
            datos.append('imagen', archivo);
            datos.append('_token', token());

            $('#cfg-hero-estado').html('<i class="fas fa-circle-notch fa-spin"></i> Subiendo imagen...');

            $.ajax({
                url: endpointImagen,
                method: 'POST',
                data: datos,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': token()
                }
            }).done(function(respuesta) {
                $('#cfg-hero-estado').empty();
                $archivo.val('');

                if (respuesta && respuesta.success) {
                    $('#cfg-imagen-fondo').val(respuesta.ruta || '');
                    pintarImagenHero(respuesta.ruta || '');
                    notificar('success', respuesta.message || 'Imagen del hero actualizada.');
                    programarPreview();
                } else {
                    notificar('error', (respuesta && respuesta.message) || 'No se pudo subir la imagen.');
                }
            }).fail(function(xhr) {
                $('#cfg-hero-estado').empty();
                $archivo.val('');

                var mensaje = 'No se pudo subir la imagen.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }

                notificar('error', mensaje);
            });
        });

        $('#btn-hero-quitar').on('click', function() {
            var $boton = $(this);

            $boton.prop('disabled', true);

            $.ajax({
                url: endpointImagenQuitar,
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': token()
                },
                data: {
                    _token: token()
                }
            }).done(function(respuesta) {
                $boton.prop('disabled', false);

                if (respuesta && respuesta.success) {
                    $('#cfg-imagen-fondo').val('');
                    pintarImagenHero('');
                    notificar('success', respuesta.message || 'Imagen del hero eliminada.');
                    programarPreview();
                } else {
                    notificar('error', (respuesta && respuesta.message) || 'No se pudo quitar la imagen.');
                }
            }).fail(function(xhr) {
                $boton.prop('disabled', false);

                var mensaje = 'No se pudo quitar la imagen.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }

                notificar('error', mensaje);
            });
        });

        $form.on('submit', function(event) {
            event.preventDefault();

            var data = recolectarBorrador();

            var token = $form.find('input[name="_token"]').val() || '';
            var $boton = $('#btn-guardar-config');
            var textoOriginal = $boton.html();

            $boton.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i> Guardando...');

            $.ajax({
                url: endpoint,
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                data: JSON.stringify({
                    config: data,
                    permisos_matriz: null,
                    _token: token
                })
            }).done(function(respuesta) {
                $boton.prop('disabled', false).html(textoOriginal);

                if (respuesta && respuesta.success) {
                    notificar('success', respuesta.message || 'Configuración guardada.');
                    cargarPreview(true);
                } else {
                    notificar('error', (respuesta && respuesta.message) || 'No se pudo guardar la configuración.');
                }
            }).fail(function(xhr) {
                $boton.prop('disabled', false).html(textoOriginal);

                var mensaje = 'Error de conexión.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                } else {
                    try {
                        var respuesta = JSON.parse(xhr.responseText);

                        if (respuesta.message) {
                            mensaje = respuesta.message;
                        }
                    } catch (error) {
                        // Mantener el mensaje genérico.
                    }
                }

                notificar('error', mensaje);
            });
        });

        cargarPreview(false);
    });
</script>