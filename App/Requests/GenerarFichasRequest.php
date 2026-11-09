<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Validación de la generación de fichas de agenda de un horario.
 *
 * La vista envía dos campos: fecha_inicio y fecha_fin. Además del formato
 * se comprueban el orden y el tamaño máximo del rango (90 días) para
 * evitar generaciones masivas por accidente.
 */
class GenerarFichasRequest extends HorarioAtencionRequest
{
    /** @var array{fecha_inicio: string, fecha_fin: string} Rango normalizado. */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'fecha_inicio' => 'required',
            'fecha_fin' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'Indica la fecha de inicio y la fecha de fin.',
            'fecha_fin.required' => 'Indica la fecha de inicio y la fecha de fin.',
        ];
    }

    protected function validarAdicional(): void
    {
        $fechaInicio = $this->normalizarFecha((string) $this->input('fecha_inicio', ''));

        if ($fechaInicio === null) {
            $this->fallar('fecha_inicio', 'La fecha de inicio no es válida.');
        }

        $fechaFin = $this->normalizarFecha((string) $this->input('fecha_fin', ''));

        if ($fechaFin === null) {
            $this->fallar('fecha_fin', 'La fecha de fin no es válida.');
        }

        if ($fechaFin < $fechaInicio) {
            $this->fallar('fecha_fin', 'La fecha de fin no puede ser anterior a la de inicio.');
        }

        $diasRango = (int) (new \DateTime($fechaInicio))->diff(new \DateTime($fechaFin))->days + 1;

        if ($diasRango > 90) {
            $this->fallar('fecha_fin', 'El rango no puede superar los 90 días.');
        }

        $this->datos = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];
    }

    /**
     * Rango de fechas validado y normalizado a 'Y-m-d'.
     *
     * @return array{fecha_inicio: string, fecha_fin: string}
     */
    public function datosFichas(): array
    {
        return $this->datos;
    }
}
