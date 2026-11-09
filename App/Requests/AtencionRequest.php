<?php

declare(strict_types=1);

namespace App\Requests;

/**
 * Base de las peticiones POST del módulo de atención integral
 * (evaluación previa, consulta médica y expediente clínico).
 *
 * Hereda de CitasRequest la autorización (POST + token CSRF) y ofrece
 * helpers comunes para normalizar los identificadores que viajan en el
 * cuerpo de la petición. La existencia de la cita, su estado y las
 * transiciones se comprueban en el controlador dentro de la transacción.
 */
abstract class AtencionRequest extends CitasRequest
{
    /**
     * ID de la cita normalizado (0 si no es un entero positivo).
     */
    protected function idCita(): int
    {
        return $this->enteroPositivo('id_cita');
    }

    /**
     * ID de la persona-paciente normalizado (0 si no es un entero positivo).
     */
    protected function idPaciente(): int
    {
        return $this->enteroPositivo('id_paciente');
    }

    /**
     * ID de la consulta médica normalizado.
     */
    protected function idConsulta(): int
    {
        return $this->enteroPositivo('id_consulta');
    }

    /**
     * Texto recortado: vacío → null (para columnas TEXT opcionales).
     */
    protected function textoNullable(string $campo, int $max = 2000): ?string
    {
        $valor = trim((string) $this->input($campo, ''));

        if ($valor === '') {
            return null;
        }

        return mb_substr($valor, 0, $max);
    }

    /**
     * Texto obligatorio normalizado (recortado al máximo indicado).
     */
    protected function textoRequerido(string $campo, int $max = 2000): string
    {
        return mb_substr(trim((string) $this->input($campo, '')), 0, $max);
    }

    /**
     * Entero opcional dentro de un rango; devuelve null si está vacío.
     */
    protected function enteroRango(string $campo, int $min, int $max): ?int
    {
        $crudo = trim((string) $this->input($campo, ''));

        if ($crudo === '') {
            return null;
        }

        $valor = (int) $crudo;

        if ($valor < $min || $valor > $max) {
            $this->fallar($campo, "El valor debe estar entre {$min} y {$max}.");
        }

        return $valor;
    }

    /**
     * Decimal opcional dentro de un rango; devuelve null si está vacío.
     */
    protected function decimalRango(string $campo, float $min, float $max, int $decimales = 1): ?float
    {
        $crudo = str_replace(',', '.', trim((string) $this->input($campo, '')));

        if ($crudo === '') {
            return null;
        }

        if (!is_numeric($crudo)) {
            $this->fallar($campo, 'El valor numérico no es válido.');
        }

        $valor = round((float) $crudo, $decimales);

        if ($valor < $min || $valor > $max) {
            $this->fallar($campo, 'El valor debe estar entre ' . $min . ' y ' . $max . '.');
        }

        return $valor;
    }
}
