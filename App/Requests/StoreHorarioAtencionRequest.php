<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Validación del formulario de horario de atención (alta).
 *
 * La vista del módulo envía el mismo payload en alta y en edición (mismo
 * modal), por lo que UpdateHorarioAtencionRequest hereda estas reglas.
 *
 * Campos que llegan: id_servicio_medico, dia_semana (1-7), hora_inicio,
 * hora_fin, duracion_ficha, fecha_desde y fecha_hasta (opcional). El estado
 * no se valida ni se envía: el alta siempre crea el horario activo (lo
 * fuerza el controlador).
 */
class StoreHorarioAtencionRequest extends HorarioAtencionRequest
{
    /** @var array<string, mixed> Datos ya validados y normalizados. */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_servicio_medico' => 'required|integer',
            'dia_semana' => 'required|in:1,2,3,4,5,6,7',
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'duracion_ficha' => 'required|integer',
            'fecha_desde' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id_servicio_medico.required' => 'Selecciona el servicio médico.',
            'id_servicio_medico.integer' => 'Selecciona el servicio médico.',
            'dia_semana.required' => 'Selecciona el día de la semana.',
            'dia_semana.in' => 'Selecciona el día de la semana.',
            'hora_inicio.required' => 'Indica la hora de inicio y la hora de fin.',
            'hora_fin.required' => 'Indica la hora de inicio y la hora de fin.',
            'duracion_ficha.required' => 'La duración de la ficha debe ser mayor a 0 minutos.',
            'duracion_ficha.integer' => 'La duración de la ficha debe ser un número entero mayor a 0 minutos.',
            'fecha_desde.required' => 'La fecha de inicio de vigencia es obligatoria.',
        ];
    }

    /**
     * Formatos de hora/fecha, rangos y coherencia entre campos (el
     * Validator base no tiene reglas de fecha/hora ni comparaciones).
     */
    protected function validarAdicional(): void
    {
        if ((int) $this->input('id_servicio_medico', 0) <= 0) {
            $this->fallar('id_servicio_medico', 'Selecciona el servicio médico.');
        }

        $horaInicio = $this->normalizarHora((string) $this->input('hora_inicio', ''));

        if ($horaInicio === null) {
            $this->fallar('hora_inicio', 'La hora de inicio no es válida (formato HH:MM).');
        }

        $horaFin = $this->normalizarHora((string) $this->input('hora_fin', ''));

        if ($horaFin === null) {
            $this->fallar('hora_fin', 'La hora de fin no es válida (formato HH:MM).');
        }

        if ($horaFin <= $horaInicio) {
            $this->fallar('hora_fin', 'La hora de fin debe ser posterior a la de inicio.');
        }

        if ((int) $this->input('duracion_ficha', 0) <= 0) {
            $this->fallar('duracion_ficha', 'La duración de la ficha debe ser mayor a 0 minutos.');
        }

        $fechaDesde = $this->normalizarFecha((string) $this->input('fecha_desde', ''));

        if ($fechaDesde === null) {
            $this->fallar('fecha_desde', 'La fecha de inicio de vigencia no es válida.');
        }

        $fechaHastaCruda = trim((string) $this->input('fecha_hasta', ''));
        $fechaHasta = $fechaHastaCruda === '' ? null : $this->normalizarFecha($fechaHastaCruda);

        if ($fechaHastaCruda !== '' && $fechaHasta === null) {
            $this->fallar('fecha_hasta', 'La fecha de fin de vigencia no es válida.');
        }

        if ($fechaHasta !== null && $fechaHasta < $fechaDesde) {
            $this->fallar('fecha_hasta', 'La fecha de fin de vigencia no puede ser anterior a la de inicio.');
        }

        $this->datos = [
            'id_servicio_medico' => (int) $this->input('id_servicio_medico', 0),
            'dia_semana' => (int) $this->input('dia_semana', 0),
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'duracion_ficha' => (int) $this->input('duracion_ficha', 0),
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];
    }

    /**
     * Datos del horario validados y normalizados:
     * horas en 'H:i:s', fechas en 'Y-m-d' y números como int
     * (fecha_hasta es null cuando el campo viene vacío).
     *
     * @return array{
     *     id_servicio_medico: int,
     *     dia_semana: int,
     *     hora_inicio: string,
     *     hora_fin: string,
     *     duracion_ficha: int,
     *     fecha_desde: string,
     *     fecha_hasta: string|null
     * }
     */
    public function datosHorario(): array
    {
        return $this->datos;
    }
}
