<?php
/**
 * Matriz de permisos por rol.
 *
 * Cada fila es un permiso del sistema, agrupado por módulo, con una
 * casilla por rol (ADMIN, RECEP, MEDICO y PAC). El guardado envía la
 * matriz completa por POST en una sola petición.
 */
$roles = $roles ?? [];
$modulos = $modulos ?? [];
$matriz = $matriz ?? [];
$totalPermisos = (int) ($totalPermisos ?? 0);

$iconosModulo = [
    'pacientes' => 'fa-procedures',
    'medicos' => 'fa-stethoscope',
    'especialidades' => 'fa-notes-medical',
    'citas' => 'fa-calendar-check',
    'reportes' => 'fa-chart-bar',
    'usuarios' => 'fa-users-cog',
    'configuracion' => 'fa-cogs',
    'general' => 'fa-folder',
];
?>

<div class="per-card">
    <h5><i class="fas fa-th mr-1"></i> Matriz de permisos</h5>
    <p class="per-sub">
        Agrupados por módulo. Las casillas marcadas indican los permisos activos de cada rol;
        lo que no esté tildado queda desactivado al guardar.
    </p>

    <form id="form-permisos" method="POST" action="<?= route('portal_permisos_guardar') ?>" onsubmit="return false;">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <?php if ($modulos === []) { ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox"></i> No hay permisos configurados en el sistema.
            </div>
        <?php } ?>

        <?php foreach ($modulos as $nombreModulo => $permisosModulo) { ?>
            <div class="per-modulo">
                <div class="per-modulo-titulo">
                    <i class="fas <?= e($iconosModulo[$nombreModulo] ?? 'fa-folder') ?>"></i>
                    <?= e(ucfirst((string) $nombreModulo)) ?>
                    <span class="badge badge-light ml-1"><?= count($permisosModulo) ?></span>
                </div>

                <div class="per-tabla">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th class="per-col-permiso">Permiso</th>
                                    <?php foreach ($roles as $rol) { ?>
                                        <th scope="col" title="<?= e((string) $rol['descripcion']) ?>">
                                            <?= e((string) $rol['nombre']) ?>
                                        </th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permisosModulo as $permiso) { ?>
                                    <?php $idPermiso = (int) $permiso['id']; ?>
                                    <tr>
                                        <td class="per-celda-permiso">
                                            <div class="per-permiso-nombre">
                                                <?= e((string) $permiso['nombre']) ?>
                                                <small><?= e((string) $permiso['clave']) ?></small>
                                            </div>
                                        </td>
                                        <?php foreach ($roles as $rol) { ?>
                                            <?php
                                            $idRol = (int) $rol['id'];
                                            $activo = !empty($matriz[$idRol][$idPermiso]);
                                            ?>
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input per-check"
                                                        name="permisos[<?= $idRol ?>][<?= $idPermiso ?>]"
                                                        value="1"
                                                        <?= $activo ? 'checked' : '' ?>
                                                        data-original="<?= $activo ? '1' : '0' ?>"
                                                        aria-label="<?= e((string) $permiso['nombre']) ?> para <?= e((string) $rol['nombre']) ?>">
                                                </div>
                                            </td>
                                        <?php } ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php } ?>
    </form>

    <div class="per-barra" id="per-barra-cambios">
        <div>
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> Cambios sin guardar</strong>
            <small class="d-block" id="per-barra-detalle">0 casilla(s) modificada(s)</small>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="button" class="btn btn-outline-light btn-sm" id="per-descartar">
                <i class="fas fa-undo mr-1"></i> Descartar
            </button>
            <button type="button" class="btn btn-success btn-sm" id="per-guardar">
                <i class="fas fa-save mr-1"></i> Guardar permisos
            </button>
        </div>
    </div>
</div>

<!--
<div class="per-card">
    <h5><i class="fas fa-info-circle mr-1"></i> Cómo funciona</h5>
    <p class="text-muted mb-0" style="font-size:0.93rem;">
        Cada casilla representa una fila en la tabla <code>rol_permiso</code> (relación rol ↔ permiso).
        Al guardar, el sistema reemplaza todas las asignaciones de los roles mostrados en una sola
        transacción y registra la acción en la bitácora con el conteo anterior y posterior por rol.
        Los permisos también se usan en los middlewares <code>Permiso:...</code> de las rutas;
        los cambios aplican en el siguiente inicio de sesión de cada usuario.
    </p>
</div>
-->

<script>
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var token = '<?= csrf_token() ?>';
    var $barra = $('#per-barra-cambios');
    var $detalle = $('#per-barra-detalle');

    /** Cuenta las casillas cuyo valor difiere del estado original. */
    function cambios() {
        var total = 0;

        $('.per-check').each(function () {
            var original = String($(this).attr('data-original')) === '1';
            if (this.checked !== original) {
                total++;
            }
        });

        return total;
    }

    function actualizarBarra() {
        var total = cambios();

        if (total > 0) {
            $detalle.text(total + ' casilla(s) modificada(s)');
            $barra.addClass('visible');
        } else {
            $barra.removeClass('visible');
        }
    }

    $(document).on('change', '.per-check', actualizarBarra);

    $('#per-descartar').on('click', function () {
        $('.per-check').each(function () {
            this.checked = String($(this).attr('data-original')) === '1';
        });
        $barra.removeClass('visible');
    });

    $('#per-guardar').on('click', function () {
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: '<?= route('portal_permisos_guardar') ?>',
            method: 'POST',
            dataType: 'json',
            data: $('#form-permisos').serialize(),
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (respuesta) {
            if (respuesta && respuesta.success) {
                if (window.notification && typeof window.notification.success === 'function') {
                    window.notification.success(respuesta.message);
                } else {
                    alert(respuesta.message);
                }
                window.location.reload();
                return;
            }
            alert((respuesta && respuesta.message) || 'No se pudieron guardar los permisos.');
        }).fail(function (xhr) {
            var r = xhr && xhr.responseJSON;
            alert((r && r.message) || 'No se pudieron guardar los permisos.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
