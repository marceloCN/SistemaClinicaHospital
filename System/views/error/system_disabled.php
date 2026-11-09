<?php

$titulo  = $titulo  ?? 'Sistema No Disponible';
$estado  = $estado  ?? 'Sistema No Disponible';
$mensaje = $mensaje ?? 'Este sistema se encuentra temporalmente fuera de servicio, Contacte con el administrador para obtener más información.';
$motivo  = $motivo  ?? 'Mantenimiento programado';
$empresa = $empresa ?? config('app.name');


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .container {
            width: 90%;
            max-width: 800px;
            text-align: center;
            padding: 50px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 20px;
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, .4);
        }

        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .status {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 30px;
            background: #dc2626;
            color: white;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .message {
            font-size: 20px;
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .reason {
            background: rgba(255, 255, 255, .05);
            border-left: 4px solid #38bdf8;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
            color: #e2e8f0;
        }

        .footer {
            margin-top: 35px;
            color: #94a3b8;
            font-size: 14px;
        }

        .background {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(56, 189, 248, .08);
            animation: float 10s infinite ease-in-out;
        }

        .circle:nth-child(1) {
            width: 250px;
            height: 250px;
            top: 5%;
            left: 5%;
        }

        .circle:nth-child(2) {
            width: 350px;
            height: 350px;
            bottom: -50px;
            right: -50px;
        }

        .circle:nth-child(3) {
            width: 180px;
            height: 180px;
            left: 40%;
            bottom: 20%;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }
    </style>
</head>

<body>

    <div class="background">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <div class="container">

        <div class="icon">⚠️</div>

        <h1><?= e((string) $estado) ?></h1>

        <div class="status">
            <?= 'No Disponible' ?>
        </div>

        <div class="message">
            <?= e((string) $mensaje) ?>
        </div>

        <?php if (!empty($motivo)) { ?>
            <div class="reason">
                <strong><?= 'Motivo' ?>:</strong>


                <?= e((string) $motivo) ?>
            </div>
        <?php } ?>

        <div class="footer">
            <?= date('Y') ?> © <?= e((string) $empresa) ?>
        </div>

    </div>

</body>

</html>