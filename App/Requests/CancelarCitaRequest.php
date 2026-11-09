<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Petición de cancelación de una cita por parte del paciente.
 *
 * Solo sesión + CSRF: la pertenencia de la cita al paciente, su estado
 * y la reapertura de la ficha se comprueban en el controlador.
 */
class CancelarCitaRequest extends CitasRequest
{
}
