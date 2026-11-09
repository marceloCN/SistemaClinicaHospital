<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

/**
 * Validación de la actualización de los datos personales del perfil.
 *
 * El nombre, el apellido, el teléfono y la dirección se validan siempre. El
 * bloque de contraseña solo se valida cuando el usuario envía una contraseña
 * nueva: en ese caso se exige también la contraseña actual y la confirmación
 * de la nueva.
 *
 * Los datos de acceso (usuario y correo) se guardan por separado con
 * UpdateAccesoPerfilRequest.
 */
final class UpdatePerfilRequest extends FormRequest
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
        $rules = [
            'nombre' => 'required|min:2|max:100',
            'apellido' => 'required|min:2|max:100',
            'correo' => 'required|email|max:150',
            'telefono' => 'max:15',
            'direccion' => 'max:255',
        ];

        // Solo se valida la contraseña cuando se quiere cambiar.
        if (trim((string) $this->input('password', '')) !== '') {
            $rules['password_actual'] = 'required';
            $rules['password'] = 'required|min:8|confirmed';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre es demasiado largo.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.max' => 'El apellido es demasiado largo.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingresa un correo válido.',
            'correo.max' => 'El correo es demasiado largo.',
            'telefono.max' => 'El teléfono no puede superar los 15 caracteres.',
            'direccion.max' => 'La dirección es demasiado larga.',
            'password_actual.required' => 'Ingresa tu contraseña actual para cambiar la contraseña.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
