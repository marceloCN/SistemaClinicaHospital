<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Finalización de la evaluación previa: signos vitales y datos
 * preliminares que el personal de recepción/enfermería completa antes
 * de habilitar al paciente para consulta.
 *
 * Los signos son opcionales pero, si se envían, deben estar dentro de
 * rangos fisiológicos coherentes (los CHECK de la BD solo exigen > 0).
 */
class GuardarEvaluacionRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_cita' => 'required|integer',
            'motivo_referido' => 'nullable|max:500',
            'anamnesis_preliminar' => 'nullable|max:4000',
            'presion_sistolica' => 'nullable|integer',
            'presion_diastolica' => 'nullable|integer',
            'frecuencia_cardiaca' => 'nullable|integer',
            'frecuencia_respiratoria' => 'nullable|integer',
            'temperatura' => 'nullable|numeric',
            'saturacion_oxigeno' => 'nullable|numeric',
            'peso_kg' => 'nullable|numeric',
            'talla_cm' => 'nullable|numeric',
            'observaciones' => 'nullable|max:4000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cita.required' => 'Selecciona el paciente a evaluar.',
            'id_cita.integer' => 'Selecciona el paciente a evaluar.',
            'presion_sistolica.integer' => 'La presión sistólica debe ser un número entero.',
            'presion_diastolica.integer' => 'La presión diastólica debe ser un número entero.',
            'frecuencia_cardiaca.integer' => 'La frecuencia cardiaca debe ser un número entero.',
            'frecuencia_respiratoria.integer' => 'La frecuencia respiratoria debe ser un número entero.',
            'temperatura.numeric' => 'La temperatura debe ser un número.',
            'saturacion_oxigeno.numeric' => 'La saturación de oxígeno debe ser un número.',
            'peso_kg.numeric' => 'El peso debe ser un número.',
            'talla_cm.numeric' => 'La talla debe ser un número.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->idCita() <= 0) {
            $this->fallar('id_cita', 'Selecciona el paciente a evaluar.');
        }

        $presionSistolica = $this->enteroRango('presion_sistolica', 30, 300);
        $presionDiastolica = $this->enteroRango('presion_diastolica', 10, 250);

        if ($presionSistolica !== null && $presionDiastolica !== null && $presionSistolica <= $presionDiastolica) {
            $this->fallar('presion_sistolica', 'La presión sistólica debe ser mayor que la diastólica.');
        }

        $this->datos = [
            'id_cita' => $this->idCita(),
            'motivo_referido' => $this->textoNullable('motivo_referido', 500),
            'anamnesis_preliminar' => $this->textoNullable('anamnesis_preliminar', 4000),
            'presion_sistolica' => $presionSistolica,
            'presion_diastolica' => $presionDiastolica,
            'frecuencia_cardiaca' => $this->enteroRango('frecuencia_cardiaca', 20, 250),
            'frecuencia_respiratoria' => $this->enteroRango('frecuencia_respiratoria', 5, 100),
            'temperatura' => $this->decimalRango('temperatura', 30, 45, 1),
            'saturacion_oxigeno' => $this->decimalRango('saturacion_oxigeno', 0, 100, 2),
            'peso_kg' => $this->decimalRango('peso_kg', 0.1, 500, 2),
            'talla_cm' => $this->decimalRango('talla_cm', 30, 250, 2),
            'observaciones' => $this->textoNullable('observaciones', 4000),
        ];
    }

    /** @return array<string, mixed> */
    public function datosEvaluacion(): array
    {
        return $this->datos;
    }
}
