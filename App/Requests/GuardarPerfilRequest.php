<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Registro/actualización del perfil médico del expediente
 * (grupo sanguíneo, factor RH y observaciones). Es 1:1 con el
 * expediente (PK id_expediente), por lo que el controlador hace upsert.
 */
class GuardarPerfilRequest extends AtencionRequest
{
    /** @var array<string, mixed> */
    private array $datos = [];

    public function rules(): array
    {
        return [
            'id_expediente' => 'required|integer',
            'grupo_sanguineo' => 'required|in:A,B,AB,O',
            'factor_rh' => 'required|in:+,-',
            'observaciones' => 'nullable|max:4000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_expediente.required' => 'El expediente clínico no existe.',
            'id_expediente.integer' => 'El expediente clínico no existe.',
            'grupo_sanguineo.required' => 'Selecciona el grupo sanguíneo.',
            'grupo_sanguineo.in' => 'El grupo sanguíneo no es válido.',
            'factor_rh.required' => 'Selecciona el factor RH.',
            'factor_rh.in' => 'El factor RH no es válido.',
        ];
    }

    protected function validarAdicional(): void
    {
        if ($this->enteroPositivo('id_expediente') <= 0) {
            $this->fallar('id_expediente', 'El expediente clínico no existe.');
        }

        $this->datos = [
            'id_expediente' => $this->enteroPositivo('id_expediente'),
            'grupo_sanguineo' => strtoupper(trim((string) $this->input('grupo_sanguineo', ''))),
            'factor_rh' => trim((string) $this->input('factor_rh', '')),
            'observaciones' => $this->textoNullable('observaciones', 4000),
        ];
    }

    /** @return array<string, mixed> */
    public function datosPerfil(): array
    {
        return $this->datos;
    }
}
