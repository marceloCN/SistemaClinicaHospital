<div class="row">
    <div class="col-lg-3">
        <aside class="docs-sidebar" id="docs-sidebar">
            <div class="docs-sidebar-card">
                <div class="docs-sidebar-search">
                    <div class="docs-search">
                        <input type="text" class="docs-search-input" id="docs-search-input"
                            placeholder="Buscar en la documentación..." autocomplete="off" spellcheck="false">
                        <span class="docs-kbd">Ctrl K</span>
                        <div class="docs-search-results" id="docs-search-results"></div>
                    </div>
                </div>
                <nav class="docs-toc" id="docs-toc" aria-label="Índice de la documentación"></nav>
                <div class="docs-sidebar-footer">
                    <a class="btn btn-danger btn-block btn-sm"
                        href="<?= e(route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') ?>"
                        target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf mr-1"></i>Descargar manual completo (PDF)
                    </a>
                    <a class="btn btn-outline-secondary btn-block btn-sm mt-2"
                        href="<?= e(route('system_routes') ?? '/join/access/dashboard/Area-of-work/system/routes') ?>">
                        <i class="fas fa-route mr-1"></i>Ver rutas registradas
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <div class="col-lg-9">
        <div class="book-toolbar" id="book-toolbar">
            <button type="button" class="book-toolbar-index" id="book-index-toggle" aria-label="Abrir índice">
                <i class="fas fa-list-ul"></i>
            </button>
            <a class="book-toolbar-home" href="#docs-cover" title="Volver a la portada">
                <i class="fas fa-book"></i>
            </a>
            <div class="book-toolbar-meta">
                <span class="book-toolbar-kicker" id="book-chapter-label">Portada</span>
                <strong class="book-toolbar-title" id="book-chapter-title">Documentación del CMS</strong>
            </div>
            <div class="book-toolbar-actions">
                <a class="btn btn-sm btn-outline-danger"
                    href="<?= e(route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') ?>"
                    target="_blank" rel="noopener" title="Descargar el manual completo en PDF">
                    <i class="fas fa-file-pdf"></i><span class="d-none d-sm-inline ml-1">PDF</span>
                </a>
            </div>
            <div class="book-progress"><span id="book-progress-bar"></span></div>
        </div>

        <script>document.documentElement.classList.add('book-js');</script>

        <div class="docs-content" id="docs-content">
            <section class="book-page book-cover" id="docs-cover">
                <div class="book-cover-inner">
                    <span class="docs-hero-kicker"><i class="fas fa-book-open mr-1"></i>Manual del sistema</span>
                    <h1>Documentación del CMS</h1>
                    <p class="book-cover-lead">
                        De la arquitectura al primer CRUD: un recorrido completo por el framework, el panel y
                        las herramientas que usarás todos los días. Léelo en orden la primera vez; después
                        úsalo como referencia con el buscador.
                    </p>

                    <div class="book-cover-meta">
                        <span class="docs-hero-chip"><i class="fas fa-layer-group mr-1"></i><?= (int) ($stats['chapters'] ?? 0) ?> capítulos</span>
                        <span class="docs-hero-chip"><i class="fas fa-file-code mr-1"></i><?= (int) ($stats['files'] ?? 0) ?> archivos del núcleo</span>
                        <span class="docs-hero-chip"><i class="fab fa-php mr-1"></i>PHP <?= e((string) ($stats['php'] ?? PHP_VERSION)) ?></span>
                        <span class="docs-hero-chip"><i class="fas fa-search mr-1"></i>Busca con Ctrl + K</span>
                    </div>

                    <div class="book-cover-actions">
                        <a class="btn btn-primary" href="#cap1">
                            <i class="fas fa-book-reader mr-1"></i>Comenzar a leer
                        </a>
                        <a class="btn btn-outline-danger"
                            href="<?= e(route('system_docs_pdf') ?? '/join/access/dashboard/Area-of-work/system/docs/pdf') ?>"
                            target="_blank" rel="noopener">
                            <i class="fas fa-file-pdf mr-1"></i>Descargar PDF completo
                        </a>
                    </div>

                    <div class="book-cover-toc">
                        <div class="book-cover-toc-title">Contenido del manual</div>
                        <?php foreach (($chapters ?? []) as $chapter): ?>
                            <a href="#<?= e((string) $chapter['id']) ?>">
                                <span><?= str_pad((string) $chapter['number'], 2, '0', STR_PAD_LEFT) ?></span>
                                <?= e((string) $chapter['title']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <p class="book-cover-foot">
                        <i class="fas fa-circle-info mr-1"></i>La versión PDF incluye portada, índice y todos
                        los diagramas. Cada capítulo también tiene su propio PDF.
                    </p>
                </div>
            </section>

            <?php foreach (($chapters ?? []) as $chapter): ?>
                <?php if (\Lib\Core\View::exists($chapter['view'])): ?>
                    <?= \Lib\Core\View::component($chapter['view']) ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="book-pager" id="book-pager">
            <button type="button" class="book-pager-btn" id="book-prev">
                <span class="book-pager-dir"><i class="fas fa-arrow-left mr-1"></i>Anterior</span>
                <strong id="book-prev-title">Portada</strong>
            </button>
            <a class="book-pager-home" href="#docs-cover">
                <i class="fas fa-book-open mr-1"></i>Portada
            </a>
            <button type="button" class="book-pager-btn book-pager-next" id="book-next">
                <span class="book-pager-dir">Siguiente<i class="fas fa-arrow-right ml-1"></i></span>
                <strong id="book-next-title">Capítulo 1</strong>
            </button>
        </div>
    </div>
</div>

<div class="book-overlay" id="book-overlay"></div>
