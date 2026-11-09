<?php

declare(strict_types=1);

use Lib\Core\Response;
use Lib\Core\Route;
use App\Controllers\HomeController;
use App\Controllers\PortalAuthController;
use App\Controllers\PortalConfiguracionController;
use App\Controllers\PortalBitacoraController;
use App\Controllers\PortalEspecidadesMedicasController;
use App\Controllers\PortalPacienteController;
use App\Controllers\PortalPerfilController;
use App\Controllers\PortalPermisosController;
use App\Controllers\PortalUsuarioController;
use App\Controllers\PortalHorarioAtencionController;
use App\Controllers\PortalReporteController;
use App\Controllers\PortalCitasController;
use App\Controllers\PortalRecepcionCitasController;
use App\Controllers\PortalAtencionController;
use App\Middleware\PortalAuth;

/*
|--------------------------------------------------------------------------
| Rutas pÃºblicas del sitio
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('inicio');

/*
| Las especialidades y el directorio de mÃ©dicos ahora viven dentro de la
| portada (#especialidades / #medicos). Estas rutas solo redirigen a las
| anclas para no romper enlaces guardados o indexados.
*/
Route::get('/especialidades', static fn() => Response::redirect('/#especialidades', 301))->name('especialidades');
Route::get('/medicos', static fn() => Response::redirect('/#medicos', 301))->name('medicos');

/*
|--------------------------------------------------------------------------
| Portal de pacientes y personal
|--------------------------------------------------------------------------
| Usa las tablas persona / cuenta_persona. Las rutas de autenticaciÃ³n son
| pÃºblicas; las demÃ¡s requieren estar logueado en el portal.
|--------------------------------------------------------------------------
*/
Route::prefix('portal', function () {
    // AutenticaciÃ³n
    Route::get('/login', [PortalAuthController::class, 'index'])->name('portal_login');
    Route::post('/verify-credentials', [PortalAuthController::class, 'verificar'])->name('portal_verify_credentials');
    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('portal_logout')->middleware('PortalAuth');

    //registro de un nuevo paciente, pero el paciente puede autoregistrarse
    Route::prefix('registro', function () {
        Route::controller(PortalAuthController::class, function () {
            Route::post('/', 'registrar')->name('portal_registro');
            // Verificaciones en vivo del registro pÃºblico (sin sesiÃ³n)
            Route::get('/usuario-disponible', 'usuarioDisponible')->name('portal_registro_usuario_disponible');
            Route::get('/correo-disponible', 'correoDisponible')->name('portal_registro_correo_disponible');
        });
    });

    // RecuperaciÃ³n de contraseÃ±a
    Route::prefix('password', function () {
        Route::controller(PortalAuthController::class, function () {
            Route::get('/recuperar', 'mostrarFormularioRecuperacion')->name('portal_password_solicitar');
            Route::post('/enviar', 'enviarRecuperacion')->name('portal_password_enviar');
            Route::get('/restablecer/{token}', 'mostrarFormularioRestablecer')->name('portal_password_restablecer');
            Route::post('/actualizar', 'actualizarPassword')->name('portal_password_actualizar');
        });
    });

    Route::middleware(['PortalAuth'], function () {
        Route::get('/dashboard', [PortalAuthController::class, 'dashboard'])->name('portal_dashboard');

        Route::prefix('dashboard', function () {

            //esta opcion modifica el perfil del usuario que se registra
            Route::controller(PortalPerfilController::class, function () {
                Route::prefix('perfil', function () {
                    Route::get('/', 'index')->name('portal_perfil');
                    Route::post('/acceso', 'guardarAcceso')->name('portal_perfil_acceso');
                    Route::get('/usuario-disponible', 'usuarioDisponible')->name('portal_perfil_usuario_disponible');
                    Route::post('/usuario-guardar', 'guardar')->name('portal_perfil_guardar');
                });
            });

            // solo el administrador puede ingresar al sistema
            Route::middleware(['Admin'], function () {
                // ConfiguraciÃ³n del portal
                Route::controller(PortalConfiguracionController::class, function () {
                    Route::get('/configuracion', 'index')->name('portal_configuracion');
                    Route::post('/configuracion/vista-previa', 'vistaPrevia')->name('portal_configuracion_preview');
                    Route::post('/configuracion/guardar', 'guardar')->name('portal_configuracion_guardar');
                    Route::post('/configuracion/tema', 'actualizarTema')->name('portal_configuracion_tema');
                    Route::post('/configuracion/imagen-hero', 'subirImagenHero')->name('portal_configuracion_imagen');
                    Route::post('/configuracion/imagen-hero/quitar', 'quitarImagenHero')->name('portal_configuracion_imagen_quitar');
                });

                //potal de bitacoras de acceso a acciones
                Route::controller(PortalBitacoraController::class, function () {
                    Route::prefix('bitacora-acceso', function () {
                        Route::get('/', 'index')->name('portal_bitacora_acceso');
                        Route::get('/detalle/{id}', 'detalle')->name('portal_bitacora_acceso_detalle');
                        Route::get('/pdf', 'pdf')->name('portal_bitacora_acceso_pdf');

                        // Reporte CSV con los mismos datos y filtros del PDF
                        Route::get('/csv', 'csv')->name('portal_bitacora_acceso_csv');
                    });
                    Route::prefix('bitacora-accion', function () {
                        Route::get('/', 'acciones')->name('portal_bitacora_accion');
                        Route::get('/detalle/{id}', 'detalleAccion')->name('portal_bitacora_accion_detalle');
                        Route::get('/pdf', 'pdfAcciones')->name('portal_bitacora_accion_pdf');

                        // Reporte CSV con los mismos datos y filtros del PDF
                        Route::get('/csv', 'csvAcciones')->name('portal_bitacora_accion_csv');
                    });
                });

                //administracion de Empleados del sistema (administrador, Recepcionista y Medico)
                Route::controller(PortalUsuarioController::class, function () {
                    Route::prefix('Usuarios', function () {
                        Route::get('/', 'index')->name('dashboard_usuarios');
                        Route::get('/detalle/{id}', 'detalle')->name('portal_usuario_detalle');

                        // Verificaciones para el registro (wizard de 2 pasos)
                        Route::get('/usuario-disponible', 'usuarioDisponible')->name('portal_usuario_disponible');
                        Route::get('/ci-disponible', 'ciDisponible')->name('portal_usuario_ci_disponible');
                        Route::get('/correo-disponible', 'correoDisponible')->name('portal_usuario_correo_disponible');

                        // Acciones del mÃ³dulo
                        Route::post('/registrar', 'registrar')->name('portal_usuario_registrar');
                        Route::post('/actualizar', 'actualizar')->name('portal_usuario_actualizar');
                        Route::post('/cambiar-estado', 'cambiarEstado')->name('portal_usuario_cambiar_estado');
                        Route::get('/pdf', 'pdf')->name('portal_usuario_pdf');
                        Route::get('/csv', 'csv')->name('portal_usuario_csv');
                    });
                });

                //gestor de permisos de controlador en funcion a los roles
                Route::controller(PortalPermisosController::class, function () {
                    Route::prefix('Permisos', function () {
                        Route::get('/', 'index')->name('dashboard_permisos');
                        Route::post('/guardar', 'guardar')->name('portal_permisos_guardar');
                    });
                });

                //Gestor de especialidades medicas (mejprar)
                Route::controller(PortalEspecidadesMedicasController::class, function () {
                    Route::prefix('Especialidades', function () {
                        Route::get('/', 'index')->name('dashboard_especialidades');
                        Route::get('/Especialidades_medicas', 'especialidadesMedicas')->name('dashboard_especialidades_medicas');
                        Route::get('/Medicos', 'medicos')->name('dashboard_especialidades_medicos');
                        Route::get('/servicio_medico', 'servicioMedico')->name('dashboard_especialidades_servicio');

                        Route::get('/detalle/{id}', 'detalleEspecialidad')->name('portal_especialidad_detalle');
                        Route::get('/{id}/bloqueos', 'bloqueosServicio')->name('portal_especialidad_bloqueos');

                        // Acciones del módulo
                        Route::post('/guardar', 'guardar')->name('portal_especialidad_guardar');
                        Route::post('/cambiar-estado', 'cambiarEstado')->name('portal_especialidad_estado');
                        Route::post('/asignar-especialidad', 'asignarEspecialidades')->name('portal_especialidad_asignar');
                        Route::post('/quitar-especialidad', 'quitarEspecialidad')->name('portal_especialidad_quitar');
                        Route::post('/servicio/guardar', 'guardarServicio')->name('portal_especialidad_servicio_guardar');
                        Route::post('/bloqueo', 'bloquearAgenda')->name('portal_especialidad_bloqueo');
                        Route::post('/bloqueo/anular', 'anularBloqueo')->name('portal_especialidad_bloqueo_anular');

                        // Reporte PDF con los filtros aplicados
                        Route::get('/pdf', 'pdf')->name('portal_especialidad_pdf');

                        // Reporte CSV con los mismos datos y filtros del PDF
                        Route::get('/csv', 'csv')->name('portal_especialidad_csv');
                    });
                });

            }); // cierra middleware Admin

            // reporte y estadistica
            Route::prefix('reportes', function () {
                Route::controller(PortalReporteController::class, function () {
                    Route::get('/', 'index')->name('dashboard_reportes');
                    Route::get('/ficha-historica', 'fichaHistorica')->name('portal_reporte_ficha_historica')->middleware('Admin');
                    Route::get('/paciente-expediente', 'pacienteExpediente')->name('portal_reporte_paciente_expediente');
                });
            });

            //el dashboard de datos personales de pacientes, es en funcion al middleware
            Route::prefix('Pacientes', function () {
                Route::controller(PortalPacienteController::class, function () {
                    Route::get('/', 'index')->name('dashboard_pacientes')->middleware('Permiso:120');
                    Route::get('/detalle/{id}', 'detalle')->name('portal_paciente_detalle')->middleware('Permiso:120');

                    // Verificaciones para el registro (wizard de 2 pasos)
                    Route::get('/usuario-disponible', 'usuarioDisponible')->name('portal_paciente_usuario_disponible')->middleware('Permiso:120');
                    Route::get('/ci-disponible', 'ciDisponible')->name('portal_paciente_ci_disponible')->middleware('Permiso:120');
                    Route::get('/correo-disponible', 'correoDisponible')->name('portal_paciente_correo_disponible')->middleware('Permiso:120');

                    // Acciones del mÃ³dulo
                    Route::post('/registrar', 'registrar')->name('portal_paciente_registrar')->middleware('Permiso:120');
                    Route::post('/actualizar', 'actualizar')->name('portal_paciente_actualizar')->middleware('Permiso:120');
                    Route::post('/contacto/guardar', 'guardarContacto')->name('portal_paciente_contacto_guardar')->middleware('Permiso:120');
                    Route::post('/cambiar-estado', 'cambiarEstado')->name('portal_paciente_cambiar_estado')->middleware('Permiso:120');
                    Route::get('/pdf', 'pdf')->name('portal_paciente_pdf')->middleware('Permiso:120');
                    Route::get('/csv', 'csv')->name('portal_paciente_csv')->middleware('Permiso:120');
                });
            });

            //el recepcionista o administrador puede gestionar los horarios de atencion de los medicos
            Route::prefix('horario-atencion', function () {
                Route::controller(PortalHorarioAtencionController::class, function () {
                    Route::get('/', 'index')->name('dashboard_horario_atencion');

                    // Reporte PDF con los filtros aplicados
                    Route::get('/pdf', 'pdf')->name('portal_horario_atencion_pdf');

                    // Reporte CSV con los mismos datos y filtros del PDF
                    Route::get('/csv', 'csv')->name('portal_horario_atencion_csv');

                    // Acciones del módulo
                    Route::post('/', 'store')->name('portal_horario_atencion_store');  // esto solo crea un nuevo horario de atencion
                    Route::post('/{id}/destroy', 'destroy')->name('portal_horario_atencion_destroy'); // esto solo da de baja al horario de atencion
                    Route::post('/{id}/generar-fichas', 'generarFichas')->name('portal_horario_atencion_generar_fichas');
                    Route::post('/{id}/eliminar-fichas', 'eliminarFichasRestantes')->name('portal_horario_atencion_eliminar_fichas'); // esto eliminar las fichas resantes del horario
                    Route::post('/{id}', 'update')->name('portal_horario_atencion_update'); // esto actualiza solo el horario de atencion, no toca fichas generadas
                });
            });

            // la programacion de citas medicas, solo
            Route::prefix('citas-medicas', function () {
                Route::controller(PortalCitasController::class, function () {
                    Route::middleware(['Paciente'], function () {
                        Route::get('/', 'index')->name('dashboard_citas_medicas'); //LISTO
                        Route::get('/mis-citas', 'misCitas')->name('dashboard_mis_citas');
                        Route::post('/{id}/cancelar', 'cancelarCita')->name('portal_cita_cancelar');
                    });
                    // datos de apoyo del asistente de generación (pasos 2 y 3)
                    Route::get('/medicos', 'medicosPorEspecialidad')->name('portal_cita_medicos'); //LISTO
                    Route::get('/fichas-disponibles', 'fichasDisponibles')->name('portal_cita_fichas'); //LISTO
                    Route::post('/', 'generarCita')->name('portal_cita_generar'); //LISTO
                    Route::post('/detalle/{id}', 'verDetalle')->name('portal_cita_detalle'); //LISTO

                });
            });

            // son los parametros de la recepcionista de citas medicas.
            Route::prefix('recepcion-cita-medica', function () {
                Route::controller(PortalRecepcionCitasController::class, function () {
                    Route::get('/', 'index')->name('dashboard_recepcion_citas_medicas');

                    // recepción por código de la cita (check-in en ventanilla)
                    Route::get('/buscar-codigo', 'buscarPorCodigo')->name('portal_recepcion_cita_buscar'); //listo
                    Route::post('/recepcionar', 'recepcionarCitaMedica')->name('portal_recepcion_cita_recepcionar'); //listo
                    Route::post('/cambiar-estado', 'cambiarEstadoCita')->name('portal_recepcion_cita_cambiar_estado'); //listo

                    // registro de una cita para un paciente
                    Route::get('/pacientes', 'buscarPaciente')->name('portal_recepcion_cita_pacientes');  //listo

                    // reprogramación de citas por bloqueo de agenda
                    Route::get('/bloqueos/{id}/afectadas', 'citasAfectadasPorBloqueo')->name('portal_recepcion_cita_afectadas'); //listo
                    Route::post('/reprogramar', 'reprogramarCitaMedica')->name('portal_recepcion_cita_reprogramar'); //listo
                });
            });

            // flujo integral de atencion: recepcion -> evaluacion previa ->
            // consulta medica -> expediente clinico -> historial
            Route::prefix('atencion', function () {
                Route::controller(PortalAtencionController::class, function () {
                    Route::get('/', 'index')->name('dashboard_atencion');

                    // apoyo de los modales
                    Route::get('/detalle/{id}', 'detalleCita')->name('portal_atencion_detalle'); //listo

                    // fase 2: evaluacion previa (recepcion/admin)
                    Route::post('/evaluacion/iniciar', 'iniciarEvaluacion')->name('portal_atencion_evaluacion_iniciar'); //listo
                    Route::post('/evaluacion/guardar', 'guardarEvaluacion')->name('portal_atencion_evaluacion_guardar'); //listo
                    Route::post('/evaluacion/sin-evaluacion', 'sinEvaluacion')->name('portal_atencion_evaluacion_sin');  // cuando el paciente listo para ingresar sin evaluacion previa

                    // fase 3/4: consulta medica (medico/admin)
                    Route::get('/consulta/{id}', 'consulta')->name('portal_atencion_consulta'); //para iniciar consulta con el id de cita
                    Route::post('/consulta/abrir', 'abrirConsulta')->name('portal_atencion_consulta_abrir');  // para abrir una consulta que ya fue abierta
                    Route::post('/consulta/guardar', 'guardarConsulta')->name('portal_atencion_consulta_guardar');  // para guardar datos de la consulta medica, estado sigue en curso
                    Route::post('/consulta/diagnostico', 'registrarDiagnostico')->name('portal_atencion_diagnostico'); // para registrar un diagnostico por parte del medico
                    Route::post('/consulta/diagnostico/eliminar', 'eliminarDiagnostico')->name('portal_atencion_diagnostico_eliminar'); // elimina un diagnostico que fue solicitado
                    Route::post('/consulta/estudio', 'registrarEstudio')->name('portal_atencion_estudio'); // para generar la atencion de un estudio solicitado bajo un estudio y diagnostico con estado=solicitado
                    Route::post('/consulta/estudio/eliminar', 'eliminarEstudio')->name('portal_atencion_estudio_eliminar'); // para eliminar un estudio medico un doctor
                    Route::post('/consulta/finalizar', 'finalizarConsulta')->name('portal_atencion_consulta_finalizar'); // termina la consulta medica con estado a finalizada

                    // fase 3/5: expediente clinico
                    Route::get('/expediente/{id}', 'expediente')->name('portal_atencion_expediente'); //listo, busca su expediente del paciente
                    //verificar el resto de expediente
                    Route::post('/expediente/crear', 'crearExpediente')->name('portal_atencion_expediente_crear');  // si no existe expediente este permite crearlo
                    Route::post('/expediente/perfil', 'guardarPerfil')->name('portal_atencion_perfil'); // donde guardar tu grupo sanguineo entre otros
                    Route::post('/expediente/alergia', 'guardarAlergia')->name('portal_atencion_alergia'); // almaceno las alergias del paciente y lo guardo en su expediente
                    Route::post('/expediente/antecedente', 'guardarAntecedente')->name('portal_atencion_antecedente'); // almacena antecedente del expediente del paciente
                    Route::post('/expediente/medicamento', 'guardarMedicamento')->name('portal_atencion_medicamento'); //almacena  a info de los medicamentos que debe consumir el paciente a lo largo del expediente clinico

                    // fase 5: historial clinico
                    Route::get('/historial/{id}', 'historial')->name('portal_atencion_historial');
                });
            });
        });
    });
});
