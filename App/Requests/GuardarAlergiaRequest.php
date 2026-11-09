<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Registro de una alergia conocida del paciente dentro de su
 * expediente clínico.
 */
class GuardarAlergiaRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_expediente' => 'required|integer',
            'sustancia' => 'required',
            'tipo' => 'required|in:MEDICAMENTO,ALIMENTO,AMBIENTAL,LATEX,OTRO',
            'gravedad' => 'required|in:LEVE,MODERADA,GRAVE,DESCONOCIDA',
            'reaccion' => 'nullable|max:1000',
            'observaciones' => 'nullable|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_expediente.required' => 'El expediente clínico no existe.',
            'id_expediente.integer' => 'El expediente clínico no existe.',
            'sustancia.required' => 'Indica la sustancia que provoca la alergia.',
            'tipo.required' => 'Selecciona el tipo de alergia.',
            'tipo.in' => 'El tipo de alergia no es válido.',
            'gravedad.required' => 'Selecciona la gravedad.',
            'gravedad.in' => 'La gravedad no es válida.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->enteroPositivo('id_expediente') <= 0) {
            $this->fallar('id_expediente', 'El expediente clínico no existe.');
        }

        $sustancia = $this->textoRequerido('sustancia', 150);

        if ($sustancia === '') {
            $this->fallar('sustancia', 'Indica la sustancia que provoca la alergia.');
        }

        $this->datos = [
            'id_expediente' => $this->enteroPositivo('id_expediente'),
            'sustancia' => $sustancia,
            'tipo' => strtoupper(trim((string) $this->input('tipo', ''))),
            'gravedad' => strtoupper(trim((string) $this->input('gravedad', ''))),
            'reaccion' => $this->textoNullable('reaccion', 1000),
            'observaciones' => $this->textoNullable('observaciones', 2000),
        ];
    }

    /** @return array<string, mixed> */
    public function datosAlergia(): array
    {
        return $this->datos;
    }
}
