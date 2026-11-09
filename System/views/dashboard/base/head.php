<?php
/**
 * Cabecera <head> reutilizable para las páginas del panel.
 *
 * Variables opcionales (todas tienen valores por defecto sensatos):
 *   title       string  Título del documento (cae a config('app.name')).
 *   description string  Meta description (cae a config('app.description')).
 *   canonical   string  URL canónica absoluta (opcional).
 *   ogImage     string  URL absoluta de imagen para Open Graph (opcional).
 *   ogType      string  Tipo Open Graph (por defecto "website").
 *   robots      string  Directiva robots (por defecto "noindex, nofollow":
 *                       el panel no debe indexarse).
 *   icon        string  URL del favicon.
 *
 * Los valores se escapan siempre: nunca inyectes HTML por estas variables.
 */
$metaTitle = $title ?? config('app.name');
$metaDescription = $description ?? config('app.description', '');
$metaRobots = $robots ?? 'noindex, nofollow';
$metaCanonical = $canonical ?? null;
$metaOgImage = $ogImage ?? null;
$metaOgType = $ogType ?? 'website';
$metaIcon = $icon ?? asset('/code/dist/img/AdminLTELogo.png');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e((string) $metaTitle) ?></title>
<?php if ((string) $metaDescription !== ''): ?>
<meta name="description" content="<?= e((string) $metaDescription) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e((string) $metaRobots) ?>">
<meta name="theme-color" content="#343a40">
<link rel="icon" href="<?= e((string) $metaIcon) ?>">
<?php if ($metaCanonical !== null): ?>
<link rel="canonical" href="<?= e((string) $metaCanonical) ?>">
<?php endif; ?>
<meta property="og:site_name" content="<?= e((string) config('app.name')) ?>">
<meta property="og:type" content="<?= e((string) $metaOgType) ?>">
<meta property="og:title" content="<?= e((string) $metaTitle) ?>">
<?php if ((string) $metaDescription !== ''): ?>
<meta property="og:description" content="<?= e((string) $metaDescription) ?>">
<?php endif; ?>
<?php if ($metaCanonical !== null): ?>
<meta property="og:url" content="<?= e((string) $metaCanonical) ?>">
<?php endif; ?>
<?php if ($metaOgImage !== null): ?>
<meta property="og:image" content="<?= e((string) $metaOgImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>
<meta name="twitter:title" content="<?= e((string) $metaTitle) ?>">
<?php if ((string) $metaDescription !== ''): ?>
<meta name="twitter:description" content="<?= e((string) $metaDescription) ?>">
<?php endif; ?>
