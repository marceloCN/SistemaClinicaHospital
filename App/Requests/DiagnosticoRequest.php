<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Registro de un diagnóstico (principal o secundario) de una consulta
 * médica en curso.
 */
class DiagnosticoRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_cita' => 'required|integer',
            'descripcion' => 'required',
            'tipo' => 'required|in:PRINCIPAL,SECUNDARIO',
            'observaciones' => 'nullable|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cita.required' => 'Selecciona la consulta.',
            'id_cita.integer' => 'Selecciona la consulta.',
            'descripcion.required' => 'Describe el diagnóstico.',
            'tipo.required' => 'Selecciona el tipo de diagnóstico.',
            'tipo.in' => 'El tipo de diagnóstico no es válido.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idCita() <= 0) {
            $this->fallar('id_cita', 'Selecciona la consulta.');
        }

        $descripcion = $this->textoRequerido('descripcion', 1000);

        if ($descripcion === '') {
            $this->fallar('descripcion', 'Describe el diagnóstico.');
        }

        $this->datos = [
            'id_cita' => $this->idCita(),
            'descripcion' => $descripcion,
            'tipo' => strtoupper(trim((string) $this->input('tipo', 'PRINCIPAL'))),
            'observaciones' => $this->textoNullable('observaciones', 2000),
        ];
    }

    /** @return array<string, mixed> */
    public function datosDiagnostico(): array
    {
        return $this->datos;
    }
}
