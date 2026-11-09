<?php
/**
 * Reporte PDF de empleados del sistema (dompdf).
 *
 * Se genera desde PortalUsuarioController::pdf() con todos los empleados
 * (roles ADMIN, RECEP y MEDICO) que coincidan con los filtros, sin
 * paginación. Estilo sobrio con DejaVu Sans (acentos y símbolos
 * garantizados).
 */
$personal = $personal ?? [];
$filtros = $filtros ?? [];
$total = (int) ($total ?? 0);
$hoy = (string) ($hoy ?? date('d/m/Y'));
$hora = (string) ($hora ?? date('H:i'));
$config = $config ?? [];

$clinica = (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud');
$telefono = (string) ($config['clinica_telefono'] ?? '');
$direccion = (string) ($config['clinica_direccion'] ?? '');
$correo = (string) ($config['clinica_correo'] ?? '');
$colorPrimario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($config['tema_color_primario'] ?? ''))
    ? (string) $config['tema_color_primario']
    : '#0f8b84';
$colorOscuro = '#0b2a38';

/** Nombres legibles de los roles para el filtro. */
$etiquetasRoles = [
    'ADMIN' => 'Administrador',
    'RECEP' => 'Recepcionista',
    'MEDICO' => 'Médico',
];

/** Descripción legible de los filtros aplicados. */
$etiquetasFiltros = [];

$mapa = [
    'nombre' => 'Nombre',
    'ci' => 'CI',
    'rol' => 'Rol',
    'estado' => 'Estado',
    'desde' => 'Registro desde',
    'hasta' => 'Registro hasta',
];

foreach ($mapa as $clave => $etiqueta) {
    $valor = trim((string) ($filtros[$clave] ?? ''));

    if ($valor === '') {
        continue;
    }

    if ($clave === 'estado') {
        $valor = $valor === '1' ? 'Activo' : 'Inactivo';
    } elseif ($clave === 'rol') {
        $valor = $etiquetasRoles[$valor] ?? $valor;
    }

    $etiquetasFiltros[$etiqueta] = $valor;
}

if ($etiquetasFiltros === []) {
    $etiquetasFiltros['Filtros'] = 'Sin filtros (todos los empleados)';
}

/** Conteos rápidos para las tarjetas del reporte. */
$conteoActivos = 0;
$conteoInactivos = 0;

foreach ($personal as $empleado) {
    if ($empleado['estado']) {
        $conteoActivos++;
    } else {
        $conteoInactivos++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte de empleados · <?= e($clinica) ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #2c3e45;
            margin: 0;
            padding: 0;
        }

        /* Encabezado */
        .cabecera {
            border-bottom: 3px solid <?= e($colorPrimario) ?>;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .cabecera-top {
            display: table;
            width: 100%;
        }

        .cabecera-izq,
        .cabecera-der {
            display: table-cell;
            vertical-align: bottom;
        }

        .cabecera-der {
            text-align: right;
        }

        .clinica-nombre {
            font-size: 19px;
            font-weight: bold;
            color: <?= e($colorPrimario) ?>;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .clinica-datos {
            font-size: 8.5px;
            color: #7f8c8d;
            margin-top: 4px;
            line-height: 1.5;
        }

        .reporte-etiqueta {
            display: inline-block;
            background: <?= e($colorPrimario) ?>;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 14px;
            border-radius: 3px;
        }

        .reporte-fecha {
            font-size: 9px;
            color: #7f8c8d;
            margin-top: 6px;
        }

        .reporte-titulo {
            font-size: 15px;
            font-weight: bold;
            color: <?= e($colorOscuro) ?>;
            margin: 14px 0 4px 0;
        }

        .reporte-subtitulo {
            font-size: 10px;
            color: #7f8c8d;
            margin: 0 0 14px 0;
        }

        /* Filtros */
        .filtros {
            background: #f4f9f8;
            border: 1px solid #d9e6e3;
            border-left: 4px solid <?= e($colorPrimario) ?>;
            border-radius: 4px;
            padding: 9px 12px;
            margin-bottom: 14px;
        }

        .filtros-titulo {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: <?= e($colorPrimario) ?>;
            margin-bottom: 5px;
        }

        .filtros-lista {
            margin: 0;
            font-size: 9.5px;
            line-height: 1.6;
        }

        .filtros-lista strong {
            color: <?= e($colorOscuro) ?>;
        }

        /* Resumen de totales */
        .resumen {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }

        .resumen-celda {
            display: table-cell;
            width: 33%;
            background: #ffffff;
            border: 1px solid #e0e6e3;
            border-top: 3px solid <?= e($colorPrimario) ?>;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }

        .resumen-valor {
            font-size: 17px;
            font-weight: bold;
            color: <?= e($colorOscuro) ?>;
            line-height: 1.2;
        }

        .resumen-etiqueta {
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #7f8c8d;
            margin-top: 2px;
        }

        /* Tabla */
        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.datos thead th {
            background: <?= e($colorOscuro) ?>;
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 8px 7px;
            text-align: left;
            border: none;
        }

        table.datos thead th.num {
            text-align: center;
        }

        table.datos tbody td {
            padding: 6px 7px;
            border-bottom: 1px solid #e8eeec;
            font-size: 9.5px;
            vertical-align: middle;
        }

        table.datos tbody tr:nth-child(even) td {
            background: #f7faf9;
        }

        table.datos tbody tr.impar td {
            background: #ffffff;
        }

        .celda-num {
            text-align: center;
            color: #7f8c8d;
            font-size: 9px;
        }

        .celda-nombre {
            font-weight: bold;
            color: <?= e($colorOscuro) ?> !important;
        }

        .celda-nombre small {
            display: block;
            font-weight: normal;
            font-size: 8px;
            color: #7f8c8d;
        }

        .badge-estado {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #ffffff;
            padding: 2px 7px;
            border-radius: 3px;
        }

        .badge-activo {
            background: #2fae60;
        }

        .badge-inactivo {
            background: #d9534f;
        }

        .badge-rol {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 2px 7px;
            border-radius: 3px;
            background: #e8f0fe;
            color: #1a73e8;
        }

        .badge-rol-admin {
            background: #e8f0fe;
            color: #1a73e8;
        }

        .badge-rol-medico {
            background: #e0f2f1;
            color: #0f8b84;
        }

        .badge-rol-recep {
            background: #e7f7ed;
            color: #1e8e3e;
        }

        .sin-datos {
            text-align: center;
            padding: 26px 10px;
            color: #95a5a6;
            font-size: 10px;
            border: 1px dashed #d9e6e3;
            border-radius: 4px;
        }

        /* Pie */
        .pie {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #e0e6e3;
            font-size: 8px;
            color: #95a5a6;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <div class="cabecera">
        <div class="cabecera-top">
            <div class="cabecera-izq">
                <p class="clinica-nombre"><?= e($clinica) ?></p>
                <div class="clinica-datos">
                    <?php if ($direccion !== '') { ?><?= e($direccion) ?><br><?php } ?>
                    <?php if ($telefono !== '') { ?>Tel. <?= e($telefono) ?><?php } ?>
                    <?php if ($telefono !== '' && $correo !== '') { ?> · <?php } ?>
                    <?php if ($correo !== '') { ?><?= e($correo) ?><?php } ?>
                </div>
            </div>
            <div class="cabecera-der">
                <span class="reporte-etiqueta">Reporte de empleados</span>
                <div class="reporte-fecha">Generado: <?= e($hoy) ?> · <?= e($hora) ?></div>
            </div>
        </div>

        <p class="reporte-titulo">Listado de empleados del sistema</p>
        <p class="reporte-subtitulo">
            Personas con rol administrador, recepcionista o médico · Total resultante: <strong><?= $total ?></strong> registro(s)
        </p>
    </div>

    <div class="filtros">
        <div class="filtros-titulo">Criterios de búsqueda aplicados</div>
        <div class="filtros-lista">
            <?php foreach ($etiquetasFiltros as $etiqueta => $valor) { ?>
                <div><strong><?= e($etiqueta) ?>:</strong> <?= e($valor) ?></div>
            <?php } ?>
        </div>
    </div>

    <div class="resumen">
        <div class="resumen-celda">
            <div class="resumen-valor"><?= $total ?></div>
            <div class="resumen-etiqueta">Empleados</div>
        </div>
        <div class="resumen-celda">
            <div class="resumen-valor"><?= $conteoActivos ?></div>
            <div class="resumen-etiqueta">Activos</div>
        </div>
        <div class="resumen-celda">
            <div class="resumen-valor"><?= $conteoInactivos ?></div>
            <div class="resumen-etiqueta">Inactivos</div>
        </div>
    </div>

    <?php if ($personal === []) { ?>
        <div class="sin-datos">
            No se encontraron empleados que coincidan con los criterios seleccionados.
        </div>
    <?php } else { ?>
        <table class="datos">
            <thead>
                <tr>
                    <th class="num" style="width:6%;">Nº</th>
                    <th style="width:27%;">Empleado</th>
                    <th style="width:13%;">CI</th>
                    <th style="width:15%;">Rol</th>
                    <th style="width:17%;">Usuario</th>
                    <th style="width:11%;">Estado</th>
                    <th style="width:11%;">Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personal as $i => $empleado) { ?>
                    <?php
                    $ts = $empleado['fecha_registro'] !== '' ? strtotime((string) $empleado['fecha_registro']) : false;
                    $claveRol = (string) $empleado['rol_clave'];
                    $claseRol = $claveRol === 'ADMIN'
                        ? 'badge-rol-admin'
                        : ($claveRol === 'MEDICO' ? 'badge-rol-medico' : 'badge-rol-recep');
                    ?>
                    <tr class="<?= $i % 2 === 1 ? 'impar' : '' ?>">
                        <td class="celda-num"><?= $i + 1 ?></td>
                        <td class="celda-nombre">
                            <?= e((string) $empleado['nombre_completo']) ?>
                            <small><?= e((string) ($empleado['correo'] !== '' ? $empleado['correo'] : 'Sin correo personal')) ?></small>
                        </td>
                        <td><?= e((string) $empleado['ci']) ?></td>
                        <td>
                            <span class="badge-rol <?= $claseRol ?>">
                                <?= e((string) $empleado['rol_nombre']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($empleado['tiene_cuenta']) { ?>
                                @<?= e((string) $empleado['usuario']) ?>
                            <?php } else { ?>
                                <span style="color:#b8860b;">Sin cuenta</span>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="badge-estado <?= $empleado['estado'] ? 'badge-activo' : 'badge-inactivo' ?>">
                                <?= $empleado['estado'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td><?= $ts !== false ? date('d/m/Y', $ts) : '—' ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>

    <div class="pie">
        <?= e($clinica) ?> · Reporte generado automáticamente desde el dashboard de empleados<br>
        Documento de uso interno · <?= e($hoy) ?>
    </div>
</body>

</html>
