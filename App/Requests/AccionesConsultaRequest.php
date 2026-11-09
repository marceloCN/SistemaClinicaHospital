<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Acciones sobre la consulta médica de una cita: apertura y finalización.
 *
 * Solo viaja id_cita; la recepción, la pertenencia al médico y los datos
 * obligatorios para finalizar se comprueban en el controlador dentro de
 * la transacción.
 */
class AccionesConsultaRequest extends AtencionRequest
{
    /** @var array{id_cita: int} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_cita' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cita.required' => 'Selecciona la cita de la consulta.',
            'id_cita.integer' => 'Selecciona la cita de la consulta.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idCita() <= 0) {
            $this->fallar('id_cita', 'Selecciona la cita de la consulta.');
        }

        $this->datos = ['id_cita' => $this->idCita()];
    }

    /** @return array{id_cita: int} */
    public function datosAccion(): array
    {
        return $this->datos;
    }
}
