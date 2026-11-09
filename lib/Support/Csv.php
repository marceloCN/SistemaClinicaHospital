<?php

declare(strict_types=1);

namespace Lib\Support;

use DateTimeInterface;
use Lib\Core\Response;

/**
 * Generación de archivos CSV compatibles con Excel.
 *
 * Escribe con separador ";" y BOM UTF-8 para que Excel en español
 * (configuración regional de Latinoamérica) abra el archivo con las
 * columnas y las tildes correctas, sin importaciones manuales.
 */
class Csv
{
    /** BOM UTF-8: evita que Excel muestre tildes y "ñ" como texto corrido. */
    private const BOM = "\xEF\xBB\xBF";

    private const DELIMITADOR = ';';
    private const ENCLOSURE = '"';

    /** '' desactiva el escape propietario de PHP: comillas como "" (RFC 4180). */
    private const ESCAPE = '';

    private const FIN_LINEA = "\r\n";

    /**
     * Genera el contenido CSV (BOM + encabezados + filas) como string.
     *
     * $encabezados puede ser:
     *  - asociativo: [clave => título]; de cada fila se toma $fila[clave].
     *  - lista: ['Título', ...]; cada fila se escribe tal cual, en orden.
     *
     * Opciones:
     *  - bom (bool): incluye el BOM UTF-8 (por defecto true).
     *  - delimitador (string): separador de campos (por defecto ";").
     *
     * @param array<string, string>|list<string> $encabezados
     * @param iterable<array<mixed>> $filas
     * @param array{bom?: bool, delimitador?: string} $opciones
     */
    public static function contenido(array $encabezados, iterable $filas, array $opciones = []): string
    {
        $bom = (bool) ($opciones['bom'] ?? true);
        $delimitador = (string) ($opciones['delimitador'] ?? self::DELIMITADOR);

        $flujo = fopen('php://temp', 'r+b');

        if ($flujo === false) {
            return '';
        }

        if ($bom) {
            fwrite($flujo, self::BOM);
        }

        self::escribirFila($flujo, array_values($encabezados), $delimitador);

        $porClaves = !array_is_list($encabezados);
        $claves = array_keys($encabezados);

        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            self::escribirFila($flujo, self::valores($fila, $porClaves, $claves), $delimitador);
        }

        rewind($flujo);
        $contenido = stream_get_contents($flujo);
        fclose($flujo);

        return $contenido === false ? '' : $contenido;
    }

    /**
     * Devuelve el CSV como descarga (Response con los headers de archivo).
     *
     * @param array<string, string>|list<string> $encabezados
     * @param iterable<array<mixed>> $filas
     * @param array{bom?: bool, delimitador?: string} $opciones
     */
    public static function descargar(string $nombreArchivo, array $encabezados, iterable $filas, array $opciones = []): Response
    {
        return Response::make(self::contenido($encabezados, $filas, $opciones), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . self::nombreSeguro($nombreArchivo) . '"',
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Campos de la fila en el orden de los encabezados.
     *
     * @param array<mixed> $fila
     * @param list<string> $claves
     * @return list<mixed>
     */
    private static function valores(array $fila, bool $porClaves, array $claves): array
    {
        if (!$porClaves) {
            return array_values($fila);
        }

        $valores = [];

        foreach ($claves as $indice => $clave) {
            $valores[] = array_is_list($fila)
                ? ($fila[$indice] ?? '')
                : ($fila[$clave] ?? '');
        }

        return $valores;
    }

    /**
     * Escribe una fila delimitada en el flujo temporal.
     *
     * @param resource $flujo
     * @param list<mixed> $campos
     */
    private static function escribirFila($flujo, array $campos, string $delimitador): void
    {
        fputcsv(
            $flujo,
            array_map(self::normalizar(...), $campos),
            $delimitador,
            self::ENCLOSURE,
            self::ESCAPE,
            self::FIN_LINEA
        );
    }

    /**
     * Valor de celda como string: null vacío, booleanos como Sí/No.
     */
    private static function normalizar(mixed $valor): string
    {
        return match (true) {
            $valor === null => '',
            is_bool($valor) => $valor ? 'Sí' : 'No',
            $valor instanceof DateTimeInterface => $valor->format('d/m/Y H:i'),
            is_scalar($valor) => (string) $valor,
            default => json_encode($valor, JSON_INVALID_UTF8_SUBSTITUTE) ?: '',
        };
    }

    /**
     * Nombre de archivo sin rutas ni caracteres problemáticos para la cabecera.
     */
    private static function nombreSeguro(string $nombre): string
    {
        $nombre = basename(str_replace('\\', '/', trim($nombre)));
        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '', $nombre) ?? '';

        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            $nombre = 'reporte-' . date('Ymd-His');
        }

        if (!str_ends_with(strtolower($nombre), '.csv')) {
            $nombre .= '.csv';
        }

        return $nombre;
    }
}
