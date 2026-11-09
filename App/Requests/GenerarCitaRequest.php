<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Validación de la generación de una cita médica por el paciente
 * (paso final del asistente de 3 pasos).
 *
 * Campos: id_ficha_agenda (slot elegido) y motivo_consulta. La
 * disponibilidad real de la ficha, su vigencia y la ausencia de
 * duplicidad se comprueban en el controlador dentro de la transacción.
 */
class GenerarCitaRequest extends CitasRequest
{
    /** @var array{id_ficha_agenda: int, motivo_consulta: string} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_ficha_agenda' => 'required|integer',
            'motivo_consulta' => 'required',
            'id_paciente' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'id_ficha_agenda.required' => 'Selecciona la fecha y hora de la cita.',
            'id_ficha_agenda.integer' => 'Selecciona la fecha y hora de la cita.',
            'motivo_consulta.required' => 'Describe el motivo de la consulta.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->enteroPositivo('id_ficha_agenda') <= 0) {
            $this->fallar('id_ficha_agenda', 'Selecciona la fecha y hora de la cita.');
        }

        $motivo = trim((string) $this->input('motivo_consulta', ''));

        if ($motivo === '') {
            $this->fallar('motivo_consulta', 'Describe el motivo de la consulta.');
        }

        if (mb_strlen($motivo) > 1000) {
            $this->fallar('motivo_consulta', 'El motivo de la consulta no puede superar los 1000 caracteres.');
        }

        $this->datos = [
            'id_ficha_agenda' => $this->enteroPositivo('id_ficha_agenda'),
            'motivo_consulta' => $motivo,
            'id_paciente' => $this->enteroPositivo('id_paciente'),
        ];
    }

    /**
     * Datos validados y normalizados.
     *
     * @return array{id_ficha_agenda: int, motivo_consulta: string, id_paciente: int}
     */
    public function datosCita(): array
    {
        return $this->datos;
    }
}
