<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Creación del expediente clínico general de un paciente (si aún no
 * existe). El expediente es 1:1 con el paciente (UNIQUE id_paciente).
 */
class CrearExpedienteRequest extends AtencionRequest
{
    /** @var array{id_paciente: int, observaciones_generales: ?string} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_paciente' => 'required|integer',
            'observaciones_generales' => 'nullable|max:4000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Selecciona el paciente.',
            'id_paciente.integer' => 'Selecciona el paciente.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idPaciente() <= 0) {
            $this->fallar('id_paciente', 'Selecciona el paciente.');
        }

        $this->datos = [
            'id_paciente' => $this->idPaciente(),
            'observaciones_generales' => $this->textoNullable('observaciones_generales', 4000),
        ];
    }

    /** @return array{id_paciente: int, observaciones_generales: ?string} */
    public function datosExpediente(): array
    {
        return $this->datos;
    }
}
