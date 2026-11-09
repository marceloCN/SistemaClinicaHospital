<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Registro de un antecedente médico relevante del paciente dentro de
 * su expediente clínico.
 */
class GuardarAntecedenteRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_expediente' => 'required|integer',
            'tipo' => 'required|in:ALERGIA,PERSONAL,FAMILIAR,QUIRURGICO,HOSPITALIZACION,ENFERMEDAD_CRONICA,OTRO',
            'descripcion' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id_expediente.required' => 'El expediente clínico no existe.',
            'id_expediente.integer' => 'El expediente clínico no existe.',
            'tipo.required' => 'Selecciona el tipo de antecedente.',
            'tipo.in' => 'El tipo de antecedente no es válido.',
            'descripcion.required' => 'Describe el antecedente médico.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->enteroPositivo('id_expediente') <= 0) {
            $this->fallar('id_expediente', 'El expediente clínico no existe.');
        }

        $descripcion = $this->textoRequerido('descripcion', 2000);

        if ($descripcion === '') {
            $this->fallar('descripcion', 'Describe el antecedente médico.');
        }

        $this->datos = [
            'id_expediente' => $this->enteroPositivo('id_expediente'),
            'tipo' => strtoupper(trim((string) $this->input('tipo', ''))),
            'descripcion' => $descripcion,
        ];
    }

    /** @return array<string, mixed> */
    public function datosAntecedente(): array
    {
        return $this->datos;
    }
}
