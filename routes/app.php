<?php

declare(strict_types=1);

use Lib\Core\Route;
use System\Controllers\AuthController;
use System\Controllers\DocsController;
use System\Controllers\GeneratorController;
use System\Controllers\SystemController;
use System\Controllers\UsuariosController;

/*
|--------------------------------------------------------------------------
| Rutas del panel de administración
|--------------------------------------------------------------------------
*/

Route::prefix('join/access/dashboard', function () {
    Route::controller(AuthController::class, function () {
        Route::get('/', 'index')->name('login');
        Route::post('/verify-credentials', 'verificar_credenciales')->name('verify_credentials');

        Route::middleware(['Auth'], function () {
            Route::post('/logout', 'logout')->name('logout');
        });
    });

    Route::middleware(['Auth'], function () {

        Route::controller(SystemController::class, function () {
            Route::get('/panel', 'panel')->name('panel');
            Route::prefix('/Area-of-work/system', function () {
                Route::get('/', 'index')->name('system');
                Route::get('/routes', 'routes')->name('system_routes');
                Route::post('/routes/toggle', 'routeToggle')->name('system_route_toggle');
                Route::get('/middleware', 'middleware')->name('system_middleware');
                Route::post('/middleware/toggle', 'middlewareToggle')->name('system_middleware_toggle');
                Route::get('/home', 'home')->name('system_home');
                Route::post('/home', 'homeUpdate')->name('system_home_update');
                Route::post('/maintenance', 'maintenanceToggle')->name('system_maintenance_toggle');
                Route::post('/debug', 'debugToggle')->name('system_debug_toggle');
                Route::get('/database', 'database')->name('system_database');
                Route::post('/database/query', 'databaseQuery')->name('system_database_query');
            });
        });

        Route::controller(GeneratorController::class, function () {
            Route::prefix('/Area-of-work/system/generators', function () {
                Route::get('/', 'index')->name('system_generators');
                Route::post('/scan', 'scan')->name('system_generators_scan');
                Route::post('/source', 'source')->name('system_generators_source');
                Route::post('/preview', 'preview')->name('system_generators_preview');
                Route::post('/create', 'create')->name('system_generators_create');
            });
        });

        Route::controller(DocsController::class, function () {
            Route::get('/Area-of-work/system/docs', 'index')->name('system_docs');
            Route::get('/Area-of-work/system/docs/pdf', 'pdf')->name('system_docs_pdf');
            Route::get('/Area-of-work/system/docs/download-base', 'downloadBase')->name('system_docs_download_base');
        });

        Route::controller(UsuariosController::class, function () {
            Route::prefix('/Area-of-work/usuarios', function () {
                Route::get('/', 'index')->name('usuarios');
                Route::post('/guardar', 'guardar')->name('usuarios_guardar');
                Route::post('/actualizar/{id}', 'actualizar')->name('usuarios_actualizar');
                Route::post('/estado/{id}', 'estado')->name('usuarios_estado');
                Route::post('/eliminar/{id}', 'eliminar')->name('usuarios_eliminar');
            });
        });
        
    });
});
