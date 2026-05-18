/**
 * Babel Directory Public Script (Vanilla JS)
 * v7.0.0 — Hito 9: Control Dinámico AJAX, Debounce y Paginación SPA React-friendly.
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('babel-search-form');
    const resultsContainer = document.getElementById('babel-directory-results');

    if (!searchForm && !resultsContainer) {
        return;
    }

    // Estado interno de la consulta
    let currentPaged = 1;

    /**
     * Helper: Crea una función con retardo (Debounce)
     * Evita sobrecargar el servidor AR1 con ráfagas concurrentes.
     */
    function debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    }

    /**
     * Realiza la petición AJAX mediante Fetch API de forma asíncrona.
     *
     * @param {number} page Página a consultar.
     */
    async function performSearch(page = 1) {
        if (!resultsContainer) return;

        currentPaged = page;

        // Añadir estado visual de carga (skeleton / spinner feeling)
        resultsContainer.classList.add('babel-loading-state');
        resultsContainer.style.opacity = '0.6';

        // Recopilar valores del formulario de manera segura
        const keyword = searchForm ? searchForm.querySelector('#babel-search-keyword').value : '';
        const category = searchForm ? searchForm.querySelector('#babel-search-category').value : '';
        const region = searchForm ? searchForm.querySelector('#babel-search-region').value : '';

        // Construir datos de envío usando URLSearchParams para compatibilidad nativa con PHP $_POST
        const payload = new URLSearchParams();
        payload.append('action', 'bd_filter_listings');
        payload.append('nonce', babel_vars.nonce);
        payload.append('keyword', keyword);
        payload.append('category', category);
        payload.append('region', region);
        payload.append('paged', currentPaged);

        try {
            const response = await fetch(babel_vars.ajax_url, {
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
                // Inyección limpia y segura del HTML compilado en el backend
                resultsContainer.innerHTML = result.data.html || '';
                
                // Desplazar la vista suavemente hasta los resultados en móvil o si es necesario
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

    // 1. Escuchar envío de formulario
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            performSearch(1);
        });

        // 2. Escuchar cambios interactivos en tiempo real (Selectores)
        const categorySelect = searchForm.querySelector('#babel-search-category');
        const regionSelect = searchForm.querySelector('#babel-search-region');
        const keywordInput = searchForm.querySelector('#babel-search-keyword');

        if (categorySelect) {
            categorySelect.addEventListener('change', () => performSearch(1));
        }

        if (regionSelect) {
            regionSelect.addEventListener('change', () => performSearch(1));
        }

        // Búsqueda en vivo al escribir con debounce de 300ms
        if (keywordInput) {
            keywordInput.addEventListener('input', debouncedSearch);
        }
    }

    // 3. Paginación SPA: Interceptar clicks en enlaces de paginación inyectados dinámicamente
    if (resultsContainer) {
        resultsContainer.addEventListener('click', (e) => {
            const pageLink = e.target.closest('.page-numbers, .babel-pagination-wrapper a');
            if (!pageLink) return;

            e.preventDefault();

            // WordPress formatea el enlace activo como un span.page-numbers.current, ignorarlo
            if (pageLink.classList.contains('current')) {
                return;
            }

            // Extraer el número de página de las clases de WordPress o de la URL del link
            let pageNum = 1;
            const href = pageLink.getAttribute('href');
            
            if (href) {
                const match = href.match(/\/page\/(\d+)/) || href.match(/paged=(\d+)/);
                if (match && match[1]) {
                    pageNum = parseInt(match[1], 10);
                } else {
                    // Si es un botón anterior o siguiente, extraer número usando su contenido textual
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
                // Fallback a parseo del texto directo del número
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

    // Cargar resultados iniciales de forma asíncrona al cargar la página
    if (resultsContainer) {
        performSearch(1);
    }
});
