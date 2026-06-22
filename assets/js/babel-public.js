/**
 * Babel Directory Public Script (Vanilla JS)
 * v8.0.0 — Buscador Simplificado y Moderno sin Listados Verticales.
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('babel-search-form');
    const resultsContainer = document.getElementById('babel-directory-results');
    const keywordInput = document.getElementById('babel-search-keyword');
    const latInput = document.getElementById('babel-search-lat');
    const lngInput = document.getElementById('babel-search-lng');
    const radiusInput = document.getElementById('babel-search-radius');
    const geoBtn = document.getElementById('babel-geo-btn');

    // reCAPTCHA v3 Interceptor para Claim Forms
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.classList.contains('babel-claim-form')) {
            const claimForm = e.target;
            if (typeof babel_vars !== 'undefined' && babel_vars.recaptcha_site_key && typeof grecaptcha !== 'undefined') {
                const tokenInput = claimForm.querySelector('.babel-recaptcha-token');
                if (tokenInput && !tokenInput.value) {
                    e.preventDefault();
                    
                    const btn = claimForm.querySelector('button[type="submit"]');
                    const originalText = btn ? btn.innerHTML : '';
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = 'Verificando...';
                    }

                    grecaptcha.ready(function() {
                        grecaptcha.execute(babel_vars.recaptcha_site_key, {action: 'claim'}).then(function(token) {
                            tokenInput.value = token;
                            if (btn) btn.innerHTML = originalText;
                            claimForm.submit();
                        }).catch(function() {
                            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                            claimForm.submit();
                        });
                    });
                }
            }
        }
    });

    if (!searchForm && !resultsContainer) {
        return;
    }

    // Parsear parámetros GET al cargar para inicializar el formulario
    if (searchForm) {
        const urlParams = new URLSearchParams(window.location.search);
        const urlKeyword = urlParams.get('keyword');
        const urlLat = urlParams.get('lat');
        const urlLng = urlParams.get('lng');
        const urlRadius = urlParams.get('radius');

        if (urlKeyword && keywordInput) {
            keywordInput.value = urlKeyword;
        }
        if (urlLat && latInput) {
            latInput.value = urlLat;
        }
        if (urlLng && lngInput) {
            lngInput.value = urlLng;
        }
        if (urlRadius && radiusInput) {
            radiusInput.value = urlRadius;
        }

        // Si hay coordenadas en la URL, activar visualmente el radar
        if (urlLat && urlLng && geoBtn && keywordInput) {
            geoBtn.classList.add('active');
            keywordInput.placeholder = '✓ Buscando cerca de ti...';
            keywordInput.classList.add('babel-radar-active');
            
            // Intentar recuperar dirección si venimos de un reload con lat/lng
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${urlLat}&lon=${urlLng}&accept-language=es`)
                .then(r => r.json())
                .then(d => {
                    if (d && d.display_name) {
                        let loc = '';
                        if (d.address) {
                            loc = d.address.city || d.address.town || d.address.village || d.address.suburb || d.address.county || '';
                        }
                        if (!loc) {
                            loc = d.display_name.split(',')[0];
                        }
                        keywordInput.placeholder = '✓ Cerca de: ' + loc;
                    }
                })
                .catch(err => console.log('Reverse geocoding error:', err));
        }
    }

    // Estado interno de la consulta
    let currentPaged = 1;

    /**
     * Helper: Crea una función con retardo (Debounce)
     */
    function debounce(func, delay) {
        let timeoutId;
        const debounced = function (...args) {
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
        debounced.cancel = function() {
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
        };
        return debounced;
    }

    /**
     * Realiza la petición AJAX mediante Fetch API de forma asíncrona.
     */
    async function performSearch(page = 1) {
        if (!resultsContainer) return;

        currentPaged = page;

        // Añadir estado visual de carga
        resultsContainer.classList.add('babel-loading-state');
        resultsContainer.style.opacity = '0.6';

        // Recopilar valores del formulario de manera segura
        const keywordInputEl = searchForm ? searchForm.querySelector('#babel-search-keyword') : null;
        const keyword = keywordInputEl ? keywordInputEl.value : '';
        
        // Categorías y Regiones obtenidas dinámicamente desde el contenedor de resultados
        const category = resultsContainer.getAttribute('data-category') || '';
        let region = resultsContainer.getAttribute('data-region') || '';

        // Si hay un selector de región interactivo, usar su valor
        const regionSelectEl = searchForm ? searchForm.querySelector('#babel-search-region-select') : null;
        if (regionSelectEl) {
            region = regionSelectEl.value;
            resultsContainer.setAttribute('data-region', region);
        }
        
        // Parámetros de geolocalización (Radar)
        const lat = searchForm ? searchForm.querySelector('#babel-search-lat').value : '';
        const lng = searchForm ? searchForm.querySelector('#babel-search-lng').value : '';
        const radius = searchForm ? searchForm.querySelector('#babel-search-radius').value : '25';

        // Construir datos de envío
        const payload = new URLSearchParams();
        payload.append('action', 'bd_filter_listings');
        payload.append('nonce', babel_vars.nonce);
        payload.append('keyword', keyword);
        payload.append('category', category);
        payload.append('region', region);
        payload.append('paged', currentPaged);

        // Adjuntar geolocalización si el radar está activo
        if (lat && lng) {
            payload.append('lat', lat);
            payload.append('lng', lng);
            payload.append('radius', radius);
        }

        try {
            // Usamos la nueva REST API de alto rendimiento en lugar de admin-ajax
            const fetchUrl = babel_vars.rest_url ? babel_vars.rest_url : babel_vars.ajax_url;

            const response = await fetch(fetchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: payload.toString()
            });

            if (!response.ok) {
                throw new Error(`HTTP Error Status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                resultsContainer.innerHTML = result.data.html || '';
                
                if (page > 1) {
                    resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                resultsContainer.innerHTML = '';
                const errDiv = document.createElement('div');
                errDiv.className = 'babel-error-message';
                errDiv.textContent = typeof result.data === 'string' ? result.data : 'Ocurrió un error inesperado al cargar los resultados.';
                resultsContainer.appendChild(errDiv);
            }
        } catch (error) {
            console.error('Babel Directory Search Error:', error);
            resultsContainer.innerHTML = '';
            const errDiv = document.createElement('div');
            errDiv.className = 'babel-error-message';
            errDiv.textContent = 'Error de conexión con el servidor. Por favor, intenta de nuevo más tarde.';
            resultsContainer.appendChild(errDiv);
        } finally {
            resultsContainer.classList.remove('babel-loading-state');
            resultsContainer.style.opacity = '1';
        }
    }

    // Crear la versión debounced de la búsqueda
    const debouncedSearch = debounce(() => performSearch(1), 300);

    // ==========================================================================
    // 0. AUTOCOMPLETADO PREDICTIVO (SUGERENCIAS)
    // ==========================================================================
    if (keywordInput) {
        // Contenedor para el dropdown
        const autocompleteDropdown = document.createElement('div');
        autocompleteDropdown.className = 'babel-autocomplete-dropdown';
        autocompleteDropdown.style.display = 'none';
        
        // Lo insertamos justo después del input
        if (keywordInput.parentNode) {
            keywordInput.parentNode.style.position = 'relative';
            keywordInput.parentNode.appendChild(autocompleteDropdown);
        }

        const fetchSuggestions = debounce(async (query) => {
            if (query.length < 2) {
                autocompleteDropdown.style.display = 'none';
                return;
            }
            
            try {
                // Obtener la base de la URL REST desde babel_vars (fallback a wp-json manual si es necesario)
                let baseRestUrl = babel_vars.rest_url ? babel_vars.rest_url.replace('/search', '') : '/wp-json/babel/v1';
                // Si la url ya termina en /search, la limpiamos.
                if (baseRestUrl.endsWith('/search')) baseRestUrl = baseRestUrl.replace('/search', '');

                const response = await fetch(`${baseRestUrl}/suggestions?q=${encodeURIComponent(query)}`);
                if (!response.ok) return;
                
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    autocompleteDropdown.innerHTML = '';
                    result.data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'babel-autocomplete-item';
                        
                        // Icono según tipo
                        let icon = '📌';
                        let typeLabel = '';
                        if (item.type === 'category') { icon = '📁'; typeLabel = '<span class="babel-ac-type">Categoría</span>'; }
                        if (item.type === 'region') { icon = '📍'; typeLabel = '<span class="babel-ac-type">Región</span>'; }
                        if (item.type === 'business') { icon = '🏪'; }
                        
                        div.innerHTML = `<span class="babel-ac-icon">${icon}</span> <span class="babel-ac-label"></span> ${typeLabel}`;
                        div.querySelector('.babel-ac-label').textContent = item.label;
                        
                        div.addEventListener('click', () => {
                            // Si es categoría o región, rellenar el input. Lo ideal es rellenar con el nombre.
                            keywordInput.value = item.label;
                            autocompleteDropdown.style.display = 'none';
                            // Lanzar búsqueda automáticamente
                            performSearch(1);
                        });
                        
                        autocompleteDropdown.appendChild(div);
                    });
                    autocompleteDropdown.style.display = 'block';
                } else {
                    autocompleteDropdown.style.display = 'none';
                }
            } catch (err) {
                console.error("Error fetching suggestions", err);
            }
        }, 200);

        keywordInput.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            fetchSuggestions(val);
        });

        // Ocultar dropdown si se hace clic fuera
        document.addEventListener('click', (e) => {
            if (!keywordInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
                autocompleteDropdown.style.display = 'none';
            }
        });
    }

    // ==========================================================================
    // 1. RADAR & GEOLOCALIZACIÓN GPS (INTEGRADO DE FORMA MODERNA)
    // ==========================================================================
    if (searchForm) {
        if (geoBtn && latInput && lngInput) {
            geoBtn.addEventListener('click', (e) => {
                e.preventDefault();

                // Si ya está activo, se desactiva
                if (geoBtn.classList.contains('active')) {
                    latInput.value = '';
                    lngInput.value = '';
                    geoBtn.classList.remove('active');
                    
                    if (keywordInput) {
                        keywordInput.placeholder = '¿Qué buscas y dónde? (ej. Sushi, Abogado, Providencia...)';
                        keywordInput.classList.remove('babel-radar-active');
                    }
                    
                    // Removido: No auto-buscar al apagar el radar según requerimiento del cliente.
                    return;
                }

                // Iniciar proceso de geolocalización
                if (!navigator.geolocation) {
                    alert('Tu navegador no soporta geolocalización.');
                    return;
                }

                geoBtn.classList.add('loading');
                if (keywordInput) {
                    keywordInput.placeholder = 'Localizando tu posición...';
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        latInput.value = lat;
                        lngInput.value = lng;

                        geoBtn.classList.remove('loading');
                        geoBtn.classList.add('active');

                        if (keywordInput) {
                            keywordInput.placeholder = '✓ Buscando cerca de ti...';
                            keywordInput.classList.add('babel-radar-active');
                            
                            // Obtener dirección (Reverse Geocoding)
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=es`)
                                .then(r => r.json())
                                .then(d => {
                                    if (d && d.display_name) {
                                        // Extraer parte relevante (ciudad, comuna, o la primera parte)
                                        let loc = '';
                                        if (d.address) {
                                            loc = d.address.city || d.address.town || d.address.village || d.address.suburb || d.address.county || '';
                                        }
                                        if (!loc) {
                                            loc = d.display_name.split(',')[0];
                                        }
                                        keywordInput.placeholder = '✓ Cerca de: ' + loc;
                                    }
                                })
                                .catch(err => console.log('Reverse geocoding error:', err));
                        }

                        // Removido: No auto-buscar al encender el radar según requerimiento del cliente.
                    },
                    (error) => {
                        console.error('GPS Error:', error);
                        geoBtn.classList.remove('loading');
                        if (keywordInput) {
                            keywordInput.placeholder = '¿Qué buscas y dónde? (ej. Sushi, Abogado, Providencia...)';
                        }
                        alert('No se pudo obtener tu ubicación. Por favor, verifica los permisos de geolocalización de tu navegador.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            });
        }

        // Búsqueda en vivo en el keyword al escribir (debounced)
        if (keywordInput) {
            keywordInput.addEventListener('input', () => {
                if (resultsContainer) {
                    debouncedSearch();
                }
            });
            keywordInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    if (resultsContainer) {
                        e.preventDefault();
                        debouncedSearch.cancel();
                        performSearch(1);
                    }
                }
            });
        }

        // Búsqueda al cambiar la región en el dropdown
        const regionSelectInput = searchForm.querySelector('#babel-search-region-select');
        if (regionSelectInput) {
            regionSelectInput.addEventListener('change', () => {
                if (resultsContainer) {
                    performSearch(1);
                }
            });
        }

        // Selector de radio de búsqueda
        const radiusSelect = document.getElementById('babel-radius-select');
        if (radiusSelect) {
            radiusSelect.addEventListener('change', function() {
                if (radiusInput) {
                    radiusInput.value = this.value;
                }
                if (resultsContainer) {
                    performSearch(1);
                }
            });
        }

        // Evitar envío tradicional del formulario solo si estamos en la página de resultados
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (resultsContainer) {
                debouncedSearch.cancel();
                performSearch(1);
            } else {
                // Portada u otra página sin contenedor: redirigir a /buscar/?keyword=...
                const kw = keywordInput ? keywordInput.value.trim() : '';
                const url = new URL('/buscar/', window.location.origin);
                if (kw) url.searchParams.set('keyword', kw);
                window.location.href = url.toString();
            }
        });
    }

    // ==========================================================================
    // 2. PAGINACIÓN SPA DE WORDPRESS (REDIRECCIONES INTERCEPTADAS)
    // ==========================================================================
    if (resultsContainer) {
        resultsContainer.addEventListener('click', (e) => {
            const pageLink = e.target.closest('.page-numbers, .babel-pagination-wrapper a');
            if (!pageLink) return;

            e.preventDefault();

            if (pageLink.classList.contains('current')) {
                return;
            }

            let pageNum = 1;
            const href = pageLink.getAttribute('href');
            
            if (href) {
                const match = href.match(/\/page\/(\d+)/) || href.match(/paged=(\d+)/);
                if (match && match[1]) {
                    pageNum = parseInt(match[1], 10);
                } else {
                    const text = pageLink.innerText.trim();
                    if (!isNaN(text) && text !== '') {
                        pageNum = parseInt(text, 10);
                    } else if (pageLink.classList.contains('prev') || pageLink.classList.contains('previous') || pageLink.innerText.includes('«') || pageLink.innerText.includes('‹')) {
                        pageNum = currentPaged - 1;
                    } else if (pageLink.classList.contains('next') || pageLink.innerText.includes('»') || pageLink.innerText.includes('›')) {
                        pageNum = currentPaged + 1;
                    }
                }
            } else {
                const text = pageLink.innerText.trim();
                if (!isNaN(text) && text !== '') {
                    pageNum = parseInt(text, 10);
                }
            }

            if (pageNum > 0) {
                performSearch(pageNum);
            }
        });
    }

    // Interacción de Click en Pills de Categorías (Región Template)
    const categoryPills = document.querySelectorAll('.bd-category-pill');
    if (categoryPills.length > 0 && resultsContainer) {
        categoryPills.forEach(pill => {
            pill.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Desactivar todos los pills
                categoryPills.forEach(p => p.classList.remove('active'));
                
                // Activar el seleccionado
                pill.classList.add('active');
                
                // Obtener categoría y asignarla al contenedor
                const selectedCat = pill.getAttribute('data-category') || '';
                resultsContainer.setAttribute('data-category', selectedCat);

                // Update URL for SEO and UX sin recargar la página SPA
                const pillHref = pill.getAttribute('href');
                if (pillHref) {
                    window.history.pushState({ path: pillHref }, '', pillHref);
                }
                
                // Disparar búsqueda AJAX
                performSearch(1);
            });
        });
    }

    // Exponer la función reset de filtros de forma global
    window.babelResetFilters = function() {
        if (searchForm) {
            const keywordInput = searchForm.querySelector('#babel-search-keyword');
            const latInput = searchForm.querySelector('#babel-search-lat');
            const lngInput = searchForm.querySelector('#babel-search-lng');
            const geoBtn = document.getElementById('babel-geo-btn');

            if (keywordInput) {
                keywordInput.value = '';
                keywordInput.placeholder = '¿Qué buscas y dónde? (ej. Sushi, Abogado, Providencia...)';
                keywordInput.classList.remove('babel-radar-active');
            }
            if (latInput) latInput.value = '';
            if (lngInput) lngInput.value = '';
            if (geoBtn) geoBtn.classList.remove('active');

            // Restablecer pills de categorías si existen
            const categoryPillsList = document.querySelectorAll('.bd-category-pill');
            if (categoryPillsList.length > 0) {
                categoryPillsList.forEach(p => p.classList.remove('active'));
                const allPill = document.querySelector('.bd-category-pill[data-category=""]');
                if (allPill) allPill.classList.add('active');
            }
            if (resultsContainer) {
                resultsContainer.setAttribute('data-category', '');
            }

            performSearch(1);
        }
    };

    // Cargar resultados iniciales de forma asíncrona al cargar la página
    if (resultsContainer) {
        performSearch(1);
    }

    // ==========================================================================
    // 5. BYPASS DIVI LIGHTBOX FOR REGION CARDS
    // ==========================================================================
    // Divi 5 a veces intercepta los clicks en las tarjetas de región y abre un lightbox
    // en lugar de navegar a la URL. Usamos la fase de captura para forzar la navegación.
    document.querySelectorAll('.babel-region-card').forEach(card => {
        card.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const href = card.getAttribute('href');
            if (href) {
                window.location.href = href;
            }
        }, true); // Fase de captura
    });
});

// ============================================================
// BABEL REGIONS CAROUSEL — Vanilla JS (sin jQuery)
// Patrón: delegación de eventos en document para compatibilidad
// con nodos reinyectados por AJAX.
// ============================================================
(function () {
    'use strict';

    function initBabelCarousel(carousel) {
        var track    = carousel.querySelector('.babel-carousel-track');
        var btnPrev  = carousel.querySelector('.babel-carousel-btn--prev');
        var btnNext  = carousel.querySelector('.babel-carousel-btn--next');
        if (!track || !btnPrev || !btnNext) { return; }

        var items        = track.querySelectorAll('.babel-region-wrapper');
        var total        = items.length;
        var currentIndex = 0;

        function getVisible() {
            var w = carousel.offsetWidth;
            if (w < 640) { return 2; }
            if (w < 980) { return 3; }
            return 5;
        }

        function getItemWidth() {
            var gap     = 16;
            var visible = getVisible();
            var wrap    = carousel.querySelector('.babel-carousel-track-wrap');
            return (wrap.offsetWidth - gap * (visible - 1)) / visible;
        }

        function goTo(index) {
            var visible  = getVisible();
            var maxIndex = Math.max(0, total - visible);
            if (index < 0)        { index = maxIndex; }
            if (index > maxIndex) { index = 0; }
            currentIndex = index;
            var itemW  = getItemWidth();
            var offset = currentIndex * (itemW + 16);
            track.style.transform = 'translateX(-' + offset + 'px)';
        }

        btnNext.addEventListener('click', function () { goTo(currentIndex + 1); });
        btnPrev.addEventListener('click', function () { goTo(currentIndex - 1); });

        // Touch/swipe
        var touchStartX = 0;
        carousel.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].clientX;
        }, { passive: true });
        carousel.addEventListener('touchend', function (e) {
            var diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) {
                goTo(diff > 0 ? currentIndex + 1 : currentIndex - 1);
            }
        }, { passive: true });

        // Resize
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () { goTo(currentIndex); }, 150);
        });
    }

    function initAllCarousels() {
        document.querySelectorAll('[data-carousel="babel-regions"]').forEach(initBabelCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllCarousels);
    } else {
        initAllCarousels();
    }
}());
