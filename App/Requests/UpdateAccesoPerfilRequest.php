<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

/**
 * Validación de los datos de acceso del perfil del portal.
 *
 * Solo se ocupan el nombre de usuario y el correo. La unicidad de ambos se
 * comprueba después, en el controlador, excluyendo la cuenta del propio
 * usuario que está editando su perfil.
 */
final class UpdateAccesoPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->isPost()) {
            throw new AuthorizationException('Método no permitido.', 405);
        }

        if (!Csrf::validate((string) $this->input('_token'))) {
            throw new AuthorizationException('Token CSRF inválido.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => 'required|min:3|max:50',
            'correo' => 'required|email|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'usuario.max' => 'El nombre de usuario es demasiado largo.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingresa un correo válido.',
            'correo.max' => 'El correo es demasiado largo.',
        ];
    }
}
