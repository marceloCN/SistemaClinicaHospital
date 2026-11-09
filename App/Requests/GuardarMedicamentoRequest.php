<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Registro de un medicamento que el paciente utiliza o tiene asociado,
 * dentro de su expediente clínico.
 */
class GuardarMedicamentoRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_expediente' => 'required|integer',
            'nombre_medicamento' => 'required',
            'dosis' => 'nullable|max:100',
            'frecuencia' => 'nullable|max:150',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'prescrito_por' => 'nullable|max:150',
            'observaciones' => 'nullable|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_expediente.required' => 'El expediente clínico no existe.',
            'id_expediente.integer' => 'El expediente clínico no existe.',
            'nombre_medicamento.required' => 'Indica el nombre del medicamento.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->enteroPositivo('id_expediente') <= 0) {
            $this->fallar('id_expediente', 'El expediente clínico no existe.');
        }

        $nombre = $this->textoRequerido('nombre_medicamento', 150);

        if ($nombre === '') {
            $this->fallar('nombre_medicamento', 'Indica el nombre del medicamento.');
        }

        $fechaInicio = trim((string) $this->input('fecha_inicio', ''));
        $fechaFin = trim((string) $this->input('fecha_fin', ''));

        if ($fechaInicio !== '' && $fechaFin !== '' && $fechaFin < $fechaInicio) {
            $this->fallar('fecha_fin', 'La fecha de fin no puede ser anterior a la fecha de inicio.');
        }

        $this->datos = [
            'id_expediente' => $this->enteroPositivo('id_expediente'),
            'nombre_medicamento' => $nombre,
            'dosis' => $this->textoNullable('dosis', 100),
            'frecuencia' => $this->textoNullable('frecuencia', 150),
            'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
            'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
            'prescrito_por' => $this->textoNullable('prescrito_por', 150),
            'observaciones' => $this->textoNullable('observaciones', 2000),
        ];
    }

    /** @return array<string, mixed> */
    public function datosMedicamento(): array
    {
        return $this->datos;
    }
}
