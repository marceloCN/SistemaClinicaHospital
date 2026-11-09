<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;
use Lib\Support\ValidationException;

/**
 * Base de las peticiones POST de los módulos de citas médicas
 * (paciente) y recepción de citas.
 *
 * Centraliza la autorización (método POST + token CSRF, aceptado como
 * campo `_token` o como cabecera `X-CSRF-TOKEN`, que es como lo envían
 * las vistas) y ofrece el hook de validaciones propias de cada
 * petición.
 */
abstract class CitasRequest extends FormRequest
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
     * Hook para las validaciones que el Validator base no cubre
     * (rangos, formatos y coherencia entre campos).
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
     * Normaliza un entero positivo del input; devuelve 0 si no lo es.
     */
    protected function enteroPositivo(string $campo): int
    {
        $valor = (int) $this->input($campo, 0);

        return $valor > 0 ? $valor : 0;
    }
}
