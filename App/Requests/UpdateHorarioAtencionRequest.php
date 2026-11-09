<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Validación de la edición de un horario de atención.
 *
 * La vista reutiliza el mismo modal que el alta (mismos campos y payload),
 * así que hereda todas las reglas, mensajes y normalizaciones de
 * StoreHorarioAtencionRequest. Si algún día la edición diverge del alta,
 * basta con sobreescribir aquí rules(), messages() o validarAdicional().
 */
class UpdateHorarioAtencionRequest extends StoreHorarioAtencionRequest
{
}
