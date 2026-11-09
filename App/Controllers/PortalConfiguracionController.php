<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Controllers\Controller;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;
use Lib\Support\Session;

class PortalConfiguracionController extends Controller
{
    private const SESION_PREFIJO = 'portal_';
    private const LIMITE_IMAGEN_HERO = 2097152; // 2 MB

    public function index(Request $request): Response
    {
        $this->sembrarConfiguracion();

        $config = ConfiguracionSistema::publica();
        $configuraciones = ConfiguracionSistema::agrupadas();
        $especialidades = Especialidad::ofrecidas();
        $medicos = Medico::directorio();

        $idsEspecialidades = array_map('intval', array_column($especialidades, 'id'));
        $seleccionEspecialidades = array_values(array_intersect(
            array_map('intval', (array) $config['inicio_especialidades']),
            $idsEspecialidades
        ));

        if ($seleccionEspecialidades === []) {
            $seleccionEspecialidades = $idsEspecialidades;
        }

        $idsMedicos = array_map('intval', array_column($medicos, 'id'));
        $seleccionMedicos = array_values(array_intersect(
            array_map('intval', (array) $config['inicio_medicos_destacados']),
            $idsMedicos
        ));

        if ($seleccionMedicos === []) {
            $seleccionMedicos = $idsMedicos;
        }

        $servicios = array_values(array_filter(
            (array) $config['inicio_servicios'],
            static fn (mixed $servicio): bool => is_array($servicio)
                && trim((string) ($servicio['title'] ?? '')) !== ''
        ));

        if ($servicios === []) {
            $servicios = ConfiguracionSistema::serviciosPorDefecto();
        }

        $presets = ConfiguracionSistema::presetsTema();
        $presetActual = (string) $config['tema_preset'];

        if (!isset($presets[$presetActual]) && $presetActual !== 'personalizado') {
            $presetActual = $this->detectarPreset(
                (string) $config['tema_color_primario'],
                (string) $config['tema_color_secundario'],
                $presets
            );
        }

        $permisos = $this->permisosRol();

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'configuracion',
            'content' => View::render('portal.configuracion.index', [
                'config' => $config,
                'configuraciones' => $configuraciones,
                'categorias' => array_keys($configuraciones),
                'servicios' => $servicios,
                'especialidades' => $especialidades,
                'medicos' => $medicos,
                'seleccionEspecialidades' => $seleccionEspecialidades,
                'seleccionMedicos' => $seleccionMedicos,
                'presets' => $presets,
                'presetActual' => $presetActual,
                'iconos' => ConfiguracionSistema::iconosServicios(),
                'permisos' => $permisos,
                'totalPermisos' => array_sum(array_map('count', $permisos)),
                'rolClave' => $this->rolClave(),
            ]),
        ]);
    }

    public function guardar(Request $request): Response
    {
        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN', ''));

        if (!Csrf::validate($token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido. Recarga la página e inténtalo de nuevo.'], 403);
        }

        $datos = $request->json('config', []);

        if (!is_array($datos) || $datos === []) {
            return $this->json(['success' => false, 'message' => 'No se recibieron datos.'], 422);
        }

        $permitidas = $this->clavesPermitidas();
        $guardadas = [];

        try {
            foreach ($datos as $clave => $valor) {
                if (!is_string($clave) || !in_array($clave, $permitidas, true)) {
                    continue;
                }

                $valorNormalizado = $this->normalizarValor($clave, $valor);
                $esVisibleSeccion = str_starts_with($clave, 'inicio_mostrar_');
                ConfiguracionSistema::guardar(
                    $clave,
                    $valorNormalizado,
                    $esVisibleSeccion ? 'boolean' : null,
                    $esVisibleSeccion ? 'pagina_inicial' : null
                );
                $guardadas[$clave] = $valorNormalizado;
            }
        } catch (\InvalidArgumentException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($guardadas === []) {
            return $this->json(['success' => false, 'message' => 'No se recibieron claves válidas.'], 422);
        }

        $this->registrarBitacora('CONFIGURACION', 'ACTUALIZAR', 0, $guardadas);

        return $this->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente.',
        ]);
    }

    /**
     * Vista previa en vivo de la portada con el borrador sin guardar.
     *
     * Devuelve el mismo HTML que la página de inicio pública para que el
     * iframe del panel muestre el resultado real de cada cambio.
     */
    public function vistaPrevia(Request $request): Response
    {
        $datos = $request->json('config', []);
        $config = ConfiguracionSistema::publica();

        if (is_array($datos)) {
            foreach ($this->normalizarDraft($datos) as $clave => $valor) {
                $config[$clave] = $valor;
            }
        }

        $html = (new HomeController())->portada($config)->getContent();
        $guard = <<<HTML
<script data-preview-guard="1">
(function () {
    document.documentElement.setAttribute('data-preview', '1');

    document.addEventListener('click', function (evento) {
        var enlace = evento.target && evento.target.closest ? evento.target.closest('a') : null;

        if (!enlace) {
            return;
        }

        var href = enlace.getAttribute('href') || '';

        if (href.charAt(0) !== '#') {
            evento.preventDefault();
            evento.stopPropagation();
        }
    }, true);

    document.addEventListener('submit', function (evento) {
        evento.preventDefault();
    }, true);
})();
</script>
HTML;

        $html = str_replace('</body>', $guard . "\n</body>", $html);

        return Response::make($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Sube la imagen de fondo del hero (JPG/PNG/WebP, máximo 2 MB).
     *
     * La guarda en public/assets/img/ con nombre único, reemplaza la imagen
     * subida anteriormente y devuelve la ruta para la vista previa en vivo.
     */
    public function subirImagenHero(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido. Recarga la página e inténtalo de nuevo.'], 403);
        }

        $archivo = $request->file('imagen');

        if (!is_array($archivo) || (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->json(['success' => false, 'message' => 'No se recibió ninguna imagen.'], 422);
        }

        $tamano = (int) ($archivo['size'] ?? 0);

        if ($tamano <= 0 || $tamano > self::LIMITE_IMAGEN_HERO) {
            return $this->json(['success' => false, 'message' => 'La imagen no puede superar los 2 MB.'], 422);
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        $mime = $tmp !== '' && is_file($tmp) ? (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) : '';

        $formatos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($formatos[$mime])) {
            return $this->json(['success' => false, 'message' => 'Formato no permitido. Usa una imagen JPG, PNG o WebP.'], 422);
        }

        $directorio = BASE_PATH . '/public/assets/img';

        if (!is_dir($directorio) && !@mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            return $this->json(['success' => false, 'message' => 'No se pudo preparar la carpeta de imágenes.'], 500);
        }

        $nombre = 'hero-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $formatos[$mime];
        $destino = $directorio . '/' . $nombre;

        if (!move_uploaded_file($tmp, $destino)) {
            return $this->json(['success' => false, 'message' => 'No se pudo guardar la imagen en el servidor.'], 500);
        }

        if ($formatos[$mime] !== 'webp') {
            $this->generarWebpHero($destino, $mime);
        }

        $ruta = 'assets/img/' . $nombre;
        $anterior = (string) ConfiguracionSistema::valor('pagina_inicial_imagen_fondo', '');

        $this->borrarImagenSubida($anterior);

        ConfiguracionSistema::guardar('pagina_inicial_imagen_fondo', $this->normalizarImagen($ruta), 'string', 'pagina_inicial');
        $this->registrarBitacora('configuracion_sistema', 'ACTUALIZAR', 0, ['pagina_inicial_imagen_fondo' => $ruta]);

        return $this->json([
            'success' => true,
            'message' => 'Imagen del hero actualizada.',
            'ruta' => $ruta,
            'url' => URL . '/' . $ruta,
        ]);
    }

    /**
     * Quita la imagen de fondo del hero (vuelve al fondo por defecto).
     */
    public function quitarImagenHero(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        $anterior = (string) ConfiguracionSistema::valor('pagina_inicial_imagen_fondo', '');

        $this->borrarImagenSubida($anterior);

        ConfiguracionSistema::guardar('pagina_inicial_imagen_fondo', '', 'string', 'pagina_inicial');
        $this->registrarBitacora('configuracion_sistema', 'ACTUALIZAR', 0, ['pagina_inicial_imagen_fondo' => '']);

        return $this->json([
            'success' => true,
            'message' => 'Imagen del hero eliminada.',
            'ruta' => '',
            'url' => '',
        ]);
    }

    private function csrfValido(Request $request): bool
    {
        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN', ''));

        return Csrf::validate($token);
    }

    /**
     * Borra solo las imágenes subidas desde el panel (hero-20261025-210341-ab12cd.jpg).
     *
     * Las imágenes que vienen con el proyecto (hero-clinica.jpg) nunca se tocan.
     */
    private function borrarImagenSubida(string $ruta): void
    {
        $nombre = basename($ruta);

        if (preg_match('/^hero-\d{8}-\d{6}-[0-9a-f]{6}\.[a-z0-9]{2,4}$/', $nombre) !== 1) {
            return;
        }

        $base = realpath(BASE_PATH . '/public/assets/img');
        $archivo = realpath(BASE_PATH . '/public/assets/img/' . $nombre);

        if ($base === false || $archivo === false || !is_file($archivo)) {
            return;
        }

        if (str_starts_with($archivo, $base . DIRECTORY_SEPARATOR)) {
            @unlink($archivo);

            // Derivative .webp generado junto a la imagen subida (mismo patrón
            // de nombre: hero-20261025-210341-ab12cd.webp).
            $derivado = preg_replace('/\.[a-z0-9]{2,4}$/i', '.webp', $archivo);

            if (
                is_string($derivado)
                && $derivado !== $archivo
                && preg_match('/^hero-\d{8}-\d{6}-[0-9a-f]{6}\.webp$/', basename($derivado)) === 1
            ) {
                @unlink($derivado);
            }
        }
    }

    /**
     * Genera un derivative .webp junto a la imagen subida (hero-....webp) para
     * que el hero pese mucho menos. Si GD no soporta WebP o falla la
     * conversión, se conserva solo el original sin romper nada.
     */
    private function generarWebpHero(string $destino, string $mime): void
    {
        if (!function_exists('imagewebp')) {
            return;
        }

        $origen = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($destino),
            'image/png' => @imagecreatefrompng($destino),
            default => null,
        };

        if ($origen === false || $origen === null) {
            return;
        }

        if ($mime === 'image/png') {
            imagesavealpha($origen, true);
        }

        $webp = preg_replace('/\.[a-z0-9]{2,4}$/i', '.webp', $destino);

        if (is_string($webp) && $webp !== $destino) {
            @imagewebp($origen, $webp, 82);
        }

        imagedestroy($origen);
    }

    public function actualizarTema(Request $request): Response
    {
        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN', ''));

        if (!Csrf::validate($token)) {
            return $this->json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
        }

        try {
            $primario = $this->normalizarColor($request->input('tema_color_primario'), 'Color primario');
            $secundario = $this->normalizarColor($request->input('tema_color_secundario'), 'Color secundario');
        } catch (\InvalidArgumentException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        ConfiguracionSistema::guardar('tema_color_primario', $primario);
        ConfiguracionSistema::guardar('tema_color_secundario', $secundario);
        ConfiguracionSistema::guardar('tema_preset', 'personalizado');

        $this->registrarBitacora('CONFIGURACION', 'TEMA', 0, [
            'tema_color_primario' => $primario,
            'tema_color_secundario' => $secundario,
        ]);

        return $this->json([
            'success' => true,
            'message' => 'Tema actualizado.',
        ]);
    }

    /**
     * Inserta los valores base si la instalación aún no los tiene.
     */
    private function sembrarConfiguracion(): void
    {
        $defaults = [
            ['clinica_nombre', 'Clínica Vida Salud', 'string', 'Nombre de la clínica', 'general'],
            ['clinica_eslogan', 'Tu salud, nuestra prioridad', 'string', 'Eslogan de la clínica', 'general'],
            ['clinica_direccion', 'Av. Principal #123, Ciudad', 'string', 'Dirección física de la clínica', 'general'],
            ['clinica_telefono', '2-1234567', 'string', 'Teléfono de contacto', 'general'],
            ['clinica_correo', 'contacto@vidasalud.com', 'string', 'Correo de contacto', 'general'],
            ['clinica_emergencias', '24/7', 'string', 'Texto o número de atención de emergencias', 'general'],
            ['clinica_horario', 'Lunes a viernes · 08:00 a 20:00', 'string', 'Horario de atención de lunes a viernes', 'general'],
            ['clinica_horario_sabado', 'Sábados · 08:00 a 13:00', 'string', 'Horario de atención de los sábados', 'general'],
            ['pagina_inicial_titulo', 'Bienvenido a la Clínica Vida Salud', 'string', 'Título principal de la página de inicio', 'pagina_inicial'],
            ['pagina_inicial_subtitulo', 'Gestiona tus citas médicas de forma rápida y segura', 'string', 'Subtítulo de la página de inicio', 'pagina_inicial'],
            ['pagina_inicial_descripcion', 'Ofrecemos atención médica especializada en múltiples áreas. Agenda tu cita con nuestros profesionales.', 'text', 'Descripción de la página de inicio', 'pagina_inicial'],
            ['pagina_inicial_imagen_fondo', 'assets/img/hero-clinica.jpg', 'string', 'Imagen de fondo del hero', 'pagina_inicial'],
            ['tema_color_primario', '#008040', 'string', 'Color primario del tema', 'apariencia'],
            ['tema_color_secundario', '#0a3d2e', 'string', 'Color secundario del tema', 'apariencia'],
            ['tema_preset', 'verde-esmeralda', 'string', 'Preset de colores', 'apariencia'],
            ['tema_modo', 'claro', 'string', 'Modo claro u oscuro del sitio', 'apariencia'],
            ['inicio_servicios', ConfiguracionSistema::serviciosPorDefecto(), 'json', 'Servicios de la página de inicio', 'pagina_inicial'],
            ['inicio_especialidades', [], 'json', 'Especialidades visibles en el inicio', 'pagina_inicial'],
            ['inicio_medicos_destacados', [], 'json', 'Médicos destacados en el inicio', 'pagina_inicial'],
            ['inicio_medicos_cantidad', 3, 'integer', 'Cantidad de médicos en el inicio', 'pagina_inicial'],
            ['inicio_mostrar_especialidades', true, 'boolean', 'Mostrar especialidades en el inicio', 'pagina_inicial'],
            ['inicio_mostrar_medicos', true, 'boolean', 'Mostrar médicos en el inicio', 'pagina_inicial'],
            ['inicio_mostrar_servicios', true, 'boolean', 'Mostrar la sección de servicios en el inicio', 'pagina_inicial'],
            ['inicio_mostrar_contacto', true, 'boolean', 'Mostrar la sección de contacto en el inicio', 'pagina_inicial'],
            ['inicio_anios_experiencia', 25, 'integer', 'Años de experiencia mostrados', 'pagina_inicial'],
            ['duracion_cita_minutos_default', 30, 'integer', 'Duración de cita por defecto', 'citas'],
            ['hora_inicio_atencion_default', '08:00', 'string', 'Hora de inicio de atención', 'citas'],
            ['hora_fin_atencion_default', '18:00', 'string', 'Hora de fin de atención', 'citas'],
        ];

        foreach ($defaults as [$clave, $valor, $tipo, $descripcion, $categoria]) {
            $existente = ConfiguracionSistema::query()->where('clave', $clave)->first();

            if ($existente === null) {
                ConfiguracionSistema::guardar($clave, $valor, $tipo, $categoria, $descripcion);
            }
        }
    }


    /**
     * Claves editables actuales más las nuevas claves del inicio.
     */
    private function clavesPermitidas(): array
    {
        $permitidas = [];

        foreach (ConfiguracionSistema::query()->get() as $fila) {
            if ((bool) $fila['editable']) {
                $permitidas[] = (string) $fila['clave'];
            }
        }

        foreach (ConfiguracionSistema::CLAVES_PAGINA_INICIAL as $clave) {
            $permitidas[] = $clave;
        }

        return array_values(array_unique($permitidas));
    }

    /**
     * Filtra y normaliza un borrador de configuración (vista previa).
     *
     * A diferencia de guardar(), los valores inválidos a medio escribir
     * (color, hora o imagen incompletos) se ignoran en lugar de fallar.
     */
    private function normalizarDraft(array $datos): array
    {
        $permitidas = $this->clavesPermitidas();
        $borrador = [];

        foreach ($datos as $clave => $valor) {
            if (!is_string($clave) || !in_array($clave, $permitidas, true)) {
                continue;
            }

            try {
                $borrador[$clave] = $this->normalizarValor($clave, $valor);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        return $borrador;
    }

    private function normalizarValor(string $clave, mixed $valor): mixed
    {
        return match ($clave) {
            'inicio_servicios' => $this->normalizarServicios($valor),
            'inicio_especialidades', 'inicio_medicos_destacados' => $this->normalizarIds($valor),
            'inicio_medicos_cantidad' => max(1, min(12, (int) $valor)),
            'inicio_anios_experiencia' => max(0, min(100, (int) $valor)),
            'inicio_mostrar_especialidades', 'inicio_mostrar_medicos',
            'inicio_mostrar_servicios', 'inicio_mostrar_contacto' => filter_var($valor, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'tema_color_primario' => $this->normalizarColor($valor, 'Color primario'),
            'tema_color_secundario' => $this->normalizarColor($valor, 'Color secundario'),
            'tema_preset' => preg_match('/^[a-z0-9_-]{1,40}$/', (string) $valor) === 1
                ? (string) $valor
                : 'personalizado',
            'tema_modo' => strtolower((string) $valor) === 'oscuro' ? 'oscuro' : 'claro',
            'duracion_cita_minutos_default' => max(5, min(240, (int) $valor)),
            'hora_inicio_atencion_default', 'hora_fin_atencion_default' => $this->normalizarHora($valor, $clave),
            'pagina_inicial_imagen_fondo' => $this->normalizarImagen($valor),
            default => $this->normalizarTexto($valor, $clave),
        };
    }

    private function normalizarServicios(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }

        $iconos = array_keys(ConfiguracionSistema::iconosServicios());
        $servicios = [];

        foreach (array_slice($valor, 0, 12) as $servicio) {
            if (!is_array($servicio)) {
                continue;
            }

            $titulo = trim(strip_tags((string) ($servicio['title'] ?? '')));
            $descripcion = trim(strip_tags((string) ($servicio['desc'] ?? '')));

            if ($titulo === '') {
                continue;
            }

            $icono = (string) ($servicio['icon'] ?? 'fa-stethoscope');

            if (!in_array($icono, $iconos, true)) {
                $icono = 'fa-stethoscope';
            }

            $servicios[] = [
                'icon' => $icono,
                'title' => mb_substr($titulo, 0, 90),
                'desc' => mb_substr($descripcion, 0, 300),
            ];
        }

        return $servicios;
    }

    private function normalizarIds(mixed $valor): array
    {
        if (!is_array($valor)) {
            return [];
        }

        $ids = array_map('intval', $valor);
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));

        return array_slice($ids, 0, 100);
    }

    private function normalizarColor(mixed $valor, string $etiqueta): string
    {
        $color = trim((string) $valor);

        if (preg_match('/^#[a-fA-F0-9]{6}$/', $color) !== 1) {
            throw new \InvalidArgumentException($etiqueta . ': usa un color hexadecimal de 6 dígitos (ej. #0f8b84).');
        }

        return strtolower($color);
    }

    private function normalizarHora(mixed $valor, string $clave): string
    {
        $hora = trim((string) $valor);

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) !== 1) {
            throw new \InvalidArgumentException('La hora de ' . $clave . ' debe tener el formato HH:MM.');
        }

        return $hora;
    }

    private function normalizarImagen(mixed $valor): string
    {
        $imagen = trim((string) $valor);

        if ($imagen === '') {
            return '';
        }

        if (mb_strlen($imagen) > 255) {
            throw new \InvalidArgumentException('La ruta de la imagen es demasiado larga.');
        }

        if (preg_match('#^(https?://[^\s]+|/?[A-Za-z0-9._\-/]+)$#', $imagen) !== 1) {
            throw new \InvalidArgumentException('La imagen debe ser una URL http(s) o una ruta relativa válida.');
        }

        return $imagen;
    }

    private function normalizarTexto(mixed $valor, string $clave): string
    {
        $texto = trim(strip_tags((string) $valor));
        $limite = in_array($clave, ['pagina_inicial_descripcion', 'clinica_direccion'], true) ? 2000 : 500;

        return mb_substr($texto, 0, $limite);
    }

    private function detectarPreset(string $primario, string $secundario, array $presets): string
    {
        foreach ($presets as $clave => $preset) {
            if (strtolower($preset['primary']) === strtolower($primario)
                && strtolower($preset['secondary']) === strtolower($secundario)) {
                return $clave;
            }
        }

        return 'personalizado';
    }

    /**
     * Permisos del rol del usuario, agrupados por módulo.
     */
    private function permisosRol(): array
    {
        $idRol = (int) Session::get(self::SESION_PREFIJO . 'id_rol', 0);

        if ($idRol <= 0) {
            return [];
        }

        $filas = Database::raw(
            "SELECT p.modulo, p.clave, p.nombre, p.descripcion
             FROM rol_permiso rp
             JOIN permiso p ON p.id = rp.id_permiso
             WHERE rp.id_rol = :id_rol AND rp.estado = TRUE
             ORDER BY p.modulo, p.nombre",
            ['id_rol' => $idRol]
        );

        $agrupados = [];

        foreach ($filas as $fila) {
            $modulo = trim((string) ($fila['modulo'] ?? '')) ?: 'General';
            $agrupados[$modulo][] = $fila;
        }

        return $agrupados;
    }

    private function esAdmin(): bool
    {
        return $this->rolClave() === 'ADMIN';
    }

    private function nombre(): string
    {
        return (string) Session::get(self::SESION_PREFIJO . 'nombre', 'Usuario');
    }

    private function registrarBitacora(string $tabla, string $accion, int $registroId, array $datos): void
    {
        try {
            Database::raw(
                "INSERT INTO bitacora_acciones (id_persona, tabla_afectada, accion, registro_id, datos_nuevos, fecha_hora)
                 VALUES (:id_persona, :tabla, :accion, :registro_id, :datos, CURRENT_TIMESTAMP)",
                [
                    'id_persona' => (int) Session::get(self::SESION_PREFIJO . 'id_persona', 0),
                    'tabla' => $tabla,
                    'accion' => $accion,
                    'registro_id' => $registroId,
                    'datos' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                ]
            );
        } catch (\Throwable $e) {
            error_log('Error registrando bitácora: ' . $e->getMessage());
        }
    }
}
