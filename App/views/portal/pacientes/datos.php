<?php
/**
 * Pestaña Datos personales del dashboard de pacientes.
 *
 * Filtros (solo aquí), botón de registro en 2 pasos, tabla paginada con
 * acciones por fila (ver detalle, editar, añadir contacto) y modales
 * de detalle, edición, contacto de emergencia y wizard de registro.
 */
$pacientes = $pacientes ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$filtroNombre = (string) ($filtroNombre ?? '');
$filtroCi = (string) ($filtroCi ?? '');
$filtroSexo = (string) ($filtroSexo ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');
$filtroRegular = (string) ($filtroRegular ?? '');
$filtroDesde = (string) ($filtroDesde ?? '');
$filtroHasta = (string) ($filtroHasta ?? '');

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$numerosPagina = $numerosPagina ?? [];

/** URL de página conservando filtros (siempre en la pestaña datos). */
$urlPagina = static function (int $nuevaPagina) use ($filtroNombre, $filtroCi, $filtroSexo, $filtroEstado, $filtroRegular, $filtroDesde, $filtroHasta): string {
    $params = array_filter([
        'tab' => 'datos',
        'nombre' => $filtroNombre,
        'ci' => $filtroCi,
        'sexo' => $filtroSexo,
        'estado' => $filtroEstado,
        'regular' => $filtroRegular,
        'desde' => $filtroDesde,
        'hasta' => $filtroHasta,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn ($v) => $v !== '' && $v !== null);

    $base = route('dashboard_pacientes') ?? '/portal/dashboard/Pacientes';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$urlDatos = route('dashboard_pacientes') ?? '/portal/dashboard/Pacientes';
$urlDatos .= '?' . http_build_query(['tab' => 'datos']);

/** URLs de los reportes conservando los filtros activos (sin paginación). */
$urlPdf = route('portal_paciente_pdf') ?? '/portal/dashboard/Pacientes/pdf';
$urlCsv = route('portal_paciente_csv') ?? '/portal/dashboard/Pacientes/csv';
$paramsReporte = array_filter([
    'nombre' => $filtroNombre,
    'ci' => $filtroCi,
    'sexo' => $filtroSexo,
    'estado' => $filtroEstado,
    'regular' => $filtroRegular,
    'desde' => $filtroDesde,
    'hasta' => $filtroHasta,
], static fn ($v) => $v !== '' && $v !== null);

if ($paramsReporte !== []) {
    $urlPdf .= '?' . http_build_query($paramsReporte);
    $urlCsv .= '?' . http_build_query($paramsReporte);
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
    <button type="button" class="btn btn-primary" id="btn-nuevo-paciente">
        <i class="fas fa-user-plus mr-1"></i> Registrar nuevo paciente
    </button>
    <small class="text-muted">Los filtros solo aplican en esta pestaña.</small>
</div>

<form class="pac-filtros" method="GET" action="<?= route('dashboard_pacientes') ?>">
    <input type="hidden" name="tab" value="datos">
    <div class="row align-items-end">
        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-nombre">Nombre</label>
            <input type="text" class="form-control" id="filtro-nombre" name="nombre" maxlength="100"
                value="<?= e($filtroNombre) ?>" placeholder="Ej. Juan Pérez">
        </div>
        <div class="col-lg-2 col-md-3 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-ci">CI</label>
            <input type="text" class="form-control" id="filtro-ci" name="ci" maxlength="15"
                value="<?= e($filtroCi) ?>" placeholder="Ej. 1234567">
        </div>
        <div class="col-lg-2 col-md-3 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-sexo">Sexo</label>
            <select class="form-control" id="filtro-sexo" name="sexo">
                <option value="">Todos</option>
                <option value="MASCULINO" <?= $filtroSexo === 'MASCULINO' ? 'selected' : '' ?>>Masculino</option>
                <option value="FEMENINO" <?= $filtroSexo === 'FEMENINO' ? 'selected' : '' ?>>Femenino</option>
                <option value="OTRO" <?= $filtroSexo === 'OTRO' ? 'selected' : '' ?>>Otro</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-estado">Estado</label>
            <select class="form-control" id="filtro-estado" name="estado">
                <option value="">Todos</option>
                <option value="1" <?= $filtroEstado === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $filtroEstado === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-regular">Ficha (paciente)</label>
            <select class="form-control" id="filtro-regular" name="regular">
                <option value="">Todos</option>
                <option value="1" <?= $filtroRegular === '1' ? 'selected' : '' ?>>Regulares (con ficha)</option>
                <option value="0" <?= $filtroRegular === '0' ? 'selected' : '' ?>>Sin ficha</option>
            </select>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-desde">Registro desde</label>
            <input type="date" class="form-control" id="filtro-desde" name="desde" value="<?= e($filtroDesde) ?>">
        </div>
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-hasta">Hasta</label>
            <input type="date" class="form-control" id="filtro-hasta" name="hasta" value="<?= e($filtroHasta) ?>">
        </div>
        <div class="col-lg-6 col-md-12 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Buscar
            </button>
            <a href="<?= e($urlDatos) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-eraser"></i> Limpiar
            </a>
            <a href="<?= e($urlPdf) ?>" class="btn btn-outline-danger" target="_blank" rel="noopener">
                <i class="fas fa-file-pdf"></i> Generar PDF
            </a>
            <a href="<?= e($urlCsv) ?>" class="btn btn-outline-success">
                <i class="fas fa-file-csv"></i> Generar CSV
            </a>
        </div>
    </div>
</form>

<div class="pac-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Paciente</th>
                    <th scope="col">CI</th>
                    <th scope="col">Sexo</th>
                    <th scope="col">Edad</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pacientes === []) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay pacientes que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($pacientes as $paciente) { ?>
                    <?php
                    $idPaciente = (int) $paciente['id_persona'];
                    ?>
                    <tr>
                        <td>
                            <div class="pac-nombre">
                                <?= e((string) $paciente['nombre_completo']) ?>
                                <small><?= e((string) $paciente['correo']) ?></small>
                            </div>
                        </td>
                        <td><?= e((string) $paciente['ci']) ?></td>
                        <td><?= e($paciente['sexo'] !== '' ? (string) $paciente['sexo'] : '—') ?></td>
                        <td>
                            <?php if ($paciente['edad'] !== null) { ?>
                                <?= (int) $paciente['edad'] ?> año(s)
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary js-ver-detalle"
                                title="Ver detalle"
                                data-id="<?= $idPaciente ?>"
                                data-url="<?= e(route('portal_paciente_detalle', ['id' => '{id}']) ?? '') ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success js-editar"
                                title="Modificar datos del paciente"
                                data-id="<?= $idPaciente ?>"
                                data-url="<?= e(route('portal_paciente_detalle', ['id' => '{id}']) ?? '') ?>">
                                <i class="fas fa-user-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger js-contacto"
                                title="Añadir contacto de emergencia"
                                data-id="<?= $idPaciente ?>"
                                data-nombre="<?= e((string) $paciente['nombre_completo']) ?>">
                                <i class="fas fa-user-friends"></i>
                            </button>
                            <?php if ($paciente['estado']) { ?>
                                <button type="button" class="btn btn-sm btn-outline-warning js-cambiar-estado"
                                    title="Dar de baja"
                                    data-id="<?= $idPaciente ?>"
                                    data-estado="0"
                                    data-nombre="<?= e((string) $paciente['nombre_completo']) ?>">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-outline-info js-cambiar-estado"
                                    title="Reactivar paciente"
                                    data-id="<?= $idPaciente ?>"
                                    data-estado="1"
                                    data-nombre="<?= e((string) $paciente['nombre_completo']) ?>">
                                    <i class="fas fa-user-check"></i>
                                </button>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pac-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> paciente(s) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de pacientes">
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

<!-- Modal de detalle -->
<div class="modal fade" id="modal-detalle-paciente" tabindex="-1" role="dialog" aria-labelledby="modal-detalle-paciente-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-detalle-paciente-title">
                    <i class="fas fa-id-card mr-2"></i> Detalle del paciente
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detalle-paciente-contenido">
                    <div class="text-center py-4">
                        <i class="fas fa-circle-notch fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-3 mb-0">Cargando información del paciente...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de edición de datos personales -->
<div class="modal fade" id="modal-editar-paciente" tabindex="-1" role="dialog" aria-labelledby="modal-editar-paciente-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e8e3e;color:#fff;">
                <h5 class="modal-title" id="modal-editar-paciente-title">
                    <i class="fas fa-user-edit mr-2"></i> Modificar datos del paciente
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="editar-alerta" class="alert alert-danger pac-alerta-error"></div>
                <form id="form-editar-paciente" class="pac-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id_persona" id="editar-id" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editar-nombre">Nombre *</label>
                            <input type="text" class="form-control" id="editar-nombre" name="nombre" maxlength="100" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editar-apellido">Apellido *</label>
                            <input type="text" class="form-control" id="editar-apellido" name="apellido" maxlength="100" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editar-ci">CI *</label>
                            <input type="text" class="form-control" id="editar-ci" name="ci" maxlength="15" required>
                            <div class="pac-check info" id="editar-ci-check"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editar-sexo">Sexo</label>
                            <select class="form-control" id="editar-sexo" name="sexo">
                                <option value="">—</option>
                                <option value="MASCULINO">Masculino</option>
                                <option value="FEMENINO">Femenino</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editar-fecha_nacimiento">Fecha nacimiento</label>
                            <input type="date" class="form-control" id="editar-fecha_nacimiento" name="fecha_nacimiento">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editar-telefono">Teléfono</label>
                            <input type="text" class="form-control" id="editar-telefono" name="telefono" maxlength="15">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editar-correo">Correo personal</label>
                            <input type="email" class="form-control" id="editar-correo" name="correo" maxlength="150">
                            <div class="pac-check info" id="editar-correo-check"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editar-estado">Estado</label>
                            <select class="form-control" id="editar-estado" name="estado">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="editar-direccion">Dirección</label>
                            <input type="text" class="form-control" id="editar-direccion" name="direccion" maxlength="255">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-guardar-edicion">
                    <i class="fas fa-save mr-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de contacto de emergencia -->
<div class="modal fade" id="modal-contacto-paciente" tabindex="-1" role="dialog" aria-labelledby="modal-contacto-paciente-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#c0392b;color:#fff;">
                <h5 class="modal-title" id="modal-contacto-paciente-title">
                    <i class="fas fa-user-friends mr-2"></i> Añadir contacto de emergencia
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="contacto-paciente-nombre"></p>
                <div id="contacto-alerta" class="alert alert-danger pac-alerta-error"></div>
                <form id="form-contacto-paciente" class="pac-form" onsubmit="return false;">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id_paciente" id="contacto-id" value="">
                    <div class="mb-3">
                        <label for="contacto-nombre">Nombre del contacto *</label>
                        <input type="text" class="form-control" id="contacto-nombre" name="nombre" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="contacto-parentesco">Parentesco *</label>
                        <input type="text" class="form-control" id="contacto-parentesco" name="parentesco" maxlength="50" placeholder="Ej. Madre, esposo..." required>
                    </div>
                    <div class="mb-3">
                        <label for="contacto-telefono">Teléfono *</label>
                        <input type="text" class="form-control" id="contacto-telefono" name="telefono" maxlength="15" required>
                    </div>
                    <div class="mb-0">
                        <label for="contacto-correo">Correo <span class="text-muted" style="text-transform:none;letter-spacing:0;">(opcional)</span></label>
                        <input type="email" class="form-control" id="contacto-correo" name="correo" maxlength="150">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-guardar-contacto">
                    <i class="fas fa-save mr-1"></i> Guardar contacto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal wizard de registro (2 pasos) -->
<div class="modal fade" id="modal-nuevo-paciente" tabindex="-1" role="dialog" aria-labelledby="modal-nuevo-paciente-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-nuevo-paciente-title">
                    <i class="fas fa-user-plus mr-2"></i> Registrar nuevo paciente
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="pac-wizard-pasos">
                    <div class="pac-wizard-paso activo" id="wizard-paso-1-indicador">
                        <i class="fas fa-user mr-1"></i> Paso 1 · Datos personales
                    </div>
                    <div class="pac-wizard-paso" id="wizard-paso-2-indicador">
                        <i class="fas fa-key mr-1"></i> Paso 2 · Cuenta de acceso
                    </div>
                </div>

                <div id="wizard-alerta" class="alert alert-danger pac-alerta-error"></div>

                <!-- Paso 1 -->
                <div id="wizard-paso-1">
                    <form id="form-wizard-paso-1" class="pac-form" onsubmit="return false;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="wz-nombre">Nombre *</label>
                                <input type="text" class="form-control" id="wz-nombre" name="nombre" maxlength="100" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="wz-apellido">Apellido *</label>
                                <input type="text" class="form-control" id="wz-apellido" name="apellido" maxlength="100" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="wz-ci">CI *</label>
                                <input type="text" class="form-control" id="wz-ci" name="ci" maxlength="15" required>
                                <div class="pac-check info" id="wz-ci-check"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="wz-sexo">Sexo</label>
                                <select class="form-control" id="wz-sexo" name="sexo">
                                    <option value="">—</option>
                                    <option value="MASCULINO">Masculino</option>
                                    <option value="FEMENINO">Femenino</option>
                                    <option value="OTRO">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="wz-fecha_nacimiento">Fecha nacimiento</label>
                                <input type="date" class="form-control" id="wz-fecha_nacimiento" name="fecha_nacimiento">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="wz-telefono">Teléfono</label>
                                <input type="text" class="form-control" id="wz-telefono" name="telefono" maxlength="15">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="wz-direccion">Dirección</label>
                                <input type="text" class="form-control" id="wz-direccion" name="direccion" maxlength="255">
                            </div>
                            <div class="col-12 mb-0">
                                <label for="wz-correo">Correo personal</label>
                                <input type="email" class="form-control" id="wz-correo" name="correo" maxlength="150">
                                <div class="pac-check info" id="wz-correo-check"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Paso 2 -->
                <div id="wizard-paso-2" style="display:none;">
                    <form id="form-wizard-paso-2" class="pac-form" onsubmit="return false;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="wz-usuario">Usuario *</label>
                                <input type="text" class="form-control" id="wz-usuario" name="usuario" maxlength="50" required>
                                <div class="pac-check info" id="wz-usuario-check"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="wz-correo-cuenta">Correo de la cuenta *</label>
                                <input type="email" class="form-control" id="wz-correo-cuenta" name="correo_cuenta" maxlength="150" required>
                                <div class="pac-check info" id="wz-correo-cuenta-check"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="wz-password">Contraseña *</label>
                                <input type="password" class="form-control" id="wz-password" name="password" minlength="8" required>
                                <ul class="list-unstyled mt-2 mb-0" id="wz-password-checklist" style="font-size:0.8rem;">
                                    <li data-regla="largo" class="text-muted"><i class="far fa-square mr-1"></i> 8 caracteres o más</li>
                                    <li data-regla="mayuscula" class="text-muted"><i class="far fa-square mr-1"></i> Al menos una mayúscula</li>
                                    <li data-regla="minuscula" class="text-muted"><i class="far fa-square mr-1"></i> Al menos una minúscula</li>
                                    <li data-regla="numero" class="text-muted"><i class="far fa-square mr-1"></i> Al menos un número</li>
                                    <li data-regla="simbolo" class="text-muted"><i class="far fa-square mr-1"></i> Al menos un símbolo (!@#$...)</li>
                                </ul>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="wz-password-confirm">Confirmar contraseña *</label>
                                <input type="password" class="form-control" id="wz-password-confirm" name="password_confirm" minlength="8" required>
                                <div class="pac-check info" id="wz-password-confirm-check"></div>
                            </div>
                        </div>
                        <div class="alert alert-light border mb-0" style="font-size:0.9rem;">
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            Verifica que el usuario y el correo de la cuenta no estén en uso por otro paciente;
                            el sistema también lo valida al guardar.
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="wizard-atras" style="visibility:hidden;">
                    <i class="fas fa-arrow-left mr-1"></i> Atrás
                </button>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="wizard-siguiente">
                        Siguiente <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="wizard-guardar" style="display:none;">
                        <i class="fas fa-save mr-1"></i> Registrar paciente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var token = '<?= csrf_token() ?>';

    function escapar(texto) {
        if (typeof window.escapeHtml === 'function') {
            return window.escapeHtml(texto);
        }
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(texto === null || texto === undefined ? '' : String(texto)));
        return div.innerHTML;
    }

    function fila(etiqueta, valor, multilinea) {
        return '<div class="form-group mb-3">'
            + '<label>' + escapar(etiqueta) + '</label>'
            + '<textarea class="form-control" rows="' + (multilinea ? 3 : 1) + '" readonly>'
            + escapar(valor) + '</textarea>'
            + '</div>';
    }

    /** Campo visual tipo ficha (sin textareas): etiqueta pequeña + valor destacado. */
    function campo(etiqueta, valor, ancho) {
        return '<div class="pac-campo" style="' + (ancho ? 'grid-column:span ' + ancho + ';' : '') + '">'
            + '<span class="pac-campo-label">' + escapar(etiqueta) + '</span>'
            + '<span class="pac-campo-valor">' + escapar(valor) + '</span>'
            + '</div>';
    }

    /** Sección del modal con encabezado e ícono. */
    function seccion(titulo, icono, contenido) {
        return '<div class="pac-detalle-seccion">'
            + '<div class="pac-detalle-titulo"><i class="fas ' + icono + '"></i> ' + escapar(titulo) + '</div>'
            + contenido
            + '</div>';
    }

    function mostrarError($alerta, mensaje) {
        $alerta.text(mensaje || 'Ocurrió un error.').show();
    }

    function ocultarError($alerta) {
        $alerta.hide().text('');
    }

    function contactosHtml(lista) {
        if (!lista || !lista.length) {
            return '<p class="text-muted mb-0"><i class="fas fa-user-friends"></i> Sin contactos de emergencia registrados.</p>';
        }
        var html = '';
        for (var i = 0; i < lista.length; i++) {
            var c = lista[i];
            html += '<div class="pac-contacto">'
                + '<strong>' + escapar(c.nombre) + '</strong>'
                + '<small>'
                + escapar(c.parentesco || 'Parentesco no registrado')
                + ' · Tel: ' + escapar(c.telefono || '—')
                + (c.correo ? ' · ' + escapar(c.correo) : '')
                + '</small>'
                + '<span class="badge ' + (c.estado ? 'badge-success' : 'badge-secondary') + ' mt-1">'
                + (c.estado ? 'Activo' : 'Inactivo') + '</span>'
                + '</div>';
        }
        return html;
    }

    function cargarDetalle(id, urlBase, cb) {
        $.ajax({
            url: urlBase.replace('{id}', id),
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (respuesta) {
            if (!respuesta || !respuesta.success) {
                cb(null, (respuesta && respuesta.message) || 'No se pudo obtener el detalle.');
                return;
            }
            cb(respuesta.paciente, null);
        }).fail(function () {
            cb(null, 'No se pudo obtener el detalle del paciente.');
        });
    }

    /* ------------------------------------------------------------------
     * Ver detalle
     * ---------------------------------------------------------------- */
    var $modalDetalle = $('#modal-detalle-paciente');
    var $contenidoDetalle = $('#detalle-paciente-contenido');

    $('.js-ver-detalle').on('click', function () {
        var id = $(this).attr('data-id');
        var url = $(this).attr('data-url');

        $contenidoDetalle.html('<div class="text-center py-4"><i class="fas fa-circle-notch fa-spin fa-2x text-muted"></i>'
            + '<p class="text-muted mt-3 mb-0">Cargando información del paciente...</p></div>');
        $modalDetalle.modal('show');

        cargarDetalle(id, url, function (p, error) {
            if (error) {
                $contenidoDetalle.html('<div class="alert alert-danger mb-0">' + escapar(error) + '</div>');
                return;
            }

            var datos = '<div class="pac-campo-grid">'
                + campo('Nombre completo', p.nombre_completo, 2)
                + campo('Cédula de identidad', p.ci)
                + campo('Sexo', p.sexo)
                + campo('Fecha de nacimiento', p.fecha_nacimiento)
                + campo('Teléfono', p.telefono)
                + campo('Correo', p.correo, 2)
                + campo('Dirección', p.direccion, 2)
                + campo('Fecha de registro', p.fecha_registro)
                + campo('Estado', p.estado_etiqueta)
                + campo('Tipo', p.tiene_registro ? 'Regular (con ficha)' : 'Sin ficha en paciente')
                + (p.tiene_registro
                    ? campo('Fecha de alta', p.fecha_alta || '—') + campo('Observaciones', p.observaciones || 'Sin observaciones registradas.', 2)
                    : '')
                + '</div>';

            if (!p.tiene_registro) {
                datos += '<div class="pac-detalle-aviso"><i class="fas fa-folder-open"></i> '
                    + 'Esta persona aún no tiene ficha en la tabla <code>paciente</code>; '
                    + 'no cuenta con fecha de alta ni observaciones clínicas.</div>';
            }

            var cuenta = '<div class="pac-campo-grid">';

            if (p.tiene_cuenta) {
                cuenta += campo('Usuario', '@' + p.usuario)
                    + campo('Correo de acceso', p.cuenta_correo || '—')
                    + '<div class="pac-campo" style="grid-column:span 2;">'
                    + '<span class="pac-campo-label">Estado de la cuenta</span>'
                    + '<span class="pac-campo-valor">'
                    + (p.bloqueado
                        ? '<span class="badge badge-warning">Bloqueada</span>'
                        : '<span class="badge badge-success">Habilitada</span>')
                    + ' <span class="badge badge-light">Intentos fallidos: ' + escapar(p.intentos_fallidos) + '</span>'
                    + '</span></div>'
                    + campo('Último acceso', p.ultimo_acceso)
                    + campo('Cuenta creada', p.fecha_creacion);
            } else {
                cuenta = '<p class="text-muted mb-0"><i class="fas fa-user-slash mr-1"></i> '
                    + 'Este paciente no tiene cuenta de acceso al portal.</p>';
            }

            cuenta += '</div>';

            var contactos = contactosHtml(p.contactos);

            var html = seccion('Datos personales', 'fa-user', datos)
                + seccion('Cuenta de acceso', 'fa-key', cuenta)
                + seccion('Contactos de emergencia', 'fa-user-friends', contactos);

            $contenidoDetalle.html(html);
        });
    });

    /* ------------------------------------------------------------------
     * Dar de baja / reactivar paciente
     * ---------------------------------------------------------------- */
    $(document).on('click', '.js-cambiar-estado', function () {
        var $btn = $(this);
        var id = $btn.attr('data-id');
        var estado = $btn.attr('data-estado');
        var nombre = $btn.attr('data-nombre') || 'este paciente';
        var mensaje = estado === '1'
            ? '¿Deseas reactivar a ' + nombre + '?'
            : '¿Deseas dar de baja a ' + nombre + '? Se marcará como inactivo.';

        if (!window.confirm(mensaje)) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_paciente_cambiar_estado') ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                _token: token,
                id_persona: id,
                estado: estado
            },
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (respuesta) {
            if (respuesta && respuesta.success) {
                window.location.reload();
                return;
            }
            alert((respuesta && respuesta.message) || 'No se pudo cambiar el estado.');
            $btn.prop('disabled', false);
        }).fail(function (xhr) {
            var r = xhr && xhr.responseJSON;
            alert((r && r.message) || 'No se pudo cambiar el estado del paciente.');
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     * Editar datos del paciente
     * ---------------------------------------------------------------- */
    var $modalEditar = $('#modal-editar-paciente');
    var $alertaEditar = $('#editar-alerta');

    $('.js-editar').on('click', function () {
        var id = $(this).attr('data-id');
        var url = $(this).attr('data-url');

        ocultarError($alertaEditar);
        $modalEditar.modal('show');

        cargarDetalle(id, url, function (p, error) {
            if (error) {
                mostrarError($alertaEditar, error);
                return;
            }

            $('#editar-id').val(p.id_persona);
            $('#editar-nombre').val(p.nombre || '');
            $('#editar-apellido').val(p.apellido || '');
            $('#editar-ci').val(p.ci);
            $('#editar-sexo').val(p.sexo === '—' ? '' : p.sexo);
            $('#editar-fecha_nacimiento').val(p.fecha_nacimiento_raw || '');
            $('#editar-telefono').val(p.telefono === '—' ? '' : p.telefono);
            $('#editar-correo').val(p.correo === '—' ? '' : p.correo);
            $('#editar-direccion').val(p.direccion === '—' ? '' : p.direccion);
            $('#editar-estado').val(p.estado ? '1' : '0');
        });
    });

    $('#btn-guardar-edicion').on('click', function () {
        ocultarError($alertaEditar);

        var payload = {
            _token: token,
            id_persona: $('#editar-id').val(),
            nombre: $.trim($('#editar-nombre').val()),
            apellido: $.trim($('#editar-apellido').val()),
            ci: $.trim($('#editar-ci').val()),
            sexo: $('#editar-sexo').val(),
            fecha_nacimiento: $('#editar-fecha_nacimiento').val(),
            telefono: $.trim($('#editar-telefono').val()),
            correo: $.trim($('#editar-correo').val()),
            direccion: $.trim($('#editar-direccion').val()),
            estado: $('#editar-estado').val()
        };

        if (!payload.nombre || !payload.apellido || !payload.ci) {
            mostrarError($alertaEditar, 'Nombre, apellido y CI son obligatorios.');
            return;
        }

        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_paciente_actualizar') ?>',
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (respuesta) {
            if (respuesta && respuesta.success) {
                window.location.reload();
                return;
            }
            mostrarError($alertaEditar, (respuesta && respuesta.message) || 'No se pudo actualizar.');
        }).fail(function (xhr) {
            var r = xhr && xhr.responseJSON;
            mostrarError($alertaEditar, (r && r.message) || 'No se pudo actualizar los datos.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    // Chequeo de CI y correo en edición
    $('#editar-ci').on('blur', function () {
        var ci = $.trim($(this).val());
        var $check = $('#editar-ci-check').removeClass('ok error').addClass('info').text('Verificando...');

        if (!ci) { $check.text(''); return; }

        $.getJSON('<?= route('portal_paciente_ci_disponible') ?>', { ci: ci, excluir: $('#editar-id').val() })
            .done(function (r) {
                if (r && r.success && r.disponible) {
                    $check.removeClass('info error').addClass('ok').text('✓ CI disponible');
                } else {
                    $check.removeClass('info ok').addClass('error').text((r && r.message) || 'CI no disponible');
                }
            });
    });

    $('#editar-correo').on('blur', function () {
        var correo = $.trim($(this).val());
        var $check = $('#editar-correo-check').removeClass('ok error').addClass('info').text('');

        if (!correo) { $check.text(''); return; }
        $check.text('Verificando...');

        $.getJSON('<?= route('portal_paciente_correo_disponible') ?>', { correo: correo, excluir: $('#editar-id').val() })
            .done(function (r) {
                if (r && r.success && r.disponible) {
                    $check.removeClass('info error').addClass('ok').text('✓ Correo disponible');
                } else {
                    $check.removeClass('info ok').addClass('error').text((r && r.message) || 'Correo no disponible');
                }
            });
    });

    /* ------------------------------------------------------------------
     * Contacto de emergencia
     * ---------------------------------------------------------------- */
    var $modalContacto = $('#modal-contacto-paciente');
    var $alertaContacto = $('#contacto-alerta');

    $('.js-contacto').on('click', function () {
        ocultarError($alertaContacto);
        $('#form-contacto-paciente')[0].reset();
        $('#contacto-id').val($(this).attr('data-id'));
        $('#contacto-paciente-nombre').text('Paciente: ' + $(this).attr('data-nombre'));
        $modalContacto.modal('show');
    });

    $('#btn-guardar-contacto').on('click', function () {
        ocultarError($alertaContacto);

        var payload = {
            _token: token,
            id_paciente: $('#contacto-id').val(),
            nombre: $.trim($('#contacto-nombre').val()),
            parentesco: $.trim($('#contacto-parentesco').val()),
            telefono: $.trim($('#contacto-telefono').val()),
            correo: $.trim($('#contacto-correo').val())
        };

        if (!payload.nombre || !payload.parentesco || !payload.telefono) {
            mostrarError($alertaContacto, 'Nombre, parentesco y teléfono del contacto son obligatorios.');
            return;
        }

        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_paciente_contacto_guardar') ?>',
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (respuesta) {
            if (respuesta && respuesta.success) {
                $modalContacto.modal('hide');
                if (window.notification && typeof window.notification.success === 'function') {
                    window.notification.success(respuesta.message);
                } else {
                    alert(respuesta.message);
                }
                return;
            }
            mostrarError($alertaContacto, (respuesta && respuesta.message) || 'No se pudo guardar el contacto.');
        }).fail(function (xhr) {
            var r = xhr && xhr.responseJSON;
            mostrarError($alertaContacto, (r && r.message) || 'No se pudo guardar el contacto.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     * Wizard de registro (2 pasos)
     * ---------------------------------------------------------------- */
    var $modalNuevo = $('#modal-nuevo-paciente');
    var $alertaWizard = $('#wizard-alerta');
    var pasoActual = 1;

    function irAPaso(paso) {
        pasoActual = paso;
        $('#wizard-paso-1').toggle(paso === 1);
        $('#wizard-paso-2').toggle(paso === 2);
        $('#wizard-paso-1-indicador')
            .toggleClass('activo', paso === 1)
            .toggleClass('completado', paso > 1);
        $('#wizard-paso-2-indicador')
            .toggleClass('activo', paso === 2)
            .toggleClass('completado', false);
        $('#wizard-atras').css('visibility', paso === 2 ? 'visible' : 'hidden');
        $('#wizard-siguiente').toggle(paso === 1);
        $('#wizard-guardar').toggle(paso === 2);
        ocultarError($alertaWizard);
    }

    $('#btn-nuevo-paciente').on('click', function () {
        $('#form-wizard-paso-1')[0].reset();
        $('#form-wizard-paso-2')[0].reset();
        $('#wz-ci-check, #wz-correo-check, #wz-usuario-check, #wz-correo-cuenta-check, #wz-password-confirm-check')
            .removeClass('ok error').addClass('info').text('');
        pintarChecklist({ largo: false, mayuscula: false, minuscula: false, numero: false, simbolo: false });
        ocultarError($alertaWizard);
        irAPaso(1);
        $modalNuevo.modal('show');
    });

    $('#wizard-siguiente').on('click', function () {
        ocultarError($alertaWizard);

        var nombre = $.trim($('#wz-nombre').val());
        var apellido = $.trim($('#wz-apellido').val());
        var ci = $.trim($('#wz-ci').val());
        var correo = $.trim($('#wz-correo').val());
        var fecha = $('#wz-fecha_nacimiento').val();

        if (!nombre || !apellido || !ci) {
            mostrarError($alertaWizard, 'Nombre, apellido y CI son obligatorios para continuar.');
            return;
        }

        if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
            mostrarError($alertaWizard, 'El correo personal no es válido.');
            return;
        }

        if (fecha && !/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
            mostrarError($alertaWizard, 'La fecha de nacimiento no es válida.');
            return;
        }

        // Verificación final de CI antes de avanzar
        $.getJSON('<?= route('portal_paciente_ci_disponible') ?>', { ci: ci })
            .done(function (r) {
                if (r && r.success && r.disponible) {
                    irAPaso(2);
                } else {
                    mostrarError($alertaWizard, (r && r.message) || 'Ese CI ya está registrado.');
                }
            })
            .fail(function () {
                irAPaso(2);
            });
    });

    $('#wizard-atras').on('click', function () {
        irAPaso(1);
    });

    // Verificaciones en vivo del paso 2
    $('#wz-usuario').on('blur', function () {
        var usuario = $.trim($(this).val());
        var $check = $('#wz-usuario-check').removeClass('ok error').addClass('info').text('');

        if (!usuario) { $check.text(''); return; }
        $check.text('Verificando...');

        $.getJSON('<?= route('portal_paciente_usuario_disponible') ?>', { usuario: usuario })
            .done(function (r) {
                if (r && r.success && r.disponible) {
                    $check.removeClass('info error').addClass('ok').text('✓ Usuario disponible');
                } else {
                    $check.removeClass('info ok').addClass('error').text((r && r.message) || 'Usuario no disponible');
                }
            });
    });

    $('#wz-correo-cuenta').on('blur', function () {
        var correo = $.trim($(this).val());
        var $check = $('#wz-correo-cuenta-check').removeClass('ok error').addClass('info').text('');

        if (!correo) { $check.text(''); return; }
        $check.text('Verificando...');

        $.getJSON('<?= route('portal_paciente_correo_disponible') ?>', { correo: correo })
            .done(function (r) {
                if (r && r.success && r.disponible) {
                    $check.removeClass('info error').addClass('ok').text('✓ Correo disponible');
                } else {
                    $check.removeClass('info ok').addClass('error').text((r && r.message) || 'Correo no disponible');
                }
            });
    });

    // Verificación en vivo de la contraseña y su confirmación
    function evaluarPassword(valor) {
        return {
            largo: valor.length >= 8,
            mayuscula: /[A-Z]/.test(valor),
            minuscula: /[a-z]/.test(valor),
            numero: /\d/.test(valor),
            simbolo: /[^A-Za-z0-9]/.test(valor)
        };
    }

    function pintarChecklist(reglas) {
        $('#wz-password-checklist li').each(function () {
            var regla = $(this).attr('data-regla');
            var ok = !!reglas[regla];
            $(this)
                .toggleClass('text-muted', !ok)
                .toggleClass('text-success', ok)
                .html((ok
                    ? '<i class="fas fa-check-square mr-1"></i> '
                    : '<i class="far fa-square mr-1"></i> ')
                    + $(this).text().replace(/^[✓✗]?\s*/, ''));
        });
    }

    $('#wz-password').on('input', function () {
        var valor = $(this).val() || '';
        var reglas = evaluarPassword(valor);
        pintarChecklist(reglas);

        if (valor === '') {
            pintarChecklist({ largo: false, mayuscula: false, minuscula: false, numero: false, simbolo: false });
        }
    });

    function actualizarConfirmacion() {
        var valor = $('#wz-password').val() || '';
        var confirmacion = $('#wz-password-confirm').val() || '';
        var $check = $('#wz-password-confirm-check').removeClass('ok error').addClass('info');

        if (confirmacion === '') {
            $check.text('');
            return;
        }

        if (valor === confirmacion) {
            $check.removeClass('info error').addClass('ok').text('✓ Las contraseñas coinciden');
        } else {
            $check.removeClass('info ok').addClass('error').text('✗ Las contraseñas no coinciden');
        }
    }

    $('#wz-password-confirm').on('input', actualizarConfirmacion);
    $('#wz-password').on('input', actualizarConfirmacion);

    $('#wizard-guardar').on('click', function () {
        ocultarError($alertaWizard);

        var password = $('#wz-password').val();
        var passwordConfirm = $('#wz-password-confirm').val();
        var usuario = $.trim($('#wz-usuario').val());
        var correoCuenta = $.trim($('#wz-correo-cuenta').val());

        if (!usuario || !correoCuenta || !password || !passwordConfirm) {
            mostrarError($alertaWizard, 'Usuario, correo de cuenta y contraseñas son obligatorios.');
            return;
        }

        var reglas = evaluarPassword(password);

        if (!reglas.largo || !reglas.mayuscula || !reglas.minuscula || !reglas.numero || !reglas.simbolo) {
            mostrarError($alertaWizard, 'La contraseña no cumple todos los requisitos: 8+ caracteres, mayúscula, minúscula, número y símbolo.');
            return;
        }

        if (password !== passwordConfirm) {
            mostrarError($alertaWizard, 'Las contraseñas no coinciden.');
            return;
        }

        var payload = {
            _token: token,
            ci: $.trim($('#wz-ci').val()),
            nombre: $.trim($('#wz-nombre').val()),
            apellido: $.trim($('#wz-apellido').val()),
            telefono: $.trim($('#wz-telefono').val()),
            direccion: $.trim($('#wz-direccion').val()),
            correo: $.trim($('#wz-correo').val()),
            fecha_nacimiento: $('#wz-fecha_nacimiento').val(),
            sexo: $('#wz-sexo').val(),
            usuario: usuario,
            correo_cuenta: correoCuenta,
            password: password,
            password_confirm: passwordConfirm
        };

        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_paciente_registrar') ?>',
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (respuesta) {
            if (respuesta && respuesta.success) {
                window.location.href = respuesta.redirect || window.location.href;
                return;
            }
            mostrarError($alertaWizard, (respuesta && respuesta.message) || 'No se pudo registrar el paciente.');
        }).fail(function (xhr) {
            var r = xhr && xhr.responseJSON;
            mostrarError($alertaWizard, (r && r.message) || 'No se pudo registrar el paciente. Revisa los datos.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
