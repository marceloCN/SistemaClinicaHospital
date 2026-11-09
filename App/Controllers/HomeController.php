<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Controllers\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->portada(ConfiguracionSistema::publica());
    }

    /**
     * Renderiza la portada pública con una configuración dada.
     *
     * Se usa también por la vista previa en vivo del panel de configuración,
     * donde se le pasa el borrador sin guardar.
     */
    public function portada(array $config): Response
    {
        $features = [];
        foreach ((array) ($config['inicio_servicios'] ?? []) as $servicio) {
            if (!is_array($servicio) || trim((string) ($servicio['title'] ?? '')) === '') {
                continue;
            }

            $features[] = [
                'icon' => (string) ($servicio['icon'] ?? 'fa-stethoscope'),
                'title' => (string) $servicio['title'],
                'desc' => (string) ($servicio['desc'] ?? ''),
            ];
        }

        if ($features === []) {
            $features = ConfiguracionSistema::serviciosPorDefecto();
        }

        $especialidadesOfertadas = Especialidad::ofrecidas();
        $medicosDirectorio = Medico::directorio();

        // Directorio de especialidades de la portada (#especialidades).
        $seleccionEspecialidades = array_map('intval', (array) ($config['inicio_especialidades'] ?? []));
        $especialidades = $especialidadesOfertadas;

        if ($seleccionEspecialidades !== []) {
            $especialidades = array_values(array_filter(
                $especialidadesOfertadas,
                static fn (array $esp): bool => in_array((int) $esp['id'], $seleccionEspecialidades, true)
            ));
        }

        foreach ($especialidades as $clave => $esp) {
            $especialidades[$clave]['icono'] = Especialidad::icono((string) ($esp['nombre'] ?? ''));
        }

        // Destacados elegidos por el admin (#medicos, primer bloque).
        $seleccionMedicos = array_map('intval', (array) ($config['inicio_medicos_destacados'] ?? []));
        $medicos = [];

        if ($seleccionMedicos !== []) {
            $medicos = array_values(array_filter(
                $medicosDirectorio,
                static fn (array $med): bool => in_array((int) $med['id'], $seleccionMedicos, true)
            ));

            $cantidadMedicos = max(1, min(12, (int) ($config['inicio_medicos_cantidad'] ?? 3)));
            $medicos = array_slice($medicos, 0, $cantidadMedicos);
        }

        $aniosExperiencia = max(0, (int) ($config['inicio_anios_experiencia'] ?? 0));

        $stats = [
            [
                'value' => $aniosExperiencia > 0 ? '+' . $aniosExperiencia : '0',
                'label' => 'Años cuidando familias',
            ],
            [
                'value' => (string) count($especialidadesOfertadas),
                'label' => 'Especialidades médicas',
            ],
            [
                'value' => (string) count($medicosDirectorio),
                'label' => 'Profesionales certificados',
            ],
            [
                'value' => (string) ($config['clinica_emergencias'] ?? ''),
                'label' => 'Atención de emergencias',
            ],
        ];

        $contacto = [
            'telefono' => $config['clinica_telefono'] ?? '',
            'emergencias' => $config['clinica_emergencias'] ?? '',
            'correo' => $config['clinica_correo'] ?? '',
            'direccion' => $config['clinica_direccion'] ?? '',
            'horario' => $config['clinica_horario'] ?? '',
            'sabado' => $config['clinica_horario_sabado'] ?? '',
        ];

        return $this->view('base.layout', [
            'head' => View::render('base.head_inicio', ['config' => $config]),
            'config' => $config,
            'contacto' => $contacto,
            'content_base' => View::render('inicio', [
                'appName' => $config['clinica_nombre'] ?? '',
                'slogan' => $config['clinica_eslogan'] ?? '',
                'titulo' => $config['pagina_inicial_titulo'] ?? '',
                'subtitulo' => $config['pagina_inicial_subtitulo'] ?? '',
                'descripcion' => $config['pagina_inicial_descripcion'] ?? '',
                'imagenFondo' => $config['pagina_inicial_imagen_fondo'] ?? '',
                'colorPrimario' => $config['tema_color_primario'] ?? '',
                'colorSecundario' => $config['tema_color_secundario'] ?? '',
                'features' => $features,
                'stats' => $stats,
                'especialidades' => $especialidades,
                'todasEspecialidades' => array_column($especialidadesOfertadas, 'nombre'),
                'mostrarEspecialidades' => (bool) ($config['inicio_mostrar_especialidades'] ?? true),
                'mostrarMedicos' => (bool) ($config['inicio_mostrar_medicos'] ?? true),
                'mostrarServicios' => (bool) ($config['inicio_mostrar_servicios'] ?? true),
                'mostrarContacto' => (bool) ($config['inicio_mostrar_contacto'] ?? true),
                'medicos' => $medicos,
                'directorioMedicos' => $medicosDirectorio,
                'totalMedicos' => count($medicosDirectorio),
                'emergencias' => $config['clinica_emergencias'] ?? '',
                'contacto' => $contacto,
            ]),
        ]);
    }
}
