<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Inicio de la evaluación previa de una cita ya recepcionada.
 *
 * Solo se envía id_cita; la recepción debe estar en EN_ESPERA y no
 * puede existir otra evaluación previa para la misma cita (se comprueba
 * en el controlador dentro de la transacción).
 */
class IniciarEvaluacionRequest extends AtencionRequest
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
            'id_cita.required' => 'Selecciona el paciente a evaluar.',
            'id_cita.integer' => 'Selecciona el paciente a evaluar.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idCita() <= 0) {
            $this->fallar('id_cita', 'Selecciona el paciente a evaluar.');
        }

        $this->datos = ['id_cita' => $this->idCita()];
    }

    /** @return array{id_cita: int} */
    public function datosEvaluacion(): array
    {
        return $this->datos;
    }
}
