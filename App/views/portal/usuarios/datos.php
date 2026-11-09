<?php
/**
 * Pestaña Empleados del dashboard de usuarios.
 *
 * Filtros (solo aquí), botón de registro en 2 pasos, tabla paginada con
 * acciones por fila (ver detalle, editar, dar de baja / reactivar) y
 * modales de detalle, edición y wizard de registro con rol y ficha de
 * médico condicional.
 */
$personal = $personal ?? [];
$total = (int) ($total ?? 0);
$pagina = (int) ($pagina ?? 1);
$paginas = (int) ($paginas ?? 1);
$porPagina = (int) ($porPagina ?? 10);
$roles = $roles ?? [];
$idActual = (int) ($idActual ?? 0);
$filtroNombre = (string) ($filtroNombre ?? '');
$filtroCi = (string) ($filtroCi ?? '');
$filtroRol = (string) ($filtroRol ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');
$filtroDesde = (string) ($filtroDesde ?? '');
$filtroHasta = (string) ($filtroHasta ?? '');

$desdeLista = $total > 0 ? (int) (($pagina - 1) * $porPagina) + 1 : 0;
$hastaLista = min($total, $pagina * $porPagina);

$numerosPagina = $numerosPagina ?? [];

/** URL de página conservando filtros (siempre en la pestaña datos). */
$urlPagina = static function (int $nuevaPagina) use ($filtroNombre, $filtroCi, $filtroRol, $filtroEstado, $filtroDesde, $filtroHasta): string {
    $params = array_filter([
        'tab' => 'datos',
        'nombre' => $filtroNombre,
        'ci' => $filtroCi,
        'rol' => $filtroRol,
        'estado' => $filtroEstado,
        'desde' => $filtroDesde,
        'hasta' => $filtroHasta,
        'page' => $nuevaPagina > 1 ? $nuevaPagina : null,
    ], static fn ($v) => $v !== '' && $v !== null);

    $base = route('dashboard_usuarios') ?? '/portal/dashboard/Usuarios';

    return $params === [] ? $base : $base . '?' . http_build_query($params);
};

$urlDatos = route('dashboard_usuarios') ?? '/portal/dashboard/Usuarios';
$urlDatos .= '?' . http_build_query(['tab' => 'datos']);

/** URLs de los reportes conservando los filtros activos (sin paginación). */
$urlPdf = route('portal_usuario_pdf') ?? '/portal/dashboard/Usuarios/pdf';
$urlCsv = route('portal_usuario_csv') ?? '/portal/dashboard/Usuarios/csv';
$paramsReporte = array_filter([
    'nombre' => $filtroNombre,
    'ci' => $filtroCi,
    'rol' => $filtroRol,
    'estado' => $filtroEstado,
    'desde' => $filtroDesde,
    'hasta' => $filtroHasta,
], static fn ($v) => $v !== '' && $v !== null);

if ($paramsReporte !== []) {
    $urlPdf .= '?' . http_build_query($paramsReporte);
    $urlCsv .= '?' . http_build_query($paramsReporte);
}

/** Catálogo de roles para filtros y displays (valor = clave). */
$opcionesRoles = [];

/** Opciones con ID numérico para el wizard y la edición (id_rol). */
$opcionesRolesId = [];
$rolPorId = [];

foreach ($roles as $rol) {
    $clave = (string) $rol['clave'];
    $id = (int) $rol['id'];

    $opcionesRoles[$clave] = (string) $rol['nombre'];
    $opcionesRolesId[$id] = (string) $rol['nombre'];
    $rolPorId[$id] = $clave;
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px;">
    <button type="button" class="btn btn-primary" id="btn-nuevo-empleado">
        <i class="fas fa-user-plus mr-1"></i> Registrar nuevo empleado
    </button>
    <small class="text-muted">Los filtros solo aplican en esta pestaña.</small>
</div>

<form class="us-filtros" method="GET" action="<?= route('dashboard_usuarios') ?>">
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
        <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
            <label class="form-label" for="filtro-rol">Rol</label>
            <select class="form-control" id="filtro-rol" name="rol">
                <option value="">Todos</option>
                <?php foreach ($opcionesRoles as $clave => $nombreRol) { ?>
                    <option value="<?= e($clave) ?>" <?= $filtroRol === $clave ? 'selected' : '' ?>>
                        <?= e($nombreRol) ?>
                    </option>
                <?php } ?>
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
            <label class="form-label" for="filtro-desde">Registro desde</label>
            <input type="date" class="form-control" id="filtro-desde" name="desde" value="<?= e($filtroDesde) ?>">
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
            <label class="form-label" for="filtro-hasta">Hasta</label>
            <input type="date" class="form-control" id="filtro-hasta" name="hasta" value="<?= e($filtroHasta) ?>">
        </div>
        <div class="col-lg-9 col-md-12 d-flex align-items-end" style="gap:8px;flex-wrap:wrap;">
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

<div class="us-tabla">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Empleado</th>
                    <th scope="col">CI</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($personal === []) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No hay empleados que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                <?php } ?>

                <?php foreach ($personal as $empleado) { ?>
                    <?php
                    $idEmpleado = (int) $empleado['id_persona'];
                    $claveRol = (string) $empleado['rol_clave'];
                    $badgeRol = $claveRol === 'ADMIN'
                        ? 'badge-primary'
                        : ($claveRol === 'MEDICO' ? 'badge-info' : 'badge-success');
                    ?>
                    <tr>
                        <td>
                            <div class="us-nombre">
                                <?= e((string) $empleado['nombre_completo']) ?>
                                <small>
                                    <?php if ($empleado['tiene_cuenta']) { ?>
                                        @<?= e((string) $empleado['usuario']) ?>
                                    <?php } else { ?>
                                        <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Sin cuenta de acceso</span>
                                    <?php } ?>
                                </small>
                            </div>
                        </td>
                        <td><?= e((string) $empleado['ci']) ?></td>
                        <td>
                            <span class="badge <?= $badgeRol ?>"><?= e((string) $empleado['rol_nombre']) ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $empleado['estado'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $empleado['estado'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary js-ver-detalle"
                                title="Ver detalle"
                                data-id="<?= $idEmpleado ?>"
                                data-url="<?= e(route('portal_usuario_detalle', ['id' => '{id}']) ?? '') ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($idEmpleado === $idActual) { ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                    title="No puedes modificar tu propia cuenta desde aquí; usa la sección Perfil.">
                                    <i class="fas fa-user-edit"></i>
                                </button>
                            <?php } elseif (!$empleado['estado']) { ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                    title="Empleado dado de baja: reactívalo para poder modificar sus datos.">
                                    <i class="fas fa-user-edit"></i>
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-outline-success js-editar"
                                    title="Modificar datos del empleado"
                                    data-id="<?= $idEmpleado ?>"
                                    data-url="<?= e(route('portal_usuario_detalle', ['id' => '{id}']) ?? '') ?>">
                                    <i class="fas fa-user-edit"></i>
                                </button>
                            <?php } ?>
                            <?php if ($idEmpleado === $idActual) { ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                    title="No puedes darte de baja a ti mismo; pide a otro administrador que lo haga.">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            <?php } elseif ($empleado['estado']) { ?>
                                <button type="button" class="btn btn-sm btn-outline-warning js-cambiar-estado"
                                    title="Dar de baja"
                                    data-id="<?= $idEmpleado ?>"
                                    data-estado="0"
                                    data-nombre="<?= e((string) $empleado['nombre_completo']) ?>">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-sm btn-outline-info js-cambiar-estado"
                                    title="Reactivar empleado"
                                    data-id="<?= $idEmpleado ?>"
                                    data-estado="1"
                                    data-nombre="<?= e((string) $empleado['nombre_completo']) ?>">
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

<div class="us-paginacion">
    <small class="text-muted">
        Mostrando <?= $desdeLista ?> a <?= $hastaLista ?> de <?= $total ?> empleado(s) · <?= $porPagina ?> por página
    </small>

    <?php if ($paginas > 1) { ?>
        <nav aria-label="Paginación de empleados">
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
<div class="modal fade" id="modal-detalle-empleado" tabindex="-1" role="dialog" aria-labelledby="modal-detalle-empleado-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-detalle-empleado-title">
                    <i class="fas fa-id-badge mr-2"></i> Detalle del empleado
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detalle-empleado-contenido">
                    <div class="text-center py-4">
                        <i class="fas fa-circle-notch fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-3 mb-0">Cargando información del empleado...</p>
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
<div class="modal fade" id="modal-editar-empleado" tabindex="-1" role="dialog" aria-labelledby="modal-editar-empleado-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e8e3e;color:#fff;">
                <h5 class="modal-title" id="modal-editar-empleado-title">
                    <i class="fas fa-user-edit mr-2"></i> Modificar datos del empleado
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="editar-alerta" class="alert alert-danger us-alerta-error"></div>
                <form id="form-editar-empleado" class="us-form" onsubmit="return false;">
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
                            <label for="editar-ci">CI <span class="text-muted" style="text-transform:none;letter-spacing:0;">(no editable)</span></label>
                            <input type="text" class="form-control" id="editar-ci" name="ci" maxlength="15" readonly
                                style="background:#eef2f1;color:#6b7c83;cursor:not-allowed;">
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
                            <div class="us-check info" id="editar-correo-check"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editar-id_rol">Rol *</label>
                            <select class="form-control" id="editar-id_rol" name="id_rol" required>
                                <?php foreach ($opcionesRolesId as $idRol => $nombreRol) { ?>
                                    <option value="<?= $idRol ?>"><?= e($nombreRol) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="editar-direccion">Dirección</label>
                            <input type="text" class="form-control" id="editar-direccion" name="direccion" maxlength="255">
                        </div>
                    </div>

                    <!-- Ficha médica: visible solo mientras el rol elegido sea Médico -->
                    <div class="us-seccion-medico" id="editar-seccion-medico" style="display:none;">
                        <h6><i class="fas fa-stethoscope mr-1"></i> Ficha de médico</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editar-nro_colegiado">Nro. de colegiado *</label>
                                <input type="text" class="form-control" id="editar-nro_colegiado" name="nro_colegiado" maxlength="50">
                                <div class="us-check info" id="editar-nro_colegiado-check"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editar-fecha_ingreso">Fecha de ingreso *</label>
                                <input type="date" class="form-control" id="editar-fecha_ingreso" name="fecha_ingreso">
                            </div>
                            <div class="col-12 mb-0">
                                <div class="us-logros">
                                    <label for="editar-logro-input">
                                        Logros y trayectoria
                                        <span class="text-muted" style="text-transform:none;letter-spacing:0;">(uno por línea: especialidades, premios, publicaciones...)</span>
                                    </label>
                                    <div class="us-logros-add">
                                        <input type="text" class="form-control" id="editar-logro-input"
                                            placeholder="Ej. Especialista en cardiología invasiva (2021)" maxlength="160">
                                        <button type="button" class="btn btn-sm btn-primary" id="editar-logro-agregar">
                                            <i class="fas fa-plus mr-1"></i> Añadir
                                        </button>
                                    </div>
                                    <ul class="us-logros-lista" id="editar-logros-lista"></ul>
                                    <textarea name="biografia" id="editar-biografia" class="d-none" maxlength="4000"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0" style="font-size:0.9rem;">
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        El CI y el estado no se modifican aquí; la contraseña y el usuario de acceso se
                        administran desde la configuración de la cuenta.
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

<!-- Modal wizard de registro (2 pasos) -->
<div class="modal fade" id="modal-nuevo-empleado" tabindex="-1" role="dialog" aria-labelledby="modal-nuevo-empleado-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f8b84;color:#fff;">
                <h5 class="modal-title" id="modal-nuevo-empleado-title">
                    <i class="fas fa-user-plus mr-2"></i> Registrar nuevo empleado
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="us-wizard-pasos">
                    <div class="us-wizard-paso activo" id="wizard-paso-1-indicador">
                        <i class="fas fa-user mr-1"></i> Paso 1 · Datos personales
                    </div>
                    <div class="us-wizard-paso" id="wizard-paso-2-indicador">
                        <i class="fas fa-id-badge mr-1"></i> Paso 2 · Rol y cuenta
                    </div>
                </div>

                <div id="wizard-alerta" class="alert alert-danger us-alerta-error"></div>

                <!-- Paso 1 -->
                <div id="wizard-paso-1">
                    <form id="form-wizard-paso-1" class="us-form" onsubmit="return false;">
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
                                <div class="us-check info" id="wz-ci-check"></div>
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
                                <div class="us-check info" id="wz-correo-check"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Paso 2 -->
                <div id="wizard-paso-2" style="display:none;">
                    <form id="form-wizard-paso-2" class="us-form" onsubmit="return false;">
                        <div class="us-grupo">
                            <h6 class="us-grupo-titulo"><i class="fas fa-id-badge mr-1"></i> Rol y cuenta de acceso</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="wz-id_rol">Rol en el sistema *</label>
                                    <select class="form-control" id="wz-id_rol" name="id_rol" required>
                                        <option value="">Selecciona un rol...</option>
                                        <?php foreach ($opcionesRolesId as $idRol => $nombreRol) { ?>
                                            <option value="<?= $idRol ?>"><?= e($nombreRol) ?></option>
                                        <?php } ?>
                                    </select>
                                    <div class="us-check info" id="wz-rol-check">Define los permisos que tendrá la cuenta.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="wz-usuario">Usuario *</label>
                                    <input type="text" class="form-control" id="wz-usuario" name="usuario" maxlength="50" required>
                                    <div class="us-check info" id="wz-usuario-check"></div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="wz-correo-cuenta">Correo de la cuenta *</label>
                                    <input type="email" class="form-control" id="wz-correo-cuenta" name="correo_cuenta" maxlength="150" required>
                                    <div class="us-check info" id="wz-correo-cuenta-check"></div>
                                </div>
                            </div>
                        </div>

                        <div class="us-grupo">
                            <h6 class="us-grupo-titulo"><i class="fas fa-lock mr-1"></i> Contraseña de acceso</h6>
                            <div class="row">
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
                                    <div class="us-check info" id="wz-password-confirm-check"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Ficha de médico: visible solo si el rol elegido es Médico -->
                        <div class="us-seccion-medico" id="wz-seccion-medico" style="display:none;">
                            <h6><i class="fas fa-stethoscope mr-1"></i> Ficha de médico (obligatoria para el rol Médico)</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="wz-nro_colegiado">Nro. de colegiado *</label>
                                    <input type="text" class="form-control" id="wz-nro_colegiado" name="nro_colegiado" maxlength="50">
                                    <div class="us-check info" id="wz-nro_colegiado-check"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="wz-fecha_ingreso">Fecha de ingreso *</label>
                                    <input type="date" class="form-control" id="wz-fecha_ingreso" name="fecha_ingreso">
                                </div>
                                <div class="col-12 mb-0">
                                    <div class="us-logros">
                                        <label for="wz-logro-input">
                                            Logros y trayectoria
                                            <span class="text-muted" style="text-transform:none;letter-spacing:0;">(opcional: especialidades, premios, publicaciones...)</span>
                                        </label>
                                        <div class="us-logros-add">
                                            <input type="text" class="form-control" id="wz-logro-input"
                                                placeholder="Ej. Especialista en cardiología invasiva (2021)" maxlength="160">
                                            <button type="button" class="btn btn-sm btn-primary" id="wz-logro-agregar">
                                                <i class="fas fa-plus mr-1"></i> Añadir
                                            </button>
                                        </div>
                                        <ul class="us-logros-lista" id="wz-logros-lista"></ul>
                                        <textarea name="biografia" id="wz-biografia" class="d-none" maxlength="4000"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border mb-0" style="font-size:0.9rem;">
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            Verifica que el usuario y el correo de la cuenta no estén en uso por otra persona;
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
                        <i class="fas fa-save mr-1"></i> Registrar empleado
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

    /** Catálogo de roles: clave => nombre (para el detalle y el rol del wizard). */
    var ROLES = <?= json_encode($opcionesRoles, JSON_UNESCAPED_UNICODE) ?>;

    /** Relación ID numérico => clave de rol (los formularios envían el ID). */
    var ROL_POR_ID = <?= json_encode($rolPorId, JSON_UNESCAPED_UNICODE) ?>;

    function claveDeRol(valor) {
        if (!valor) {
            return '';
        }
        if (ROL_POR_ID.hasOwnProperty(String(valor))) {
            return String(ROL_POR_ID[String(valor)]);
        }
        return String(valor);
    }

    /**
     * Componente de "logros" para la biografía del médico: añade ítems
     * uno a uno y los sincroniza en un textarea oculto como lista con viñetas.
     */
    function crearLogros(inputId, listaId, textareaId, btnAgregarId) {
        var $input = $(inputId);
        var $lista = $(listaId);
        var $texto = $(textareaId);
        var items = [];

        function sincronizar() {
            $texto.val(items.map(function (item) { return '• ' + item; }).join('\n'));
        }

        function render() {
            if (!items.length) {
                $lista.html('<li class="us-logros-vacio"><i class="far fa-lightbulb mr-1"></i> '
                    + 'Aún no hay logros. Añade especialidades, premios, publicaciones o años de experiencia.</li>');
                return;
            }

            var html = '';

            for (var i = 0; i < items.length; i++) {
                html += '<li class="us-logros-item">'
                    + '<i class="fas fa-award"></i>'
                    + '<span>' + escapar(items[i]) + '</span>'
                    + '<button type="button" class="us-logros-quitar" data-i="' + i + '" title="Quitar logro">'
                    + '<i class="fas fa-times"></i></button>'
                    + '</li>';
            }

            $lista.html(html);
        }

        function agregar() {
            var texto = $.trim($input.val());

            if (!texto) {
                return false;
            }

            if (items.length >= 40) {
                alert('Puedes añadir hasta 40 logros.');
                return false;
            }

            items.push(texto);
            $input.val('').focus();
            sincronizar();
            render();

            return true;
        }

        $(document).on('click', listaId + ' .us-logros-quitar', function () {
            var indice = parseInt($(this).attr('data-i'), 10);

            if (!isNaN(indice)) {
                items.splice(indice, 1);
                sincronizar();
                render();
            }
        });

        $(document).on('click', btnAgregarId, agregar);

        $input.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                agregar();
            }
        });

        return {
            /** Carga la biografía existente (viñetas o líneas simples). */
            cargar: function (texto) {
                items = String(texto || '')
                    .split(/\r?\n/)
                    .map(function (linea) {
                        return linea.replace(/^\s*[•\-\*·o]?\s*/, '').trim();
                    })
                    .filter(function (linea) { return linea !== ''; });
                sincronizar();
                render();
            },
            limpiar: function () {
                items = [];
                $input.val('');
                sincronizar();
                render();
            },
            obtener: function () {
                return items.slice();
            }
        };
    }

    var logrosWizard = crearLogros('#wz-logro-input', '#wz-logros-lista', '#wz-biografia', '#wz-logro-agregar');
    var logrosEdicion = crearLogros('#editar-logro-input', '#editar-logros-lista', '#editar-biografia', '#editar-logro-agregar');

    function escapar(texto) {
        if (typeof window.escapeHtml === 'function') {
            return window.escapeHtml(texto);
        }
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(texto === null || texto === undefined ? '' : String(texto)));
        return div.innerHTML;
    }

    /** Campo visual tipo ficha: etiqueta pequeña + valor destacado. */
    function campo(etiqueta, valor, ancho) {
        return '<div class="us-campo" style="' + (ancho ? 'grid-column:span ' + ancho + ';' : '') + '">'
            + '<span class="us-campo-label">' + escapar(etiqueta) + '</span>'
            + '<span class="us-campo-valor">' + escapar(valor) + '</span>'
            + '</div>';
    }

    /** Sección del modal con encabezado e ícono. */
    function seccion(titulo, icono, contenido) {
        return '<div class="us-detalle-seccion">'
            + '<div class="us-detalle-titulo"><i class="fas ' + icono + '"></i> ' + escapar(titulo) + '</div>'
            + contenido
            + '</div>';
    }

    function mostrarError($alerta, mensaje) {
        $alerta.text(mensaje || 'Ocurrió un error.').show();
    }

    function ocultarError($alerta) {
        $alerta.hide().text('');
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
            cb(respuesta.empleado, null);
        }).fail(function () {
            cb(null, 'No se pudo obtener el detalle del empleado.');
        });
    }

    /* ------------------------------------------------------------------
     * Ver detalle
     * ---------------------------------------------------------------- */
    var $modalDetalle = $('#modal-detalle-empleado');
    var $contenidoDetalle = $('#detalle-empleado-contenido');

    $('.js-ver-detalle').on('click', function () {
        var id = $(this).attr('data-id');
        var url = $(this).attr('data-url');

        $contenidoDetalle.html('<div class="text-center py-4"><i class="fas fa-circle-notch fa-spin fa-2x text-muted"></i>'
            + '<p class="text-muted mt-3 mb-0">Cargando información del empleado...</p></div>');
        $modalDetalle.modal('show');

        cargarDetalle(id, url, function (p, error) {
            if (error) {
                $contenidoDetalle.html('<div class="alert alert-danger mb-0">' + escapar(error) + '</div>');
                return;
            }

            var datos = '<div class="us-campo-grid">'
                + campo('Nombre completo', p.nombre_completo, 2)
                + campo('Cédula de identidad', p.ci)
                + campo('Sexo', p.sexo)
                + campo('Fecha de nacimiento', p.fecha_nacimiento)
                + campo('Edad', p.edad)
                + campo('Teléfono', p.telefono)
                + campo('Correo', p.correo, 2)
                + campo('Dirección', p.direccion, 2)
                + campo('Fecha de registro', p.fecha_registro)
                + campo('Estado', p.estado_etiqueta)
                + '</div>';

            var rol = '<div class="us-campo-grid">'
                + campo('Rol asignado', p.rol_nombre + ' (' + p.rol_clave + ')', 2)
                + '<div class="us-campo" style="grid-column:span 2;">'
                + '<span class="us-campo-label">Permisos del rol</span>'
                + '<span class="us-campo-valor">Los permisos se administran en el módulo '
                + '<em>Permisos</em> del panel lateral.</span>'
                + '</div>'
                + '</div>';

            var cuenta = '<div class="us-campo-grid">';

            if (p.tiene_cuenta) {
                cuenta += campo('Usuario', '@' + p.usuario)
                    + campo('Correo de acceso', p.cuenta_correo || '—')
                    + '<div class="us-campo" style="grid-column:span 2;">'
                    + '<span class="us-campo-label">Estado de la cuenta</span>'
                    + '<span class="us-campo-valor">'
                    + (p.bloqueado
                        ? '<span class="badge badge-warning">Bloqueada</span>'
                        : '<span class="badge badge-success">Habilitada</span>')
                    + ' <span class="badge badge-light">Intentos fallidos: ' + escapar(p.intentos_fallidos) + '</span>'
                    + '</span></div>'
                    + campo('Último acceso', p.ultimo_acceso)
                    + campo('Cuenta creada', p.fecha_creacion);
            } else {
                cuenta = '<p class="text-muted mb-0"><i class="fas fa-user-slash mr-1"></i> '
                    + 'Este empleado no tiene cuenta de acceso al portal.</p>';
            }

            cuenta += '</div>';

            var html = seccion('Datos personales', 'fa-user', datos)
                + seccion('Rol en el sistema', 'fa-user-shield', rol)
                + seccion('Cuenta de acceso', 'fa-key', cuenta);

            if (p.rol_clave === 'MEDICO') {
                var medico = '<div class="us-campo-grid">'
                    + campo('Nro. de colegiado', p.nro_colegiado || '—')
                    + campo('Fecha de ingreso', p.fecha_ingreso_legible || '—')
                    + campo('Estado de la ficha', p.medico_estado ? 'Activa' : 'Inactiva', 2)
                    + campo('Biografía', p.biografia || 'Sin biografía registrada.', 2)
                    + '</div>';

                if (!p.es_medico) {
                    medico += '<div class="us-detalle-aviso"><i class="fas fa-stethoscope"></i> '
                        + 'Este empleado tiene el rol Médico pero aún no tiene ficha completa '
                        + 'en la tabla <code>medico</code>.</div>';
                }

                html += seccion('Ficha de médico', 'fa-stethoscope', medico);
            }

            $contenidoDetalle.html(html);
        });
    });

    /* ------------------------------------------------------------------
     * Dar de baja / reactivar empleado
     * ---------------------------------------------------------------- */
    $(document).on('click', '.js-cambiar-estado', function () {
        var $btn = $(this);
        var id = $btn.attr('data-id');
        var estado = $btn.attr('data-estado');
        var nombre = $btn.attr('data-nombre') || 'este empleado';
        var mensaje = estado === '1'
            ? '¿Deseas reactivar a ' + nombre + '?'
            : '¿Deseas dar de baja a ' + nombre + '? Se marcará como inactivo.';

        if (!window.confirm(mensaje)) {
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_usuario_cambiar_estado') ?>',
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
            alert((r && r.message) || 'No se pudo cambiar el estado del empleado.');
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     * Editar datos del empleado
     * ---------------------------------------------------------------- */
    var $modalEditar = $('#modal-editar-empleado');
    var $alertaEditar = $('#editar-alerta');

    $('.js-editar').on('click', function () {
        var id = $(this).attr('data-id');
        var url = $(this).attr('data-url');

        ocultarError($alertaEditar);
        logrosEdicion.limpiar();
        $('#editar-seccion-medico').hide();
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
            $('#editar-id_rol').val(String(p.id_rol || ''));
            $('#editar-nro_colegiado').val(p.nro_colegiado || '');
            $('#editar-fecha_ingreso').val(p.fecha_ingreso || '');
            logrosEdicion.cargar(p.biografia || '');
            $('#editar-nro_colegiado-check')
                .removeClass('ok error').addClass('info')
                .text(p.es_medico ? 'Ficha médica existente en el sistema.' : '');
            actualizarSeccionMedicoEdicion();
        });
    });

    /** Muestra u oculta la ficha médica según el rol elegido al editar. */
    function actualizarSeccionMedicoEdicion() {
        var esMedico = claveDeRol($('#editar-id_rol').val()) === 'MEDICO';
        $('#editar-seccion-medico').toggle(esMedico);

        if (!esMedico) {
            $('#editar-nro_colegiado, #editar-fecha_ingreso').val('');
            $('#editar-nro_colegiado-check').removeClass('ok error').addClass('info').text('');
            logrosEdicion.limpiar();
        }
    }

    $('#editar-id_rol').on('change', actualizarSeccionMedicoEdicion);

    $('#btn-guardar-edicion').on('click', function () {
        ocultarError($alertaEditar);

        var idRol = $('#editar-id_rol').val();
        var esMedico = claveDeRol(idRol) === 'MEDICO';
        var nroColegiado = $.trim($('#editar-nro_colegiado').val());
        var fechaIngreso = $('#editar-fecha_ingreso').val();

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
            id_rol: idRol
        };

        if (!payload.nombre || !payload.apellido || !payload.ci) {
            mostrarError($alertaEditar, 'Nombre, apellido y CI son obligatorios.');
            return;
        }

        if (!payload.id_rol) {
            mostrarError($alertaEditar, 'Selecciona un rol para el empleado.');
            return;
        }

        if (esMedico) {
            payload.nro_colegiado = nroColegiado;
            payload.fecha_ingreso = fechaIngreso;
            payload.biografia = logrosEdicion.obtener().map(function (i) { return '• ' + i; }).join('\n');

            if (!payload.nro_colegiado) {
                mostrarError($alertaEditar, 'El número de colegiado es obligatorio para el rol Médico.');
                return;
            }

            if (!payload.fecha_ingreso) {
                mostrarError($alertaEditar, 'La fecha de ingreso es obligatoria para el rol Médico.');
                return;
            }
        }

        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_usuario_actualizar') ?>',
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
            mostrarError($alertaEditar, (r && r.message) || 'No se pudieron actualizar los datos.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* ------------------------------------------------------------------
     * Chequeos en vivo de disponibilidad (usuario, CI y correos)
     * ---------------------------------------------------------------- */
    function verificarDisponible(url, params, $check, textoOk) {
        $check.removeClass('ok error').addClass('info').text('Verificando...');

        $.getJSON(url, params)
            .done(function (r) {
                if (r && r.success && r.disponible) {
                    $check.removeClass('info error').addClass('ok').text('✓ ' + textoOk);
                } else {
                    $check.removeClass('info ok').addClass('error')
                        .text((r && r.message) || 'No disponible.');
                }
            })
            .fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                $check.removeClass('info ok').addClass('error')
                    .text((r && r.message) || 'No se pudo verificar. Vuelve al campo y sal para reintentar.');
            });
    }

    // Chequeo de correo en edición (el CI es de solo lectura)
    $('#editar-correo').on('blur', function () {
        var correo = $.trim($(this).val());
        var $check = $('#editar-correo-check');

        if (!correo) { $check.removeClass('ok error').addClass('info').text(''); return; }

        verificarDisponible('<?= route('portal_usuario_correo_disponible') ?>',
            { correo: correo, excluir: $('#editar-id').val() }, $check, 'Correo disponible');
    });

    /* ------------------------------------------------------------------
     * Wizard de registro (2 pasos)
     * ---------------------------------------------------------------- */
    var $modalNuevo = $('#modal-nuevo-empleado');
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

    /** Muestra u oculta la ficha médica según el rol elegido en el paso 2. */
    function actualizarSeccionMedico() {
        var esMedico = claveDeRol($('#wz-id_rol').val()) === 'MEDICO';
        $('#wz-seccion-medico').toggle(esMedico);

        if (!esMedico) {
            $('#wz-nro_colegiado, #wz-fecha_ingreso').val('');
            $('#wz-nro_colegiado-check').removeClass('ok error').addClass('info').text('');
            logrosWizard.limpiar();
        }
    }

    $('#wz-id_rol').on('change', function () {
        actualizarSeccionMedico();
        var clave = claveDeRol($(this).val());
        var $check = $('#wz-rol-check').removeClass('ok error').addClass('info');

        if (!clave) {
            $check.text('Define los permisos que tendrá la cuenta.');
            return;
        }

        $check.removeClass('info').addClass('ok').text('✓ Rol: ' + (ROLES[clave] || clave));
    });

    $('#btn-nuevo-empleado').on('click', function () {
        $('#form-wizard-paso-1')[0].reset();
        $('#form-wizard-paso-2')[0].reset();
        $('#wz-ci-check, #wz-correo-check, #wz-usuario-check, #wz-correo-cuenta-check, #wz-password-confirm-check, #wz-nro_colegiado-check')
            .removeClass('ok error').addClass('info').text('');
        $('#wz-rol-check').removeClass('ok error').addClass('info').text('Define los permisos que tendrá la cuenta.');
        logrosWizard.limpiar();
        actualizarSeccionMedico();
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
        var $checkCi = $('#wz-ci-check');
        $checkCi.removeClass('ok error').addClass('info').text('Verificando...');

        $.getJSON('<?= route('portal_usuario_ci_disponible') ?>', { ci: ci })
            .done(function (r) {
                if (r && r.success && r.disponible) {
                    $checkCi.removeClass('info error').addClass('ok').text('✓ CI disponible');
                    irAPaso(2);
                } else {
                    $checkCi.removeClass('info ok').addClass('error')
                        .text((r && r.message) || 'Ese CI ya está registrado.');
                    mostrarError($alertaWizard, (r && r.message) || 'Ese CI ya está registrado.');
                }
            })
            .fail(function (xhr) {
                var r = xhr && xhr.responseJSON;
                var mensaje = (r && r.message) || 'No se pudo verificar el CI. Intenta de nuevo.';
                $checkCi.removeClass('info ok').addClass('error').text(mensaje);
                mostrarError($alertaWizard, mensaje);
            });
    });

    $('#wizard-atras').on('click', function () {
        irAPaso(1);
    });

    // Verificaciones en vivo del paso 1 (CI y correo personal)
    $('#wz-ci').on('blur', function () {
        var ci = $.trim($(this).val());
        var $check = $('#wz-ci-check');

        if (!ci) { $check.removeClass('ok error').addClass('info').text(''); return; }

        verificarDisponible('<?= route('portal_usuario_ci_disponible') ?>', { ci: ci }, $check, 'CI disponible');
    });

    $('#wz-correo').on('blur', function () {
        var correo = $.trim($(this).val());
        var $check = $('#wz-correo-check');

        if (!correo) { $check.removeClass('ok error').addClass('info').text(''); return; }

        verificarDisponible('<?= route('portal_usuario_correo_disponible') ?>', { correo: correo }, $check, 'Correo disponible');
    });

    // Verificaciones en vivo del paso 2
    $('#wz-usuario').on('blur', function () {
        var usuario = $.trim($(this).val());
        var $check = $('#wz-usuario-check');

        if (!usuario) { $check.removeClass('ok error').addClass('info').text(''); return; }

        verificarDisponible('<?= route('portal_usuario_disponible') ?>', { usuario: usuario }, $check, 'Usuario disponible');
    });

    $('#wz-correo-cuenta').on('blur', function () {
        var correo = $.trim($(this).val());
        var $check = $('#wz-correo-cuenta-check');

        if (!correo) { $check.removeClass('ok error').addClass('info').text(''); return; }

        verificarDisponible('<?= route('portal_usuario_correo_disponible') ?>', { correo: correo }, $check, 'Correo disponible');
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
        var idRol = $('#wz-id_rol').val();
        var esMedico = claveDeRol(idRol) === 'MEDICO';
        var nroColegiado = $.trim($('#wz-nro_colegiado').val());
        var fechaIngreso = $('#wz-fecha_ingreso').val();

        if (!idRol) {
            mostrarError($alertaWizard, 'Selecciona el rol del empleado en el sistema.');
            return;
        }

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

        if (esMedico && !nroColegiado) {
            mostrarError($alertaWizard, 'El número de colegiado es obligatorio para el rol Médico.');
            return;
        }

        if (esMedico && !fechaIngreso) {
            mostrarError($alertaWizard, 'La fecha de ingreso es obligatoria para el rol Médico.');
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
            id_rol: idRol,
            usuario: usuario,
            correo_cuenta: correoCuenta,
            password: password,
            password_confirm: passwordConfirm,
            nro_colegiado: nroColegiado,
            fecha_ingreso: fechaIngreso,
            biografia: esMedico ? logrosWizard.obtener().map(function (i) { return '• ' + i; }).join('\n') : ''
        };

        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_usuario_registrar') ?>',
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (respuesta) {
            if (respuesta && respuesta.success) {
                window.location.href = respuesta.redirect || window.location.href;
                return;
            }
            mostrarError($alertaWizard, (respuesta && respuesta.message) || 'No se pudo registrar el empleado.');
        }).fail(function (xhr) {
            var r = xhr && xhr.responseJSON;
            mostrarError($alertaWizard, (r && r.message) || 'No se pudo registrar el empleado. Revisa los datos.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
