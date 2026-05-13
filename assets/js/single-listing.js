/**
 * Babel Directory — Single Listing Script v3.7
 * Maneja el Mapa Leaflet, Galería, Reseñas y Reservas (Hito 7).
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // 1. Inicializar Mapa en Single
        const $mapEl = $('#bd-single-map');
        if ($mapEl.length) {
            const lat = parseFloat($mapEl.data('lat'));
            const lng = parseFloat($mapEl.data('lng'));
            const title = $mapEl.data('title');

            if (!isNaN(lat) && !isNaN(lng)) {
                const map = L.map('bd-single-map', {
                    scrollWheelZoom: false
                }).setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                const icon = L.icon({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41]
                });

                L.marker([lat, lng], { icon: icon }).addTo(map)
                    .bindPopup(title)
                    .openPopup();
            }
        }

        // 2. Galería / Lightbox Sencillo
        $('.bd-lightbox').on('click', function(e) {
            e.preventDefault();
            const fullImg = $(this).attr('href');
            
            const $overlay = $('<div class="bd-lightbox-overlay"><div class="bd-lightbox-content"><img src="' + fullImg + '"><span class="bd-close">&times;</span></div></div>');
            
            $('body').append($overlay);
            $overlay.fadeIn(300);

            $overlay.on('click', function() {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            });
        });

        // 3. Sistema de Reservas (Hito 7)
        $(document).on('submit', '#bd-booking-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const $response = $('#bd-booking-response');
            const formData = $form.serialize();

            $.ajax({
                url: bd_vars.ajax_url,
                type: 'POST',
                data: formData + '&action=bd_submit_booking',
                beforeSend: function() {
                    $btn.prop('disabled', true);
                    $btn.find('.btn-text').css('opacity', '0.5');
                    $btn.find('i.fa-spinner').show();
                    $response.hide().removeClass('success error');
                },
                success: function(response) {
                    if (response.success) {
                        $response.addClass('success').text(response.data).fadeIn();
                        $form[0].reset();
                    } else {
                        $response.addClass('error').text(response.data).fadeIn();
                    }
                },
                error: function() {
                    $response.addClass('error').text('Ocurrió un fallo en el servidor. Reintente.').fadeIn();
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').css('opacity', '1');
                    $btn.find('i.fa-spinner').hide();
                }
            });
        });

        // 4. Acciones de Reseñas (Hito 6)
        $(document).on('click', '.bd-helpful', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const reviewId = $btn.data('id');

            $.ajax({
                url: bd_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'bd_review_action',
                    action_type: 'helpful',
                    review_id: reviewId,
                    nonce: bd_vars.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        $btn.find('.count').text(response.data.new_count);
                        $btn.addClass('voted').html('<i class="fas fa-check"></i> ¡Gracias!');
                    } else {
                        alert(response.data);
                    }
                },
                complete: function() {
                    $btn.css('opacity', '1');
                }
            });
        });

        $(document).on('click', '.bd-report', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const reviewId = $btn.data('id');

            if (!confirm('¿Estás seguro de que deseas reportar esta reseña por contenido inapropiado?')) return;

            $.ajax({
                url: bd_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'bd_review_action',
                    action_type: 'report',
                    review_id: reviewId,
                    nonce: bd_vars.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true).css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        alert('La reseña ha sido reportada. El equipo de moderación la revisará pronto.');
                        $btn.html('<i class="fas fa-info-circle"></i> Reportada');
                    } else {
                        alert(response.data);
                    }
                }
            });
        });

    });

})(jQuery);
