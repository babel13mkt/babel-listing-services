/**
 * Babel Directory — Metabox Geocoding Autocomplete
 * Autocompletado de dirección con Nominatim (OpenStreetMap) para el formulario de admin.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        var $addressInput = $('#babel_address');
        if (!$addressInput.length) return;

        // Crear dropdown de autocompletado
        var $dropdown = $('<div id="bd-address-autocomplete" style="position:absolute;z-index:99999;display:none;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.12);max-height:260px;overflow-y:auto;"></div>');
        $addressInput.closest('.bd-field-group').css('position', 'relative').append($dropdown);

        var debounceTimer = null;
        var lastQuery = '';

        $addressInput.on('input', function() {
            var query = $(this).val().trim();
            if (query.length < 3) {
                $dropdown.hide();
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                if (query === lastQuery) return;
                lastQuery = query;

                $dropdown.html('<div style="padding:14px 16px;font-size:13px;color:#94a3b8;font-style:italic;">Buscando...</div>').show();

                $.post(babel_geocoding_vars.ajaxurl, {
                    action: 'babel_autocomplete_address',
                    q: query
                }, function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        $dropdown.empty();
                        response.data.forEach(function(item) {
                            var $item = $('<div class="bd-addr-suggestion" style="padding:10px 16px;cursor:pointer;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;transition:background 0.15s;"></div>');
                            $item.text(item.label);
                            $item.data('lat', item.lat);
                            $item.data('lng', item.lng);
                            $item.on('click', function() {
                                $addressInput.val(item.label);
                                $('#babel_lat').val(item.lat);
                                $('#babel_lng').val(item.lng);
                                $('#bd-geo-status')
                                    .removeClass('bd-geo-idle bd-geo-loading bd-geo-error')
                                    .addClass('bd-geo-ok')
                                    .text('✓ Ubicación encontrada: ' + item.lat + ', ' + item.lng);
                                $dropdown.hide();
                            });
                            $item.on('mouseenter', function() { $(this).css('background', '#f0f9ff'); });
                            $item.on('mouseleave', function() { $(this).css('background', ''); });
                            $dropdown.append($item);
                        });
                    } else {
                        $dropdown.html('<div style="padding:14px 16px;font-size:13px;color:#94a3b8;font-style:italic;">No se encontraron resultados</div>');
                    }
                }).fail(function() {
                    $dropdown.html('<div style="padding:14px 16px;font-size:13px;color:#ef4444;">Error de conexión</div>');
                });
            }, 350);
        });

        // Botón "Geocodificar" junto al input de dirección
        if (!$('#bd-geocode-btn').length) {
            var $geocodeBtn = $('<button type="button" id="bd-geocode-btn" class="button button-small" style="margin-left:8px;" title="Buscar coordenadas vía OpenStreetMap"><span class="dashicons dashicons-location-alt" style="font-size:16px;width:16px;height:16px;vertical-align:text-bottom;"></span> Geocodificar</button>');
            $addressInput.after($geocodeBtn);

            $geocodeBtn.on('click', function(e) {
                e.preventDefault();
                var address = $addressInput.val().trim();
                if (!address) {
                    alert('Por favor, ingresa una dirección primero.');
                    return;
                }

                $geocodeBtn.prop('disabled', true).text('Buscando...');
                $('#bd-geo-status')
                    .removeClass('bd-geo-idle bd-geo-ok bd-geo-error')
                    .addClass('bd-geo-loading')
                    .text('Geocodificando dirección...');

                $.post(babel_geocoding_vars.ajaxurl, {
                    action: 'babel_geocode_address',
                    address: address
                }, function(response) {
                    $geocodeBtn.prop('disabled', false).html('<span class="dashicons dashicons-location-alt" style="font-size:16px;width:16px;height:16px;vertical-align:text-bottom;"></span> Geocodificar');
                    if (response.success) {
                        $('#babel_lat').val(response.data.lat);
                        $('#babel_lng').val(response.data.lng);
                        $('#bd-geo-status')
                            .removeClass('bd-geo-idle bd-geo-loading bd-geo-error')
                            .addClass('bd-geo-ok')
                            .text('✓ Coordenadas encontradas: ' + response.data.lat + ', ' + response.data.lng);
                    } else {
                        $('#bd-geo-status')
                            .removeClass('bd-geo-idle bd-geo-loading bd-geo-ok')
                            .addClass('bd-geo-error')
                            .text('✗ ' + (response.data && response.data.message ? response.data.message : 'No se pudo geocodificar'));
                    }
                }).fail(function() {
                    $geocodeBtn.prop('disabled', false).html('<span class="dashicons dashicons-location-alt" style="font-size:16px;width:16px;height:16px;vertical-align:text-bottom;"></span> Geocodificar');
                    $('#bd-geo-status')
                        .removeClass('bd-geo-idle bd-geo-loading bd-geo-ok')
                        .addClass('bd-geo-error')
                        .text('✗ Error de conexión con el servicio de geocoding');
                });
            });
        }

        // Cerrar dropdown al hacer clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#babel_address, #bd-address-autocomplete').length) {
                $dropdown.hide();
            }
        });
    });
})(jQuery);
