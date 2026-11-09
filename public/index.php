<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

require_once BASE_PATH . '/routes/app.php';
require_once BASE_PATH . '/routes/web.php';

$request = Lib\Core\Request::createFromGlobals();

// -------------------------------------------------------------------------
// Modo mantenimiento: bloquea todo el sitio público (nunca el panel).
// -------------------------------------------------------------------------
$publicPath = trim($request->uri(), '/');
$isAdminArea = str_starts_with($publicPath, 'join/access/dashboard');

if (!$isAdminArea && Lib\Support\Session::get('MAINTENANCE')) {
    $response = Lib\Core\Response::make(
        Lib\Core\View::render('error.maintenance', [
            'siteName' => Lib\Support\Session::get('SITE_NAME', config('app.name')),
        ]),
        503
    );

    $response->send();
}

try {
    $response = Lib\Core\Route::dispatch($request);
} catch (Throwable $e) {
    error_log(sprintf(
        'Unhandled error: %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    $debug = (bool) config('app.debug');

    if ($request->isAjax()) {
        $response = Lib\Core\Response::json([
            'success' => false,
            'message' => $debug ? $e->getMessage() : 'Error interno del servidor.',
        ], 500);
    } else {
        $response = Lib\Core\Response::make(
            Lib\Core\View::render('error.error500', [
                'debug' => $debug,
                'message' => $e->getMessage(),
            ]),
            500
        );
    }
}

// Visitas del sitio público (solo respuestas 200 del frontend).
if (!$isAdminArea && $request->method() === 'GET' && $response->getStatus() === 200) {
    Lib\Support\Analytics::track($request);
}

$response->send();
