<?php
$debug = $debug ?? false;
$message = $message ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error interno - 500</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            text-align: center;
            padding: 50px;
        }

        .container {
            max-width: 640px;
            margin: 0 auto;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, .1);
        }

        h1 {
            font-size: 90px;
            margin: 0;
            color: #dc3545;
        }

        p {
            font-size: 18px;
            margin: 18px 0;
        }

        pre {
            text-align: left;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            overflow: auto;
            font-size: 13px;
        }

        a {
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>500</h1>
        <p>Ocurri&oacute; un error interno. Intenta de nuevo m&aacute;s tarde.</p>
        <?php if ($debug && $message !== ''): ?>
            <pre><?= e($message) ?></pre>
        <?php endif; ?>
        <p><a href="/">Volver a la p&aacute;gina principal</a></p>
    </div>
</body>

</html>
