/* Clínica Vida Salud - Landing interactions + AJAX navigation */
(function ($) {
    'use strict';

    var CL = {
        /* =====================================================================
           INICIALIZACIÓN
           ===================================================================== */
        init: function () {
            this.scrollAnchors();
            this.mobileMenu();
            this.navShadow();
            this.revealOnScroll();
            this.tiltCards();
            this.animatedStats();
            this.ajaxNavigation();
            this.filtrosListados();
        },

        /* =====================================================================
           SCROLL SUAVE PARA ANCLAS
           ===================================================================== */
        scrollAnchors: function () {
            $(document).on('click', 'a[href^="#"]', function (e) {
                var href = $(this).attr('href');
                if (href === '#') return;
                var $target = $(href);
                if ($target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 80
                    }, 500);
                    $('#clNavLinks').removeClass('is-open');
                    $('#clNavToggle').attr('aria-expanded', 'false');
                }
            });
        },

        /* =====================================================================
           MENÚ MÓVIL
           ===================================================================== */
        mobileMenu: function () {
            $(document).on('click', '#clNavToggle', function () {
                var $links = $('#clNavLinks');
                var isOpen = $links.toggleClass('is-open').hasClass('is-open');
                $(this).attr('aria-expanded', String(isOpen));
            });
        },

        /* =====================================================================
           SOMBRA EN NAVEGACIÓN AL HACER SCROLL
           ===================================================================== */
        navShadow: function () {
            var $nav = $('#clNav');
            if (!$nav.length) return;

            $(window).on('scroll', function () {
                $nav.toggleClass('scrolled', $(window).scrollTop() > 10);
            });
        },

        /* =====================================================================
           REVELADO DE ELEMENTOS AL HACER SCROLL
           ===================================================================== */
        revealOnScroll: function () {
            var $elements = $('.cl-reveal');
            if (!$elements.length) return;

            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            $(entry.target).addClass('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

                $elements.each(function () {
                    observer.observe(this);
                });
            } else {
                $elements.addClass('is-visible');
            }
        },

        /* =====================================================================
           EFECTO TILT EN TARJETAS
           ===================================================================== */
        tiltCards: function () {
            $(document).on('mousemove', '[data-tilt]', function (e) {
                var $card = $(this);
                var rect = this.getBoundingClientRect();
                var x = e.clientX - rect.left;
                var y = e.clientY - rect.top;
                var cx = rect.width / 2;
                var cy = rect.height / 2;
                var dx = (x - cx) / cx;
                var dy = (y - cy) / cy;
                $card.css('transform', 'perspective(800px) rotateY(' + (dx * 4) + 'deg) rotateX(' + (-dy * 4) + 'deg) translateY(-4px)');
            }).on('mouseleave', '[data-tilt]', function () {
                $(this).css('transform', '');
            });
        },

        /* =====================================================================
           CONTADOR ANIMADO PARA ESTADÍSTICAS
           ===================================================================== */
        animatedStats: function () {
            $('.cl-stat strong').each(function () {
                var $stat = $(this);
                var original = $stat.text().trim();
                var numeric = parseFloat(original.replace(/[^0-9.]/g, ''));
                var suffix = original.replace(/[0-9.]/g, '');

                if (isNaN(numeric) || numeric <= 0) return;

                var animated = false;
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting && !animated) {
                            animated = true;
                            CL.animateValue($stat, 0, numeric, 1200, suffix);
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });

                observer.observe(this);
            });
        },

        animateValue: function ($element, start, end, duration, suffix) {
            var startTime = performance.now();
            function update(currentTime) {
                var elapsed = currentTime - startTime;
                var progress = Math.min(elapsed / duration, 1);
                var ease = 1 - Math.pow(1 - progress, 3);
                var current = Math.floor(start + (end - start) * ease);
                $element.text(current + suffix);
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            requestAnimationFrame(update);
        },

        /* =====================================================================
           NAVEGACIÓN AJAX
           ===================================================================== */
        ajaxNavigation: function () {
            var that = this;

            $(document).on('click', 'a[data-ajax="true"], a[href^="/"], a[href^="' + window.location.origin + '/"]', function (e) {
                var $link = $(this);
                var href = $link.attr('href');

                if (!href || href === '#' || href.startsWith('#')) return;
                if ($link.is('[data-no-ajax]')) return;
                if ($link.attr('target') === '_blank') return;
                if (e.ctrlKey || e.metaKey || e.shiftKey) return;
                if (!href.startsWith('/') && !href.startsWith(window.location.origin)) return;

                // Áreas que siguen recargando normalmente
                if (href.startsWith('/join/access/dashboard') || href.startsWith('/portal')) return;

                e.preventDefault();
                that.loadPage(href, true);
            });

            $(window).on('popstate', function (e) {
                if (e.originalEvent.state && e.originalEvent.state.url) {
                    that.loadPage(e.originalEvent.state.url, false);
                }
            });
        },

        loadPage: function (url, pushState) {
            var that = this;
            var $main = $('#contenido');

            if (!$main.length) {
                window.location.href = url;
                return;
            }

            $main.addClass('cl-page-loading');

            $.ajax({
                url: url,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .done(function (html) {
                var $parsed = $('<div>').append($.parseHTML(html, document, true));
                var $newMain = $parsed.find('#contenido');

                if (!$newMain.length) {
                    window.location.href = url;
                    return;
                }

                // Limpiar e inyectar estilos específicos de la nueva página
                $('style[data-ajax-style], link[data-ajax-style]').remove();
                $parsed.find('style').each(function () {
                    var $style = $(this).clone();
                    $style.attr('data-ajax-style', 'true');
                    $('head').append($style);
                });

                $main.replaceWith($newMain);
                that.executeScripts($newMain);
                that.reinitContent();

                var newTitle = $parsed.find('title').text();
                if (newTitle) document.title = newTitle;

                var newDesc = $parsed.find('meta[name="description"]').attr('content');
                if (newDesc) $('meta[name="description"]').attr('content', newDesc);

                if (pushState) {
                    history.pushState({ url: url }, newTitle || document.title, url);
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .fail(function () {
                window.location.href = url;
            })
            .always(function () {
                $main.removeClass('cl-page-loading');
            });
        },

        reinitContent: function () {
            this.revealOnScroll();
            this.tiltCards();
            this.animatedStats();
            this.filtrosListados();
        },

        executeScripts: function ($container) {
            $container.find('script').each(function () {
                var $script = $(this);
                var code = $script.html();
                if (code) {
                    try {
                        $.globalEval(code);
                    } catch (e) {
                        console.error('Error ejecutando script inline:', e);
                    }
                }
            });
        },

        /* =====================================================================
           FILTROS DE LISTADOS
           ===================================================================== */
        filtrosListados: function () {
            // Filtrado de especialidades de la portada (#especialidades)
            $(document).on('input', '#buscar-especialidad', function () {
                var termino = String($(this).val() || '').toLowerCase().trim();
                var $cards = $('#especialidades-grid .cl-especialidad-card');
                var visibles = 0;

                $cards.each(function () {
                    var $card = $(this);
                    var coincide = String($card.data('nombre') || '').toLowerCase().indexOf(termino) !== -1;
                    $card.toggle(coincide);
                    if (coincide) visibles++;
                });

                var vacio = visibles === 0 && termino !== '';
                $('#especialidades-sin-resultados').toggle(vacio);
                $('#especialidades-grid').toggle(!vacio);
            });

            // Filtrado del directorio de médicos de la portada (#medicos)
            function filtrarMedicos() {
                var termino = String($('#buscar-medico').val() || '').toLowerCase().trim();
                var especialidad = String($('#filtrar-especialidad').val() || '').toLowerCase();
                var $cards = $('#medicos-grid .cl-medico-card');
                var visibles = 0;

                $cards.each(function () {
                    var $card = $(this);
                    var nombre = String($card.data('nombre') || '').toLowerCase();
                    var especialidades = String($card.data('especialidades') || '').toLowerCase();
                    var coincideNombre = nombre.indexOf(termino) !== -1;
                    var coincideEspecialidad = especialidad === '' || especialidades.indexOf(especialidad) !== -1;
                    var coincide = coincideNombre && coincideEspecialidad;
                    $card.toggle(coincide);
                    if (coincide) visibles++;
                });

                $('#medicos-sin-resultados').toggle(visibles === 0);
                $('#medicos-grid').toggle(visibles !== 0);
            }

            $(document).on('input', '#buscar-medico', filtrarMedicos);
            $(document).on('change', '#filtrar-especialidad', filtrarMedicos);

            // Tarjeta de especialidad -> precarga el buscador de médicos
            $(document).on('click', '.js-ver-medicos', function () {
                var termino = String($(this).data('buscar') || '');

                if (termino === '') {
                    return;
                }

                $('#buscar-medico').val(termino);
                $('#filtrar-especialidad').val('');
                filtrarMedicos();
            });
        }
    };

    $(function () {
        CL.init();
    });

})(jQuery);
