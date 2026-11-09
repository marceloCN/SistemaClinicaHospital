<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Validación de la reprogramación de una cita sobre una nueva ficha.
 *
 * Campos: id_cita, id_ficha_nueva y motivo (queda en la tabla
 * reprogramacion_cita). La pertenencia de la cita, el estado de ambas
 * fichas y que ambas pertenezcan al mismo médico se comprueban en el
 * controlador dentro de la transacción.
 */
class ReprogramarCitaRequest extends CitasRequest
{
    /** @var array{id_cita: int, id_ficha_nueva: int, motivo: string} */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_cita' => 'required|integer',
            'id_ficha_nueva' => 'required|integer',
            'motivo' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cita.required' => 'Selecciona la cita a reprogramar.',
            'id_cita.integer' => 'Selecciona la cita a reprogramar.',
            'id_ficha_nueva.required' => 'Selecciona el nuevo turno disponible.',
            'id_ficha_nueva.integer' => 'Selecciona el nuevo turno disponible.',
            'motivo.required' => 'Indica el motivo de la reprogramación.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->enteroPositivo('id_cita') <= 0) {
            $this->fallar('id_cita', 'Selecciona la cita a reprogramar.');
        }

        if ($this->enteroPositivo('id_ficha_nueva') <= 0) {
            $this->fallar('id_ficha_nueva', 'Selecciona el nuevo turno disponible.');
        }

        $motivo = trim((string) $this->input('motivo', ''));

        if ($motivo === '') {
            $this->fallar('motivo', 'Indica el motivo de la reprogramación.');
        }

        if (mb_strlen($motivo) > 250) {
            $this->fallar('motivo', 'El motivo no puede superar los 250 caracteres.');
        }

        $this->datos = [
            'id_cita' => $this->enteroPositivo('id_cita'),
            'id_ficha_nueva' => $this->enteroPositivo('id_ficha_nueva'),
            'motivo' => $motivo,
        ];
    }

    /**
     * Datos validados y normalizados.
     *
     * @return array{id_cita: int, id_ficha_nueva: int, motivo: string}
     */
    public function datosReprogramacion(): array
    {
        return $this->datos;
    }
}
