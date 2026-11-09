<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Validación del registro de una cita médica en la recepción: se
 * reserva para un paciente elegido en el buscador, sobre la misma
 * lógica de ficha que el asistente del paciente.
 *
 * Campos: id_paciente, id_ficha_agenda y motivo_consulta.
 */
class RegistrarCitaRequest extends CitasRequest
{
    /** @var array{id_paciente: int, id_ficha_agenda: int, motivo_consulta: string} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_paciente' => 'required|integer',
            'id_ficha_agenda' => 'required|integer',
            'motivo_consulta' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Selecciona al paciente.',
            'id_paciente.integer' => 'Selecciona al paciente.',
            'id_ficha_agenda.required' => 'Selecciona la fecha y hora de la cita.',
            'id_ficha_agenda.integer' => 'Selecciona la fecha y hora de la cita.',
            'motivo_consulta.required' => 'Describe el motivo de la consulta.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->enteroPositivo('id_paciente') <= 0) {
            $this->fallar('id_paciente', 'Selecciona al paciente.');
        }

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
            'id_paciente' => $this->enteroPositivo('id_paciente'),
            'id_ficha_agenda' => $this->enteroPositivo('id_ficha_agenda'),
            'motivo_consulta' => $motivo,
        ];
    }

    /**
     * Datos validados y normalizados.
     *
     * @return array{id_paciente: int, id_ficha_agenda: int, motivo_consulta: string}
     */
    public function datosRegistro(): array
    {
        return $this->datos;
    }
}
