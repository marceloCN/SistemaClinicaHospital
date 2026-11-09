<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Eliminación de un registro auxiliar de la consulta médica
 * (diagnóstico o estudio solicitado) mediante su ID.
 */
class EliminarRegistroRequest extends AtencionRequest
{
    /** @var array{id: int} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Registro no válido.',
            'id.integer' => 'Registro no válido.',
        ];
    }

    protected function validarAdicional(): void
    {
        $id = $this->enteroPositivo('id');

        if ($id <= 0) {
            $this->fallar('id', 'Registro no válido.');
        }

        $this->datos = ['id' => $id];
    }

    /** @return array{id: int} */
    public function datosRegistro(): array
    {
        return $this->datos;
    }
}
