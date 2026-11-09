<?php
$contacto = $contacto ?? [];
$telefono = (string) ($contacto['telefono'] ?? '');
$emergencias = (string) ($contacto['emergencias'] ?? '');
$correo = (string) ($contacto['correo'] ?? '');
$direccion = (string) ($contacto['direccion'] ?? '');
$horario = (string) ($contacto['horario'] ?? '');
$sabado = (string) ($contacto['sabado'] ?? '');
$telHref = 'tel:' . preg_replace('/[^0-9+]/', '', $telefono);
$emergenciaHref = 'tel:' . preg_replace('/[^0-9+]/', '', $emergencias);
$titulo = (string) ($titulo ?? 'Tu salud, en manos de especialistas');
$subtitulo = (string) ($subtitulo ?? 'Medicina cercana, precisa y humana');
$descripcion = (string) ($descripcion ?? '');
$imagenFondo = (string) ($imagenFondo ?? '');
$appName = (string) ($appName ?? 'Clínica Vida Salud');
$slogan = (string) ($slogan ?? '');
$features = $features ?? [];
$stats = $stats ?? [];
$especialidades = $especialidades ?? [];
$todasEspecialidades = $todasEspecialidades ?? [];
$medicos = $medicos ?? [];
$directorioMedicos = $directorioMedicos ?? [];
$mostrarEspecialidades = (bool) ($mostrarEspecialidades ?? true);
$mostrarMedicos = (bool) ($mostrarMedicos ?? true);
$mostrarServicios = (bool) ($mostrarServicios ?? true);
$mostrarContacto = (bool) ($mostrarContacto ?? true);
$anclaMedicos = $mostrarMedicos ? '#medicos' : ($mostrarContacto ? '#contacto' : URL . '/');
$anclaContacto = $mostrarContacto ? '#contacto' : ($mostrarMedicos ? '#medicos' : URL . '/');
$totalMedicos = (int) ($totalMedicos ?? count($medicos));
$emergencias = $emergencias !== '' ? $emergencias : '24/7';

$imagenUrl = imagenHeroUrl($imagenFondo);

$tituloHtml = e($titulo);
if ($appName !== '' && str_contains($titulo, $appName)) {
    $nombreHtml = e($appName);
    $tituloHtml = str_replace($nombreHtml, '<span class="cl-highlight">' . $nombreHtml . '</span>', $tituloHtml);
}
$puntoTitulo = str_ends_with(rtrim($titulo), '.') ? '' : '.';

$subtituloLead = rtrim($subtitulo);
if ($subtituloLead !== '' && !preg_match('/[.!?]$/', $subtituloLead)) {
    $subtituloLead .= '.';
}
$lead = trim($subtituloLead . ' ' . $descripcion);
?>

<section class="cl-hero <?= $imagenFondo !== '' ? 'cl-hero-has-bg' : '' ?>" id="inicio">
    <div class="cl-hero-bg" <?= $imagenUrl !== '' ? 'style="background-image: url(' . e($imagenUrl) . ')"' : '' ?>></div>
    <div class="cl-container cl-hero-grid">
        <div class="cl-hero-copy cl-reveal">
            <span class="cl-eyebrow"><span class="cl-dot"></span> <?= e($slogan) ?></span>
            <h1><?= $tituloHtml ?><?= $puntoTitulo ?></h1>
            <p class="cl-hero-lead"><?= e($lead) ?></p>
            <div class="cl-actions">
                <a href="<?= $anclaMedicos ?>" class="cl-btn cl-btn-primary cl-btn-lg">
                    Reservar una cita <i class="fas fa-arrow-right"></i>
                </a>
                <?php if ($mostrarServicios) { ?>
                <a href="<?= URL ?>/#servicios" class="cl-btn cl-btn-ghost cl-btn-lg">
                    Conocer servicios
                </a>
                <?php } ?>
            </div>
            <ul class="cl-hero-points">
                <li><i class="fas fa-check"></i> Agenda en línea</li>
                <li><i class="fas fa-check"></i> Resultados digitales</li>
                <li><i class="fas fa-check"></i> Acompañamiento cercano</li>
            </ul>
        </div>

        <div class="cl-hero-visual cl-reveal" style="--delay: 0.16s" aria-hidden="true">
            <div class="cl-hero-halo"></div>

            <div class="cl-vitals-card" data-tilt>
                <div class="cl-vitals-head">
                    <span class="cl-vitals-icon"><i class="fas fa-heartbeat"></i></span>
                    <div class="cl-vitals-title">
                        <strong>Monitoreo de bienestar</strong>
                        <small>Signos vitales estables</small>
                    </div>
                    <span class="cl-live"><span></span> En vivo</span>
                </div>

                <svg class="cl-pulse-svg" viewBox="0 0 340 90" preserveAspectRatio="none" role="presentation">
                    <path d="M0 48 H82 L96 48 L108 22 L124 72 L140 34 L154 48 H340" />
                </svg>

                <div class="cl-vitals-grid">
                    <div><span>Frecuencia</span><strong>72 <small>bpm</small></strong></div>
                    <div><span>Oxígeno</span><strong>98 <small>%</small></strong></div>
                    <div><span>Presión</span><strong>120/80</strong></div>
                </div>
            </div>

            <div class="cl-float cl-float-a">
                <span class="cl-float-icon"><i class="fas fa-user-md"></i></span>
                <div><strong><?= $totalMedicos ?> médico(s)</strong><small>certificados</small></div>
            </div>

            <div class="cl-float cl-float-b">
                <span class="cl-float-icon"><i class="fas fa-clock"></i></span>
                <div><strong>Atención <?= e($emergencias) ?></strong><small>emergencias</small></div>
            </div>
        </div>
    </div>
</section>

<section class="cl-stats" aria-label="Indicadores de la clínica">
    <div class="cl-container cl-stats-grid">
        <?php foreach ($stats as $stat) { ?>
            <div class="cl-stat cl-reveal">
                <strong><?= e((string) $stat['value']) ?></strong>
                <span><?= e((string) $stat['label']) ?></span>
            </div>
        <?php } ?>
    </div>
</section>

<?php if ($mostrarServicios) { ?>
<section class="cl-section" id="servicios">
    <div class="cl-container">
        <div class="cl-section-head cl-reveal">
            <span class="cl-eyebrow">Nuestros servicios</span>
            <h2>Atención completa para cada etapa de tu vida.</h2>
            <p>Desde la prevención hasta el tratamiento especializado, reunimos todo lo que necesitas en un solo lugar.</p>
        </div>

        <div class="cl-services">
            <?php foreach ($features as $index => $feature) { ?>
                <article class="cl-service cl-reveal" style="--delay: <?= $index * 0.05 ?>s">
                    <span class="cl-service-icon"><i class="fas <?= e((string) $feature['icon']) ?>"></i></span>
                    <h3><?= e((string) $feature['title']) ?></h3>
                    <p><?= e((string) $feature['desc']) ?></p>
                </article>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

<?php if ($mostrarEspecialidades) { ?>
<section class="cl-section cl-section-soft" id="especialidades">
    <div class="cl-container">
        <div class="cl-split">
            <div class="cl-split-copy cl-reveal">
                <span class="cl-eyebrow">Especialidades</span>
                <h2>Un equipo multidisciplinario, una sola prioridad: tú.</h2>
                <p>
                    Nuestros especialistas trabajan de forma coordinada para ofrecerte diagnósticos oportunos
                    y tratamientos personalizados, con el respaldo de estudios clínicos y de imagen.
                </p>
                <a class="cl-link" href="<?= $anclaMedicos ?>">
                    Conocer a nuestros especialistas <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="cl-split-visual cl-reveal">
                <div class="cl-split-deco"></div>
                <div class="cl-appointment-card">
                    <div class="cl-appointment-head">
                        <span class="cl-service-icon"><i class="fas fa-calendar-check"></i></span>
                        <div>
                            <strong>Agenda tu consulta</strong>
                            <small>Elige especialidad, médico y horario.</small>
                        </div>
                    </div>
                    <div class="cl-appointment-body">
                        <div class="cl-appointment-row"><i class="fas fa-clock"></i><span><?= e($horario) ?></span></div>
                        <div class="cl-appointment-row"><i class="fas fa-calendar-day"></i><span><?= e($sabado) ?></span></div>
                        <div class="cl-appointment-row"><i class="fas fa-phone-alt"></i><a href="<?= e($telHref) ?>"><?= e($telefono) ?></a></div>
                        <div class="cl-appointment-row"><i class="fas fa-ambulance"></i><a href="<?= e($emergenciaHref) ?>">Emergencias <?= e($emergencias) ?></a></div>
                    </div>
                    <a href="<?= $anclaMedicos ?>" class="cl-btn cl-btn-primary cl-btn-block">Reservar una cita</a>
                </div>
            </div>
        </div>

        <div class="cl-search-bar cl-reveal">
            <div class="cl-search-input">
                <i class="fas fa-search"></i>
                <input type="text" id="buscar-especialidad" placeholder="Buscar especialidad..." autocomplete="off">
            </div>
            <span class="cl-badge-count"><?= count($especialidades) ?> especialidad(es)</span>
        </div>

        <div class="cl-especialidades-grid" id="especialidades-grid">
            <?php foreach ($especialidades as $index => $esp) { ?>
                <article class="cl-especialidad-card cl-reveal"
                         data-nombre="<?= e(strtolower((string) ($esp['nombre'] ?? ''))) ?>"
                         style="--delay: <?= $index * 0.05 ?>s">
                    <div class="cl-especialidad-icon">
                        <i class="fas <?= e((string) ($esp['icono'] ?? 'fa-stethoscope')) ?>"></i>
                    </div>
                    <h3><?= e((string) ($esp['nombre'] ?? '')) ?></h3>
                    <p><?= e((string) ($esp['descripcion'] ?? 'Especialidad médica con atención personalizada y profesionales certificados.')) ?></p>
                    <span class="cl-especialidad-count">
                        <i class="fas fa-user-md"></i>
                        <?= (int) ($esp['total_medicos'] ?? 0) ?> médico(s) con servicio activo
                    </span>
                    <a href="<?= $anclaMedicos ?>" class="cl-link cl-link-sm js-ver-medicos"
                       data-buscar="<?= e(strtolower((string) ($esp['nombre'] ?? ''))) ?>">
                        Ver médicos <i class="fas fa-arrow-right"></i>
                    </a>
                </article>
            <?php } ?>
        </div>

        <?php if ($especialidades === []) { ?>
            <div class="cl-empty-state cl-reveal">
                <i class="fas fa-stethoscope fa-3x"></i>
                <h3>No hay especialidades registradas</h3>
                <p>Pronto actualizaremos la información de nuestras especialidades médicas.</p>
            </div>
        <?php } ?>

        <div id="especialidades-sin-resultados" class="cl-empty-state" style="display: none;">
            <i class="fas fa-search fa-3x"></i>
            <h3>No se encontraron especialidades</h3>
            <p>Intenta con otro término de búsqueda.</p>
        </div>
    </div>
</section>
<?php } ?>

<?php if ($mostrarMedicos && ($medicos !== [] || $directorioMedicos !== [])) { ?>
    <section class="cl-section" id="medicos">
        <div class="cl-container">
            <div class="cl-section-head cl-reveal">
                <span class="cl-eyebrow">Nuestro equipo</span>
                <h2>Especialistas que cuidan de ti.</h2>
                <p>Profesionales con servicio activo en nuestra clínica, listos para acompañarte en cada etapa de tu salud.</p>
            </div>

            <?php if ($medicos !== []) { ?>
                <div class="cl-subhead cl-reveal">
                    <h3><i class="fas fa-star"></i> Destacados del equipo</h3>
                    <p>Especialistas elegidos por la clínica para acompañarte primero.</p>
                </div>

                <div class="cl-medicos-grid cl-medicos-destacados" id="medicos-destacados">
                    <?php foreach ($medicos as $index => $medico) { ?>
                        <article class="cl-medico-card cl-medico-destacado cl-reveal" style="--delay: <?= $index * 0.06 ?>s">
                            <span class="cl-destacado-badge"><i class="fas fa-star"></i> Destacado</span>
                            <div class="cl-medico-avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="cl-medico-body">
                                <h3>Dr./Dra. <?= e((string) $medico['nombre']) ?> <?= e((string) $medico['apellido']) ?></h3>
                                <span class="cl-medico-especialidad">
                                    <i class="fas fa-stethoscope"></i>
                                    <?= e((string) ($medico['especialidades'] ?? 'Especialidad médica')) ?>
                                </span>
                                <?php if ((string) ($medico['biografia'] ?? '') !== '') { ?>
                                    <p class="cl-medico-bio"><?= e((string) $medico['biografia']) ?></p>
                                <?php } ?>
                                <div class="cl-medico-meta">
                                    <?php if ((string) ($medico['nro_colegiado'] ?? '') !== '') { ?>
                                        <span><i class="fas fa-id-card"></i> CMP: <?= e((string) $medico['nro_colegiado']) ?></span>
                                    <?php } ?>
                                    <?php if ((string) ($medico['telefono'] ?? '') !== '') { ?>
                                        <span><i class="fas fa-phone-alt"></i> <?= e((string) $medico['telefono']) ?></span>
                                    <?php } ?>
                                </div>
                                <a href="<?= $anclaContacto ?>" class="cl-btn cl-btn-primary cl-btn-sm cl-btn-block mt-3">
                                    <i class="fas fa-calendar-check"></i> Agendar consulta
                                </a>
                            </div>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ($directorioMedicos !== []) { ?>
                <div class="cl-subhead cl-reveal">
                    <h3><i class="fas fa-address-card"></i> Directorio médico</h3>
                    <p>Busca por nombre o especialidad y encuentra al profesional que necesitas.</p>
                </div>

                <div class="cl-search-bar cl-reveal">
                    <div class="cl-search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="buscar-medico" placeholder="Buscar por nombre o especialidad..." autocomplete="off">
                    </div>

                    <select id="filtrar-especialidad" class="form-control cl-select">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($todasEspecialidades as $nombreEsp) { ?>
                            <option value="<?= e(strtolower((string) $nombreEsp)) ?>"><?= e((string) $nombreEsp) ?></option>
                        <?php } ?>
                    </select>

                    <span class="cl-badge-count"><?= count($directorioMedicos) ?> médico(s)</span>
                </div>

                <div class="cl-medicos-grid" id="medicos-grid">
                    <?php foreach ($directorioMedicos as $index => $med) { ?>
                        <article class="cl-medico-card cl-reveal"
                                 data-nombre="<?= e(strtolower((string) ($med['nombre'] ?? '') . ' ' . (string) ($med['apellido'] ?? ''))) ?>"
                                 data-especialidades="<?= e(strtolower((string) ($med['especialidades'] ?? ''))) ?>"
                                 style="--delay: <?= $index * 0.05 ?>s">
                            <div class="cl-medico-avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="cl-medico-body">
                                <h3>Dr./Dra. <?= e((string) ($med['nombre'] ?? '')) ?> <?= e((string) ($med['apellido'] ?? '')) ?></h3>
                                <span class="cl-medico-especialidad">
                                    <i class="fas fa-stethoscope"></i>
                                    <?= e((string) ($med['especialidades'] ?? 'Especialidad médica')) ?>
                                </span>
                                <?php if ((string) ($med['biografia'] ?? '') !== '') { ?>
                                    <p class="cl-medico-bio"><?= e((string) $med['biografia']) ?></p>
                                <?php } ?>
                                <div class="cl-medico-meta">
                                    <?php if ((string) ($med['nro_colegiado'] ?? '') !== '') { ?>
                                        <span><i class="fas fa-id-card"></i> CMP: <?= e((string) $med['nro_colegiado']) ?></span>
                                    <?php } ?>
                                    <?php if ((string) ($med['telefono'] ?? '') !== '') { ?>
                                        <span><i class="fas fa-phone-alt"></i> <?= e((string) $med['telefono']) ?></span>
                                    <?php } ?>
                                </div>
                                <a href="<?= $anclaContacto ?>" class="cl-btn cl-btn-primary cl-btn-sm cl-btn-block mt-3">
                                    <i class="fas fa-calendar-check"></i> Agendar consulta
                                </a>
                            </div>
                        </article>
                    <?php } ?>
                </div>

                <div id="medicos-sin-resultados" class="cl-empty-state" style="display: none;">
                    <i class="fas fa-search fa-3x"></i>
                    <h3>No se encontraron médicos</h3>
                    <p>Intenta con otro nombre o especialidad.</p>
                </div>
            <?php } ?>
        </div>
    </section>
<?php } ?>

<section class="cl-section">
    <div class="cl-container">
        <div class="cl-section-head cl-reveal">
            <span class="cl-eyebrow">Cómo funciona</span>
            <h2>Agendar tu atención es simple.</h2>
            <p>Tres pasos para recibir la atención que necesitas, sin complicaciones.</p>
        </div>

        <div class="cl-steps">
            <article class="cl-step cl-reveal">
                <span class="cl-step-number">01</span>
                <h3>Elige tu especialidad</h3>
                <p>Explora nuestras especialidades y encuentra al profesional adecuado para ti.</p>
            </article>
            <article class="cl-step cl-reveal">
                <span class="cl-step-number">02</span>
                <h3>Reserva tu horario</h3>
                <p>Agenda en línea o comunícate con recepción para confirmar tu cita.</p>
            </article>
            <article class="cl-step cl-reveal">
                <span class="cl-step-number">03</span>
                <h3>Recibe seguimiento</h3>
                <p>Accede a tus resultados y recomendaciones desde el portal del paciente.</p>
            </article>
        </div>
    </div>
</section>

<?php if ($mostrarContacto) { ?>
<section class="cl-contact" id="contacto">
    <div class="cl-container cl-contact-grid">
        <div class="cl-contact-copy cl-reveal">
            <span class="cl-eyebrow cl-eyebrow-light">Estamos cerca</span>
            <h2>Conversemos sobre tu salud.</h2>
            <p>Resuelve tus dudas o agenda una consulta. Nuestro equipo está listo para orientarte.</p>

            <div class="cl-contact-list">
                <a class="cl-contact-item" href="<?= e($telHref) ?>">
                    <span><i class="fas fa-phone-alt"></i></span>
                    <div><small>Teléfono</small><strong><?= e($telefono) ?></strong></div>
                </a>
                <a class="cl-contact-item" href="<?= e($emergenciaHref) ?>">
                    <span><i class="fas fa-ambulance"></i></span>
                    <div><small>Emergencias</small><strong><?= e($emergencias) ?></strong></div>
                </a>
                <a class="cl-contact-item" href="mailto:<?= e($correo) ?>">
                    <span><i class="fas fa-envelope"></i></span>
                    <div><small>Correo</small><strong><?= e($correo) ?></strong></div>
                </a>
                <div class="cl-contact-item">
                    <span><i class="fas fa-map-marker-alt"></i></span>
                    <div><small>Dirección</small><strong><?= e($direccion) ?></strong></div>
                </div>
            </div>
        </div>

        <div class="cl-contact-card cl-reveal">
            <span class="cl-eyebrow">Portal del paciente</span>
            <h3>Tu historial y resultados, siempre disponibles.</h3>
            <p>Accede de forma segura a tus citas, estudios y recomendaciones médicas.</p>
            <a href="<?= route('portal_login') ?>" class="cl-btn cl-btn-primary cl-btn-block">
                Ingresar al portal <i class="fas fa-arrow-right"></i>
            </a>
            <div class="cl-contact-note"><i class="fas fa-shield-alt"></i> Información protegida y confidencial.</div>
        </div>
    </div>
</section>
<?php } ?>
