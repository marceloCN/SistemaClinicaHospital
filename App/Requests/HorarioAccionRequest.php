<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Peticiones POST del módulo de horarios que no llevan datos, solo
 * sesión + CSRF: alternar el estado de un horario (destroy: baja o
 * reactivación) y eliminar las fichas restantes.
 *
 * La existencia del horario, su vigencia y sus citas asociadas se
 * comprueban después, en el controlador.
 */
class HorarioAccionRequest extends HorarioAtencionRequest
{
}
