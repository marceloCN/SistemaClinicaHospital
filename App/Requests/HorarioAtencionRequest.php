<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;
use Lib\Support\ValidationException;

/**
 * Base de las peticiones POST del módulo de horarios de atención.
 *
 * Centraliza la autorización (método POST + token CSRF, aceptado como campo
 * `_token` o como cabecera `X-CSRF-TOKEN`, que es como lo envía la vista
 * del módulo) y ofrece los normalizadores de hora/fecha junto con un hook
 * para las validaciones propias de cada petición.
 */
abstract class HorarioAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->isPost()) {
            throw new AuthorizationException('Método no permitido.', 405);
        }

        $token = (string) ($this->input('_token') ?: $this->header('X-CSRF-TOKEN', ''));

        if (!Csrf::validate($token)) {
            throw new AuthorizationException('Sesión expirada. Recarga la página.');
        }

        return true;
    }

    /**
     * Ejecuta las validaciones propias de cada petición después de las
     * reglas de `rules()` (el router la llama antes del controlador).
     */
    public function validateResolved(): void
    {
        parent::validateResolved();

        $this->validarAdicional();
    }

    /**
     * Hook para las validaciones que el Validator base no cubre (formatos,
     * rangos y coherencia entre campos).
     */
    protected function validarAdicional(): void
    {
    }

    /**
     * Lanza el error de validación de un campo: el router responde 422 con
     * este mensaje como `message` y el campo en `errors`.
     */
    protected function fallar(string $campo, string $mensaje): never
    {
        throw new ValidationException([$campo => [$mensaje]]);
    }

    /**
     * Normaliza un valor de input time ('08:00' u '08:00:00') a 'H:i:s'.
     * Devuelve null si está vacío o no es una hora válida.
     */
    protected function normalizarHora(string $valor): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        if (!preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', $valor, $coincidencia)) {
            return null;
        }

        $hora = \DateTime::createFromFormat('H:i', $coincidencia[1] . ':' . $coincidencia[2]);

        // createFromFormat acepta desbordes ('25:00' → 01:00, '08:60' →
        // 09:00): se compara el resultado para descartarlos.
        if ($hora === false || $hora->format('H:i') !== $coincidencia[1] . ':' . $coincidencia[2]) {
            return null;
        }

        return $hora->format('H:i:s');
    }

    /**
     * Normaliza un valor de input date ('2026-05-10') a 'Y-m-d'.
     * Devuelve null si está vacío o no es una fecha válida.
     */
    protected function normalizarFecha(string $valor): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $valor, $coincidencia)) {
            return null;
        }

        $fecha = \DateTime::createFromFormat('!Y-m-d', $coincidencia[1]);

        if ($fecha === false || $fecha->format('Y-m-d') !== $coincidencia[1]) {
            return null;
        }

        return $coincidencia[1];
    }
}
