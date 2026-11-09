<?php
/**
 * Reporte PDF de la bitácora de accesos (dompdf).
 *
 * Se genera desde PortalBitacoraController::pdf() con todos los accesos que
 * coincidan con los filtros, sin paginación. Estilo sobrio con DejaVu Sans
 * (acentos y símbolos garantizados).
 */
$accesos = $accesos ?? [];
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

/** Descripción legible de los filtros aplicados. */
$etiquetasFiltros = [];

$mapa = [
    'nombre' => 'Usuario o persona',
    'desde' => 'Fecha desde',
    'hasta' => 'Fecha hasta',
];

foreach ($mapa as $clave => $etiqueta) {
    $valor = trim((string) ($filtros[$clave] ?? ''));

    if ($valor !== '') {
        $etiquetasFiltros[$etiqueta] = $valor;
    }
}

if ($etiquetasFiltros === []) {
    $etiquetasFiltros['Filtros'] = 'Sin filtros (todos los accesos)';
}

/** Etiquetas y colores por estado del acceso. */
$estados = [
    'EXITOSO' => ['Acceso válido', 'badge-ok'],
    'FALLIDO' => ['Intento fallido', 'badge-fallido'],
    'CERRADO' => ['Cierre de sesión', 'badge-cerrado'],
    'BLOQUEADO' => ['Cuenta bloqueada', 'badge-bloqueado'],
];

/** Conteos rápidos para las tarjetas del reporte. */
$conteoExitosos = 0;
$conteoFallidos = 0;
$conteoBloqueados = 0;

foreach ($accesos as $acceso) {
    $estado = strtoupper((string) $acceso['estado']);

    if ($estado === 'EXITOSO') {
        $conteoExitosos++;
    } elseif ($estado === 'BLOQUEADO') {
        $conteoBloqueados++;
    } else {
        $conteoFallidos++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Bitácora de accesos · <?= e($clinica) ?></title>
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
            width: 25%;
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

        .resumen-valor.ok {
            color: #2fae60;
        }

        .resumen-valor.malo {
            color: #d9534f;
        }

        .resumen-valor.alerta {
            color: #c58a00;
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

        .celda-fecha {
            color: <?= e($colorOscuro) ?>;
            white-space: nowrap;
        }

        .celda-persona {
            font-weight: bold;
            color: <?= e($colorOscuro) ?> !important;
        }

        .celda-persona small {
            display: block;
            font-weight: normal;
            font-size: 8px;
            color: #7f8c8d;
        }

        .celda-detalle {
            display: block;
            font-size: 8px;
            color: #7f8c8d;
            margin-top: 3px;
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

        .badge-ok {
            background: #2fae60;
        }

        .badge-fallido {
            background: #d9534f;
        }

        .badge-cerrado {
            background: #7f8c8d;
        }

        .badge-bloqueado {
            background: #c58a00;
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
                <span class="reporte-etiqueta">Reporte de bitácora</span>
                <div class="reporte-fecha">Generado: <?= e($hoy) ?> · <?= e($hora) ?></div>
            </div>
        </div>

        <p class="reporte-titulo">Bitácora de accesos al sistema</p>
        <p class="reporte-subtitulo">
            Inicios de sesión, intentos fallidos, cierres y bloqueos · Total resultante: <strong><?= $total ?></strong> registro(s)
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
            <div class="resumen-etiqueta">Accesos</div>
        </div>
        <div class="resumen-celda">
            <div class="resumen-valor ok"><?= $conteoExitosos ?></div>
            <div class="resumen-etiqueta">Válidos</div>
        </div>
        <div class="resumen-celda">
            <div class="resumen-valor malo"><?= $conteoFallidos ?></div>
            <div class="resumen-etiqueta">Fallidos</div>
        </div>
        <div class="resumen-celda">
            <div class="resumen-valor alerta"><?= $conteoBloqueados ?></div>
            <div class="resumen-etiqueta">Bloqueados</div>
        </div>
    </div>

    <?php if ($accesos === []) { ?>
        <div class="sin-datos">
            No se encontraron accesos que coincidan con los criterios seleccionados.
        </div>
    <?php } else { ?>
        <table class="datos">
            <thead>
                <tr>
                    <th class="num" style="width:5%;">Nº</th>
                    <th style="width:16%;">Fecha y hora</th>
                    <th style="width:24%;">Persona</th>
                    <th style="width:22%;">Tipo de acceso</th>
                    <th style="width:11%;">IP</th>
                    <th style="width:22%;">Equipo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accesos as $i => $acceso) { ?>
                    <?php
                    $estado = strtoupper((string) $acceso['estado']);
                    [$etiqueta, $clase] = $estados[$estado] ?? ['Acceso', 'badge-cerrado'];
                    $ts = $acceso['fecha_hora'] !== '' ? strtotime((string) $acceso['fecha_hora']) : false;
                    $usuario = (string) ($acceso['usuario_intentado'] !== '' ? $acceso['usuario_intentado'] : '—');
                    $equipo = trim((string) $acceso['user_agent']);
                    ?>
                    <tr class="<?= $i % 2 === 1 ? 'impar' : '' ?>">
                        <td class="celda-num"><?= $i + 1 ?></td>
                        <td class="celda-fecha"><?= $ts !== false ? date('d/m/Y H:i:s', $ts) : e((string) $acceso['fecha_hora']) ?></td>
                        <td class="celda-persona">
                            <?= e((string) $acceso['nombre_completo']) ?>
                            <small>@<?= e($usuario) ?><?php if ($acceso['rol_clave'] !== '') { ?> · <?= e((string) $acceso['rol_clave']) ?><?php } ?></small>
                        </td>
                        <td>
                            <span class="badge-estado <?= e($clase) ?>"><?= e($etiqueta) ?></span>
                            <?php if ($acceso['detalle'] !== '') { ?>
                                <span class="celda-detalle"><?= e((string) $acceso['detalle']) ?></span>
                            <?php } ?>
                        </td>
                        <td><?= $acceso['ip_address'] !== '' ? e((string) $acceso['ip_address']) : '—' ?></td>
                        <td><?= $equipo !== '' ? e(mb_substr($equipo, 0, 90)) : '—' ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>

    <div class="pie">
        <?= e($clinica) ?> · Reporte generado automáticamente desde la bitácora de accesos<br>
        Documento de uso interno · <?= e($hoy) ?>
    </div>
</body>

</html>
