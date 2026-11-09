<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Solicitud de un estudio complementario dentro de una consulta médica.
 *
 * El estudio nace en estado SOLICITADO (default de la BD) y puede
 * vincularse opcionalmente con uno de los diagnósticos de la consulta.
 */
class EstudioRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_cita' => 'required|integer',
            'id_tipo_estudio' => 'required|integer',
            'id_diagnostico' => 'nullable|integer',
            'indicacion' => 'required',
            'observaciones' => 'nullable|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cita.required' => 'Selecciona la consulta.',
            'id_cita.integer' => 'Selecciona la consulta.',
            'id_tipo_estudio.required' => 'Selecciona el tipo de estudio.',
            'id_tipo_estudio.integer' => 'Selecciona el tipo de estudio.',
            'indicacion.required' => 'Indica la justificación del estudio.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idCita() <= 0) {
            $this->fallar('id_cita', 'Selecciona la consulta.');
        }

        if ($this->enteroPositivo('id_tipo_estudio') <= 0) {
            $this->fallar('id_tipo_estudio', 'Selecciona el tipo de estudio.');
        }

        $indicacion = $this->textoRequerido('indicacion', 2000);

        if ($indicacion === '') {
            $this->fallar('indicacion', 'Indica la justificación del estudio.');
        }

        $this->datos = [
            'id_cita' => $this->idCita(),
            'id_tipo_estudio' => $this->enteroPositivo('id_tipo_estudio'),
            'id_diagnostico' => $this->enteroPositivo('id_diagnostico') ?: null,
            'indicacion' => $indicacion,
            'observaciones' => $this->textoNullable('observaciones', 2000),
        ];
    }

    /** @return array<string, mixed> */
    public function datosEstudio(): array
    {
        return $this->datos;
    }
}
