<?php

declare(strict_types=1);

namespace Lib\Core;

use Lib\Support\AuthorizationException;
use Lib\Support\ValidationException;
use Lib\Support\Validator;

/**
 * Validación por request estilo Laravel.
 *
 * Extiende Request (mismos input/all/isPost/...). El router la detecta por el
 * type-hint del método del controlador, la instancia con la petición real,
 * ejecuta authorize() y rules() antes de ejecutar el método, y expone los
 * datos limpios con validated().
 */
abstract class FormRequest extends Request
{
    private array $validatedData = [];

    /**
     * Copia el estado de la petición real dentro de esta clase.
     */
    public static function createFrom(Request $request): static
    {
        $instance = new static();

        $instance->query = $request->query;
        $instance->body = $request->body;
        $instance->cookies = $request->cookies;
        $instance->server = $request->server;
        $instance->files = $request->files;
        $instance->json = $request->json;

        return $instance;
    }

    /**
     * ¿La petición está autorizada? Devuelve false (403) o lanza
     * AuthorizationException con un mensaje/status propios.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación ('campo' => 'required|min:3').
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Mensajes personalizados ('campo.regla' => 'mensaje').
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Ejecuta authorize() + Validator. La llama el router antes del método.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function validateResolved(): void
    {
        if (!$this->authorize()) {
            throw new AuthorizationException();
        }

        $validator = Validator::make($this->all(), $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw ValidationException::fromValidator($validator, $this->all(), !$this->isAjax());
        }

        $this->validatedData = $validator->validated();
    }

    /**
     * Datos validados: solo las claves que tienen regla.
     */
    public function validated(): array
    {
        return $this->validatedData;
    }
}
