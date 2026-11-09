<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Registro de la información clínica de una consulta médica en curso
 * (anamnesis, examen físico, evolución, plan de tratamiento y
 * observaciones). El estado lo gestiona el controlador.
 */
class GuardarConsultaRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_cita' => 'required|integer',
            'anamnesis' => 'required',
            'examen_fisico' => 'required',
            'evolucion' => 'nullable|max:8000',
            'plan_tratamiento' => 'required',
            'observaciones' => 'nullable|max:4000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cita.required' => 'Selecciona la consulta.',
            'id_cita.integer' => 'Selecciona la consulta.',
            'anamnesis.required' => 'Registra la anamnesis de la consulta.',
            'examen_fisico.required' => 'Registra el examen físico.',
            'plan_tratamiento.required' => 'Registra el plan de tratamiento.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idCita() <= 0) {
            $this->fallar('id_cita', 'Selecciona la consulta.');
        }

        $this->datos = [
            'id_cita' => $this->idCita(),
            'anamnesis' => $this->textoRequerido('anamnesis', 8000),
            'examen_fisico' => $this->textoRequerido('examen_fisico', 8000),
            'evolucion' => $this->textoNullable('evolucion', 8000),
            'plan_tratamiento' => $this->textoRequerido('plan_tratamiento', 8000),
            'observaciones' => $this->textoNullable('observaciones', 4000),
        ];

        if ($this->datos['anamnesis'] === '') {
            $this->fallar('anamnesis', 'Registra la anamnesis de la consulta.');
        }

        if ($this->datos['examen_fisico'] === '') {
            $this->fallar('examen_fisico', 'Registra el examen físico.');
        }

        if ($this->datos['plan_tratamiento'] === '') {
            $this->fallar('plan_tratamiento', 'Registra el plan de tratamiento.');
        }
    }

    /** @return array<string, mixed> */
    public function datosConsulta(): array
    {
        return $this->datos;
    }
}
