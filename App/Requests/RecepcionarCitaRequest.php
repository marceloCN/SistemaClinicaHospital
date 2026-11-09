<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Validación de la recepción (check-in) de una cita por su código.
 *
 * La existencia de la cita, su estado y que aún no esté recepcionada
 * se comprueban en el controlador dentro de la transacción.
 */
class RecepcionarCitaRequest extends CitasRequest
{
    /** @var array{codigo: string} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'codigo' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código de la cita.',
        ];
    }

    protected function validarAdicional(): void
    {
        $codigo = trim((string) $this->input('codigo', ''));

        if ($codigo === '') {
            $this->fallar('codigo', 'Ingresa el código de la cita.');
        }

        if (!preg_match('/^[A-Za-z0-9_-]{4,20}$/', $codigo)) {
            $this->fallar('codigo', 'El código de la cita no es válido.');
        }

        $this->datos = ['codigo' => strtoupper($codigo)];
    }

    /**
     * Código normalizado (en mayúsculas).
     *
     * @return array{codigo: string}
     */
    public function datosRecepcion(): array
    {
        return $this->datos;
    }
}
