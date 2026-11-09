<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

final class LoginRequest extends FormRequest
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
        return [
            'usuario' => 'required|min:3',
            'password' => 'required|min:4',
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'Ingresa tu usuario o correo.',
            'password.required' => 'Ingresa tu contraseña.',
        ];
    }
}
