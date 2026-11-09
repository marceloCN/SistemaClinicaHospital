<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitio en Mantenimiento</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #0f172a;
            color: white;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            max-width: 700px;
            padding: 40px;
        }

        .logo {
            font-size: 50px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        p {
            font-size: 20px;
            color: #cbd5e1;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .loader {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(255, 255, 255, 0.1);
            border-top: 8px solid #38bdf8;
            border-radius: 50%;
            margin: auto;
            animation: spin 1s linear infinite;
        }

        .countdown {
            margin-top: 30px;
            font-size: 24px;
            color: #38bdf8;
        }

        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #94a3b8;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .background {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(56, 189, 248, 0.1);
            animation: float 10s infinite linear;
        }

        .circle:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
        }

        .circle:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: 5%;
            right: 5%;
        }

        .circle:nth-child(3) {
            width: 150px;
            height: 150px;
            bottom: 30%;
            left: 40%;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0px);
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

        <div class="logo">
            Sistema CMS
        </div>

        <h1>Sitio en Mantenimiento</h1>

        <p>
            Estamos realizando mejoras en nuestro sistema para brindarte
            una mejor experiencia.
            Volveremos muy pronto.
        </p>

        <div class="loader"></div>

        <div class="countdown" id="countdown">
            Cargando...
        </div>

        <div class="footer">
            &copy; <?= date('Y') ?> <?= htmlspecialchars((string) ($siteName ?? config('app.name'))) ?>
        </div>

    </div>

    <script>
        // Fecha objetivo
        const targetDate = new Date();
        targetDate.setHours(targetDate.getHours() + 5);

        function updateCountdown() {

            const now = new Date();

            const diff = targetDate - now;

            if (diff <= 0) {
                document.getElementById("countdown").innerHTML =
                    "El sitio ya está disponible";
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));

            const minutes = Math.floor(
                (diff % (1000 * 60 * 60)) / (1000 * 60)
            );

            const seconds = Math.floor(
                (diff % (1000 * 60)) / 1000
            );

            document.getElementById("countdown").innerHTML =
                `${hours}h ${minutes}m ${seconds}s`;

        }

        setInterval(updateCountdown, 1000);

        updateCountdown();
    </script>

</body>

</html>