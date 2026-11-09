/* ==========================================================================
   Documentación del sistema — buscador, índice lateral, copiar código
   ========================================================================== */
(function () {
    'use strict';

    var content = document.getElementById('docs-content');
    var tocBox = document.getElementById('docs-toc');
    var searchInput = document.getElementById('docs-search-input');
    var resultsBox = document.getElementById('docs-search-results');
    var cover = document.getElementById('docs-cover');
    var sidebar = document.getElementById('docs-sidebar');
    var overlay = document.getElementById('book-overlay');
    var indexToggle = document.getElementById('book-index-toggle');
    var chapterLabel = document.getElementById('book-chapter-label');
    var chapterTitle = document.getElementById('book-chapter-title');
    var progressBar = document.getElementById('book-progress-bar');
    var prevBtn = document.getElementById('book-prev');
    var nextBtn = document.getElementById('book-next');
    var prevTitle = document.getElementById('book-prev-title');
    var nextTitle = document.getElementById('book-next-title');

    if (!content) {
        return;
    }

    var bookChapters = Array.prototype.slice.call(content.querySelectorAll('[data-doc-chapter]'));
    var activePage = null;

    document.documentElement.classList.add('book-js');

    if (cover) {
        cover.classList.add('book-page');
    }

    bookChapters.forEach(function (chapter) {
        chapter.classList.add('book-page');
    });

    /* ---------------------------------------------------------------------
     * Utilidades de texto (normalización sin romper posiciones)
     * ------------------------------------------------------------------- */

    var DIACRITICS = {
        'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ü': 'u', 'ñ': 'n',
        'à': 'a', 'è': 'e', 'ì': 'i', 'ò': 'o', 'ù': 'u', 'ç': 'c',
        'Á': 'a', 'É': 'e', 'Í': 'i', 'Ó': 'o', 'Ú': 'u', 'Ü': 'u', 'Ñ': 'n',
        'À': 'a', 'È': 'e', 'Ì': 'i', 'Ò': 'o', 'Ù': 'u', 'Ç': 'c'
    };

    var DIACRITIC_RE = new RegExp('[' + Object.keys(DIACRITICS).join('') + ']', 'g');

    function normalize(text) {
        return String(text || '')
            .replace(DIACRITIC_RE, function (char) {
                return DIACRITICS[char];
            })
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function escapeHtml(text) {
        return String(text === null || text === undefined ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function markSnippet(text, terms) {
        var normalized = normalize(text);
        var ranges = [];
        var i;
        var position;

        for (i = 0; i < terms.length; i++) {
            position = 0;

            while (position < normalized.length) {
                var found = normalized.indexOf(terms[i], position);

                if (found === -1) {
                    break;
                }

                ranges.push([found, found + terms[i].length]);
                position = found + terms[i].length;
            }
        }

        if (ranges.length === 0) {
            return escapeHtml(text);
        }

        ranges.sort(function (a, b) {
            return a[0] - b[0];
        });

        var merged = [ranges[0]];

        for (i = 1; i < ranges.length; i++) {
            var last = merged[merged.length - 1];

            if (ranges[i][0] <= last[1]) {
                last[1] = Math.max(last[1], ranges[i][1]);
            } else {
                merged.push(ranges[i]);
            }
        }

        var html = '';
        var cursor = 0;

        for (i = 0; i < merged.length; i++) {
            html += escapeHtml(text.slice(cursor, merged[i][0]));
            html += '<mark>' + escapeHtml(text.slice(merged[i][0], merged[i][1])) + '</mark>';
            cursor = merged[i][1];
        }

        html += escapeHtml(text.slice(cursor));

        return html;
    }

    /* ---------------------------------------------------------------------
     * Índice de búsqueda (a partir del DOM renderizado)
     * ------------------------------------------------------------------- */

    var index = [];

    function buildIndex() {
        index = [];

        content.querySelectorAll('[data-doc-chapter]').forEach(function (chapter, chapterPosition) {
            var chapterTitle = chapter.getAttribute('data-title') || '';
            var chapterNumber = chapter.getAttribute('data-number') || String(chapterPosition + 1);
            var lead = chapter.querySelector('.doc-chapter-lead');

            index.push({
                id: chapter.id,
                chapterId: chapter.id,
                chapterTitle: chapterTitle,
                number: chapterNumber,
                title: chapterTitle,
                text: normalize(lead ? lead.textContent : chapterTitle)
            });

            chapter.querySelectorAll('[data-doc-section]').forEach(function (section) {
                if (!section.id) {
                    return;
                }

                index.push({
                    id: section.id,
                    chapterId: chapter.id,
                    chapterTitle: chapterTitle,
                    number: chapterNumber,
                    title: section.getAttribute('data-title') || section.id,
                    text: normalize(section.textContent)
                });
            });
        });
    }

    /* ---------------------------------------------------------------------
     * Índice lateral (TOC) + scroll spy
     * ------------------------------------------------------------------- */

    var tocLinks = [];

    function buildToc() {
        if (!tocBox) {
            return;
        }

        var html = '<a class="book-toc-cover" href="#docs-cover">'
            + '<i class="fas fa-book-open mr-2"></i>Portada</a>'
            + '<div class="docs-toc-group-title">Contenido</div>';

        content.querySelectorAll('[data-doc-chapter]').forEach(function (chapter, position) {
            var number = chapter.getAttribute('data-number') || String(position + 1);
            var title = chapter.getAttribute('data-title') || chapter.id;

            html += '<div class="book-toc-chapter" data-chapter="' + escapeHtml(chapter.id) + '">'
                + '<a class="doc-toc-chapter" href="#' + chapter.id + '">'
                + '<span class="docs-toc-num">' + escapeHtml(number) + '.</span> '
                + escapeHtml(title) + '</a>'
                + '<div class="book-toc-sections">';

            chapter.querySelectorAll('[data-doc-section]').forEach(function (section) {
                if (!section.id) {
                    return;
                }

                html += '<a class="doc-toc-section" href="#' + section.id + '">'
                    + escapeHtml(section.getAttribute('data-title') || section.id) + '</a>';
            });

            html += '</div></div>';
        });

        tocBox.innerHTML = html;

        tocLinks = Array.prototype.slice.call(tocBox.querySelectorAll('a'));

        tocLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                var hash = link.getAttribute('href');

                clearHighlights();
                closeSidebar();
                navigate(hash, 'auto');
                setHash(hash);
            });
        });
    }

    /* ---------------------------------------------------------------------
     * Libro virtual: portada, páginas, progreso y paginador
     * ------------------------------------------------------------------- */

    function pageList() {
        return (cover ? [cover] : []).concat(bookChapters);
    }

    function pageIndex(page) {
        return bookChapters.indexOf(page);
    }

    function pageTitle(page) {
        return page.getAttribute('data-title') || page.id;
    }

    function setHash(hash) {
        if (window.history && window.history.pushState) {
            window.history.pushState(null, '', hash);
        } else {
            window.location.hash = hash;
        }
    }

    function showPage(page, options) {
        if (!page) {
            return;
        }

        options = options || {};
        activePage = page;

        pageList().forEach(function (item) {
            item.classList.toggle('is-active', item === page);
        });

        var index = pageIndex(page);

        if (chapterLabel) {
            chapterLabel.textContent = page === cover
                ? 'Portada'
                : 'Capítulo ' + (index + 1) + ' de ' + bookChapters.length;
        }

        if (chapterTitle) {
            chapterTitle.textContent = page === cover
                ? 'Documentación del CMS'
                : pageTitle(page);
        }

        if (tocBox) {
            tocBox.querySelectorAll('.book-toc-chapter').forEach(function (group) {
                group.classList.toggle('is-current', group.getAttribute('data-chapter') === page.id);
            });

            var coverLink = tocBox.querySelector('.book-toc-cover');

            if (coverLink) {
                coverLink.classList.toggle('is-current', page === cover);
            }
        }

        var previous = page === cover ? null : (index === 0 ? cover : bookChapters[index - 1]);
        var next = page === cover
            ? (bookChapters[0] || null)
            : (index < bookChapters.length - 1 ? bookChapters[index + 1] : null);

        if (prevBtn) {
            prevBtn.disabled = !previous;
        }

        if (nextBtn) {
            nextBtn.disabled = !next;
        }

        if (prevTitle) {
            prevTitle.textContent = !previous
                ? 'Inicio del manual'
                : (previous === cover ? 'Portada' : 'Capítulo ' + (pageIndex(previous) + 1));
        }

        if (nextTitle) {
            nextTitle.textContent = !next
                ? 'Fin del manual'
                : 'Capítulo ' + (pageIndex(next) + 1);
        }

        updateProgress();

        if (options.scroll !== false) {
            window.scrollTo({ top: 0, behavior: options.behavior || 'auto' });
        }
    }

    function navigate(hash, behavior) {
        var id = String(hash || '').replace(/^#/, '');
        var target = id ? document.getElementById(id) : null;
        var page = null;

        if (target) {
            page = target.closest ? target.closest('[data-doc-chapter]') : null;

            if (!page && target === cover) {
                page = cover;
            }
        } else if (!id || id === 'portada' || id === 'docs-cover') {
            page = cover;
        }

        if (!page) {
            showPage(cover, { scroll: false, behavior: behavior });
            window.scrollTo({ top: 0, behavior: behavior || 'auto' });
            return;
        }

        showPage(page, { scroll: false, behavior: behavior });

        if (target && target !== page) {
            window.requestAnimationFrame(function () {
                target.scrollIntoView({ behavior: behavior || 'auto', block: 'start' });
            });
        } else {
            window.scrollTo({ top: 0, behavior: behavior || 'auto' });
        }
    }

    function goRelative(delta) {
        var pages = pageList();
        var position = pages.indexOf(activePage || cover);
        var page = pages[position + delta];

        if (!page) {
            return;
        }

        navigate('#' + page.id, 'auto');
        setHash('#' + page.id);
    }

    function updateProgress() {
        if (!progressBar) {
            return;
        }

        var max = document.documentElement.scrollHeight - window.innerHeight;
        var percent = max > 0 ? Math.min(100, Math.max(0, (window.scrollY / max) * 100)) : 0;

        progressBar.style.width = percent.toFixed(2) + '%';
    }

    function openSidebar() {
        if (!sidebar) {
            return;
        }

        sidebar.classList.add('is-open');

        if (overlay) {
            overlay.classList.add('is-open');
        }
    }

    function closeSidebar() {
        if (!sidebar) {
            return;
        }

        sidebar.classList.remove('is-open');

        if (overlay) {
            overlay.classList.remove('is-open');
        }
    }

    if (indexToggle) {
        indexToggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('is-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            goRelative(-1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            goRelative(1);
        });
    }

    window.addEventListener('hashchange', function () {
        navigate(window.location.hash, 'auto');
    });

    function scrollSpy() {
        if (tocLinks.length === 0 || !activePage || activePage === cover) {
            tocLinks.forEach(function (link) {
                link.classList.remove('active');
            });

            return;
        }

        var threshold = 120;
        var current = null;
        var links = tocLinks.filter(function (link) {
            var target = document.getElementById((link.getAttribute('href') || '').slice(1));

            return target && (target === activePage || activePage.contains(target));
        });

        for (var i = 0; i < links.length; i++) {
            var target = document.getElementById(links[i].getAttribute('href').slice(1));

            if (!target) {
                continue;
            }

            if (target.getBoundingClientRect().top <= threshold) {
                current = links[i];
            } else {
                break;
            }
        }

        tocLinks.forEach(function (link) {
            link.classList.remove('active');
        });

        if (current) {
            current.classList.add('active');

            var box = tocBox.getBoundingClientRect();
            var link = current.getBoundingClientRect();

            if (link.top < box.top || link.bottom > box.bottom) {
                current.scrollIntoView({ block: 'nearest' });
            }
        }
    }

    var spyScheduled = false;

    window.addEventListener('scroll', function () {
        if (spyScheduled) {
            return;
        }

        spyScheduled = true;

        window.requestAnimationFrame(function () {
            spyScheduled = false;
            scrollSpy();
            updateProgress();
        });
    }, { passive: true });

    /* ---------------------------------------------------------------------
     * Búsqueda
     * ------------------------------------------------------------------- */

    var state = {
        results: [],
        active: -1
    };

    function findResults(query) {
        var terms = normalize(query).split(' ').filter(function (term) {
            return term.length > 1;
        });

        if (terms.length === 0) {
            return [];
        }

        var results = [];

        index.forEach(function (entry) {
            var titleNorm = normalize(entry.title);
            var ok = terms.every(function (term) {
                return entry.text.indexOf(term) !== -1 || titleNorm.indexOf(term) !== -1;
            });

            if (!ok) {
                return;
            }

            var score = 0;

            if (titleNorm.indexOf(terms[0]) === 0) {
                score += 40;
            } else if (titleNorm.indexOf(terms[0]) !== -1) {
                score += 25;
            }

            if (entry.text.indexOf(terms[0]) !== -1) {
                score += 10;
            }

            results.push({ entry: entry, score: score });
        });

        results.sort(function (a, b) {
            return b.score - a.score;
        });

        return results.slice(0, 20).map(function (item) {
            return item.entry;
        });
    }

    function snippetFor(entry, terms) {
        var text = entry.text;
        var position = -1;

        for (var i = 0; i < terms.length && position === -1; i++) {
            position = text.indexOf(terms[i]);
        }

        if (position === -1) {
            position = 0;
        }

        var start = Math.max(0, position - 70);
        var end = Math.min(text.length, position + 130);
        var slice = (start > 0 ? '…' : '') + text.slice(start, end) + (end < text.length ? '…' : '');

        return markSnippet(slice, terms);
    }

    function renderResults() {
        if (!resultsBox) {
            return;
        }

        var query = searchInput ? searchInput.value : '';
        var terms = normalize(query).split(' ').filter(function (term) {
            return term.length > 1;
        });

        if (terms.length === 0) {
            resultsBox.classList.remove('open');
            resultsBox.innerHTML = '';
            state.results = [];
            state.active = -1;
            return;
        }

        state.results = findResults(query);
        state.active = state.results.length > 0 ? 0 : -1;

        var html = '';

        if (state.results.length === 0) {
            html = '<div class="docs-search-empty">Sin resultados para «' + escapeHtml(query) + '»</div>';
        } else {
            html += '<div class="docs-search-count">' + state.results.length + ' resultado(s) · usa ↑ ↓ y Enter</div>';

            state.results.forEach(function (entry, position) {
                html += '<a class="docs-search-item' + (position === 0 ? ' active' : '') + '" href="#' + entry.id + '" data-position="' + position + '">'
                    + '<span class="docs-search-crumb">Capítulo ' + escapeHtml(entry.number) + ' · ' + escapeHtml(entry.chapterTitle) + '</span>'
                    + '<span class="docs-search-title">' + markSnippet(entry.title, terms) + '</span>'
                    + '<span class="docs-search-snippet">' + snippetFor(entry, terms) + '</span>'
                    + '</a>';
            });
        }

        resultsBox.innerHTML = html;
        resultsBox.classList.add('open');

        resultsBox.querySelectorAll('.docs-search-item').forEach(function (item) {
            item.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });

            item.addEventListener('click', function () {
                jumpTo(state.results[parseInt(item.getAttribute('data-position'), 10)], terms);
                closeSearch();
            });
        });
    }

    function closeSearch() {
        if (resultsBox) {
            resultsBox.classList.remove('open');
        }
    }

    function moveActive(delta) {
        if (!resultsBox || state.results.length === 0) {
            return;
        }

        state.active = (state.active + delta + state.results.length) % state.results.length;

        resultsBox.querySelectorAll('.docs-search-item').forEach(function (item, position) {
            item.classList.toggle('active', position === state.active);
        });

        var activeItem = resultsBox.querySelector('.docs-search-item.active');

        if (activeItem) {
            activeItem.scrollIntoView({ block: 'nearest' });
        }
    }

    /* ---------------------------------------------------------------------
     * Resaltado dentro del contenido
     * ------------------------------------------------------------------- */

    function clearHighlights() {
        content.querySelectorAll('mark.doc-mark').forEach(function (mark) {
            var parent = mark.parentNode;

            if (!parent) {
                return;
            }

            parent.replaceChild(document.createTextNode(mark.textContent), mark);
            parent.normalize();
        });
    }

    function highlightSection(section, terms) {
        var walker = document.createTreeWalker(section, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                if (!node.nodeValue || normalize(node.nodeValue).length === 0) {
                    return NodeFilter.FILTER_REJECT;
                }

                if (node.parentElement && node.parentElement.closest('svg')) {
                    return NodeFilter.FILTER_REJECT;
                }

                var normalized = normalize(node.nodeValue);
                var matches = terms.some(function (term) {
                    return normalized.indexOf(term) !== -1;
                });

                return matches ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });

        var nodes = [];

        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }

        nodes.forEach(function (node) {
            var text = node.nodeValue;
            var normalized = normalize(text);
            var ranges = [];

            terms.forEach(function (term) {
                var position = 0;

                while (position < normalized.length) {
                    var found = normalized.indexOf(term, position);

                    if (found === -1) {
                        break;
                    }

                    ranges.push([found, found + term.length]);
                    position = found + term.length;
                }
            });

            if (ranges.length === 0) {
                return;
            }

            ranges.sort(function (a, b) {
                return a[0] - b[0];
            });

            var fragment = document.createDocumentFragment();
            var cursor = 0;

            ranges.forEach(function (range) {
                if (range[0] < cursor) {
                    return;
                }

                fragment.appendChild(document.createTextNode(text.slice(cursor, range[0])));

                var mark = document.createElement('mark');
                mark.className = 'doc-mark';
                mark.textContent = text.slice(range[0], range[1]);
                fragment.appendChild(mark);

                cursor = range[1];
            });

            fragment.appendChild(document.createTextNode(text.slice(cursor)));
            node.parentNode.replaceChild(fragment, node);
        });
    }

    function jumpTo(entry, terms) {
        var target = document.getElementById(entry.id);

        if (!target) {
            return;
        }

        var page = target.closest ? target.closest('[data-doc-chapter]') : null;

        if (page) {
            showPage(page, { scroll: false });
        }

        clearHighlights();

        if (terms && terms.length > 0) {
            highlightSection(target, terms);
        }

        window.requestAnimationFrame(function () {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + entry.id);
        }
    }

    /* ---------------------------------------------------------------------
     * Atajos de teclado
     * ------------------------------------------------------------------- */

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            renderResults();
        });

        searchInput.addEventListener('focus', function () {
            if (searchInput.value.trim().length > 1) {
                renderResults();
            }
        });

        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveActive(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(-1);
            } else if (event.key === 'Enter') {
                if (state.results.length > 0 && state.active >= 0 && resultsBox && resultsBox.classList.contains('open')) {
                    event.preventDefault();

                    var terms = normalize(searchInput.value).split(' ').filter(function (term) {
                        return term.length > 1;
                    });

                    jumpTo(state.results[state.active], terms);
                    closeSearch();
                    searchInput.blur();
                }
            } else if (event.key === 'Escape') {
                searchInput.value = '';
                closeSearch();
                searchInput.blur();
                clearHighlights();
            }
        });

        document.addEventListener('click', function (event) {
            if (!resultsBox) {
                return;
            }

            if (!resultsBox.contains(event.target) && event.target !== searchInput) {
                closeSearch();
            }
        });

        document.addEventListener('keydown', function (event) {
            var isTyping = /^(input|textarea|select)$/i.test(document.activeElement && document.activeElement.tagName);

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                openSidebar();
                searchInput.focus();
                searchInput.select();
                return;
            }

            if (event.key === '/' && !isTyping) {
                event.preventDefault();
                openSidebar();
                searchInput.focus();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        var focused = document.activeElement;
        var isTyping = focused && (/^(input|textarea|select)$/i.test(focused.tagName) || focused.isContentEditable);

        if (isTyping) {
            return;
        }

        if (event.key === 'Escape') {
            closeSidebar();
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            goRelative(1);
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            goRelative(-1);
        }
    });

    /* ---------------------------------------------------------------------
     * Copiar bloques de código + botón subir
     * ------------------------------------------------------------------- */

    document.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-doc-copy]') : null;

        if (!button) {
            return;
        }

        var block = button.closest('.doc-code');
        var pre = block ? block.querySelector('pre') : null;

        if (!pre) {
            return;
        }

        var text = pre.innerText;

        function feedback(ok) {
            button.textContent = ok ? 'Copiado' : 'Error';
            button.classList.add('copied');

            window.setTimeout(function () {
                button.textContent = 'Copiar';
                button.classList.remove('copied');
            }, 1600);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                feedback(true);
            }, function () {
                feedback(false);
            });

            return;
        }

        var area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();

        try {
            feedback(document.execCommand('copy'));
        } catch (error) {
            feedback(false);
        }

        document.body.removeChild(area);
    });

    var backTop = document.createElement('button');
    backTop.type = 'button';
    backTop.className = 'doc-back-top';
    backTop.innerHTML = '&uarr;';
    backTop.setAttribute('title', 'Volver arriba');
    backTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    document.body.appendChild(backTop);

    window.addEventListener('scroll', function () {
        backTop.classList.toggle('show', window.scrollY > 600);
    }, { passive: true });

    /* ---------------------------------------------------------------------
     * Inicio
     * ------------------------------------------------------------------- */

    buildIndex();
    buildToc();

    if (window.location.hash) {
        navigate(window.location.hash, 'auto');
    } else {
        showPage(cover, { scroll: false });
        window.scrollTo(0, 0);
    }

    scrollSpy();
    updateProgress();
})();
