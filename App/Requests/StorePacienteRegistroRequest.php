<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

/**
 * Validación del registro público de un nuevo usuario paciente.
 *
 * Comprueba el método y el token CSRF; las reglas cubren los datos de la
 * persona (cedula, nombres, contacto) y los de la cuenta de usuario
 * (usuario, correo y contraseña).
 */
class StorePacienteRegistroRequest extends FormRequest
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
            'ci' => 'required|min:5|max:15',
            'nombre' => 'required|min:2|max:100',
            'apellido' => 'required|min:2|max:100',
            'telefono' => 'max:15',
            'direccion' => 'max:255',
            'sexo' => 'in:MASCULINO,FEMENINO',
            'usuario' => 'required|min:3|max:50',
            'correo' => 'required|email|max:150',
            'password' => 'required|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required' => 'La cédula de identidad es obligatoria.',
            'ci.min' => 'La cédula de identidad es demasiado corta.',
            'ci.max' => 'La cédula de identidad es demasiado larga.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre es demasiado largo.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.max' => 'El apellido es demasiado largo.',
            'telefono.max' => 'El teléfono no puede superar los 15 caracteres.',
            'direccion.max' => 'La dirección es demasiado larga.',
            'sexo.in' => 'El sexo seleccionado no es válido.',
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'usuario.max' => 'El nombre de usuario es demasiado largo.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingresa un correo válido.',
            'correo.max' => 'El correo es demasiado largo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
