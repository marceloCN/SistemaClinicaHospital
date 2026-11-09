<?php

declare(strict_types=1);

namespace Lib\Support;

class ValidationException extends \Exception
{
    /**
     * Campos que nunca se guardan en sesión para repoblar formularios.
     */
    private const DONT_FLASH = [
        'password',
        'password_confirmation',
        '_token',
        '_method',
    ];

    public function __construct(private array $errors)
    {
        parent::__construct('Validation failed.');
    }

    /**
     * Guarda errores (_errors) y valores previos (_old_*) en sesión para las
     * vistas, y devuelve la excepción lista para lanzar.
     *
     * Solo se guarda en sesión cuando $flash es true (peticiones HTML normales);
     * en AJAX/JSON la respuesta ya lleva 'errors' y el panel abre el modal, así
     * que no se contamina la sesión con errores que aparecerían en otra página.
     */
    public static function fromValidator(Validator $validator, array $input = [], bool $flash = true): self
    {
        if ($flash) {
            Session::flash('_errors', $validator->errors());

            foreach ($input as $key => $value) {
                if (in_array($key, self::DONT_FLASH, true)) {
                    continue;
                }

                Session::flash('_old_' . $key, $value);
            }
        }

        return new self($validator->errors());
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
