<?php
/**
 * Vista de impresión de la documentación (dompdf).
 *
 * Variables: $chapters, $appName, $appUrl, $php, $generatedAt, $single, $logo.
 * Los SVG de los capítulos se convierten a imágenes data URI en DocsController::inlineSvgs().
 */
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Documentación <?= e((string) $appName) ?></title>
    <style>
        @page {
            margin: 26mm 16mm 24mm 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.6pt;
            line-height: 1.5;
            color: #1f2937;
        }

        a {
            color: #0b4ea8;
            text-decoration: none;
        }

        h1, h2, h3, h4, h5 {
            margin: 0;
            color: #0f172a;
        }

        p {
            margin: 0 0 8px;
        }

        ul, ol {
            margin: 0 0 10px;
            padding-left: 18px;
        }

        li {
            margin-bottom: 4px;
        }

        code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8.6pt;
            background: #f1f5f9;
            color: #0f172a;
            padding: 1px 3px;
            border-radius: 3px;
        }

        /* --- Portada ------------------------------------------------------ */

        .pdf-cover {
            page-break-after: always;
            padding-top: 40mm;
            text-align: center;
        }

        .pdf-cover-logo {
            max-width: 88px;
            max-height: 88px;
            margin-bottom: 18px;
        }

        .pdf-cover-kicker {
            font-size: 8.5pt;
            letter-spacing: 2.4px;
            text-transform: uppercase;
            color: #0d6efd;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .pdf-cover h1 {
            font-size: 27pt;
            line-height: 1.15;
            margin-bottom: 8px;
        }

        .pdf-cover-subtitle {
            font-size: 12pt;
            color: #475569;
            margin-bottom: 34px;
        }

        .pdf-cover-meta {
            width: 72%;
            margin: 0 auto;
            border-top: 1px solid #dbe3ec;
            border-bottom: 1px solid #dbe3ec;
            padding: 14px 0;
        }

        .pdf-cover-meta table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .pdf-cover-meta td {
            padding: 3px 6px;
            text-align: left;
            color: #334155;
        }

        .pdf-cover-meta td:first-child {
            color: #64748b;
            width: 42%;
        }

        .pdf-cover-foot {
            margin-top: 34px;
            font-size: 8.3pt;
            color: #64748b;
        }

        /* --- Índice ------------------------------------------------------- */

        .pdf-toc {
            page-break-after: always;
        }

        .pdf-toc h2 {
            font-size: 17pt;
            margin-bottom: 4px;
        }

        .pdf-toc-lead {
            color: #64748b;
            font-size: 9pt;
            margin-bottom: 16px;
        }

        .pdf-toc table {
            width: 100%;
            border-collapse: collapse;
        }

        .pdf-toc td {
            padding: 6px 4px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: top;
        }

        .pdf-toc .pdf-toc-num {
            width: 34px;
            color: #0d6efd;
            font-weight: bold;
        }

        .pdf-toc .pdf-toc-title {
            font-weight: bold;
            color: #0f172a;
        }

        .pdf-toc .pdf-toc-summary {
            display: block;
            font-weight: normal;
            font-size: 8.4pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* --- Capítulos ---------------------------------------------------- */

        .pdf-chapter {
            page-break-before: always;
        }

        .pdf-single-head {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .pdf-single-head strong {
            font-size: 12pt;
            color: #0f172a;
        }

        .pdf-single-head span {
            float: right;
            font-size: 8pt;
            color: #64748b;
            padding-top: 4px;
        }

        .doc-chapter-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .doc-chapter-num {
            display: block;
            font-size: 8pt;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #0d6efd;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .doc-chapter-title {
            font-size: 19pt;
            line-height: 1.2;
            margin: 0 0 8px;
        }

        .doc-chapter-lead {
            font-size: 10pt;
            color: #475569;
            margin: 0;
        }

        .doc-chapter-actions {
            display: none;
        }

        .doc-section {
            margin-bottom: 18px;
        }

        .doc-section-title {
            font-size: 13pt;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            page-break-after: avoid;
        }

        .doc-anchor {
            display: none;
        }

        .doc-h4 {
            font-size: 10.5pt;
            font-weight: bold;
            margin: 14px 0 6px;
            color: #0f172a;
            page-break-after: avoid;
        }

        .doc-section p,
        .doc-chapter p {
            color: #334155;
        }

        /* --- Código ------------------------------------------------------- */

        .doc-code {
            margin: 10px 0 14px;
            border: 1px solid #dbe3ec;
            border-radius: 5px;
            background: #f8fafc;
            page-break-inside: avoid;
        }

        .doc-code-header {
            background: #eef2f7;
            border-bottom: 1px solid #dbe3ec;
            padding: 5px 8px;
            font-size: 8pt;
            color: #475569;
        }

        .doc-code-file {
            font-weight: bold;
            color: #1e293b;
        }

        .doc-code-lang {
            float: right;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 7pt;
            color: #64748b;
        }

        .doc-copy {
            display: none;
        }

        .doc-code pre {
            margin: 0;
            padding: 8px 10px;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7.6pt;
            line-height: 1.45;
            color: #0f172a;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .doc-code pre code {
            background: transparent;
            padding: 0;
            font-size: 7.6pt;
        }

        /* --- Avisos ------------------------------------------------------- */

        .doc-note,
        .doc-tip,
        .doc-warn,
        .doc-danger {
            margin: 10px 0 14px;
            padding: 8px 10px;
            border-left: 3px solid #0d6efd;
            background: #f5f9ff;
            page-break-inside: avoid;
        }

        .doc-tip {
            border-left-color: #22c55e;
            background: #f4fcf6;
        }

        .doc-warn,
        .doc-danger {
            border-left-color: #f59e0b;
            background: #fffaf0;
        }

        .doc-note-title,
        .doc-tip-title,
        .doc-warn-title,
        .doc-danger-title {
            display: block;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 3px;
            color: #0f172a;
        }

        .doc-note p:last-child,
        .doc-tip p:last-child,
        .doc-warn p:last-child,
        .doc-danger p:last-child {
            margin-bottom: 0;
        }

        /* --- Tablas ------------------------------------------------------- */

        .doc-table-wrap {
            margin: 10px 0 14px;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.4pt;
        }

        .doc-table th {
            background: #eef2f7;
            color: #0f172a;
            text-align: left;
            padding: 5px 6px;
            border: 1px solid #dbe3ec;
            font-size: 8.2pt;
        }

        .doc-table td {
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            color: #334155;
        }

        .doc-table tr {
            page-break-inside: avoid;
        }

        .doc-table code {
            font-size: 7.6pt;
        }

        /* --- Listas de comprobación --------------------------------------- */

        .doc-check {
            list-style: none;
            padding-left: 0;
        }

        .doc-check li {
            padding-left: 16px;
            position: relative;
            margin-bottom: 5px;
        }

        .doc-check li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #22c55e;
            font-weight: bold;
        }

        /* --- Figuras ------------------------------------------------------ */

        .doc-figure-wrap {
            margin: 10px 0 14px;
            page-break-inside: avoid;
            text-align: center;
        }

        .doc-figure-wrap.doc-figure-box {
            padding: 6px;
            border: 1px solid #e2e8f0;
            background: #fbfdff;
        }

        img.doc-pdf-figure {
            max-width: 100%;
            height: auto;
        }

        .doc-caption {
            display: block;
            margin-top: 4px;
            font-size: 8pt;
            color: #64748b;
            text-align: center;
        }

        /* --- Mini tarjetas (sin flex) ------------------------------------- */

        .doc-cols {
            display: block;
            margin: 8px 0 12px;
        }

        .doc-mini-card {
            display: block;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 8px 10px;
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .doc-mini-title {
            display: block;
            font-weight: bold;
            margin-bottom: 3px;
            color: #0f172a;
        }

        .doc-mini-card p {
            margin: 0;
            font-size: 8.8pt;
            color: #334155;
        }

        .doc-badge {
            font-size: 7.5pt;
            padding: 1px 5px;
            border-radius: 3px;
            background: #eef2f7;
            color: #334155;
        }

        .doc-chip {
            display: none;
        }
    </style>
</head>
<body>
<?php if (empty($single)): ?>
    <div class="pdf-cover">
        <?php if (!empty($logo) && extension_loaded('gd')): ?>
            <img class="pdf-cover-logo" src="<?= e((string) $logo) ?>" alt="Logo">
        <?php endif; ?>

        <div class="pdf-cover-kicker">Manual del sistema</div>
        <h1><?= e((string) $appName) ?></h1>
        <p class="pdf-cover-subtitle">Documentación técnica y guía de desarrollo</p>

        <div class="pdf-cover-meta">
            <table>
                <tr>
                    <td>Aplicación</td>
                    <td><?= e((string) $appName) ?></td>
                </tr>
                <tr>
                    <td>URL</td>
                    <td><?= e((string) $appUrl) ?></td>
                </tr>
                <tr>
                    <td>Versión de PHP</td>
                    <td><?= e((string) $php) ?></td>
                </tr>
                <tr>
                    <td>Capítulos</td>
                    <td><?= count($chapters ?? []) ?></td>
                </tr>
                <tr>
                    <td>Generado</td>
                    <td><?= e((string) $generatedAt) ?></td>
                </tr>
            </table>
        </div>

        <p class="pdf-cover-foot">
            Generado automáticamente desde el panel de administración.<br>
            La versión interactiva, con buscador, está disponible en la sección Documentación.
        </p>
    </div>

    <div class="pdf-toc">
        <h2>Índice</h2>
        <p class="pdf-toc-lead">Cada capítulo empieza en una página nueva.</p>

        <table>
            <?php foreach (($chapters ?? []) as $chapter): ?>
                <tr>
                    <td class="pdf-toc-num"><?= (int) $chapter['number'] ?></td>
                    <td>
                        <a class="pdf-toc-title" href="#<?= e((string) $chapter['id']) ?>">
                            <?= e((string) $chapter['title']) ?>
                        </a>
                        <span class="pdf-toc-summary"><?= e((string) ($chapter['summary'] ?? '')) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php else: ?>
    <div class="pdf-single-head">
        <span><?= e((string) $generatedAt) ?></span>
        <strong><?= e((string) $appName) ?> — Documentación</strong>
    </div>
<?php endif; ?>

<?php foreach (($chapters ?? []) as $chapter): ?>
    <div class="pdf-chapter">
        <?php if (\Lib\Core\View::exists($chapter['view'])): ?>
            <?= \Lib\Core\View::render($chapter['view']) ?>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</body>
</html>
