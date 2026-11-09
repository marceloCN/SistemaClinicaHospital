<?php

declare(strict_types=1);

namespace System\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

/**
 * Edición de usuarios del panel (POST /Area-of-work/usuarios/actualizar/{id}).
 * La contraseña es opcional: si viene vacía se conserva la actual.
 */
class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->isPost()) {
            throw new AuthorizationException('Método no permitido.', 405);
        }

        if (!Csrf::validate($this->input('_token'))) {
            throw new AuthorizationException('Token CSRF inválido.');
        }

        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|min:3|max:100',
            'email' => 'required|min:3|max:150',
        ];

        if ($this->filled('password')) {
            $rules['password'] = 'min:6|confirmed';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'Ingresa el usuario o correo.',
            'email.min' => 'El usuario o correo es demasiado corto.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
