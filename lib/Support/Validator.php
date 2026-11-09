<?php

declare(strict_types=1);

namespace Lib\Support;

class Validator
{
    private array $data;
    private array $rules;
    private array $messages;
    private array $errors = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $this->data[$field] ?? null;

            // Una regla 'nullable' permite que el campo venga vacío; el resto
            // de reglas solo se aplica cuando el valor no está vacío.
            $isEmpty = $value === null || $value === '';

            if ($isEmpty && in_array('nullable', $rules, true)) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                $this->validateRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(?string $field = null): ?string
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $errors) {
            return $errors[0] ?? null;
        }

        return null;
    }

    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }

    private function validateRule(string $field, mixed $value, string $rule): void
    {
        [$ruleName, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        $isEmpty = $value === null || $value === '';

        switch ($ruleName) {
            case 'required':
                if ($isEmpty) {
                    $this->addError($field, 'required', 'El campo es obligatorio.');
                }
                break;

            case 'email':
                if (!$isEmpty && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email', 'Debe ser un correo válido.');
                }
                break;

            case 'min':
                if (!$isEmpty && mb_strlen((string) $value) < (int) $parameter) {
                    $this->addError($field, 'min', "Debe tener al menos {$parameter} caracteres.");
                }
                break;

            case 'max':
                if (!$isEmpty && mb_strlen((string) $value) > (int) $parameter) {
                    $this->addError($field, 'max', "Debe tener como máximo {$parameter} caracteres.");
                }
                break;

            case 'numeric':
                if (!$isEmpty && !is_numeric($value)) {
                    $this->addError($field, 'numeric', 'Debe ser un valor numérico.');
                }
                break;

            case 'integer':
                if (!$isEmpty && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, 'integer', 'Debe ser un número entero.');
                }
                break;

            case 'in':
                $allowed = explode(',', $parameter ?? '');
                if (!$isEmpty && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, 'in', 'El valor seleccionado no es válido.');
                }
                break;

            case 'confirmed':
                $confirmation = $this->data[$field . '_confirmation'] ?? null;
                if ($value !== $confirmation) {
                    $this->addError($field, 'confirmed', 'Las contraseñas no coinciden.');
                }
                break;

            case 'date':
                if (!$isEmpty) {
                    $date = \DateTime::createFromFormat('!Y-m-d', (string) $value);

                    if ($date === false || $date->format('Y-m-d') !== (string) $value) {
                        $this->addError($field, 'date', 'La fecha no es válida.');
                    }
                }
                break;

            default:
                // Un error tipográfico en una regla no debe pasar en silencio.
                throw new \InvalidArgumentException(
                    "Regla de validación desconocida: '{$ruleName}' (campo '{$field}')."
                );
        }
    }

    private function addError(string $field, string $ruleName, string $message): void
    {
        $this->errors[$field][] = $this->messages[$field . '.' . $ruleName] ?? $message;
    }
}
