// babel-submission.js
document.addEventListener('DOMContentLoaded', function() {
    const radarBtn = document.getElementById('babel-radar-btn');
    const mapContainer = document.getElementById('babel-map-container');
    let map = null;
    let marker = null;

    if (radarBtn) {
        radarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            radarBtn.innerHTML = 'Buscando satélites...';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                alert("Tu navegador no soporta geolocalización.");
                radarBtn.innerHTML = 'Radar (Ubicación Actual)';
            }
        });
    }

    function showPosition(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        // Guardar silenciosamente en los inputs ocultos
        document.getElementById('babel_lat').value = lat;
        document.getElementById('babel_lng').value = lng;
        radarBtn.innerHTML = '¡Ubicación Detectada!';

        // Inyectar el mapa de Leaflet
        mapContainer.style.height = '300px';
        if (!map) {
            map = L.map('babel-map-container').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            marker = L.marker([lat, lng]).addTo(map);
        } else {
            map.setView([lat, lng], 16);
            marker.setLatLng([lat, lng]);
        }
        
        // AJAX inverso opcional a Nominatim para autocompletar la dirección en texto
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(res => res.json())
            .then(data => {
                if(data.display_name) {
                    document.getElementById('babel_address').value = data.display_name;
                }
            })
            .catch(err => console.error("Error Nominatim:", err));
    }

    function showError(error) {
        console.error(error);
        radarBtn.innerHTML = 'Radar (Ubicación Actual)';
        alert("Error al obtener la ubicación. Comprueba tus permisos de GPS.");
    }

    // AJAX Submission Logic
    const submissionForm = document.getElementById('babel-submission-form');
    if (submissionForm) {
        submissionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('babel-submit-btn');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Enviando...';
            btn.disabled = true;
            
            let formData = new FormData(this);
            formData.append('action', 'babel_frontend_submission');
            formData.append('security', babel_vars.submission_nonce);
            
            fetch(babel_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                const msgDiv = document.getElementById('babel-response-message');
                if(data.success) {
                    msgDiv.innerHTML = '<div class="babel-notice babel-notice-success" style="padding: 15px; border-left: 4px solid #10b981; background-color: #d1fae5; color: #065f46; margin-top: 15px;">' + data.data.message + '</div>';
                    submissionForm.reset();
                    if(map) {
                        map.remove();
                        map = null;
                        mapContainer.style.height = '0';
                    }
                    radarBtn.innerHTML = 'Radar (Ubicación Actual)';
                } else {
                    msgDiv.innerHTML = '<div class="babel-notice babel-notice-error" style="padding: 15px; border-left: 4px solid #ef4444; background-color: #fee2e2; color: #991b1b; margin-top: 15px;">Error: ' + (data.data.message || 'Error desconocido') + '</div>';
                }
            })
            .catch(error => {
                console.error("Error AJAX:", error);
                btn.disabled = false;
                btn.innerHTML = originalText;
                document.getElementById('babel-response-message').innerHTML = '<div class="babel-notice babel-notice-error" style="padding: 15px; border-left: 4px solid #ef4444; background-color: #fee2e2; color: #991b1b; margin-top: 15px;">Error de conexión.</div>';
            });
        });
    }
});
