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
        
        // Categorías y Regiones ahora son procesadas de forma inteligente a través de la keyword
        const category = '';
        const region = '';
        
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
                resultsContainer.innerHTML = `<div class="babel-error-message">${result.data || 'Ocurrió un error inesperado al cargar los resultados.'}</div>`;
            }
        } catch (error) {
            console.error('Babel Directory Search Error:', error);
            resultsContainer.innerHTML = `<div class="babel-error-message">Error de conexión con el servidor. Por favor, intenta de nuevo más tarde.</div>`;
        } finally {
            resultsContainer.classList.remove('babel-loading-state');
            resultsContainer.style.opacity = '1';
        }
    }

    // Crear la versión debounced de la búsqueda
    const debouncedSearch = debounce(() => performSearch(1), 300);

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
                    
                    if (resultsContainer) {
                        performSearch(1);
                    } else {
                        searchForm.submit();
                    }
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
                        }

                        // Disparar búsqueda automática
                        if (resultsContainer) {
                            performSearch(1);
                        } else {
                            searchForm.submit();
                        }
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

        // Evitar envío tradicional del formulario solo si estamos en la página de resultados
        searchForm.addEventListener('submit', (e) => {
            if (resultsContainer) {
                e.preventDefault();
                debouncedSearch.cancel();
                performSearch(1);
            } else {
                // Si no hay contenedor de resultados, desactivamos parámetros vacíos del radar para evitar canonical loops en WordPress
                if (latInput && lngInput && radiusInput && (!latInput.value || !lngInput.value)) {
                    latInput.disabled = true;
                    lngInput.disabled = true;
                    radiusInput.disabled = true;
                }
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

            performSearch(1);
        }
    };

    // Cargar resultados iniciales de forma asíncrona al cargar la página
    if (resultsContainer) {
        performSearch(1);
    }
});
