document.addEventListener('DOMContentLoaded', function() {
    // Verificar si ya existe la cookie de geolocalización
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    const regionCookie = getCookie('babel_user_region_slug');
    
    // Si no hay cookie, o si queremos asegurarnos en el Frontend, disparamos AJAX
    if (!regionCookie) {
        const ajaxUrl = typeof babel_vars !== 'undefined' ? babel_vars.ajaxUrl : '/wp-admin/admin-ajax.php';
        
        fetch(ajaxUrl + '?action=babel_geolocate_me')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.region) {
                    const regionSlug = data.data.region;
                    // Opcional: Actualizar UI
                    updateUIRegion(regionSlug);
                }
            })
            .catch(err => console.error('Error obteniendo geolocalización:', err));
    } else {
        // Si ya hay cookie, actualizamos la UI inmediatamente si es necesario
        updateUIRegion(regionCookie);
    }

    function updateUIRegion(slug) {
        if (!slug || slug === 'unknown') return;
        
        // Convertir slug a nombre legible para UI (básico)
        const regionNames = {
            'metropolitana': 'Santiago',
            'valparaiso': 'Valparaíso',
            'biobio': 'Bío Bío',
            'araucania': 'La Araucanía',
            'coquimbo': 'Coquimbo',
            'los-lagos': 'Los Lagos',
            'antofagasta': 'Antofagasta',
            'maule': 'Maule',
            'los-rios': 'Los Ríos',
            'tarapaca': 'Tarapacá',
            'atacama': 'Atacama',
            'magallanes': 'Magallanes',
            'aysen': 'Aysén',
            'arica': 'Arica',
            'nuble': 'Ñuble',
            'ohiggins': "O'Higgins"
        };
        
        const regionName = regionNames[slug] || slug;
        
        // Buscar el input de búsqueda del shortcode [bd_filter_bar]
        const searchInput = document.getElementById('babel-search-keyword');
        if (searchInput && !searchInput.value) {
            searchInput.placeholder = `ej: Sushi en ${regionName}`;
        }
        
        // Si hay un contenedor de región predeterminada (para Trivago style popup / banner)
        const geoBanner = document.getElementById('babel-geo-banner');
        if (geoBanner) {
            geoBanner.innerHTML = `📍 Resultados locales para <strong>${regionName}</strong>`;
            geoBanner.style.display = 'block';
        }
    }
});
