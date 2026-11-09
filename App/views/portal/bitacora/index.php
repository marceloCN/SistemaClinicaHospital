<?php
/**
 * Bitácora de accesos al sistema.
 *
 * Lista los accesos (10 por página) con filtros por persona y rango de
 * fechas. El botón "Más información" de cada fila carga el detalle por AJAX
 * en un modal, sin recargar la página.
 */
$accesos = $accesos ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$filtroNombre = (string) ($filtroNombre ?? '');
$filtroDesde = (string) ($filtroDesde ?? '');
$filtroHasta = (string) ($filtroHasta ?? '');

$desde = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hasta = min($total, $pagina * $porPagina);

$numerosPagina = $numerosPagina ?? [];

$etiquetas = [
    'EXITOSO' => ['Acceso válido', 'badge-success'],
    'FALLIDO' => ['Intento fallido', 'badge-danger'],
    'CERRADO' => ['Cierre de sesión', 'badge-secondary'],
    'BLOQUEADO' => ['Cuenta bloqueada', 'badge-warning'],
];

/** Construye la URL de la página conservando los filtros activos. */
$urlPagina = static function (int $nuevaPagina) use ($filtroNombre, $filtroDesde, $filtroHasta): string {
    $params = array_filter([
        'nombre' => $filtroNombre,
        'desde' => $filtroDesde,
        'hasta' => $filtroHasta,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ]);

    $base = route('portal_bitacora_acceso') ?? '/portal/dashboard/bitacora-acceso';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

/** Construye la URL del PDF y del CSV conservando los filtros activos. */
$urlPdf = static function () use ($filtroNombre, $filtroDesde, $filtroHasta): string {
    $params = array_filter([
        'nombre' => $filtroNombre,
        'desde' => $filtroDesde,
        'hasta' => $filtroHasta,
    ]);

    $base = route('portal_bitacora_acceso_pdf') ?? '/portal/dashboard/bitacora-acceso/pdf';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$urlCsv = static function () use ($filtroNombre, $filtroDesde, $filtroHasta): string {
    $params = array_filter([
        'nombre' => $filtroNombre,
        'desde' => $filtroDesde,
        'hasta' => $filtroHasta,
    ]);

    $base = route('portal_bitacora_acceso_csv') ?? '/portal/dashboard/bitacora-acceso/csv';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};
?>

<style>
    .bitacora-filtros {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }

    .bitacora-filtros .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .bitacora-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .bitacora-tabla table {
        margin-bottom: 0;
    }

    .bitacora-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }

    .bitacora-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.92rem;
    }

    .bitacora-persona {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .bitacora-persona small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.8rem;
    }

    .bitacora-fecha {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        color: #0b2a38;
    }

    .bitacora-paginacion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .bitacora-paginacion .page-link {
        color: #44545c;
        border-color: #e0e6e3;
    }

    .bitacora-paginacion .page-item.active .page-link {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
    }

    .bitacora-detalle-form .form-control,
    .bitacora-detalle-form .form-control:focus {
        background: #f7f9f8;
        border-color: #e0e6e3;
        box-shadow: none;
    }

    .bitacora-detalle-form label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    @media (max-width: 575.98px) {
        .bitacora-tabla thead {
            display: none;
        }

        .bitacora-tabla, .bitacora-tabla tbody, .bitacora-tabla tr, .bitacora-tabla td {
            display: block;
            width: 100%;
        }

        .bitacora-tabla tr {
            border-bottom: 1px solid #eef2f1;
            padding: 10px 0;
        }

        .bitacora-tabla td {
            border: 0;
            padding: 4px 16px;
        }

    .bitacora-filtros {
        padding: 16px;
    }

    .busqueda-voz-group .btn-voz {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        transition: all 0.2s ease;
    }

    .busqueda-voz-group .btn-voz:hover {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
        color: #fff;
    }

    .busqueda-voz-group .btn-voz.escuchando {
        background: #c0392b;
        border-color: #c0392b;
        color: #fff;
        animation: pulse-microphone 1.2s infinite;
    }

    @keyframes pulse-microphone {
        0% { box-shadow: 0 0 0 0 rgba(192, 57, 43, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(192, 57, 43, 0); }
        100% { box-shadow: 0 0 0 0 rgba(192, 57, 43, 0); }
    }

    /* Modal de detalle (mismo estilo que bitácora de acciones) */
    .bitacora-modal {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 18px 55px rgba(13, 38, 45, .18);
    }

    .bitacora-modal-header {
        background: linear-gradient(135deg, #0f766e, #0f8b84);
        color: #fff;
        border-bottom: 0;
        padding: 20px 24px;
    }

    .bitacora-modal .modal-body {
        background: #f7f9f8;
        padding: 22px;
    }

    .bitacora-resumen {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        background: #fff;
        border: 1px solid #e1e9e6;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 20px;
    }

    .bitacora-resumen h6 {
        margin: 8px 0 4px;
        color: #12313a;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .bitacora-resumen p {
        color: #64747a;
        margin: 0;
        font-size: .88rem;
    }

    .bitacora-seccion {
        background: #fff;
        border: 1px solid #e1e9e6;
        border-radius: 12px;
        padding: 17px;
        margin-bottom: 16px;
    }

    .bitacora-seccion-titulo {
        color: #24434b;
        font-size: .88rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .045em;
        padding-bottom: 10px;
        margin: 0 0 14px;
        border-bottom: 1px solid #edf1ef;
    }

    .bitacora-dato {
        min-width: 0;
        margin-bottom: 13px;
    }

    .bitacora-dato:last-child {
        margin-bottom: 0;
    }

    .bitacora-dato label {
        display: block;
        color: #66777d;
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .045em;
        margin-bottom: 5px;
    }

    .bitacora-valor {
        background: #f7f9f8;
        border: 1px solid #e5ebe8;
        border-radius: 8px;
        color: #17343b;
        padding: 9px 11px;
        min-height: 38px;
        font-size: .9rem;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }

    .bitacora-valor-vacio {
        color: #87949a;
        font-style: italic;
    }

    .bitacora-json {
        margin: 0;
        padding: 13px;
        max-height: 300px;
        overflow: auto;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        border: 1px solid #e1e8e5;
        border-radius: 8px;
        background: #f7f9f8;
        color: #233b42;
        font: .82rem/1.55 Consolas, Monaco, monospace;
    }

    .bitacora-json-vacio {
        color: #87949a;
        font-style: italic;
    }

    @media (max-width: 575.98px) {
        .bitacora-modal .modal-body {
            padding: 14px;
        }

        .bitacora-modal-header {
            padding: 16px;
        }

        .bitacora-resumen {
            flex-direction: column;
        }
    }
}
</style>

<div class="d-flex justify-content-between align-items-start flex-wrap mb-4" style="gap:12px;">
    <div>
        <h3 class="m-0"><i class="fas fa-clipboard-list text-secondary"></i> Bitácora de accesos</h3>
        <p class="text-muted mb-0">Todos los accesos al sistema: inicio de sesión, intentos fallidos y cierres de sesión.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= $total ?> registro(s)</span>
</div>

<form class="bitacora-filtros" method="GET" action="<?= route('portal_bitacora_acceso') ?>">
    <div class="row align-items-end">
        <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-nombre">Nombre o usuario</label>
            <div class="input-group busqueda-voz-group">
                <input type="text" class="form-control" id="filtro-nombre" name="nombre" maxlength="100"
                    value="<?= e($filtroNombre) ?>" placeholder="Ej. Juan Pérez o jperez">
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-voz" id="btn-buscar-voz"
                        title="Buscar mediante voz" aria-label="Buscar mediante voz">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-3 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-desde">Desde</label>
            <input type="date" class="form-control" id="filtro-desde" name="desde" value="<?= e($filtroDesde) ?>">
        </div>
        <div class="col-lg-2 col-md-3 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-hasta">Hasta</label>
            <input type="date" class="form-control" id="filtro-hasta" name="hasta" value="<?= e($filtroHasta) ?>">
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="d-flex flex-wrap" style="gap:8px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?= route('portal_bitacora_acceso') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-eraser"></i> Limpiar
                </a>
                <a href="<?= e($urlPdf()) ?>" class="btn btn-outline-dark" target="_blank" rel="noopener">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="<?= e($urlCsv()) ?>" class="btn btn-outline-success">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
            </div>
        </div>
    </div>
</form>

<div class="bitacora-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Fecha y hora</th>
                    <th scope="col">Persona</th>
                    <th scope="col">Tipo de acceso</th>
                    <th scope="col" class="text-right">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($accesos === []) { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay accesos que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($accesos as $acceso) { ?>
                    <?php
                    $estado = strtoupper((string) $acceso['estado']);
                    [$etiqueta, $color] = $etiquetas[$estado] ?? ['Acceso', 'badge-light'];
                    $fecha = $acceso['fecha_hora'] !== '' ? strtotime((string) $acceso['fecha_hora']) : false;
                    ?>
                    <tr>
                        <td class="bitacora-fecha">
                            <?= $fecha !== false ? date('d/m/Y H:i:s', $fecha) : e((string) $acceso['fecha_hora']) ?>
                        </td>
                        <td>
                            <div class="bitacora-persona">
                                <?= e((string) $acceso['nombre_completo']) ?>
                                <small>
                                    @<?= e((string) ($acceso['usuario_intentado'] !== '' ? $acceso['usuario_intentado'] : '—')) ?>
                                    <?php if ($acceso['rol_clave'] !== '') { ?>
                                        · <?= e((string) $acceso['rol_clave']) ?>
                                    <?php } ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= e($color) ?>"><?= e($etiqueta) ?></span>
                            <?php if ($acceso['detalle'] !== '') { ?>
                                <small class="d-block text-muted mt-1"><?= e((string) $acceso['detalle']) ?></small>
                            <?php } ?>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-primary js-ver-detalle"
                                data-id="<?= (int) $acceso['id'] ?>"
                                data-url="<?= e(route('portal_bitacora_acceso_detalle', ['id' => '{id}']) ?? '') ?>">
                                <i class="fas fa-eye"></i> Más información
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bitacora-paginacion">
    <small class="text-muted">
        Mostrando <?= $desde ?> a <?= $hasta ?> de <?= $total ?> acceso(s) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de la bitácora">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($urlPagina(max(1, $pagina - 1))) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>

                <?php foreach ($numerosPagina as $numero) { ?>
                    <?php if ($numero === '...') { ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php } else { ?>
                        <li class="page-item <?= (int) $numero === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e($urlPagina((int) $numero)) ?>"><?= (int) $numero ?></a>
                        </li>
                    <?php } ?>
                <?php } ?>

                <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($urlPagina(min($paginas, $pagina + 1))) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    <?php } ?>
</div>

<!-- Modal de detalle: se carga por AJAX, sin refrescar la página -->
<div class="modal fade" id="modal-detalle-acceso" tabindex="-1" role="dialog"
    aria-labelledby="modal-detalle-acceso-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content bitacora-modal">
            <div class="modal-header bitacora-modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="modal-detalle-acceso-title">
                        <i class="fas fa-clipboard-list mr-2" aria-hidden="true"></i>
                        Detalle del acceso
                    </h5>
                    <small class="text-white-50">Información de auditoría del acceso al sistema</small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" id="detalle-acceso-contenido" aria-live="polite">
                <div class="bitacora-cargando text-center py-5">
                    <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                    <p class="text-muted mt-3 mb-0">Selecciona un acceso para consultar su detalle.</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">
                    <i class="fas fa-times mr-1" aria-hidden="true"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        var $modal = $('#modal-detalle-acceso');
        var $contenido = $('#detalle-acceso-contenido');

        function escapar(valor) {
            return $('<div>').text(valor === null || valor === undefined ? '' : String(valor)).html();
        }

        function normalizarValor(valor) {
            return valor === null || valor === undefined || String(valor) === '' ? '—' : String(valor);
        }

        function dato(etiqueta, valor) {
            var texto = normalizarValor(valor);
            var claseVacia = texto === '—' ? ' bitacora-valor-vacio' : '';
            return '<div class="bitacora-dato">' +
                '<label>' + escapar(etiqueta) + '</label>' +
                '<div class="bitacora-valor' + claseVacia + '">' + escapar(texto) + '</div>' +
                '</div>';
        }

        function datoColumna(etiqueta, valor) {
            return '<div class="col-md-6">' + dato(etiqueta, valor) + '</div>';
        }

        function seccion(titulo, contenido) {
            return '<section class="bitacora-seccion">' +
                '<h6 class="bitacora-seccion-titulo">' + escapar(titulo) + '</h6>' +
                contenido +
                '</section>';
        }

        function claseBadgeEstado(estado) {
            estado = String(estado || '').toUpperCase();
            if (estado === 'FALLIDO' || estado === 'BLOQUEADO') {
                return 'badge-danger';
            }
            if (estado === 'CERRADO') {
                return 'badge-secondary';
            }
            return 'badge-success';
        }

        function iconoBadgeEstado(estado) {
            estado = String(estado || '').toUpperCase();
            if (estado === 'FALLIDO' || estado === 'BLOQUEADO') {
                return 'fa-times-circle';
            }
            if (estado === 'CERRADO') {
                return 'fa-sign-out-alt';
            }
            return 'fa-check-circle';
        }

        function mostrarCarga() {
            $contenido.html(
                '<div class="bitacora-cargando text-center py-5">' +
                '<span class="spinner-border text-primary" role="status" aria-hidden="true"></span>' +
                '<p class="text-muted mt-3 mb-0">Cargando el detalle del acceso...</p>' +
                '</div>'
            );
        }

        $('.js-ver-detalle').on('click', function () {
            var id = $(this).attr('data-id');
            var url = ($(this).attr('data-url') || '').replace('{id}', id);

            if (!url) {
                $contenido.html('<div class="alert alert-danger mb-0">No se encontró la dirección para consultar este detalle.</div>');
                $modal.modal('show');
                return;
            }

            mostrarCarga();
            $modal.modal('show');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).done(function (respuesta) {
                if (!respuesta || !respuesta.success || !respuesta.acceso) {
                    var mensaje = respuesta && respuesta.message ?
                        respuesta.message :
                        'No se pudo obtener la información de este acceso.';
                    $contenido.html('<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-circle mr-1"></i>' + escapar(mensaje) + '</div>');
                    return;
                }

                var a = respuesta.acceso;
                var claseBadge = claseBadgeEstado(a.estado);
                var iconoBadge = iconoBadgeEstado(a.estado);
                var estadoTexto = a.estado_etiqueta || a.estado || 'Acceso registrado';

                var html = '';
                html += '<div class="bitacora-resumen">';
                html += '<div>';
                html += '<span class="badge ' + claseBadge + ' px-2 py-2">';
                html += '<i class="fas ' + iconoBadge + ' mr-1" aria-hidden="true"></i>';
                html += escapar(estadoTexto) + '</span>';
                html += '<h6>' + escapar(a.nombre_completo || 'Usuario no identificado') + '</h6>';
                html += '<p><i class="far fa-clock mr-1" aria-hidden="true"></i>' + escapar(a.fecha_hora_legible || 'Fecha no disponible') +
                    ' &nbsp;·&nbsp; ID #' + escapar(a.id) + '</p>';
                html += '</div>';
                html += '</div>';

                html += seccion('Información general',
                    '<div class="row">' +
                    datoColumna('ID del acceso', a.id) +
                    datoColumna('Fecha y hora', a.fecha_hora_legible) +
                    datoColumna('Estado', a.estado_etiqueta) +
                    datoColumna('Resultado', a.resultado_etiqueta) +
                    '</div>' +
                    dato('Detalle', a.detalle)
                );

                html += seccion('Persona',
                    '<div class="row">' +
                    datoColumna('Nombre completo', a.nombre_completo) +
                    datoColumna('Usuario', a.usuario) +
                    datoColumna('Usuario intentado', a.usuario_intentado) +
                    datoColumna('Rol', a.rol_clave || a.rol) +
                    '</div>'
                );

                html += seccion('Origen de la solicitud',
                    '<div class="row">' +
                    datoColumna('Dirección IP', a.ip_address) +
                    datoColumna('Equipo / navegador', a.user_agent) +
                    '</div>'
                );

                $contenido.html(html);
            }).fail(function (xhr) {
                var mensaje = 'No se pudo consultar el detalle. Intenta nuevamente.';
                if (xhr && xhr.status === 403) {
                    mensaje = 'No tienes permisos para consultar el detalle de este acceso.';
                } else if (xhr && xhr.status === 404) {
                    mensaje = 'El registro solicitado no existe o ya no está disponible.';
                }
                $contenido.html('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle mr-1"></i>' + escapar(mensaje) + '</div>');
            });
        });

        // -----------------------------------------------------------------
        // Búsqueda por voz
        // -----------------------------------------------------------------
        function inicializarBusquedaPorVoz(opciones) {
            var $input = $(opciones.input);
            var $boton = $(opciones.boton);
            var $form = $(opciones.formulario);
            var $extras = opciones.extras ? $(opciones.extras) : $();

            if ($input.length === 0 || $boton.length === 0) {
                return;
            }

            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                $boton.hide();
                return;
            }

            var reconocimiento = new SpeechRecognition();

            reconocimiento.lang = 'es-BO';
            reconocimiento.continuous = false;
            reconocimiento.interimResults = false;
            reconocimiento.maxAlternatives = 1;

            function normalizarTexto(texto) {
                return String(texto)
                    .toLocaleLowerCase('es-BO')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[.,!?¿¡]/g, '')
                    .trim();
            }

            function enviarFormulario() {
                var formulario = $form[0];

                if (formulario && formulario.requestSubmit) {
                    formulario.requestSubmit();
                } else {
                    $form.trigger('submit');
                }
            }

            function marcarEscuchando(escuchando) {
                $boton
                    .prop('disabled', escuchando)
                    .toggleClass('escuchando', escuchando);

                $boton.find('i')
                    .attr('class', escuchando ? 'fas fa-circle-notch fa-spin' : 'fas fa-microphone');
            }

            $boton.on('click', function () {
                try {
                    reconocimiento.start();
                } catch (error) {
                    // El reconocimiento ya estaba iniciado.
                }
            });

            reconocimiento.onstart = function () {
                marcarEscuchando(true);
            };

            reconocimiento.onresult = function (evento) {
                var textoOriginal = String(evento.results[0][0].transcript).trim();
                var comando = normalizarTexto(textoOriginal);

                if (/^(limpiar|borrar|quitar)\s+(los\s+)?filtros$/.test(comando)) {
                    $input.val('');
                    $extras.val('');
                    enviarFormulario();
                    return;
                }

                var busqueda = textoOriginal.replace(/^(buscar|busca|filtrar por|filtra por|buscar por)\s+/i, '').trim();

                if (!busqueda) {
                    return;
                }

                $input.val(busqueda);
                enviarFormulario();
            };

            reconocimiento.onerror = function () {
                // No se muestra mensaje para mantener la alineación limpia.
            };

            reconocimiento.onend = function () {
                marcarEscuchando(false);
            };
        }

        inicializarBusquedaPorVoz({
            input: '#filtro-nombre',
            boton: '#btn-buscar-voz',
            formulario: '.bitacora-filtros',
            extras: '#filtro-desde, #filtro-hasta'
        });
    });
</script>
