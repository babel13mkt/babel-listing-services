/**
 * Babel Directory - Soy de Chile Admin SPA Scripts
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Main SPA Tabs Logic
    const mainTabBtns = document.querySelectorAll('.sdc-tab-btn');
    const mainTabContents = document.querySelectorAll('.sdc-tab-content');

    mainTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            
            // Deactivate all
            mainTabBtns.forEach(b => b.classList.remove('active'));
            mainTabContents.forEach(c => c.classList.remove('active'));
            
            // Activate target
            btn.classList.add('active');
            const targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    // 2. Editor Internal Tabs Logic
    const editorTabBtns = document.querySelectorAll('.sdc-editor-tab');
    const editorPanels = document.querySelectorAll('.sdc-editor-panel');

    editorTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            
            // Deactivate all
            editorTabBtns.forEach(b => b.classList.remove('active'));
            editorPanels.forEach(p => p.classList.remove('active'));
            
            // Activate target
            btn.classList.add('active');
            const targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    // 3. OpenStreetMap Auto-Geocoding
    const addressInput = document.getElementById('sdc_biz_address');
    const latInput = document.getElementById('sdc_biz_lat');
    const lngInput = document.getElementById('sdc_biz_lng');
    const mapIframe = document.getElementById('sdc_map_preview');

    if (addressInput && latInput && lngInput && mapIframe) {
        addressInput.addEventListener('blur', function() {
            const address = this.value;
            if (!address) return;

            // Fetch coordinates from Nominatim (OpenStreetMap)
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = data[0].lat;
                        const lon = data[0].lon;
                        
                        // Update hidden inputs
                        latInput.value = lat;
                        lngInput.value = lon;

                        // Update Map Iframe (using simple OSM embed)
                        const bbox = `${parseFloat(lon)-0.01},${parseFloat(lat)-0.01},${parseFloat(lon)+0.01},${parseFloat(lat)+0.01}`;
                        mapIframe.src = `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lon}`;
                        
                        showToast('Ubicación actualizada en el mapa', 'success');
                    }
                })
                .catch(error => console.error('Error geocoding address:', error));
        });
    }

    // 4. AJAX Save for Settings
    const settingsForm = document.getElementById('sdc-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = settingsForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Guardando...';
            btn.disabled = true;

            const formData = new FormData(settingsForm);
            formData.append('action', 'sdc_save_settings');
            
            // Append WP nonce if exists
            if (typeof ajaxurl !== 'undefined') {
                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Configuración Guardada Exitosamente');
                    } else {
                        showToast('Error al guardar', 'error');
                    }
                })
                .catch(() => showToast('Error de red', 'error'))
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            } else {
                // Mock success for development if ajaxurl is missing
                setTimeout(() => {
                    showToast('Configuración Guardada Exitosamente');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 800);
            }
        });
    }

    // 4.5 AJAX Save for Business
    const businessForm = document.getElementById('sdc-business-form');
    if (businessForm) {
        businessForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // In case the save button is outside the form, or we catch the generic submit
            const btn = document.querySelector('.sdc-editor-footer .sdc-btn-primary');
            let originalText = 'Guardar Negocio Completamente';
            if (btn) {
                originalText = btn.innerHTML;
                btn.innerHTML = '<span class="dashicons dashicons-update"></span> Guardando...';
                btn.disabled = true;
            }

            const formData = new FormData(businessForm);
            formData.append('action', 'sdc_save_business');
            
            // Collect amenities toggles if they are not standard inputs
            // The toggles in the UI might need special handling if they are custom divs. 
            // We assume they are standard checkboxes or hidden inputs for now, but if they are custom:
            const toggles = document.querySelectorAll('.sdc-toggle-input');
            toggles.forEach(toggle => {
                formData.append(toggle.name, toggle.checked ? '1' : '0');
            });

            if (typeof ajaxurl !== 'undefined') {
                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Negocio guardado exitosamente');
                        // Optionally set the hidden post_id if returned
                        if (data.data && data.data.post_id) {
                            let postIdInput = document.getElementById('sdc_post_id');
                            if (!postIdInput) {
                                postIdInput = document.createElement('input');
                                postIdInput.type = 'hidden';
                                postIdInput.name = 'post_id';
                                postIdInput.id = 'sdc_post_id';
                                businessForm.appendChild(postIdInput);
                            }
                            postIdInput.value = data.data.post_id;
                        }
                    } else {
                        showToast('Error al guardar: ' + (data.data || ''), 'error');
                    }
                })
                .catch(() => showToast('Error de red', 'error'))
                .finally(() => {
                    if (btn) {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                });
            } else {
                setTimeout(() => {
                    showToast('Negocio guardado (Modo Desarrollo)');
                    if (btn) {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                }, 800);
            }
        });
        
        // Also bind the footer button to submit the form
        const footerBtn = document.querySelector('.sdc-editor-footer .sdc-btn-primary');
        if (footerBtn) {
            footerBtn.addEventListener('click', function(e) {
                // Remove the old onclick alert and trigger submit
                e.preventDefault();
                businessForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            });
            // Clean up the inline onclick from HTML
            footerBtn.removeAttribute('onclick');
        }
    }

    // 5. Toast Notification System
    function showToast(message, type = 'success') {
        // Remove existing toast if any
        const existing = document.getElementById('sdc-toast');
        if (existing) existing.remove();

        // Create new toast
        const toast = document.createElement('div');
        toast.id = 'sdc-toast';
        toast.className = 'sdc-toast';
        
        // Icon based on type
        const icon = type === 'success' ? '✅' : '❌';
        
        toast.innerHTML = `${icon} <span>${message}</span>`;
        
        if (type === 'error') {
            toast.style.background = 'var(--sdc-red)';
        }

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.add('show'), 100);

        // Animate out
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
