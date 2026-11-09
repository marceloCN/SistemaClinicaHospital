<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Paciente que no requiere evaluación previa: deja la recepción en
 * LISTO_PARA_CONSULTA y, si ya existe un registro de evaluación previa,
 * lo anula dejando constancia del motivo en observaciones.
 */
class SinEvaluacionRequest extends AtencionRequest
{
    /** @var array{id_cita: int, motivo: string} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_cita' => 'required|integer',
            'motivo' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cita.required' => 'Selecciona el paciente.',
            'id_cita.integer' => 'Selecciona el paciente.',
            'motivo.required' => 'Indica el motivo por el que no se realiza la evaluación previa.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idCita() <= 0) {
            $this->fallar('id_cita', 'Selecciona el paciente.');
        }

        $motivo = $this->textoRequerido('motivo', 500);

        if ($motivo === '') {
            $this->fallar('motivo', 'Indica el motivo por el que no se realiza la evaluación previa.');
        }

        $this->datos = [
            'id_cita' => $this->idCita(),
            'motivo' => $motivo,
        ];
    }

    /** @return array{id_cita: int, motivo: string} */
    public function datosDecision(): array
    {
        return $this->datos;
    }
}
