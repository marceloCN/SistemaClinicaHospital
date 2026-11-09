<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

class UpdateUsuarioAppRequest extends FormRequest
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
            'ci' => 'required|min:3|max:15',
            'nombre' => 'required|min:2|max:100',
            'apellido' => 'required|min:2|max:100',
            'telefono' => 'max:15',
            'idrol' => 'required|integer',
            'usuario' => 'required|min:3|max:50',
            'correo' => 'required|email|max:150',
        ];

        if ($this->filled('password')) {
            $rules['password'] = 'min:6|confirmed';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'ci.required' => 'La cédula de identidad es obligatoria.',
            'ci.min' => 'La cédula de identidad es demasiado corta.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 15 caracteres.',
            'idrol.required' => 'Selecciona un rol.',
            'idrol.integer' => 'El rol seleccionado no es válido.',
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingresa un correo válido.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
