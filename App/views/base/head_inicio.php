<?php
$config = $config ?? [];
$appName = (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud');
$eslogan = (string) ($config['clinica_eslogan'] ?? 'Tu salud, nuestra prioridad');
$descripcion = (string) ($config['pagina_inicial_descripcion'] ?? 'Atención médica integral, especialistas certificados y gestión de citas en línea.');
$imagen = (string) ($config['pagina_inicial_imagen_fondo'] ?? '');
$imagenUrl = asset('code/dist/img/AdminLTELogo.png');

if ($imagen !== '') {
    $imagenUrl = preg_match('#^https?://#i', $imagen) ? $imagen : asset($imagen);
}

$imagenHero = $imagen !== '' ? imagenHeroUrl($imagen) : '';
$imagenHeroWebp = str_ends_with((string) (parse_url($imagenHero, PHP_URL_PATH) ?? ''), '.webp');
?>
<?php if ($imagenHero !== '') { ?>
<link rel="preload" as="image" href="<?= e($imagenHero) ?>"<?= $imagenHeroWebp ? ' type="image/webp"' : '' ?> fetchpriority="high">
<?php } ?>
<title><?= e($appName) ?> | <?= e($eslogan) ?></title>
<meta name="description" content="<?= e($descripcion) ?>">
<meta name="theme-color" content="<?= e((string) ($config['tema_color_primario'] ?? '#0f8b84')) ?>">
<link rel="canonical" href="<?= URL ?>/">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($appName) ?>">
<meta property="og:title" content="<?= e($appName) ?> | <?= e($eslogan) ?>">
<meta property="og:description" content="<?= e($descripcion) ?>">
<meta property="og:url" content="<?= URL ?>/">
<meta property="og:image" content="<?= e($imagenUrl) ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="<?= asset('code/dist/img/AdminLTELogo.png') ?>">
<link rel="preload" as="font" type="font/woff2" href="<?= URL ?>/medicina/fonts/inter-latin.woff2" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="<?= URL ?>/medicina/fonts/manrope-latin.woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('medicina/css/fonts.css') ?>">
<script>document.documentElement.classList.add('js');</script>
