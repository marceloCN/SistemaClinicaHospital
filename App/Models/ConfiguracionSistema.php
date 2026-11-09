<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class ConfiguracionSistema extends Model
{
    protected string $table = 'configuracion_sistema';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'categoria',
        'editable',
        'fecha_actualizacion',
    ];

    protected array $casts = [
        'editable' => 'boolean',
    ];

    protected bool $timestamps = false;

    private static ?array $cache = null;

    /**
     * Claves nuevas que administra la página de configuración.
     */
    public const CLAVES_PAGINA_INICIAL = [
        'tema_color_secundario',
        'tema_preset',
        'inicio_servicios',
        'inicio_especialidades',
        'inicio_medicos_destacados',
        'inicio_medicos_cantidad',
        'inicio_mostrar_especialidades',
        'inicio_mostrar_medicos',
        'inicio_mostrar_servicios',
        'inicio_mostrar_contacto',
        'inicio_anios_experiencia',
        'tema_modo',
        'clinica_emergencias',
        'clinica_horario',
        'clinica_horario_sabado',
    ];

    /**
     * Obtiene un valor de configuración por su clave.
     */
    public static function valor(string $clave, mixed $default = null): mixed
    {
        self::cargarCache();

        if (!isset(self::$cache[$clave])) {
            return $default;
        }

        return self::convertir(self::$cache[$clave]);
    }

    /**
     * Obtiene una configuración JSON como arreglo.
     */
    public static function json(string $clave, array $default = []): array
    {
        $valor = self::valor($clave, $default);

        if (is_array($valor)) {
            return $valor;
        }

        $decodificado = json_decode((string) $valor, true);

        return is_array($decodificado) ? $decodificado : $default;
    }

    /**
     * Configuración pública usada por el sitio y el panel.
     */
    public static function publica(): array
    {
        $config = [
            'clinica_nombre' => (string) self::valor('clinica_nombre', 'Clínica Vida Salud'),
            'clinica_eslogan' => (string) self::valor('clinica_eslogan', 'Tu salud, nuestra prioridad'),
            'clinica_direccion' => (string) self::valor('clinica_direccion', 'Av. Principal #123, Ciudad'),
            'clinica_telefono' => (string) self::valor('clinica_telefono', '2-1234567'),
            'clinica_correo' => (string) self::valor('clinica_correo', 'contacto@vidasalud.com'),
            'clinica_emergencias' => (string) self::valor('clinica_emergencias', '24/7'),
            'clinica_horario' => (string) self::valor('clinica_horario', 'Lunes a viernes · 08:00 a 20:00'),
            'clinica_horario_sabado' => (string) self::valor('clinica_horario_sabado', 'Sábados · 08:00 a 13:00'),
            'pagina_inicial_titulo' => (string) self::valor('pagina_inicial_titulo', 'Bienvenido a la Clínica Vida Salud'),
            'pagina_inicial_subtitulo' => (string) self::valor('pagina_inicial_subtitulo', 'Gestiona tus citas médicas de forma rápida y segura'),
            'pagina_inicial_descripcion' => (string) self::valor('pagina_inicial_descripcion', 'Ofrecemos atención médica especializada en múltiples áreas. Agenda tu cita con nuestros profesionales.'),
            'pagina_inicial_imagen_fondo' => (string) self::valor('pagina_inicial_imagen_fondo', ''),
            'tema_color_primario' => (string) self::valor('tema_color_primario', '#0f8b84'),
            'tema_color_secundario' => (string) self::valor('tema_color_secundario', '#0b2a38'),
            'tema_preset' => (string) self::valor('tema_preset', 'vida-salud'),
            'tema_modo' => self::valor('tema_modo', 'claro') === 'oscuro' ? 'oscuro' : 'claro',
            'inicio_anios_experiencia' => (int) self::valor('inicio_anios_experiencia', 25),
            'inicio_mostrar_especialidades' => (bool) self::valor('inicio_mostrar_especialidades', true),
            'inicio_mostrar_medicos' => (bool) self::valor('inicio_mostrar_medicos', true),
            'inicio_mostrar_servicios' => (bool) self::valor('inicio_mostrar_servicios', true),
            'inicio_mostrar_contacto' => (bool) self::valor('inicio_mostrar_contacto', true),
            'inicio_medicos_cantidad' => (int) self::valor('inicio_medicos_cantidad', 3),
        ];

        $config['inicio_servicios'] = self::json('inicio_servicios', self::serviciosPorDefecto());
        $config['inicio_especialidades'] = array_values(array_map('intval', self::json('inicio_especialidades', [])));
        $config['inicio_medicos_destacados'] = array_values(array_map('intval', self::json('inicio_medicos_destacados', [])));

        return $config;
    }

    /**
     * Servicios que se muestran en la página de inicio por defecto.
     */
    public static function serviciosPorDefecto(): array
    {
        return [
            [
                'icon' => 'fa-stethoscope',
                'title' => 'Consulta médica especializada',
                'desc' => 'Evaluación integral con especialistas certificados y seguimiento personalizado.',
            ],
            [
                'icon' => 'fa-heartbeat',
                'title' => 'Cardiología y chequeos',
                'desc' => 'Estudios preventivos y control cardiovascular con tecnología de precisión.',
            ],
            [
                'icon' => 'fa-child',
                'title' => 'Pediatría y control infantil',
                'desc' => 'Acompañamiento cercano para el crecimiento y bienestar de los más pequeños.',
            ],
            [
                'icon' => 'fa-flask',
                'title' => 'Laboratorio clínico',
                'desc' => 'Resultados confiables y oportunos para respaldar cada diagnóstico.',
            ],
            [
                'icon' => 'fa-x-ray',
                'title' => 'Diagnóstico por imagen',
                'desc' => 'Equipos modernos y lectura especializada para una evaluación precisa.',
            ],
            [
                'icon' => 'fa-briefcase-medical',
                'title' => 'Salud ocupacional',
                'desc' => 'Programas de prevención y bienestar para empresas y colaboradores.',
            ],
        ];
    }

    /**
     * Iconos disponibles para los servicios del inicio.
     */
    public static function iconosServicios(): array
    {
        return [
            'fa-stethoscope' => 'Estetoscopio',
            'fa-heartbeat' => 'Cardiología',
            'fa-child' => 'Pediatría',
            'fa-flask' => 'Laboratorio',
            'fa-x-ray' => 'Imagen',
            'fa-briefcase-medical' => 'Salud ocupacional',
            'fa-user-md' => 'Médico',
            'fa-tooth' => 'Odontología',
            'fa-eye' => 'Oftalmología',
            'fa-bone' => 'Traumatología',
            'fa-brain' => 'Neurología / Psicología',
            'fa-lungs' => 'Neumología',
            'fa-syringe' => 'Vacunación',
            'fa-pills' => 'Farmacia',
            'fa-microscope' => 'Investigación',
            'fa-ambulance' => 'Emergencias',
            'fa-hospital' => 'Hospitalización',
            'fa-wheelchair' => 'Rehabilitación',
        ];
    }

    /**
     * Presets de color para la página de inicio.
     */
    public static function presetsTema(): array
    {
        return [
            'vida-salud' => [
                'nombre' => 'Vida Salud',
                'primary' => '#0f8b84',
                'secondary' => '#0b2a38',
            ],
            'verde-esmeralda' => [
                'nombre' => 'Verde esmeralda',
                'primary' => '#008040',
                'secondary' => '#0a3d2e',
            ],
            'azul-clinico' => [
                'nombre' => 'Azul clínico',
                'primary' => '#007bff',
                'secondary' => '#0b2a38',
            ],
            'violeta-clinico' => [
                'nombre' => 'Violeta clínico',
                'primary' => '#7c3aed',
                'secondary' => '#2e1065',
            ],
            'coral' => [
                'nombre' => 'Coral',
                'primary' => '#e11d48',
                'secondary' => '#4c0519',
            ],
            'grafito' => [
                'nombre' => 'Grafito',
                'primary' => '#334155',
                'secondary' => '#0f172a',
            ],
            'turquesa' => [
                'nombre' => 'Turquesa',
                'primary' => '#0891b2',
                'secondary' => '#083344',
            ],
            'cobalto' => [
                'nombre' => 'Cobalto',
                'primary' => '#2563eb',
                'secondary' => '#1e3a8a',
            ],
            'naranja' => [
                'nombre' => 'Naranja',
                'primary' => '#ea580c',
                'secondary' => '#7c2d12',
            ],
            'bosque' => [
                'nombre' => 'Bosque',
                'primary' => '#15803d',
                'secondary' => '#14532d',
            ],
        ];
    }

    /**
     * Actualiza o inserta un valor de configuración.
     */
    public static function guardar(
        string $clave,
        mixed $valor,
        ?string $tipo = null,
        ?string $categoria = null,
        ?string $descripcion = null
    ): bool {
        $existente = self::query()->where('clave', $clave)->first();
        $tipo = $tipo ?? self::inferirTipo($valor);

        $datos = [
            'valor' => is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : self::valorComoTexto($valor),
            'fecha_actualizacion' => date('Y-m-d H:i:s'),
        ];

        if ($existente !== null) {
            if (is_array($valor) && (string) $existente['tipo'] !== 'json') {
                $datos['tipo'] = 'json';
            }

            self::query()->update($datos, ['id' => $existente['id']]);
            self::$cache = null;

            return true;
        }

        $datos['clave'] = $clave;
        $datos['tipo'] = $tipo;
        $datos['categoria'] = $categoria ?? 'general';
        $datos['descripcion'] = $descripcion ?? '';
        $datos['editable'] = true;

        $ok = (bool) self::query()->insert($datos);
        self::$cache = null;

        return $ok;
    }

    /**
     * Devuelve todas las configuraciones agrupadas por categoría.
     */
    public static function agrupadas(): array
    {
        $items = self::query()->get();

        usort($items, static function (array $a, array $b): int {
            return [$a['categoria'], $a['clave']] <=> [$b['categoria'], $b['clave']];
        });

        $agrupadas = [];

        foreach ($items as $item) {
            $agrupadas[$item['categoria']][] = $item;
        }

        return $agrupadas;
    }

    public static function limpiarCache(): void
    {
        self::$cache = null;
    }

    private static function cargarCache(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];

        foreach (self::query()->get() as $fila) {
            self::$cache[(string) $fila['clave']] = $fila;
        }
    }

    private static function convertir(array $config): mixed
    {
        return match ($config['tipo']) {
            'integer' => (int) $config['valor'],
            'boolean' => filter_var($config['valor'], FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $config['valor'], true),
            default => $config['valor'],
        };
    }

    private static function inferirTipo(mixed $valor): string
    {
        if (is_array($valor)) {
            return 'json';
        }

        if (is_bool($valor)) {
            return 'boolean';
        }

        if (is_int($valor)) {
            return 'integer';
        }

        return 'string';
    }

    private static function valorComoTexto(mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return (string) $valor;
    }
}
