/**
 * babel-submission.js
 * v7.3.0 — Hito 21: Formulario Completo — Preview fotos, toggle horarios, galería múltiple
 */

// ============================================================
// GOOGLE IDENTITY SERVICES — Callback Global
// ============================================================

window.handleBabelGoogleLogin = function( googleResponse ) {
    if ( ! googleResponse || ! googleResponse.credential ) return;

    const loginScreen  = document.getElementById( 'babel-login-screen' );
    const loadingPanel = document.getElementById( 'babel-login-loading' );
    const loginContent = loginScreen ? loginScreen.querySelector( ':scope > div:not(#babel-login-loading)' ) : null;

    if ( loginContent ) loginContent.style.display = 'none';
    if ( loadingPanel ) loadingPanel.classList.remove( 'hidden' );

    const formData = new FormData();
    formData.append( 'action', 'babel_google_login' );
    formData.append( 'security', babel_vars.google_login_nonce );
    formData.append( 'credential', googleResponse.credential );

    fetch( babel_vars.ajax_url, { method: 'POST', body: formData } )
        .then( res => res.json() )
        .then( data => {
            if ( data.success && data.data && data.data.user ) {
                transitionToFormScreen( data.data.user );
            } else {
                const msg = data.data ? data.data.message : 'Error al iniciar sesión.';
                showLoginError( msg );
                if ( loginContent ) loginContent.style.display = '';
                if ( loadingPanel ) loadingPanel.classList.add( 'hidden' );
            }
        } )
        .catch( () => {
            showLoginError( 'Error de conexión. Intenta nuevamente.' );
            if ( loginContent ) loginContent.style.display = '';
            if ( loadingPanel ) loadingPanel.classList.add( 'hidden' );
        } );
};

function transitionToFormScreen( user ) {
    const loginScreen = document.getElementById( 'babel-login-screen' );
    const formScreen  = document.getElementById( 'babel-form-screen' );

    if ( user.avatar ) {
        const av = document.getElementById( 'babel-user-avatar' );
        if ( av ) av.src = user.avatar;
    }
    const nameEl = document.getElementById( 'babel-user-name' );
    if ( nameEl && user.name ) nameEl.textContent = user.name;

    // Pre-llenar email en el form
    const emailInput = document.querySelector( '#babel-submission-form [name="email"]' );
    if ( emailInput && user.email && ! emailInput.value ) emailInput.value = user.email;

    if ( loginScreen ) {
        loginScreen.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        loginScreen.style.opacity    = '0';
        loginScreen.style.transform  = 'translateY(-20px)';
        setTimeout( () => loginScreen.style.display = 'none', 400 );
    }
    if ( formScreen ) {
        formScreen.classList.remove( 'hidden' );
        formScreen.style.opacity    = '0';
        formScreen.style.transform  = 'translateY(20px)';
        formScreen.style.transition = 'opacity 0.4s ease 0.2s, transform 0.4s ease 0.2s';
        void formScreen.offsetWidth;
        formScreen.style.opacity    = '1';
        formScreen.style.transform  = 'translateY(0)';
        setTimeout( () => formScreen.scrollIntoView( { behavior: 'smooth', block: 'start' } ), 300 );
    }
}

function showLoginError( message ) {
    const loginScreen = document.getElementById( 'babel-login-screen' );
    if ( ! loginScreen ) return;
    let errorEl = document.getElementById( 'babel-login-error' );
    if ( ! errorEl ) {
        errorEl = document.createElement( 'div' );
        errorEl.id = 'babel-login-error';
        errorEl.className = 'mx-8 mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm text-center';
        loginScreen.appendChild( errorEl );
    }
    errorEl.textContent = message;
}

// ============================================================
// DOM READY
// ============================================================
document.addEventListener( 'DOMContentLoaded', function() {

    // Usuario ya logueado al cargar
    if ( typeof babel_vars !== 'undefined' && babel_vars.is_logged_in === '1' ) {
        const formScreen = document.getElementById( 'babel-form-screen' );
        if ( formScreen ) formScreen.style.opacity = '1';
        if ( babel_vars.current_user && babel_vars.current_user.email ) {
            const emailInput = document.querySelector( '#babel-submission-form [name="email"]' );
            if ( emailInput && ! emailInput.value ) emailInput.value = babel_vars.current_user.email;
        }

        // ── HIDRATACIÓN DE DATOS (EDICIÓN) ───────────────────────
        if ( babel_vars.edit_data ) {
            const d = babel_vars.edit_data;
            const setVal = (name, val) => {
                if (val) {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (el) el.value = val;
                }
            };

            setVal('business_name', d.business_name);
            setVal('business_rut', d.business_rut);
            setVal('business_category', d.business_category);
            setVal('business_region', d.business_region);
            setVal('description', d.description);
            setVal('phone', d.phone);
            setVal('whatsapp', d.whatsapp);
            setVal('email', d.email);
            setVal('website', d.website);
            setVal('instagram', d.instagram);
            setVal('address', d.address);
            setVal('babel_lat', d.babel_lat);
            setVal('babel_lng', d.babel_lng);

            if (d.raw_meta) {
                ['wifi', 'parking', 'delivery', 'accesibilidad', 'tarjetas', 'reservas'].forEach(attr => {
                    const mk = `_babel_attr_${attr}`;
                    if (d.raw_meta[mk] && d.raw_meta[mk][0] === '1') {
                        const chk = document.querySelector(`[name="attr_${attr}"]`);
                        if (chk) chk.checked = true;
                    }
                });

                if (d.raw_meta['_babel_horarios'] && d.raw_meta['_babel_horarios'][0]) {
                    try {
                        const h = JSON.parse(d.raw_meta['_babel_horarios'][0]);
                        Object.keys(h).forEach(day => {
                            if (h[day].cerrado) {
                                const c = document.querySelector(`[name="horario_cerrado_${day}"]`);
                                if (c) { c.checked = true; setTimeout(() => c.dispatchEvent(new Event('change')), 100); }
                            } else {
                                setVal(`horario_abre_${day}`, h[day].abre);
                                setVal(`horario_cierra_${day}`, h[day].cierra);
                            }
                        });
                    } catch(e) {}
                }
            }

            // Paywall: Bloquear campos si el plan es gratis
            if (d.plan_type === 'gratis') {
                const lockField = (name) => {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (el) {
                        el.readOnly = true;
                        el.classList.add('bg-gray-100', 'text-gray-400');
                        el.parentElement.style.position = 'relative';
                        el.parentElement.insertAdjacentHTML('beforeend', `<div class="absolute inset-0 flex items-center justify-center bg-white/40 backdrop-blur-[1px] z-10 rounded-lg cursor-pointer" onclick="alert('Esta función es exclusiva de los planes Profesionales. Ve a tu Panel para mejorar tu plan.')" title="Mejora tu plan para editar este campo"><span class="material-symbols-outlined text-amber-500 bg-white border border-amber-200 rounded-full p-1.5 shadow-md">lock</span></div>`);
                    }
                };
                lockField('whatsapp');
                lockField('website');
                
                const gInput = document.getElementById('babel-gallery-input');
                if (gInput) {
                    gInput.disabled = true;
                    const gParent = gInput.closest('label');
                    if (gParent) {
                        gParent.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                        gParent.parentElement.style.position = 'relative';
                        gParent.parentElement.insertAdjacentHTML('beforeend', `<div class="absolute inset-0 flex items-center justify-center bg-white/40 backdrop-blur-[1px] z-10 rounded-lg cursor-pointer" onclick="alert('La galería es exclusiva de planes de pago.')"><span class="material-symbols-outlined text-amber-500 bg-white border border-amber-200 rounded-full p-1.5 shadow-md">lock</span></div>`);
                    }
                }
            }
        }
    }

    // ── PREVIEW FOTO PRINCIPAL ──────────────────────────────
    const mainPhotoInput       = document.getElementById( 'babel-featured-image' );
    const mainPhotoPreview     = document.getElementById( 'babel-main-photo-preview' );
    const mainPhotoPlaceholder = document.getElementById( 'babel-main-photo-placeholder' );
    const mainPhotoDrop        = document.getElementById( 'babel-main-photo-drop' );

    function showMainPreview( file ) {
        if ( ! file || ! file.type.startsWith( 'image/' ) ) return;
        const reader = new FileReader();
        reader.onload = e => {
            if ( mainPhotoPreview ) {
                mainPhotoPreview.src = e.target.result;
                mainPhotoPreview.classList.remove( 'hidden' );
            }
            if ( mainPhotoPlaceholder ) mainPhotoPlaceholder.classList.add( 'hidden' );
        };
        reader.readAsDataURL( file );
    }

    if ( mainPhotoInput ) {
        mainPhotoInput.addEventListener( 'change', () => {
            if ( mainPhotoInput.files[0] ) showMainPreview( mainPhotoInput.files[0] );
        } );
    }

    // Drag & drop en foto principal
    if ( mainPhotoDrop ) {
        mainPhotoDrop.addEventListener( 'dragover', e => {
            e.preventDefault();
            mainPhotoDrop.classList.add( 'border-secondary', 'bg-secondary/5' );
        } );
        mainPhotoDrop.addEventListener( 'dragleave', () => {
            mainPhotoDrop.classList.remove( 'border-secondary', 'bg-secondary/5' );
        } );
        mainPhotoDrop.addEventListener( 'drop', e => {
            e.preventDefault();
            mainPhotoDrop.classList.remove( 'border-secondary', 'bg-secondary/5' );
            const file = e.dataTransfer.files[0];
            if ( file && mainPhotoInput ) {
                // Crear DataTransfer para asignar al input
                const dt = new DataTransfer();
                dt.items.add( file );
                mainPhotoInput.files = dt.files;
                showMainPreview( file );
            }
        } );
    }

    // ── GALERÍA MÚLTIPLE ────────────────────────────────────
    const galleryInput    = document.getElementById( 'babel-gallery-input' );
    const galleryPreviews = document.getElementById( 'babel-gallery-previews' );
    let galleryFiles      = [];

    if ( galleryInput ) {
        galleryInput.addEventListener( 'change', function() {
            const newFiles = Array.from( this.files );
            const remaining = 5 - galleryFiles.length;
            const toAdd = newFiles.slice( 0, remaining );

            toAdd.forEach( file => {
                if ( ! file.type.startsWith( 'image/' ) ) return;
                galleryFiles.push( file );

                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement( 'div' );
                    div.className = 'relative group';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-20 object-cover rounded-lg border border-outline-variant/30">
                        <button type="button" class="babel-gallery-remove absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" data-index="${galleryFiles.length - 1}">×</button>
                    `;
                    galleryPreviews.appendChild( div );
                };
                reader.readAsDataURL( file );
            } );

            // Reconstruir el FileList del input con galleryFiles
            syncGalleryInput();
        } );

        // Remover imagen de galería
        galleryPreviews.addEventListener( 'click', function( e ) {
            const btn = e.target.closest( '.babel-gallery-remove' );
            if ( ! btn ) return;
            const idx = parseInt( btn.dataset.index, 10 );
            galleryFiles.splice( idx, 10 );
            // Limpiar y re-renderizar
            galleryPreviews.innerHTML = '';
            const temp = [ ...galleryFiles ];
            galleryFiles = [];
            temp.forEach( file => {
                galleryFiles.push( file );
                const reader = new FileReader();
                reader.onload = e2 => {
                    const div2 = document.createElement( 'div' );
                    div2.className = 'relative group';
                    div2.innerHTML = `
                        <img src="${e2.target.result}" class="w-full h-20 object-cover rounded-lg border border-outline-variant/30">
                        <button type="button" class="babel-gallery-remove absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" data-index="${galleryFiles.length - 1}">×</button>
                    `;
                    galleryPreviews.appendChild( div2 );
                };
                reader.readAsDataURL( file );
            } );
            syncGalleryInput();
        } );
    }

    function syncGalleryInput() {
        if ( ! galleryInput ) return;
        const dt = new DataTransfer();
        galleryFiles.forEach( f => dt.items.add( f ) );
        galleryInput.files = dt.files;
    }

    // ── TOGGLE HORARIOS ─────────────────────────────────────
    const closedToggles = document.querySelectorAll( '.babel-closed-toggle' );
    closedToggles.forEach( toggle => {
        toggle.addEventListener( 'change', function() {
            const day     = this.dataset.day;
            const row     = document.querySelector( `.babel-hours-row[data-day="${day}"]` );
            const inputs  = row ? row.querySelectorAll( 'input[type="time"]' ) : [];

            inputs.forEach( input => {
                input.disabled = this.checked;
                input.style.opacity = this.checked ? '0.35' : '1';
            } );
            if ( row ) {
                row.style.opacity = this.checked ? '0.4' : '1';
            }
        } );
    } );

    // ── RADAR GPS + LEAFLET ──────────────────────────────────
    const radarBtn     = document.getElementById( 'babel-radar-btn' );
    const mapContainer = document.getElementById( 'babel-map-container' );
    let map = null, marker = null;

    if ( radarBtn ) {
        radarBtn.addEventListener( 'click', function( e ) {
            e.preventDefault();
            if ( ! navigator.geolocation ) {
                alert( 'Tu navegador no soporta geolocalización.' );
                return;
            }
            const btnText = document.getElementById( 'radar-btn-text' );
            if ( btnText ) btnText.textContent = '…';
            radarBtn.disabled = true;
            radarBtn.classList.add( 'opacity-70' );

            navigator.geolocation.getCurrentPosition(
                position => {
                    showPosition( position );
                    radarBtn.disabled = false;
                    radarBtn.classList.remove( 'opacity-70' );
                    if ( btnText ) btnText.textContent = '✓ GPS';
                },
                () => {
                    radarBtn.disabled = false;
                    radarBtn.classList.remove( 'opacity-70' );
                    if ( btnText ) btnText.textContent = 'GPS';
                    alert( 'No se pudo obtener tu ubicación.' );
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        } );
    }

    function showPosition( position ) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const latInput = document.getElementById( 'babel_lat' );
        const lngInput = document.getElementById( 'babel_lng' );
        if ( latInput ) latInput.value = lat;
        if ( lngInput ) lngInput.value = lng;
        if ( mapContainer ) mapContainer.style.height = '280px';

        if ( typeof L !== 'undefined' ) {
            if ( ! map ) {
                map = L.map( 'babel-map-container' ).setView( [ lat, lng ], 16 );
                L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
                } ).addTo( map );
                marker = L.marker( [ lat, lng ], { draggable: true } ).addTo( map );
                marker.on( 'dragend', evt => {
                    const pos = evt.target.getLatLng();
                    if ( latInput ) latInput.value = pos.lat.toFixed( 7 );
                    if ( lngInput ) lngInput.value = pos.lng.toFixed( 7 );
                    reverseGeocode( pos.lat, pos.lng );
                } );
            } else {
                map.setView( [ lat, lng ], 16 );
                marker.setLatLng( [ lat, lng ] );
            }
            setTimeout( () => map.invalidateSize(), 450 );
        }
        reverseGeocode( lat, lng );
    }

    function reverseGeocode( lat, lng ) {
        fetch( `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=es` )
            .then( r => r.json() )
            .then( d => {
                if ( d.display_name ) {
                    const addr = document.getElementById( 'babel_address' );
                    if ( addr ) addr.value = d.display_name;
                }
            } )
            .catch( () => {} );
    }

    // ── ENVÍO AJAX DEL FORMULARIO ────────────────────────────
    const submissionForm = document.getElementById( 'babel-submission-form' );
    if ( submissionForm ) {
        submissionForm.addEventListener( 'submit', function( e ) {
            e.preventDefault();

            const submitBtn      = document.getElementById( 'babel-submit-btn' );
            const responseDiv    = document.getElementById( 'babel-response-message' );
            const originalBtnHTML = submitBtn ? submitBtn.innerHTML : '';

            if ( submitBtn ) {
                submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Enviando...';
                submitBtn.disabled  = true;
                submitBtn.classList.add( 'opacity-80' );
            }

            const formData = new FormData( submissionForm );
            formData.append( 'action', 'babel_frontend_submission' );
            formData.append( 'security', babel_vars.submission_nonce );

            // Adjuntar archivos de galería manualmente
            if ( galleryFiles.length > 0 ) {
                // El input de galería ya tiene los files sincronizados via syncGalleryInput()
                // pero FormData no los toma si el input es dinámico, así que los añadimos manualmente
                formData.delete( 'gallery_images[]' );
                galleryFiles.forEach( ( file, i ) => {
                    if ( i < 5 ) formData.append( 'gallery_images[]', file );
                } );
            }

            fetch( babel_vars.ajax_url, { method: 'POST', body: formData } )
                .then( res => res.json() )
                .then( data => {
                    if ( submitBtn ) {
                        submitBtn.innerHTML = originalBtnHTML;
                        submitBtn.disabled  = false;
                        submitBtn.classList.remove( 'opacity-80' );
                    }
                    if ( data.success ) {
                        if ( responseDiv ) {
                            responseDiv.innerHTML = `
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 flex gap-4 items-start mt-4">
                                    <span class="material-symbols-outlined text-emerald-600 text-3xl mt-0.5">check_circle</span>
                                    <div>
                                        <p class="font-headline-md text-headline-md text-emerald-800 mb-1">¡Negocio enviado!</p>
                                        <p class="font-body-md text-body-md text-emerald-700">${data.data.message}</p>
                                    </div>
                                </div>`;
                            responseDiv.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
                        }
                        submissionForm.reset();
                        // Limpiar previews
                        if ( mainPhotoPreview ) { mainPhotoPreview.src = ''; mainPhotoPreview.classList.add( 'hidden' ); }
                        if ( mainPhotoPlaceholder ) mainPhotoPlaceholder.classList.remove( 'hidden' );
                        if ( galleryPreviews ) galleryPreviews.innerHTML = '';
                        galleryFiles = [];
                        // Limpiar mapa
                        if ( map ) { map.remove(); map = null; marker = null; }
                        if ( mapContainer ) mapContainer.style.height = '0';
                        // Restaurar horarios
                        document.querySelectorAll( '.babel-hours-row input' ).forEach( i => {
                            i.disabled = false;
                            i.style.opacity = '1';
                        } );
                        const btnText = document.getElementById( 'radar-btn-text' );
                        if ( btnText ) btnText.textContent = 'GPS';
                    } else {
                        const msg = data.data ? data.data.message : 'Ocurrió un error.';
                        if ( responseDiv ) {
                            responseDiv.innerHTML = `
                                <div class="bg-red-50 border border-red-200 rounded-xl p-6 flex gap-4 items-start mt-4">
                                    <span class="material-symbols-outlined text-red-600 text-3xl mt-0.5">error</span>
                                    <div>
                                        <p class="font-headline-md text-headline-md text-red-800 mb-1">Error al enviar</p>
                                        <p class="font-body-md text-body-md text-red-700">${msg}</p>
                                    </div>
                                </div>`;
                            responseDiv.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
                        }
                    }
                } )
                .catch( () => {
                    if ( submitBtn ) { submitBtn.innerHTML = originalBtnHTML; submitBtn.disabled = false; submitBtn.classList.remove( 'opacity-80' ); }
                    if ( responseDiv ) {
                        responseDiv.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-xl p-6 flex gap-4 items-start mt-4">
                            <span class="material-symbols-outlined text-red-600 text-3xl mt-0.5">wifi_off</span>
                            <p class="font-body-md text-body-md text-red-700">Error de conexión.</p>
                        </div>`;
                    }
                } );
        } );
    }

} ); // END DOMContentLoaded
