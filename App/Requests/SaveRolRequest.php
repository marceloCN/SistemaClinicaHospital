<?php

declare(strict_types=1);

namespace App\Requests;

use Lib\Core\FormRequest;
use Lib\Support\AuthorizationException;
use Lib\Support\Csrf;

class SaveRolRequest extends FormRequest
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
            'claverol' => 'required|min:2|max:50',
            'nombrerol' => 'required|min:3|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'claverol.required' => 'La clave del rol es obligatoria.',
            'claverol.min' => 'La clave del rol es demasiado corta.',
            'nombrerol.required' => 'El nombre del rol es obligatorio.',
            'nombrerol.min' => 'El nombre del rol debe tener al menos 3 caracteres.',
        ];
    }
}
