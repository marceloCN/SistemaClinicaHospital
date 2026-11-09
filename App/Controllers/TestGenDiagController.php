<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Controller;
use Lib\Core\Request;
use Lib\Core\Response;

/**
 * Diagnostico temporal
 *
 * Registra sus rutas en routes/app.php apuntando a las acciones que uses.
 */
class TestGenDiagController extends Controller
{
    /**
     * Listado / pagina principal del modulo.
     * Corresponde a GET  /recurso
     * Flujo tipico: cargar datos -> $this->vista('ruta', datos) o json().
     */
    public function index(Request $request): Response
    {
        // TODO: cargar datos y devolver la vista.
        return $this->json(['success' => true, 'data' => []]);
    }

    /**
     * Alta de un registro.
     * Corresponde a POST /recurso
     * Flujo tipico: el FormRequest tipado ya valido; validated() da los datos limpios -> guardar -> json().
     */
    public function store(Request $request): Response
    {
        // TODO: validar (ideal con un FormRequest) y guardar.
        return $this->json(['success' => true, 'message' => 'Creado correctamente.']);
    }
}
