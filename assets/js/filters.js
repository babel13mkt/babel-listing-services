/**
 * Babel Directory — Filter Engine v3.5
 * Debounce added for keyword search and unified logic.
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const $form = $('#bd-filter-form');
        const $gridContainer = $('#bd-grid-container');
        const $sortSelect = $('#bd-sort');
        const $countShown = $('#bd-count-shown');
        const $keywordInput = $form.find('input[name="keyword"]');

        if (!$form.length) return;

        let debounceTimer;

        function runFilters(paged = 1) {
            $gridContainer.addClass('bd-loading').css('opacity', 0.5);
            
            if (!$gridContainer.find('.bd-loading-overlay').length) {
                $gridContainer.append('<div class="bd-loading-overlay"><i class="fas fa-circle-notch fa-spin"></i></div>');
            }

            let formData = $form.serializeArray();
            formData.push({ name: 'nonce', value: bd_vars.nonce });
            formData.push({ name: 'action', value: 'bd_filter_listings' });
            formData.push({ name: 'sort', value: $sortSelect.val() });
            formData.push({ name: 'paged', value: paged });

            $.ajax({
                url: bd_vars.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $gridContainer.html(response.data.html);
                        if ($countShown.length) {
                            $countShown.text(response.data.count);
                        }
                        
                        if (response.data.count === 0) {
                             $gridContainer.addClass('is-empty');
                        } else {
                             $gridContainer.removeClass('is-empty');
                        }

                        if (paged > 1) {
                            $('html, body').animate({
                                scrollTop: $gridContainer.offset().top - 100
                            }, 500);
                        }
                    }
                },
                error: function() {
                    console.error('Babel Directory: AJAX Error');
                },
                complete: function() {
                    $gridContainer.removeClass('bd-loading').css('opacity', 1);
                    $gridContainer.find('.bd-loading-overlay').remove();
                }
            });
        }

        $form.on('change', 'select', function() {
            runFilters(1);
        });

        $keywordInput.on('keyup input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                runFilters(1);
            }, 500);
        });

        $sortSelect.on('change', function() {
            runFilters(1);
        });

        $form.on('submit', function(e) {
            e.preventDefault();
            runFilters(1);
        });

        $(document).on('click', '.bd-pagination a', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            let paged = 1;
            
            if (href.includes('paged=')) {
                paged = href.split('paged=')[1].split('&')[0];
            } else {
                const match = href.match(/\/page\/([0-9]+)/);
                if (match) paged = match[1];
            }
            
            runFilters(paged);
        });

        const $geoBtn = $('#bd-geo-btn');
        const $radiusSelect = $('#bd-filter-radius');
        const $latInput = $('#bd-lat');
        const $lngInput = $('#bd-lng');

        $geoBtn.on('click', function() {
            if (!navigator.geolocation) {
                alert('Tu navegador no soporta geolocalización.');
                return;
            }

            $geoBtn.addClass('active-loading').html('<i class="fas fa-sync fa-spin"></i>');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    $latInput.val(lat);
                    $lngInput.val(lng);
                    $radiusSelect.fadeIn();
                    $geoBtn.removeClass('active-loading').addClass('active').html('<i class="fas fa-map-marker-alt"></i>').attr('title', 'Radar Activo - ' + $radiusSelect.find('option:selected').text());
                    
                    if ($sortSelect.find('option[value="distance"]').length) {
                        $sortSelect.val('distance');
                    }

                    runFilters(1);
                },
                function(error) {
                    $geoBtn.removeClass('active-loading').html('<i class="fas fa-crosshairs"></i>').attr('title', 'Cerca de mí');
                    alert('No se pudo obtener tu ubicación.');
                },
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        });

        $('#bd-reset-filters').on('click', function() {
            $form[0].reset();
            $latInput.val('');
            $lngInput.val('');
            $radiusSelect.hide();
            $geoBtn.removeClass('active').html('<i class="fas fa-crosshairs"></i>').attr('title', 'Cerca de mí');
            $sortSelect.val('newest');
            runFilters(1);
        });
    });

})(jQuery);
