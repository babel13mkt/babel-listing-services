/**
 * Babel Directory - Soy de Chile Admin SPA Scripts
 */

let isBabelAdminInitialized = false;
function initBabelAdmin() {
    if (isBabelAdminInitialized) return;
    isBabelAdminInitialized = true;
    console.log("🚀 Babel Admin JS Initialized");
    try {
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

    // 3. OpenStreetMap Geocoding
    const addressInput = document.getElementById('sdc_biz_address');
    const latInput = document.getElementById('sdc_biz_lat');
    const lngInput = document.getElementById('sdc_biz_lng');
    const mapIframe = document.getElementById('sdc_map_preview');
    const geocodeBtn = document.getElementById('sdc_btn_geocode');

    function performGeocode() {
        if (!addressInput || !latInput || !lngInput || !mapIframe) return;
        
        let address = addressInput.value.trim();
        if (!address) {
            showToast('Por favor, ingresa una dirección primero.', 'error');
            return;
        }

        // Add 'Chile' to help OpenStreetMap if it's not present
        if (address.toLowerCase().indexOf('chile') === -1) {
            address += ', Chile';
        }

        if (geocodeBtn) {
            geocodeBtn.innerHTML = '<span class="dashicons dashicons-update"></span>...';
            geocodeBtn.disabled = true;
        }

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
                    
                    showToast('Ubicación encontrada y actualizada', 'success');
                } else {
                    showToast('No se encontró la dirección exacta. Intenta simplificarla.', 'error');
                }
            })
            .catch(error => {
                console.error('Error geocoding address:', error);
                showToast('Hubo un error al conectar con el mapa.', 'error');
            })
            .finally(() => {
                if (geocodeBtn) {
                    geocodeBtn.innerHTML = '<span class="dashicons dashicons-location-alt" style="margin-top:2px;"></span> Ubicar';
                    geocodeBtn.disabled = false;
                }
            });
    }

    if (geocodeBtn) {
        geocodeBtn.addEventListener('click', performGeocode);
    }
    
    if (addressInput) {
        addressInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Prevent form submission
                performGeocode();
            }
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

    // 4.6 Quick Actions (Suspend/Trash)
    function executeQuickAction(action, postIds, btnElement, originalText) {
        // Skip confirm() - it can be blocked in WP admin contexts. Use double-click pattern.
        if (btnElement) {
            btnElement.innerHTML = '...';
            btnElement.disabled = true;
        }

        const formData = new FormData();
        formData.append('action', 'sdc_quick_action');
        formData.append('q_action', action);
        formData.append('nonce', (typeof sdc_admin_vars !== 'undefined') ? sdc_admin_vars.nonce : '');
        
        // Append multiple post_ids
        if (Array.isArray(postIds)) {
            postIds.forEach(id => formData.append('post_ids[]', id));
        } else {
            formData.append('post_ids[]', postIds);
        }

        const endpoint = (typeof sdc_admin_vars !== 'undefined') ? sdc_admin_vars.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
        if (!endpoint) {
            if (btnElement) { btnElement.disabled = false; btnElement.innerHTML = originalText; }
            showToast('Error: no se encontró la URL de AJAX.', 'error');
            return;
        }

        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.data);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast('Error: ' + (data.data || 'Respuesta inesperada del servidor'), 'error');
                if (btnElement) {
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalText;
                }
            }
        })
        .catch(err => {
            showToast('Error de red: ' + err.message, 'error');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
            }
        });
    }

    // Individual Action Buttons
    const quickActionBtns = document.querySelectorAll('.sdc-quick-action-btn');
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            const postId = this.getAttribute('data-postid');
            const originalText = action === 'trash' ? 'Borrar' : 'Suspender';
            
            const actionText = action === 'trash' ? 'borrar' : 'suspender';
            if (!confirm(`¿Estás seguro de que deseas ${actionText} este negocio?`)) {
                return;
            }
            
            executeQuickAction(action, postId, this, originalText);
        });
    });

    // Bulk Select All Checkbox
    const selectAllCb = document.getElementById('sdc-bulk-select-all');
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.sdc-bulk-select-item').forEach(cb => cb.checked = isChecked);
        });
    }

    // Bulk Action Handlers
    function handleBulkAction(action, btnId) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = Array.from(document.querySelectorAll('.sdc-bulk-select-item:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Selecciona al menos un negocio.');
                return;
            }
            
            const actionText = action === 'trash' ? 'borrar' : 'suspender';
            if (!confirm(`¿Estás seguro de que deseas ${actionText} los negocios seleccionados?`)) {
                return;
            }

            const originalText = action === 'trash' ? 'Borrar Seleccionados' : 'Suspender Seleccionados';
            executeQuickAction(action, selected, this, originalText);
        });
    }
    
    handleBulkAction('suspend', 'sdc-bulk-suspend-btn');
    handleBulkAction('trash', 'sdc-bulk-trash-btn');

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
    
    } catch (e) {
        console.error("❌ Babel Admin JS Error:", e);
        alert("⚠️ Babel Directory Error: No se pudo cargar correctamente la interfaz de administración (" + e.message + "). Por favor, contacta a soporte o revisa la consola para más detalles.");
    }
}

// Initialize when DOM is ready (or immediately if already ready)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBabelAdmin);
} else {
    initBabelAdmin();
}
window.addEventListener('load', initBabelAdmin); // Fallback
