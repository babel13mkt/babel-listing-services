<?php
namespace Babel\Directory;

/**
 * Shortcodes para UI del Frontend (BD_Shortcodes)
 * Provee componentes visuales para integrar en Divi 5.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Shortcodes {

    public function __construct() {
        add_shortcode( 'babel_region_grid', array( $this, 'render_region_grid' ) );
        add_shortcode( 'bd_popular_regions', array( $this, 'render_region_grid' ) ); // Alias simétrico
        add_shortcode( 'babel_region_carousel', array( $this, 'render_region_carousel' ) );
        add_shortcode( 'bd_popular_categories', array( $this, 'render_category_grid' ) );
        add_shortcode( 'bd_footer_regions', array( $this, 'render_footer_regions' ) );
        add_shortcode( 'bd_footer_categories', array( $this, 'render_footer_categories' ) );
        add_shortcode( 'bd_archive_loop', array( $this, 'render_archive_loop' ) );
        add_shortcode( 'bd_region_template', array( $this, 'render_region_template' ) );
        add_shortcode( 'bd_business_profile', array( $this, 'render_business_profile' ) );
        add_shortcode( 'bd_breadcrumbs', array( $this, 'render_breadcrumbs' ) );
        add_shortcode( 'bd_filter_bar', array( $this, 'render_filter_bar' ) );
        add_shortcode( 'babel_auth_menu', array( $this, 'render_auth_menu' ) );
        add_action( 'wp_footer', array( $this, 'render_global_auth_modal' ) );
        add_action( 'wp_footer', array( $this, 'render_mobile_footer' ) );
        add_shortcode( 'babel_institutions', array( $this, 'render_region_institutions_pills' ) );
        add_shortcode( 'bd_region_institutions_pills', array( $this, 'render_region_institutions_pills' ) );

        // Shortcodes adicionales B2B restaurados
        add_shortcode( 'babel_claim_business', array( $this, 'render_claim_business' ) );
        add_shortcode( 'bd_user_dashboard', array( $this, 'render_user_dashboard' ) );

        // Micro-shortcodes atómicos LTM
        add_shortcode( 'bd_business_gallery', array( $this, 'render_business_gallery' ) );
        add_shortcode( 'bd_business_hours', array( $this, 'render_business_hours' ) );
        add_shortcode( 'bd_business_map', array( $this, 'render_business_map' ) );
        add_shortcode( 'bd_business_contact', array( $this, 'render_business_contact' ) );
        add_shortcode( 'bd_business_badges', array( $this, 'render_business_badges' ) );

        // Shortcode de banners publicitarios segmentados
        add_shortcode( 'bd_ad_space', array( $this, 'render_ad_space' ) );

        // Shortcode de listado de negocios destacados premium
        add_shortcode( 'bd_featured_businesses', array( $this, 'render_featured_businesses' ) );
        add_shortcode( 'babel_pricing_tables', array( $this, 'render_pricing_tables' ) );

        // Hooks para alertas transaccionales y estadísticas
        add_action( 'post_updated', array( $this, 'notify_user_on_claim_approved' ), 10, 3 );
        add_action( 'wp_head', array( $this, 'track_business_view' ) );
    }

    public function render_pricing_tables( $atts ) {
        ob_start();
        ?>
        <style>
            .bp-pricing-wrapper {
                display: flex;
                flex-wrap: wrap;
                gap: 30px;
                justify-content: center;
                padding: 40px 20px;
                font-family: 'Inter', sans-serif;
                background: transparent;
            }
            .bp-card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 24px;
                padding: 40px 30px;
                width: 100%;
                max-width: 340px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                display: flex;
                flex-direction: column;
                position: relative;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                border: 1px solid rgba(0,0,0,0.05);
            }
            .bp-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            }
            .bp-card.bp-popular {
                border: 2px solid #0c71c3;
                transform: scale(1.05);
                box-shadow: 0 20px 50px rgba(12, 113, 195, 0.15);
                background: #ffffff;
            }
            .bp-card.bp-popular:hover {
                transform: scale(1.05) translateY(-10px);
            }
            .bp-popular-badge {
                position: absolute;
                top: -16px;
                left: 50%;
                transform: translateX(-50%);
                background: linear-gradient(135deg, #0c71c3, #0a5a9c);
                color: white;
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: 1px;
                text-transform: uppercase;
                box-shadow: 0 4px 10px rgba(12, 113, 195, 0.3);
            }
            .bp-title {
                font-size: 24px;
                font-weight: 700;
                color: #111827;
                margin-bottom: 15px;
                text-align: center;
            }
            .bp-price {
                font-size: 42px;
                font-weight: 800;
                color: #0c71c3;
                text-align: center;
                margin-bottom: 10px;
            }
            .bp-price span {
                font-size: 16px;
                font-weight: 500;
                color: #6b7280;
            }
            .bp-desc {
                text-align: center;
                color: #6b7280;
                font-size: 15px;
                margin-bottom: 30px;
            }
            .bp-features {
                list-style: none;
                padding: 0;
                margin: 0 0 40px 0;
                flex-grow: 1;
            }
            .bp-features li {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
                color: #374151;
                font-size: 15px;
            }
            .bp-features li.disabled {
                color: #9ca3af;
                text-decoration: line-through;
            }
            .bp-features .material-symbols-outlined {
                font-size: 20px;
                margin-right: 12px;
            }
            .bp-features li .material-symbols-outlined {
                color: #10b981; /* green check */
            }
            .bp-features li.disabled .material-symbols-outlined {
                color: #d1d5db; /* gray disabled */
            }
            .bp-btn {
                display: block;
                width: 100%;
                padding: 16px;
                border-radius: 12px;
                text-align: center;
                font-weight: 600;
                font-size: 16px;
                text-decoration: none;
                transition: all 0.3s ease;
            }
            .bp-btn-outline {
                background: transparent;
                border: 2px solid #e5e7eb;
                color: #374151;
            }
            .bp-btn-outline:hover {
                border-color: #0c71c3;
                color: #0c71c3;
            }
            .bp-btn-primary {
                background: linear-gradient(135deg, #0c71c3, #0a5a9c);
                color: #ffffff;
                box-shadow: 0 10px 20px rgba(12, 113, 195, 0.2);
            }
            .bp-btn-primary:hover {
                box-shadow: 0 15px 30px rgba(12, 113, 195, 0.3);
                transform: translateY(-2px);
                color: #ffffff;
            }
            
            @media (max-width: 768px) {
                .bp-card.bp-popular {
                    transform: scale(1);
                }
                .bp-card.bp-popular:hover {
                    transform: translateY(-5px);
                }
            }
        </style>

        <div class="bp-pricing-wrapper">
            <!-- Plan Basico -->
            <div class="bp-card">
                <h3 class="bp-title">Básico</h3>
                <div class="bp-price">Gratis</div>
                <div class="bp-desc">Para aparecer en el mapa y empezar a captar clientes locales.</div>
                <ul class="bp-features">
                    <li><span class="material-symbols-outlined">check_circle</span> Datos básicos de contacto</li>
                    <li><span class="material-symbols-outlined">check_circle</span> 1 Foto de portada</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Mapa de ubicación</li>
                    <li class="disabled"><span class="material-symbols-outlined">cancel</span> Enlaces a Redes Sociales</li>
                    <li class="disabled"><span class="material-symbols-outlined">cancel</span> Botón de WhatsApp Directo</li>
                    <li class="disabled"><span class="material-symbols-outlined">cancel</span> Gestión de Reseñas</li>
                    <li class="disabled"><span class="material-symbols-outlined">cancel</span> Posicionamiento Prioritario</li>
                </ul>
                <a href="/publicar-negocio/" class="bp-btn bp-btn-outline">Comenzar Gratis</a>
            </div>

            <!-- Plan Profesional -->
            <div class="bp-card bp-popular">
                <div class="bp-popular-badge">Más Popular</div>
                <h3 class="bp-title">Profesional</h3>
                <div class="bp-price">$15.000 <span>/mes</span></div>
                <div class="bp-desc">El plan ideal para atraer más clientes e interactuar con ellos.</div>
                <ul class="bp-features">
                    <li><span class="material-symbols-outlined">check_circle</span> Datos básicos de contacto</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Galería (hasta 10 fotos)</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Mapa de ubicación</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Enlaces a Redes Sociales</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Botón de WhatsApp Directo</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Sello de Negocio Verificado</li>
                    <li class="disabled"><span class="material-symbols-outlined">cancel</span> Posicionamiento Prioritario</li>
                </ul>
                <a href="/publicar-negocio/" class="bp-btn bp-btn-primary">Elegir Profesional</a>
            </div>

            <!-- Plan Premium -->
            <div class="bp-card">
                <h3 class="bp-title">Premium</h3>
                <div class="bp-price">$35.000 <span>/mes</span></div>
                <div class="bp-desc">Máxima exposición y posicionamiento líder en tu categoría.</div>
                <ul class="bp-features">
                    <li><span class="material-symbols-outlined">check_circle</span> Todo lo Profesional</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Galería Ilimitada &amp; Video</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Gestión de Reseñas VIP</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Destacado en Portada</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Posicionamiento Top Ranking</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Sin publicidad de terceros</li>
                    <li><span class="material-symbols-outlined">check_circle</span> Soporte Prioritario 24/7</li>
                </ul>
                <a href="/publicar-negocio/" class="bp-btn bp-btn-outline">Elegir Premium</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_filter_bar( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        $atts = shortcode_atts( array(
            'region'       => '',
            'show_results' => 'no',
        ), $atts, 'bd_filter_bar' );

        // Obtener la región actual si estamos en una página de taxonomía
        $current_region_slug = $atts['region'];
        if ( empty( $current_region_slug ) && is_tax( 'babel_region' ) ) {
            $term = get_queried_object();
            if ( $term ) {
                $current_region_slug = $term->slug;
            }
        }

        // Auto-Detección: Leer cookie de Geolocalización Pasiva si no hay región forzada
        $auto_region_name = '';
        if ( empty( $current_region_slug ) && isset( $_COOKIE['babel_user_region_slug'] ) && $_COOKIE['babel_user_region_slug'] !== 'unknown' ) {
            $current_region_slug = sanitize_text_field( $_COOKIE['babel_user_region_slug'] );
            $term = get_term_by( 'slug', $current_region_slug, 'babel_region' );
            if ( $term && ! is_wp_error( $term ) ) {
                $auto_region_name = $term->name;
            }
        }

        // Obtener todas las regiones para el dropdown
        $regions = get_transient('bd_filter_bar_regions');
        if ( false === $regions ) {
            $regions = get_terms( array(
                'taxonomy'   => 'babel_region',
                'hide_empty' => false,
                'parent'     => 0,
            ) );
            if ( ! is_wp_error( $regions ) ) {
                set_transient('bd_filter_bar_regions', $regions, 12 * HOUR_IN_SECONDS);
            }
        }

        ob_start();

        // Modificar el Placeholder del input dinámicamente
        $placeholder = "Buscar comercios o instituciones...";
        if ( ! empty( $auto_region_name ) ) {
            $placeholder = "Buscar comercios o instituciones en " . esc_html( $auto_region_name );
        }
        ?>
        <div class="babel-filter-bar-section">
            <form id="babel-search-form" class="babel-filter-bar-form" action="/buscar/" method="GET" autocomplete="off">
                <div class="babel-filter-bar-inner" data-babel-filter="true">
                    <!-- 1. Búsqueda libre -->
                    <div class="babel-filter-keyword">
                        <input type="text" id="babel-search-keyword" name="keyword" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
                    </div>

                    <!-- 2. Selector de Región eliminado a petición del usuario -->

                    <!-- 3. Radar GPS -->
                    <div class="babel-filter-radar" style="display: flex; align-items: center;">
                        <span class="babel-radar-hint" style="font-size: 13px; color: #94a3b8; margin-right: 12px; font-weight: 500; white-space: nowrap; cursor: default;">Usar mi ubicación &rarr;</span>
                        <button type="button" id="babel-geo-btn" class="babel-radar-btn" title="Buscar cerca de mí (GPS)">
                            <svg class="radar-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3"></circle>
                                <line x1="12" y1="1" x2="12" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="23"></line>
                                <line x1="1" y1="12" x2="3" y2="12"></line>
                                <line x1="21" y1="12" x2="23" y2="12"></line>
                            </svg>
                            <span class="radar-ripple"></span>
                        </button>
                        <input type="hidden" id="babel-search-lat" name="lat" value="" />
                        <input type="hidden" id="babel-search-lng" name="lng" value="" />
                        <input type="hidden" id="babel-search-radius" name="radius" value="25" />
                    </div>

                    <!-- 3b. Selector de Radio de Búsqueda -->
                    <div class="babel-filter-radius" style="display: flex; align-items: center; gap: 8px;">
                        <label for="babel-radius-select" style="font-size: 13px; color: #94a3b8; font-weight: 500; white-space: nowrap;">Radio:</label>
                        <select id="babel-radius-select" name="radius_select" style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-size: 13px; color: #334155; background: #f8fafc; cursor: pointer;">
                            <option value="2">2 km</option>
                            <option value="5">5 km</option>
                            <option value="10">10 km</option>
                            <option value="25" selected>25 km</option>
                            <option value="50">50 km</option>
                            <option value="100">100 km</option>
                            <option value="0">Sin límite</option>
                        </select>
                    </div>

                    <!-- 4. Botón Buscar -->
                    <div class="babel-filter-submit">
                        <button type="submit" class="babel-search-submit-btn">BUSCAR</button>
                    </div>
                </div>
            </form>

            <!-- FILTROS RÁPIDOS DE CATEGORÍA (PASTILLAS) -->
            <div class="babel-search-tabs-container">
                <div id="babel-institutions-filters" class="babel-institutions-filters">
                    <div class="babel-inst-carousel">
                        <button type="button" class="babel-inst-pill bd-category-pill active" data-category="">Todas</button>
                        <button type="button" class="babel-inst-pill bd-category-pill" data-category="municipalidades">Municipalidades</button>
                        <button type="button" class="babel-inst-pill bd-category-pill" data-category="carabineros-y-pdi">Policía</button>
                        <button type="button" class="babel-inst-pill bd-category-pill" data-category="hospitales-y-sapu">Salud</button>
                        <button type="button" class="babel-inst-pill bd-category-pill" data-category="bomberos">Bomberos</button>
                        <button type="button" class="babel-inst-pill bd-category-pill" data-category="registro-civil">Registro Civil</button>
                        <button type="button" class="babel-inst-pill bd-category-pill" data-category="correos-y-encomiendas">Correos</button>
                    </div>
                </div>
            </div>

            <!-- Contenedor Dinámico para Carga Asíncrona (AJAX) -->
            <!-- Usamos data-region dinámico basado en PHP para heredar el contexto -->
            <?php if ( 'yes' === $atts['show_results'] || 'true' === $atts['show_results'] || true === $atts['show_results'] || '1' === $atts['show_results'] ) : ?>
                <style>
                    /* Forzar Fullwidth en Divi para resultados de búsqueda y archivos del directorio */
                    #main-content .container::before { display: none !important; }
                    #sidebar { display: none !important; }
                    #left-area { width: 100% !important; padding-right: 0 !important; border-right: none !important; float: none !important; }
                </style>
                <div id="babel-directory-results" class="babel-results-container" data-region="<?php echo esc_attr( $current_region_slug ); ?>" data-category="" data-entity-type="all"></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_auth_menu( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_style( 'babel-global-auth-css' );
        $ms_client_id = get_option( 'babel_microsoft_client_id', '' );
        if ( ! empty( $ms_client_id ) ) {
            wp_enqueue_script( 'msal-browser' );
        }
        $is_logged_in = is_user_logged_in();
        ob_start();
        ?>
        <div class="babel-global-auth-menu">
            <?php if ( ! $is_logged_in ) : ?>
                <button type="button" class="babel-auth-login-btn" onclick="openBabelAuthModal()">
                    <span class="material-symbols-outlined">person</span>
                    <span>Iniciar sesión</span>
                </button>
            <?php else : 
                $user = wp_get_current_user();
                $avatar = get_user_meta( $user->ID, '_babel_google_avatar', true ) ?: get_avatar_url( $user->ID, array( 'size' => 32 ) );
                $dashboard_url = home_url('/mi-cuenta/');
            ?>
                <div class="babel-auth-user-dropdown-wrap">
                    <button type="button" class="babel-auth-user-btn">
                        <img src="<?php echo esc_url( $avatar ); ?>" alt="Avatar" class="babel-auth-avatar">
                        <span class="babel-auth-username"><?php echo esc_html( explode(' ', $user->display_name)[0] ); ?></span>
                        <span class="material-symbols-outlined dropdown-icon">expand_more</span>
                    </button>
                    <ul class="babel-auth-dropdown-menu">
                        <li><a href="<?php echo esc_url( $dashboard_url ); ?>"><span class="material-symbols-outlined">dashboard</span> Mi Panel</a></li>
                        <li><a href="<?php echo wp_logout_url( home_url() ); ?>"><span class="material-symbols-outlined">logout</span> Cerrar sesión</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_mobile_footer() {
        if ( is_admin() ) return;
        ?>
        <div class="babel-mobile-bottom-nav">
            <a href="/" class="babel-mb-nav-item">
                <span class="material-symbols-outlined">home</span>
                <span class="babel-mb-nav-label">Home</span>
            </a>
            <a href="/buscar/" class="babel-mb-nav-item">
                <span class="material-symbols-outlined">search</span>
                <span class="babel-mb-nav-label">Buscar</span>
            </a>
            <a href="/buscar/" class="babel-mb-nav-item" onclick="document.getElementById('babel-geo-btn') && document.getElementById('babel-geo-btn').click(); return false;">
                <span class="material-symbols-outlined">location_on</span>
                <span class="babel-mb-nav-label">Mapa</span>
            </a>
            <a href="#" class="babel-mb-nav-item babel-trigger-auth">
                <span class="material-symbols-outlined">person</span>
                <span class="babel-mb-nav-label">Perfil</span>
            </a>
        </div>
        <?php
    }

    public function render_global_auth_modal() {
        if ( is_user_logged_in() ) return;
        $client_id = get_option( 'babel_google_client_id', '' );
        $ms_client_id = get_option( 'babel_microsoft_client_id', '' );
        if ( empty( $client_id ) && empty( $ms_client_id ) ) return;
        ?>
        <!-- Modal Global de Login (Babel Directory - Trivago Style) -->
        <div id="babel-auth-modal" class="babel-modal-overlay hidden" aria-hidden="true" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(4px); z-index: 999999; display: flex; align-items: center; justify-content: center; visibility: hidden; opacity: 0; pointer-events: none; transition: opacity 0.3s ease;">
            <div class="babel-modal-container" style="background: #ffffff; width: 100%; max-width: 420px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); position: relative; overflow: hidden; transform: translateY(20px); transition: transform 0.3s ease;">
                
                <!-- Botón Cerrar -->
                <button type="button" class="babel-modal-close" onclick="closeBabelAuthModal()" aria-label="Cerrar" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; font-size: 24px; color: #6b7280; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; transition: background 0.2s;">&times;</button>
                
                <!-- Cabecera -->
                <div style="padding: 32px 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
                    <h2 style="font-family: 'Inter', -apple-system, sans-serif; font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px 0;">Inicia sesión o regístrate</h2>
                    <p style="font-family: 'Inter', -apple-system, sans-serif; font-size: 14px; color: #6b7280; margin: 0;">Para publicar tu negocio y gestionar reseñas.</p>
                </div>

                <!-- Contenido -->
                <div style="padding: 24px 32px 32px;">
                    <!-- Email Form (Visual Placeholder for Trivago look) -->
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; text-align: left;">Correo electrónico</label>
                        <input type="email" placeholder="ejemplo@correo.com" style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; outline: none; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='#0c71c3'" onblur="this.style.borderColor='#d1d5db'">
                        <button type="button" style="width: 100%; margin-top: 16px; background: #0c71c3; color: white; border: none; padding: 12px; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#0a5a9c'" onmouseout="this.style.background='#0c71c3'">Continuar con email</button>
                    </div>

                    <!-- Divisor -->
                    <div style="display: flex; align-items: center; margin: 24px 0;">
                        <div style="flex-grow: 1; height: 1px; background: #e5e7eb;"></div>
                        <span style="padding: 0 12px; font-family: 'Inter', sans-serif; font-size: 13px; color: #9ca3af; text-transform: uppercase; font-weight: 600;">O usar</span>
                        <div style="flex-grow: 1; height: 1px; background: #e5e7eb;"></div>
                    </div>

                    <!-- Social Buttons -->
                    <div class="flex flex-col items-center gap-4" style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                        <?php if ( ! empty( $client_id ) ) : ?>
                        <div id="babel-global-google-btn-container" style="min-height: 44px; width: 100%; display: flex; justify-content: center;">
                            <div id="g_id_onload_global" data-client_id="<?php echo esc_attr( $client_id ); ?>" data-callback="handleGlobalBabelGoogleLogin" data-auto_prompt="false"></div>
                            <!-- Ajustado a width: 100% para simular Trivago -->
                            <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline" data-text="continue_with" data-size="large" data-locale="es" data-logo_alignment="center" style="width: 100%;"></div>
                        </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $ms_client_id ) ) : ?>
                        <button type="button" id="babel-microsoft-login-btn" data-client_id="<?php echo esc_attr( $ms_client_id ); ?>" style="width: 100%; min-height: 44px; display: flex; align-items: center; justify-content: center; gap: 12px; background: #ffffff; border: 1px solid #dadce0; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 500; color: #3c4043; cursor: pointer; transition: background 0.2s;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21"><path fill="#f35325" d="M1 1h9v9H1z"/><path fill="#81bc06" d="M11 1h9v9h-9z"/><path fill="#05a6f0" d="M1 11h9v9H1z"/><path fill="#ffba08" d="M11 11h9v9h-9z"/></svg>
                            Continuar con Microsoft
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Loading State -->
                    <div id="babel-global-login-loading" class="hidden" style="display: none; margin-top: 24px; text-align: center;">
                        <div style="display: inline-block; width: 32px; height: 32px; border: 3px solid rgba(12, 113, 195, 0.2); border-top-color: #0c71c3; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <p style="font-family: 'Inter', sans-serif; font-size: 14px; color: #4b5563; margin-top: 12px;">Conectando...</p>
                    </div>
                </div>
            </div>
        </div>
        <style>
            @keyframes spin { to { transform: rotate(360deg); } }
            /* Se removió slideUpFade porque ahora usamos transition transform */
            .babel-modal-close:hover { background: #f3f4f6 !important; color: #111827 !important; }
        </style>
        <?php
    }

    public function render_region_carousel( $atts ) {
        if ( ! is_array( $atts ) ) {
            $atts = array();
        }
        $atts['layout']  = 'carousel';
        $atts['rows']    = 1;
        $atts['columns'] = 20; // Enough to fit all regions
        return $this->render_region_grid( $atts );
    }

    public function render_region_grid( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        $atts = shortcode_atts( array(
            'columns' => 4,
            'rows'    => 4,
            'layout'  => 'grid',
            'orderby' => 'geographic',
        ), $atts, 'babel_region_grid' );

        $columns = intval( $atts['columns'] );
        $rows = intval( $atts['rows'] );
        $layout  = sanitize_key( $atts['layout'] );
        $orderby = sanitize_key( $atts['orderby'] );
        $limit = $columns * $rows;

        $terms_args = array(
            'taxonomy'   => 'babel_region',
            'hide_empty' => false,
            'parent'     => 0,
        );

        $tkey = 'bd_regions_grid_' . $layout . '_' . $orderby . '_' . $limit . '_' . md5(serialize($terms_args));
        $terms = get_transient($tkey);
        if ( false === $terms ) {
            $terms = get_terms( $terms_args );
            if ( ! \is_wp_error( $terms ) && ! empty( $terms ) ) {
                // Función auxiliar para convertir números romanos a enteros
                $roman_to_int = function( $roman ) {
                    $romans = array(
                        'I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100, 'D' => 500, 'M' => 1000
                    );
                    $result = 0;
                    $roman = strtoupper( trim( $roman ) );
                    for ( $i = 0; $i < strlen( $roman ); $i++ ) {
                        if ( $i + 1 < strlen( $roman ) && isset( $romans[$roman[$i]], $romans[$roman[$i + 1]] ) && $romans[$roman[$i]] < $romans[$roman[$i + 1]] ) {
                            $result -= $romans[$roman[$i]];
                        } elseif ( isset( $romans[$roman[$i]] ) ) {
                            $result += $romans[$roman[$i]];
                        }
                    }
                    return $result;
                };

                // Calcular business_count recursivo para cada región para poder ordenar u optimizar
                foreach ( $terms as $term ) {
                    $child_ids = get_term_children( $term->term_id, 'babel_region' );
                    $term_ids = array( $term->term_id );
                    if ( ! \is_wp_error( $child_ids ) && ! empty( $child_ids ) ) {
                        $term_ids = array_merge( $term_ids, $child_ids );
                    }

                    $business_query = new \WP_Query( array(
                        'post_type'      => 'babel_business',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                        'no_found_rows'  => false,
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'babel_region',
                                'field'    => 'term_id',
                                'terms'    => $term_ids,
                                'operator' => 'IN',
                            ),
                        ),
                    ) );
                    $term->business_count = $business_query->found_posts;
                    
                    // Pre-calcular imágenes y links para que el transient sea completo
                    $image_id = get_term_meta( $term->term_id, 'bd_term_image_id', true );
                    $term->image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
                    $term_link = get_term_link( $term );
                    $term->term_link = \is_wp_error( $term_link ) ? '' : $term_link;
                }

                // Ordenar
                if ( $orderby === 'count' ) {
                    usort( $terms, function( $a, $b ) {
                        return $b->business_count <=> $a->business_count;
                    } );
                } else {
                    // Orden por defecto: geográfico por número romano
                    usort( $terms, function( $a, $b ) use ( $roman_to_int ) {
                        preg_match( '/^([IVXLCDM]+)/i', $a->name, $a_matches );
                        preg_match( '/^([IVXLCDM]+)/i', $b->name, $b_matches );

                        $val_a = ! empty( $a_matches[1] ) ? $roman_to_int( $a_matches[1] ) : 999;
                        $val_b = ! empty( $b_matches[1] ) ? $roman_to_int( $b_matches[1] ) : 999;

                        return $val_a <=> $val_b;
                    } );
                }

                // Aplicar el límite
                if ( $limit > 0 && count( $terms ) > $limit ) {
                    $terms = array_slice( $terms, 0, $limit );
                }

                set_transient( $tkey, $terms, 12 * HOUR_IN_SECONDS );
            }
        }

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p>No se encontraron regiones.</p>';
        }

        ob_start();
        if ( $layout === 'carousel' ) {
            echo '<div class="babel-regions-carousel" data-carousel="babel-regions">';
            echo '<button class="babel-carousel-btn babel-carousel-btn--prev" aria-label="Anterior">';
            echo '    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">';
            echo '        <polyline points="15 18 9 12 15 6"></polyline>';
            echo '    </svg>';
            echo '</button>';
            echo '<div class="babel-carousel-track-wrap">';
            echo '<div class="babel-carousel-track">';
        } else {
            echo '<div class="babel-region-grid" style="--babel-grid-cols: ' . esc_attr( $columns ) . ';">';
        }
        
        foreach ( $terms as $term ) {
            $image_url = ! empty( $term->image_url ) ? $term->image_url : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="%232c3e50"/></svg>';
            
            if ( empty( $term->term_link ) ) {
                continue;
            }

            // Limpiar nombre para la clase PIP
            $clean_name = preg_replace('/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $term->name);

            // Detectar si la región tiene efecto PIP en la imagen original para recortar bordes borrosos
            $has_pip = false;
            $pip_regions = array( 'atacama', 'valparaiso', 'valparaíso', 'magallanes', 'arica' );
            foreach ( $pip_regions as $pip_r ) {
                if ( stripos( $clean_name, $pip_r ) !== false ) {
                    $has_pip = true;
                    break;
                }
            }
            $pip_class = $has_pip ? ' babel-region-pip-fix' : '';

            ?>
            <div class="babel-region-wrapper">
                <a href="<?php echo esc_url( $term->term_link ); ?>" class="babel-region-card no-lightbox disable-lightbox" target="_self" data-et-has-event-already="true">
                    <div class="babel-region-bg<?php echo esc_attr( $pip_class ); ?>" style="background-image: url('<?php echo esc_url( $image_url ); ?>');"></div>
                    <div class="babel-region-overlay"></div>
                    <div class="babel-region-content">
                        <span class="babel-region-title"><?php echo esc_html( $term->name ); ?></span>
                    </div>
                </a>
            </div>
            <?php
        }
        
        if ( $layout === 'carousel' ) {
            echo '</div>'; // babel-carousel-track
            echo '</div>'; // babel-carousel-track-wrap
            echo '<button class="babel-carousel-btn babel-carousel-btn--next" aria-label="Siguiente">';
            echo '    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">';
            echo '        <polyline points="9 18 15 12 9 6"></polyline>';
            echo '    </svg>';
            echo '</button>';
            echo '</div>'; // babel-regions-carousel
        } else {
            echo '</div>'; // babel-region-grid
        }
        return ob_get_clean();
    }

    public function render_category_grid( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        $atts = shortcode_atts( array(
            'columns' => 4,
            'rows'    => 4,
        ), $atts, 'bd_popular_categories' );

        $columns = intval( $atts['columns'] );
        $rows = intval( $atts['rows'] );
        $limit = $columns * $rows;

        $terms_args = array(
            'taxonomy'   => 'babel_category',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'count',
            'order'      => 'DESC',
        );

        $tkey = 'bd_popular_cats_' . md5(serialize($terms_args));
        $terms = get_transient($tkey);
        if ( false === $terms ) {
            $terms = get_terms( $terms_args );
            if ( ! is_wp_error( $terms ) ) { set_transient( $tkey, $terms, 12 * HOUR_IN_SECONDS ); }
        }

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p>No se encontraron categorías.</p>';
        }

        if ( $limit > 0 && count( $terms ) > $limit ) {
            $terms = array_slice( $terms, 0, $limit );
        }

        ob_start();
        ?>
        <style>
        .babel-category-grid-ml {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 16px;
            padding: 20px 0;
        }
        .babel-cat-card {
            background: #ffffff;
            border: 1px solid #eaeaea;
            border-radius: 16px;
            padding: 24px 12px;
            text-align: center;
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            position: relative;
            overflow: hidden;
        }
        .babel-cat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 57, 166, 0.12);
            border-color: transparent;
        }
        .babel-cat-icon-wrapper {
            width: 64px;
            height: 64px;
            background: #f0f4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: #0039A6;
            transition: all 0.3s ease;
        }
        .babel-cat-card:hover .babel-cat-icon-wrapper {
            background: #0039A6;
            color: #ffffff;
            transform: scale(1.05);
        }
        .babel-cat-icon-wrapper .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
        }
        .babel-cat-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .babel-cat-count {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .babel-category-grid-ml {
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
                gap: 12px;
            }
            .babel-cat-card {
                padding: 16px 8px;
            }
            .babel-cat-icon-wrapper {
                width: 52px;
                height: 52px;
                margin-bottom: 12px;
            }
            .babel-cat-icon-wrapper .dashicons {
                font-size: 26px;
                width: 26px;
                height: 26px;
            }
            .babel-cat-title {
                font-size: 13px;
            }
        }
        </style>
        <div class="babel-category-grid-ml">
        <?php
        
        foreach ( $terms as $term ) {
            $cat_url = home_url( '/buscar/?categoria=' . $term->slug );
            $icon = get_term_meta( $term->term_id, 'dashicon', true );
            if ( empty( $icon ) ) {
                $icon = 'dashicons-store'; // Ícono premium por defecto
            }

            ?>
            <a href="<?php echo esc_url( $cat_url ); ?>" class="babel-cat-card no-lightbox disable-lightbox" target="_self">
                <div class="babel-cat-icon-wrapper">
                    <span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
                </div>
                <span class="babel-cat-title"><?php echo esc_html( $term->name ); ?></span>
                <span class="babel-cat-count"><?php echo esc_html( $term->count ); ?> locales</span>
            </a>
            <?php
        }
        
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_footer_regions] para renderizar un listado de regiones ordenadas alfabéticamente
     */
    public function render_footer_regions( $atts ) {
        wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'columns' => 2,
            'rows'    => 8,
            'orderby' => 'name',
            'order'   => 'ASC',
        ), $atts, 'bd_footer_regions' );

        $columns = intval( $atts['columns'] );
        $rows = intval( $atts['rows'] );

        $terms = get_transient('bd_footer_regions_cache');
        if ( false === $terms ) {
            $terms = get_terms( array(
                'taxonomy'   => 'babel_region',
                'parent'     => 0,
                'hide_empty' => false,
            ) );
            if ( ! is_wp_error( $terms ) ) { set_transient( 'bd_footer_regions_cache', $terms, 12 * HOUR_IN_SECONDS ); }
        }

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        // Ordenar alfabéticamente por el nombre original (incluye números romanos)
        usort( $terms, function( $a, $b ) {
            return strcasecmp( $a->name, $b->name );
        } );

        // Limitar los elementos
        $limit = $columns * $rows;
        if ( $limit > 0 && count( $terms ) > $limit ) {
            $terms = array_slice( $terms, 0, $limit );
        }

        ob_start();
        ?>
        <div class="bd-regions-col">
            <ul style="column-count: <?php echo esc_attr( $columns ); ?>; column-gap: 20px;">
                <?php foreach ( $terms as $term ) : ?>
                    <?php
                    $term_link = get_term_link( $term );
                    if ( \is_wp_error( $term_link ) ) {
                        continue;
                    }
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $term_link ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_footer_categories] para renderizar un listado de categorías principales
     */
    public function render_footer_categories( $atts ) {
        wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'columns' => 3,
            'rows'    => 8,
            'parent'  => 0,
            'orderby' => 'name',
            'order'   => 'ASC',
        ), $atts, 'bd_footer_categories' );

        $columns = intval( $atts['columns'] );
        $rows = intval( $atts['rows'] );
        $parent = $atts['parent'] === 'any' ? '' : intval( $atts['parent'] );

        $terms_args = array(
            'taxonomy'   => 'babel_category',
            'hide_empty' => false,
            'orderby'    => $atts['orderby'],
            'order'      => $atts['order'],
        );

        if ( $parent !== '' ) {
            $terms_args['parent'] = $parent;
        }

        $tkey = 'bd_footer_cats_' . md5(serialize($terms_args));
        $terms = get_transient($tkey);
        if ( false === $terms ) {
            $terms = get_terms( $terms_args );
            if ( ! is_wp_error( $terms ) ) { set_transient( $tkey, $terms, 12 * HOUR_IN_SECONDS ); }
        }

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        // Limitar los elementos
        $limit = $columns * $rows;
        if ( $limit > 0 && count( $terms ) > $limit ) {
            $terms = array_slice( $terms, 0, $limit );
        }

        ob_start();
        ?>
        <div class="bd-categories-col">
            <ul style="column-count: <?php echo esc_attr( $columns ); ?>; column-gap: 30px;">
                <?php foreach ( $terms as $term ) : ?>
                    <?php
                    $term_link = get_term_link( $term );
                    if ( \is_wp_error( $term_link ) ) {
                        continue;
                    }
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $term_link ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_archive_loop] para inyectar el loop nativo de resultados
     * en plantillas visuales del Divi Theme Builder (ideal para CPT/Taxonomías)
     */
    public function render_archive_loop( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        
        ob_start();
        $card_counter = 0;
        ?>
        <style>
            /* Forzar Fullwidth en Divi para resultados de búsqueda y archivos del directorio */
            #main-content .container::before { display: none !important; }
            #sidebar { display: none !important; }
            #left-area { width: 100% !important; padding-right: 0 !important; border-right: none !important; float: none !important; }
        </style>
        <div class="sdc-grid-archive">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post();

                    $post_id   = get_the_ID();
                    $categorias = get_the_terms( $post_id, 'babel_category' );
                    $regiones   = get_the_terms( $post_id, 'babel_region' );

                    $cat_name   = ( ! empty( $categorias ) && ! \is_wp_error( $categorias ) )
                                    ? esc_html( $categorias[0]->name )
                                    : '';
                    $reg_name   = '';
                    if ( ! empty( $regiones ) && ! \is_wp_error( $regiones ) ) {
                        $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $regiones[0]->name );
                        $reg_name = esc_html( $reg_name );
                    }

                    $price_range = \get_post_meta( $post_id, '_babel_price_range', true );
                    $rating_avg  = (float) \get_post_meta( $post_id, '_babel_rating_avg', true );
                    $rating_count = (int) \get_post_meta( $post_id, '_babel_rating_count', true );
                    $is_featured = \get_post_meta( $post_id, '_babel_featured', true );
                    $is_verified = \get_post_meta( $post_id, '_babel_verified', true );

                    // Thumbnail: usa imagen destacada del post, sin fallbacks externos.
                    $thumb_id  = get_post_thumbnail_id( $post_id );
                    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';
                ?>
                    <a href="<?php the_permalink(); ?>" class="babel-biz-card" aria-label="<?php the_title_attribute(); ?>">

                        <!-- Zona de imagen -->
                        <div class="babel-biz-card__image-wrap">
                            <?php if ( $thumb_url ) : ?>
                                <img
                                    src="<?php echo esc_url( $thumb_url ); ?>"
                                    alt="<?php the_title_attribute(); ?>"
                                    class="babel-biz-card__image"
                                    loading="lazy"
                                />
                            <?php else : ?>
                                <div class="babel-biz-card__placeholder">
                                    <span class="material-symbols-outlined" style="font-size:56px;">store</span>
                                </div>
                            <?php endif; ?>

                            <!-- Badges flotantes -->
                            <?php if ( $is_featured || $is_verified ) : ?>
                                <div class="babel-biz-card__badges">
                                    <?php if ( $is_featured ) : ?>
                                        <span class="babel-biz-card__badge babel-biz-card__badge--featured">
                                            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">stars</span>
                                            <?php esc_html_e( 'Destacado', 'babel-directory' ); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ( $is_verified ) : ?>
                                        <span class="babel-biz-card__badge babel-biz-card__badge--verified">
                                            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">verified</span>
                                            <?php esc_html_e( 'Verificado', 'babel-directory' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div><!-- /.babel-biz-card__image-wrap -->

                        <!-- Cuerpo de la tarjeta -->
                        <div class="babel-biz-card__body">

                            <h3 class="babel-biz-card__title"><?php the_title(); ?></h3>

                            <?php if ( $rating_count > 0 ) : ?>
                                <div class="babel-biz-card__rating" aria-label="<?php echo esc_attr( number_format( $rating_avg, 1 ) ); ?> de 5 estrellas">
                                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;" aria-hidden="true">star</span>
                                    <span class="babel-biz-card__rating-score"><?php echo esc_html( number_format( $rating_avg, 1 ) ); ?></span>
                                    <span class="babel-biz-card__rating-count">(<?php echo esc_html( $rating_count ); ?>)</span>
                                </div>
                            <?php endif; ?>

                            <?php if ( $cat_name || $reg_name ) : ?>
                                <div class="babel-biz-card__meta">
                                    <?php if ( $cat_name ) : ?>
                                        <span class="babel-biz-card__meta-item">
                                            <span class="material-symbols-outlined" aria-hidden="true">category</span>
                                            <?php echo $cat_name; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ( $cat_name && $reg_name ) : ?>
                                        <span class="babel-biz-card__meta-sep" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <?php if ( $reg_name ) : ?>
                                        <span class="babel-biz-card__meta-item">
                                            <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                                            <?php echo $reg_name; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="babel-biz-card__footer">
                                <?php if ( $price_range ) : ?>
                                    <span class="babel-biz-card__price"><?php echo esc_html( $price_range ); ?></span>
                                <?php else : ?>
                                    <span></span>
                                <?php endif; ?>
                                <span class="babel-biz-card__cta">
                                    <?php esc_html_e( 'Ver perfil', 'babel-directory' ); ?>
                                    <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                                </span>
                            </div>

                        </div><!-- /.babel-biz-card__body -->

                    </a><!-- /.babel-biz-card -->
                    <?php 
                    $card_counter++;
                    if ( 0 === $card_counter % 4 ) {
                        // Inyectar anuncio publicitario in-loop adaptado a la región actual
                        echo $this->render_ad_space( array( 'position' => 'in_loop_ad', 'region' => 'auto' ) );
                    }
                    ?>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="sdc-no-results"><?php esc_html_e( 'No se encontraron negocios en esta categoría.', 'babel-directory' ); ?></p>
            <?php endif; ?>
        </div>


        <!-- Pagination -->
        <div class="sdc-pagination">
            <?php
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '<i class="material-symbols-outlined">chevron_left</i>',
                'next_text' => '<i class="material-symbols-outlined">chevron_right</i>',
            ) );
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_region_template] para renderizar la página de región interactiva completa.
     */
    public function render_region_template( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        $atts = shortcode_atts( array(
            'region' => 'auto',
        ), $atts, 'bd_region_template' );

        $term = null;
        if ( 'auto' === $atts['region'] ) {
            $term = get_queried_object();
        } else {
            $term = get_term_by( 'slug', $atts['region'], 'babel_region' );
        }

        if ( ! $term || \is_wp_error( $term ) || ! is_a( $term, 'WP_Term' ) || ! in_array( $term->taxonomy, array( 'babel_region', 'babel_category' ) ) ) {
            // Fallback para cuando no estamos en una página de taxonomía (ej. previsualización en página normal)
            $terms = get_terms( array(
                'taxonomy'   => 'babel_region',
                'number'     => 1,
                'hide_empty' => false,
            ) );
            if ( ! \is_wp_error( $terms ) && ! empty( $terms ) ) {
                $term = $terms[0];
            }
        }

        if ( ! $term ) {
            return '<p>' . esc_html__( 'Taxonomía no encontrada.', 'babel-directory' ) . '</p>';
        }

        // Limpiar el nombre
        if ( 'babel_region' === $term->taxonomy ) {
            $full_name = $term->name;
            $clean_name = preg_replace('/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $full_name);
            preg_match('/^([IVX]+)/i', $full_name, $matches);
            $eyebrow = ! empty( $matches[1] ) ? sprintf( __( 'Región %s', 'babel-directory' ), $matches[1] ) : __( 'Región de Chile', 'babel-directory' );
        } else {
            $clean_name = $term->name;
            $eyebrow = __( 'Categoría Comercial', 'babel-directory' );
        }

        // Obtener la imagen
        $image_id = get_term_meta( $term->term_id, 'bd_term_image_id', true );
        $bg_style = '';
        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'large' );
            $bg_style = "background-image: url('" . esc_url( $image_url ) . "');";
        } else {
            // Degradee de portada por defecto
            $bg_style = "background-image: linear-gradient(180deg, #000f54 42%, #0c71c3 100%);";
        }

        // Conteo de negocios
        $child_ids = get_term_children( $term->term_id, $term->taxonomy );
        $term_ids = array( $term->term_id );
        if ( ! \is_wp_error( $child_ids ) && ! empty( $child_ids ) ) {
            $term_ids = array_merge( $term_ids, $child_ids );
        }
        $business_query = new \WP_Query( array(
            'post_type'      => 'babel_business',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'tax_query'      => array(
                array(
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_ids,
                    'operator' => 'IN',
                ),
            ),
        ) );
        $business_count = $business_query->found_posts;

        ob_start();
        ?>
        <div class="bd-region-container">
            <!-- Breadcrumbs -->
            <div class="bd-region-breadcrumbs-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 20px 20px 0 20px;">
                <?php echo do_shortcode( '[bd_breadcrumbs]' ); ?>
            </div>

            <!-- Hero Section -->
            <div class="bd-region-hero">
                <div class="bd-region-hero-bg" style="<?php echo $bg_style; ?>"></div>
                <div class="bd-region-hero-overlay" <?php echo empty($image_id) ? 'style="opacity: 0.1;"' : ''; ?>></div>
                <div class="bd-region-hero-content">
                    <span class="bd-region-hero-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
                    <h1 class="bd-region-hero-title"><?php echo esc_html( $clean_name ); ?></h1>
                    <p class="bd-region-hero-count">
                        <?php 
                        printf( 
                            _n( '<strong>%d</strong> negocio registrado', '<strong>%d</strong> negocios registrados', $business_count, 'babel-directory' ), 
                            $business_count 
                        ); 
                        ?>
                    </p>
                </div>
            </div>

            <?php
            // Secciones dinámicas para la taxonomía babel_region (regiones y comunas)
            if ( 'babel_region' === $term->taxonomy ) {
                // 3. Instituciones removido de aquí (ahora es shortcode independiente)

                // 4. Categorías más buscadas de la Región
                $top_cats_key = 'bd_top_cats_' . $term->term_id;
                $top_cats = get_transient( $top_cats_key );
                if ( false === $top_cats ) {
                    $biz_ids = \get_posts( array(
                        'post_type'      => 'babel_business',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => $term->taxonomy,
                                'field'    => 'term_id',
                                'terms'    => $term_ids,
                                'operator' => 'IN',
                            ),
                        ),
                    ) );
                    $cat_counts = array();
                    foreach ( $biz_ids as $biz_id ) {
                        $biz_cats = \wp_get_post_terms( $biz_id, 'babel_category', array( 'fields' => 'ids' ) );
                        if ( ! \is_wp_error( $biz_cats ) ) {
                            foreach ( $biz_cats as $cat_id ) {
                                $cat_counts[ $cat_id ] = ( isset( $cat_counts[ $cat_id ] ) ? $cat_counts[ $cat_id ] : 0 ) + 1;
                            }
                        }
                    }
                    \arsort( $cat_counts );
                    $top_cat_ids = array_slice( array_keys( $cat_counts ), 0, 10 );
                    $top_cats = array();
                    foreach ( $top_cat_ids as $cat_id ) {
                        $cat_term = \get_term( $cat_id, 'babel_category' );
                        if ( $cat_term && ! \is_wp_error( $cat_term ) ) {
                            $top_cats[] = $cat_term;
                        }
                    }
                    set_transient( $top_cats_key, $top_cats, 12 * HOUR_IN_SECONDS );
                }

                if ( ! empty( $top_cats ) ) {
                    ?>
                    <div class="bd-region-top-cats">
                        <div class="bd-region-top-cats__inner">
                            <h3 class="bd-region-top-cats__title"><?php printf( esc_html__( 'Lo más buscado en %s', 'babel-directory' ), esc_html( $clean_name ) ); ?></h3>
                            <div class="bd-region-top-cats__chips">
                                <?php foreach ( $top_cats as $top_cat ) :
                                    $cat_url = \home_url( '/buscar/?categoria=' . $top_cat->slug . '&region=' . $term->slug );
                                ?>
                                <a href="<?php echo esc_url( $cat_url ); ?>" class="bd-top-cat-chip">
                                    <?php echo esc_html( $top_cat->name ); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }

                // 5. Negocios Destacados en Página de Región
                $has_featured = ! empty( \get_posts( array(
                    'post_type'      => 'babel_business',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array( 'key' => '_babel_featured', 'value' => '1', 'compare' => '=' ),
                    ),
                    'tax_query' => array(
                        array(
                            'taxonomy' => $term->taxonomy,
                            'field'    => 'term_id',
                            'terms'    => array( $term->term_id ),
                            'operator' => 'IN',
                        ),
                    ),
                ) ) );

                if ( $has_featured ) {
                    ?>
                    <div class="bd-region-featured-wrap">
                        <div class="bd-region-featured-header">
                            <h3 class="bd-region-featured-title">
                                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">stars</span>
                                <?php esc_html_e( 'Negocios Destacados', 'babel-directory' ); ?>
                            </h3>
                        </div>
                        <?php echo do_shortcode( '[bd_featured_businesses region="' . esc_attr( $term->slug ) . '" limit="4"]' ); ?>
                    </div>
                    <?php
                }
            }
            ?>

            <!-- Search Filter Bar (Pre-filtrado) -->
            <div class="bd-region-search-wrapper" style="position: relative; z-index: 10;">
                <?php echo do_shortcode( '[bd_filter_bar]' ); ?>
            </div>

            <!-- Chips de Subcategorías (Solo para Categorías con hijos) -->
            <?php
            if ( 'babel_category' === $term->taxonomy ) {
                $subcats = get_terms( array(
                    'taxonomy'   => 'babel_category',
                    'parent'     => $term->term_id,
                    'hide_empty' => false,
                ) );

                if ( ! empty( $subcats ) && ! \is_wp_error( $subcats ) ) {
                    echo '<div class="bd-taxonomy-chips">';
                    foreach ( $subcats as $subcat ) {
                        $subcat_link = get_term_link( $subcat );
                        if ( ! \is_wp_error( $subcat_link ) ) {
                            echo '<a href="' . esc_url( $subcat_link ) . '" class="bd-taxonomy-chip">';
                            echo esc_html( $subcat->name );
                            echo '</a>';
                        }
                    }
                    echo '</div>';
                }
            }
            ?>

            <!-- Results Header Section -->
            <div class="bd-region-results-header">
                <h2 class="bd-region-results-title">
                    <?php 
                    printf( 
                        esc_html__( 'Todos los negocios en %s', 'babel-directory' ), 
                        esc_html( $clean_name ) 
                    ); 
                    ?>
                </h2>
            </div>

            <!-- Results Wrap -->
            <div class="bd-region-results-wrap">
                <?php
                $data_region = ( 'babel_region' === $term->taxonomy ) ? $term->slug : '';
                $data_category = ( 'babel_category' === $term->taxonomy ) ? $term->slug : '';
                ?>
                <div id="babel-directory-results" class="babel-results-container" data-region="<?php echo esc_attr( $data_region ); ?>" data-category="<?php echo esc_attr( $data_category ); ?>">
                    <!-- Los resultados se cargan vía AJAX/REST al cargar la página -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_business_profile] para renderizar el perfil de negocio premium (Stitch UI Reestructurado)
     */
    public function render_business_profile( $atts ) {
        \wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'id' => '',
        ), $atts, 'bd_business_profile' );

        if ( ! empty( $atts['id'] ) ) {
            $post_id = intval( $atts['id'] );
        } else {
            $queried_id = \get_queried_object_id();
            if ( $queried_id && 'babel_business' === \get_post_type( $queried_id ) ) {
                $post_id = $queried_id;
            } else {
                $post_id = \get_the_ID();
            }
        }

        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            // Último intento: revisar el global post
            global $post;
            if ( isset( $post ) && 'babel_business' === $post->post_type ) {
                $post_id = $post->ID;
            } else {
                return '';
            }
        }

        // Obtener metadatos
        $phone            = \get_post_meta( $post_id, '_babel_phone', true );
        $whatsapp         = \get_post_meta( $post_id, '_babel_whatsapp', true );
        $email            = \get_post_meta( $post_id, '_babel_email', true );
        $website          = \get_post_meta( $post_id, '_babel_website', true );
        $address          = \get_post_meta( $post_id, '_babel_address', true );
        $instagram        = \get_post_meta( $post_id, '_babel_instagram', true );
        $facebook         = \get_post_meta( $post_id, '_babel_facebook', true );
        $linkedin         = \get_post_meta( $post_id, '_babel_linkedin', true );
        $tiktok           = \get_post_meta( $post_id, '_babel_tiktok', true );
        $twitter          = \get_post_meta( $post_id, '_babel_twitter', true );
        $youtube_channel  = \get_post_meta( $post_id, '_babel_youtube_channel', true );
        $verified         = \get_post_meta( $post_id, '_babel_verified', true );
        $featured         = \get_post_meta( $post_id, '_babel_featured', true );
        $is_institution   = \get_post_meta( $post_id, '_babel_is_institution', true );
        $gallery_meta     = \get_post_meta( $post_id, '_babel_gallery', true );
        $hours_meta       = \get_post_meta( $post_id, '_babel_hours', true );
        $rut              = \get_post_meta( $post_id, '_babel_rut', true );
        $razon_social     = \get_post_meta( $post_id, '_babel_razon_social', true );
        $parking          = \get_post_meta( $post_id, '_babel_parking', true );
        $pet_friendly     = \get_post_meta( $post_id, '_babel_pet_friendly', true );
        $wifi             = \get_post_meta( $post_id, '_babel_wifi', true );
        $reservations     = \get_post_meta( $post_id, '_babel_reservations', true );
        $delivery         = \get_post_meta( $post_id, '_babel_delivery', true );
        $price_range      = \get_post_meta( $post_id, '_babel_price_range', true );
        $biz_type         = \get_post_meta( $post_id, '_babel_biz_type', true );

        if ( \is_array( $hours_meta ) ) {
            $hours = $hours_meta;
        } else {
            $hours = ! empty( $hours_meta ) ? \json_decode( $hours_meta, true ) : array();
            if ( ! \is_array( $hours ) ) { $hours = array(); }
        }

        $content = \get_post_field( 'post_content', $post_id );

        // Encolar los estilos y scripts registrados
        \wp_enqueue_style( 'babel-public-css' );
        \wp_enqueue_script( 'babel-public-js' );
        \wp_enqueue_style( 'leaflet-css' );
        \wp_enqueue_script( 'leaflet-js' );

        \ob_start();
        ?>
        <div class="bd-profile-wrapper <?php echo '1' === $featured ? 'bd-profile-featured' : ''; ?>">
            <!-- Breadcrumbs -->
            <div class="bd-profile-breadcrumbs" style="max-width: 1200px; margin: 0 auto; padding: 20px 20px 0 20px;">
                <?php echo do_shortcode( '[bd_breadcrumbs]' ); ?>
            </div>

            <!-- Hero Section -->
            <section class="bd-profile-hero">
                <?php if ( \has_post_thumbnail( $post_id ) ) : ?>
                <div class="bd-profile-logo-wrapper">
                    <?php echo \get_the_post_thumbnail( $post_id, 'medium', array( 'class' => 'bd-profile-logo' ) ); ?>
                </div>
                <?php endif; ?>
                <div class="bd-profile-title-section">
                    <div class="bd-profile-badges-row">
                        <?php if ( '1' === $is_institution ) : ?>
                        <span class="bd-badge-pill bd-badge-institution" style="background-color: #dc2626; color: #ffffff;">
                            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings: 'FILL' 1;">gavel</span>
                            Servicio Público
                        </span>
                        <?php endif; ?>
                        <?php if ( '1' === $verified ) : ?>
                        <span class="bd-badge-pill bd-badge-verified">
                            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings: 'FILL' 1;">verified</span>
                            Verificado
                        </span>
                        <?php endif; ?>
                        <?php if ( '1' === $featured ) : ?>
                        <span class="bd-badge-pill bd-badge-featured">
                            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings: 'FILL' 1;">stars</span>
                            Destacado
                        </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="bd-profile-name">
                        <?php echo esc_html( \get_the_title( $post_id ) ); ?>
                    </h1>
                    <div class="bd-profile-meta-row">
                        <?php if ( ! empty( $price_range ) && '1' !== $is_institution ) : ?>
                        <span class="bd-meta-item bd-meta-price">
                            <span class="material-symbols-outlined" style="font-size:18px;">payments</span>
                            <strong><?php echo esc_html( $price_range ); ?></strong>
                        </span>
                        <?php endif; ?>
                        <?php if ( ! empty( $biz_type ) ) : 
                            $biz_types = array( 'physical' => 'Local físico', 'online' => 'Solo online', 'hybrid' => 'Híbrido', 'mobile' => 'Móvil' );
                            if ( isset( $biz_types[ $biz_type ] ) ) : ?>
                                <span class="bd-meta-item">
                                    <span class="material-symbols-outlined" style="font-size:18px;">location_on</span>
                                    <?php echo esc_html( $biz_types[ $biz_type ] ); ?>
                                </span>
                        <?php endif; endif; ?>
                    </div>
                </div>
            </section>

            <div class="bd-profile-grid">
                <!-- Main Content Area -->
                <div class="bd-profile-main">
                    <?php if ( ! empty( $content ) ) : ?>
                    <section class="bd-profile-section">
                        <h2 class="bd-section-subtitle">Sobre nosotros</h2>
                        <div class="bd-description-content">
                            <?php echo \wpautop( $content ); ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Gallery Highlight -->
                    <?php if ( ! empty( $gallery_meta ) ) : 
                        $gallery_ids = is_array( $gallery_meta ) ? $gallery_meta : explode( ',', $gallery_meta );
                        if ( count( $gallery_ids ) > 0 ) :
                            $main_img_url = wp_get_attachment_image_url( $gallery_ids[0], 'large' );
                            if ( $main_img_url ) :
                    ?>
                    <section class="bd-profile-section">
                        <h2 class="bd-section-subtitle">Galería de Imágenes</h2>
                        <div class="bd-gallery-main" style="height:380px;border-radius:16px;overflow:hidden;position:relative;margin-bottom:12px;box-shadow:var(--bd-shadow);">
                            <img id="main-gallery-img" src="<?php echo esc_url( $main_img_url ); ?>" alt="Galería" style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;" />
                        </div>
                        <?php if ( count( $gallery_ids ) > 1 ) : ?>
                        <div class="bd-photo-grid" style="display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none;-ms-overflow-style:none;">
                            <?php foreach ( $gallery_ids as $img_id ) : 
                                $thumb_url = wp_get_attachment_image_url( $img_id, 'medium' );
                                $full_url = wp_get_attachment_image_url( $img_id, 'large' );
                                if ( ! $thumb_url ) continue;
                            ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>" onclick="document.getElementById('main-gallery-img').src='<?php echo esc_url( $full_url ); ?>'" alt="Miniatura" style="width:96px;height:96px;border-radius:12px;object-fit:cover;cursor:pointer;border:2px solid transparent;transition:border-color 0.2s;flex-shrink:0;" class="bd-gallery-thumbnail-item" onmouseover="this.style.borderColor='var(--color-secondary-fixed-dim,#e9c349)'" onmouseout="this.style.borderColor='transparent'" />
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </section>
                    <?php endif; endif; endif; ?>

                    <?php if ( '1' !== $is_institution ) : ?>
                    <!-- Features Section -->
                    <section class="bd-profile-section">
                        <h2 class="bd-section-subtitle">Características</h2>
                        <div class="bd-amenities-grid">
                            <?php if ( ! empty( $parking ) && 'none' !== $parking ) : ?>
                            <div class="bd-amenity-chip">
                                <span class="material-symbols-outlined bd-icon">local_parking</span>
                                <span>Estacionamiento</span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $wifi ) && 'none' !== $wifi ) : ?>
                            <div class="bd-amenity-chip">
                                <span class="material-symbols-outlined bd-icon">wifi</span>
                                <span>Wi-Fi</span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $pet_friendly ) && 'no' !== $pet_friendly ) : ?>
                            <div class="bd-amenity-chip">
                                <span class="material-symbols-outlined bd-icon">pets</span>
                                <span>Pet Friendly</span>
                            </div>
                            <?php endif; ?>
                            <?php if ( '1' === $delivery ) : ?>
                            <div class="bd-amenity-chip">
                                <span class="material-symbols-outlined bd-icon">delivery_dining</span>
                                <span>Delivery</span>
                            </div>
                            <?php endif; ?>
                            <?php if ( '1' === $reservations ) : ?>
                            <div class="bd-amenity-chip">
                                <span class="material-symbols-outlined bd-icon">calendar_month</span>
                                <span>Reservas</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Puntaje y Comentarios -->
                    <?php
                    $rating_avg = \get_post_meta( $post_id, '_babel_rating_avg', true ) ?: 0;
                    $rating_count = \get_post_meta( $post_id, '_babel_rating_count', true ) ?: 0;
                    $reviews = get_comments( array( 'post_id' => $post_id, 'type' => 'babel_review', 'status' => 'approve', 'number' => 4 ) );
                    
                    if ( '1' !== $is_institution && ( $rating_count > 0 || ! empty( $reviews ) ) ) :
                    ?>
                    <section class="bd-profile-section">
                        <h2 class="bd-section-subtitle">Puntaje y Comentarios</h2>
                        <div class="bd-rating-summary" style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
                            <div class="bd-rating-stars" style="color:var(--color-secondary-fixed-dim,#e9c349);font-size:24px;display:flex;">
                                <?php 
                                $avg_int = floor( $rating_avg );
                                $avg_half = ( $rating_avg - $avg_int ) >= 0.5 ? 1 : 0;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $avg_int) echo '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">star</span>';
                                    elseif ($i == $avg_int + 1 && $avg_half) echo '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 0.5;">star_half</span>';
                                    else echo '<span class="material-symbols-outlined">star</span>';
                                }
                                ?>
                            </div>
                            <div class="bd-rating-score-box" style="display:flex;align-items:baseline;gap:4px;">
                                <span style="font-size:32px;font-weight:700;color:var(--color-primary);"><?php echo number_format((float)$rating_avg, 1, '.', ''); ?></span>
                                <span style="color:var(--color-on-surface-variant);font-size:16px;">/ 5</span>
                            </div>
                            <span style="color:var(--color-on-surface-variant);font-size:14px;border-left:1px solid var(--color-outline-variant);padding-left:16px;">(<?php echo esc_html( $rating_count ); ?> reseñas)</span>
                        </div>
                        <div class="bd-reviews-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                            <?php foreach ( $reviews as $review ) : 
                                $r_rating = get_comment_meta( $review->comment_ID, 'babel_rating', true ) ?: 5;
                                $initials = strtoupper( substr( $review->comment_author, 0, 2 ) );
                            ?>
                            <div class="bd-review-card" style="padding:20px;background:var(--color-surface-container-lowest,#ffffff);border-radius:12px;border:1px solid var(--color-outline-variant);box-shadow:var(--bd-shadow);">
                                <div class="bd-review-header" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                                    <div class="bd-review-avatar" style="width:40px;height:40px;border-radius:50%;background:var(--color-primary);color:#ffffff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;"><?php echo esc_html( $initials ); ?></div>
                                    <div class="bd-review-author-meta">
                                        <p style="margin:0;font-weight:700;color:var(--color-primary);font-size:14px;"><?php echo esc_html( $review->comment_author ); ?></p>
                                        <div class="bd-review-stars" style="color:var(--color-secondary-fixed-dim,#e9c349);display:flex;">
                                            <?php for ($i=1; $i<=5; $i++) {
                                                echo '<span class="material-symbols-outlined text-[16px]" style="font-size:16px;font-variation-settings: \'FILL\' '.($i <= $r_rating ? '1' : '0').';">star</span>';
                                            } ?>
                                        </div>
                                    </div>
                                </div>
                                <p class="bd-review-text" style="margin:0;font-style:italic;color:var(--color-on-surface-variant);font-size:14px;line-height:1.5;">"<?php echo esc_html( $review->comment_content ); ?>"</p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <!-- Sidebar / Sticky Contact Section -->
                <div class="bd-profile-sidebar">
                    <div class="bd-sidebar-wrapper" style="background:var(--color-surface-container-lowest,#ffffff);padding:30px;border-radius:16px;border:1px solid var(--color-outline-variant);box-shadow:var(--bd-shadow);display:flex;flex-direction:column;gap:24px;">
                        <div class="bd-contact-info-list" style="display:flex;flex-direction:column;gap:16px;">
                            <?php if ( ! empty( $phone ) ) : ?>
                            <div class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;">
                                <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
                                    <span class="material-symbols-outlined">call</span>
                                </div>
                                <span style="font-size:14px;font-weight:500;color:var(--color-on-surface);"><?php echo esc_html( $phone ); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $whatsapp ) ) : ?>
                            <a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/', '', $whatsapp) ); ?>" target="_blank" class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
                                <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:#25D366;">
                                    <span class="material-symbols-outlined">chat</span>
                                </div>
                                <span style="font-size:14px;font-weight:700;color:#25D366;">WhatsApp Directo</span>
                            </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $email ) ) : ?>
                            <div class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;">
                                <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
                                    <span class="material-symbols-outlined">mail</span>
                                </div>
                                <span style="font-size:14px;font-weight:500;color:var(--color-on-surface);word-break:break-all;"><?php echo esc_html( $email ); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $website ) ) : ?>
                            <div class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;">
                                <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
                                    <span class="material-symbols-outlined">language</span>
                                </div>
                                <a href="<?php echo esc_url( $website ); ?>" target="_blank" style="font-size:14px;font-weight:500;color:var(--color-primary);text-decoration:none;word-break:break-all;" class="bd-website-link"><?php echo esc_html( str_replace(array('http://','https://'), '', $website) ); ?></a>
                            </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $address ) ) : ?>
                            <div class="bd-contact-info-item" style="display:flex;align-items:flex-start;gap:12px;">
                                <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);margin-top:2px;">
                                    <span class="material-symbols-outlined">pin_drop</span>
                                </div>
                                <span style="font-size:14px;font-weight:500;color:var(--color-on-surface);line-height:1.4;"><?php echo esc_html( $address ); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- OpenStreetMap Leaflet Integration -->
                        <?php 
                        $lat = \get_post_meta( $post_id, '_babel_lat', true );
                        $lng = \get_post_meta( $post_id, '_babel_lng', true );
                        if ( $lat && $lng ) :
                        ?>
                        <div class="bd-sidebar-map-section" style="position:relative;z-index:10;">
                            <div id="babel-osm-map" class="bd-sidebar-map-canvas" style="width:100%;height:150px;border-radius:12px;border:1px solid var(--color-outline-variant);" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>"></div>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                var mapEl = document.getElementById('babel-osm-map');
                                if(mapEl && typeof L !== 'undefined') {
                                    var lat = parseFloat(mapEl.getAttribute('data-lat'));
                                    var lng = parseFloat(mapEl.getAttribute('data-lng'));
                                    if(lat && lng) {
                                        var map = L.map('babel-osm-map', { zoomControl: false, dragging: false, scrollWheelZoom: false }).setView([lat, lng], 15);
                                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                                            attribution: '&copy; OpenStreetMap'
                                        }).addTo(map);
                                        L.marker([lat, lng]).addTo(map);
                                    }
                                }
                            });
                        </script>
                        <?php endif; ?>

                        <!-- Condensed Weekly Hours -->
                        <?php if ( ! empty( $hours ) ) : ?>
                        <div class="bd-sidebar-hours-section" style="padding-top:16px;border-top:1px solid var(--color-outline-variant);">
                            <h3 style="font-size:18px;font-weight:700;color:var(--color-primary);margin:0 0 12px 0;text-align:center;">Horario</h3>
                            <div class="bd-hours-list" style="display:flex;flex-direction:column;gap:8px;">
                                <?php
                                $dias_es = array('monday'=>'Lunes', 'tuesday'=>'Martes', 'wednesday'=>'Miércoles', 'thursday'=>'Jueves', 'friday'=>'Viernes', 'saturday'=>'Sábado', 'sunday'=>'Domingo');
                                foreach ( $dias_es as $key => $label ) {
                                    $day_data = null;
                                    if ( is_array( $hours ) ) {
                                        if ( isset( $hours[$key] ) ) $day_data = $hours[$key];
                                        elseif ( isset( $hours[$label] ) ) $day_data = $hours[$label];
                                    }
                                    if ( ! empty( $day_data ) ) {
                                        if ( is_array( $day_data ) ) $val = ! empty( $day_data['closed'] ) ? 'Cerrado' : esc_html( ( $day_data['open'] ?? '' ) . ' - ' . ( $day_data['close'] ?? '' ) );
                                        else $val = esc_html( $day_data );
                                        
                                        $is_current_day = ($key === strtolower(date('l')));
                                        $highlight_class = $is_current_day ? 'color:var(--color-secondary);font-weight:700;' : 'color:var(--color-on-surface-variant);';
                                        
                                        echo '<div class="bd-hours-row" style="display:flex;justify-content:between;align-items:center;padding:6px 0;border-bottom:1px dashed var(--color-outline-variant);font-size:13px;'.$highlight_class.'">';
                                        echo '<span style="font-weight:600;">' . esc_html( $label ) . '</span>';
                                        echo '<span style="margin-left:auto;">' . $val . '</span>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Social -->
                        <div class="bd-sidebar-social-section" style="padding-top:16px;border-top:1px solid var(--color-outline-variant);">
                            <p style="font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--color-on-surface-variant);text-align:center;margin:0 0 12px 0;">Nuestras Redes</p>
                            <div class="bd-social-row" style="display:flex;justify-content:center;gap:12px;">
                                <?php if ( ! empty( $instagram ) ) : ?>
                                <a href="https://instagram.com/<?php echo esc_attr( $instagram ); ?>" target="_blank" style="width:36px;height:36px;border-radius:50%;border:1px solid var(--color-outline-variant);display:flex;align-items:center;justify-content:center;color:var(--color-primary);text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">photo_camera</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $facebook ) ) : ?>
                                <a href="https://facebook.com/<?php echo esc_attr( $facebook ); ?>" target="_blank" style="width:36px;height:36px;border-radius:50%;border:1px solid var(--color-outline-variant);display:flex;align-items:center;justify-content:center;color:var(--color-primary);text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">social_leaderboard</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $linkedin ) ) : ?>
                                <a href="https://linkedin.com/company/<?php echo esc_attr( $linkedin ); ?>" target="_blank" style="width:36px;height:36px;border-radius:50%;border:1px solid var(--color-outline-variant);display:flex;align-items:center;justify-content:center;color:var(--color-primary);text-decoration:none;"><span class="material-symbols-outlined" style="font-size:18px;">hub</span></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="bd-profile-legal-section" style="margin-top:48px;padding-top:24px;border-top:1px solid var(--color-outline-variant);text-align:center;font-size:11px;letter-spacing:0.5px;color:var(--color-on-surface-variant);opacity:0.6;">
                <?php if ( ! empty( $rut ) && ! empty( $razon_social ) ) : ?>
                <p>RUT <?php echo esc_html( $rut ); ?> | Razón Social <?php echo esc_html( $razon_social ); ?></p>
                <?php endif; ?>
            </section>
        </div>

        <?php
        return \ob_get_clean();
    }

    /**
     * Helper para obtener el ID del negocio objetivo en micro-shortcodes.
     */
    private function get_target_post_id( $atts ) {
        $atts = shortcode_atts( array(
            'id' => '',
        ), $atts );

        // 1. Si se define un ID explícitamente en el bloque/shortcode
        if ( ! empty( $atts['id'] ) ) {
            return intval( $atts['id'] );
        }

        // 2. Si estamos en el loop/página del negocio actual
        $queried_id = \get_queried_object_id();
        if ( $queried_id && 'babel_business' === \get_post_type( $queried_id ) ) {
            return $queried_id;
        }

        global $post;
        if ( isset( $post ) && 'babel_business' === $post->post_type ) {
            return $post->ID;
        }

        $current_id = \get_the_ID();
        if ( $current_id && 'babel_business' === \get_post_type( $current_id ) ) {
            return $current_id;
        }

        // 3. Fallback para el Editor Gutenberg (FSE / ServerSideRender)
        // Si no estamos en un negocio, buscamos el primer negocio publicado para previsualización.
        $args = array(
            'post_type'      => 'babel_business',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        );
        $latest_biz = get_posts( $args );
        if ( ! empty( $latest_biz ) ) {
            return intval( $latest_biz[0] );
        }

        return 0;
    }

    /**
     * Shortcode [bd_business_badges] para insignias de Verificado y Destacado.
     */
    public function render_business_badges( $atts ) {
        $post_id = $this->get_target_post_id( $atts );
        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            return '';
        }
        $verified       = \get_post_meta( $post_id, '_babel_verified', true );
        $featured       = \get_post_meta( $post_id, '_babel_featured', true );
        $is_institution = \get_post_meta( $post_id, '_babel_is_institution', true );

        if ( '1' !== $verified && '1' !== $featured && '1' !== $is_institution ) {
            return '';
        }

        \wp_enqueue_style( 'babel-public-css' );
        \ob_start();
        ?>
        <div class="bd-profile-badges-row">
            <?php if ( '1' === $is_institution ) : ?>
            <span class="bd-badge-pill bd-badge-institution" style="background-color: #dc2626; color: #ffffff;">
                <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings: 'FILL' 1;">gavel</span>
                Servicio Público
            </span>
            <?php endif; ?>
            <?php if ( '1' === $verified ) : ?>
            <span class="bd-badge-pill bd-badge-verified">
                <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings: 'FILL' 1;">verified</span>
                Verificado
            </span>
            <?php endif; ?>
            <?php if ( '1' === $featured ) : ?>
            <span class="bd-badge-pill bd-badge-featured">
                <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings: 'FILL' 1;">stars</span>
                Destacado
            </span>
            <?php endif; ?>
        </div>
        <?php
        return \ob_get_clean();
    }

    /**
     * Shortcode [bd_business_gallery] para la galería multimedia premium.
     */
    public function render_business_gallery( $atts ) {
        $post_id = $this->get_target_post_id( $atts );
        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            return '';
        }
        $gallery_meta = \get_post_meta( $post_id, '_babel_gallery', true );
        if ( empty( $gallery_meta ) ) {
            return '';
        }

        $gallery_ids = is_array( $gallery_meta ) ? $gallery_meta : explode( ',', $gallery_meta );
        if ( count( $gallery_ids ) === 0 ) {
            return '';
        }

        $main_img_url = wp_get_attachment_image_url( $gallery_ids[0], 'large' );
        if ( ! $main_img_url ) {
            return '';
        }

        \wp_enqueue_style( 'babel-public-css' );
        \ob_start();
        ?>
        <div class="bd-business-gallery-wrapper">
            <div class="bd-gallery-main" style="height:380px;border-radius:16px;overflow:hidden;position:relative;margin-bottom:12px;box-shadow:var(--bd-shadow);">
                <img id="main-gallery-img" src="<?php echo esc_url( $main_img_url ); ?>" alt="Galería" style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;" />
            </div>
            <?php if ( count( $gallery_ids ) > 1 ) : ?>
            <div class="bd-photo-grid" style="display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none;-ms-overflow-style:none;">
                <?php foreach ( $gallery_ids as $img_id ) : 
                    $thumb_url = wp_get_attachment_image_url( $img_id, 'medium' );
                    $full_url = wp_get_attachment_image_url( $img_id, 'large' );
                    if ( ! $thumb_url ) continue;
                ?>
                    <img src="<?php echo esc_url( $thumb_url ); ?>" onclick="document.getElementById('main-gallery-img').src='<?php echo esc_url( $full_url ); ?>'" alt="Miniatura" style="width:96px;height:96px;border-radius:12px;object-fit:cover;cursor:pointer;border:2px solid transparent;transition:border-color 0.2s;flex-shrink:0;" class="bd-gallery-thumbnail-item" onmouseover="this.style.borderColor='var(--color-secondary-fixed-dim,#e9c349)'" onmouseout="this.style.borderColor='transparent'" />
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return \ob_get_clean();
    }

    /**
     * Shortcode [bd_business_hours] para mostrar el horario de atención.
     */
    public function render_business_hours( $atts ) {
        $post_id = $this->get_target_post_id( $atts );
        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            return '';
        }
        $hours_meta = \get_post_meta( $post_id, '_babel_hours', true );
        if ( empty( $hours_meta ) ) {
            return '';
        }

        if ( \is_array( $hours_meta ) ) {
            $hours = $hours_meta;
        } else {
            $hours = ! empty( $hours_meta ) ? \json_decode( $hours_meta, true ) : array();
            if ( ! \is_array( $hours ) ) { $hours = array(); }
        }

        if ( empty( $hours ) ) {
            return '';
        }

        \wp_enqueue_style( 'babel-public-css' );
        \ob_start();
        ?>
        <div class="bd-sidebar-hours-section-standalone" style="padding: 20px; background: var(--color-surface-container-lowest,#ffffff); border-radius: 12px; border: 1px solid var(--color-outline-variant);">
            <h3 style="font-size:18px;font-weight:700;color:var(--color-primary);margin:0 0 12px 0;text-align:center;">Horario de Atención</h3>
            <div class="bd-hours-list" style="display:flex;flex-direction:column;gap:8px;">
                <?php
                $dias_es = array('monday'=>'Lunes', 'tuesday'=>'Martes', 'wednesday'=>'Miércoles', 'thursday'=>'Jueves', 'friday'=>'Viernes', 'saturday'=>'Sábado', 'sunday'=>'Domingo');
                $dias_es_lower = array('monday'=>'lunes', 'tuesday'=>'martes', 'wednesday'=>'miercoles', 'thursday'=>'jueves', 'friday'=>'viernes', 'saturday'=>'sabado', 'sunday'=>'domingo');
                foreach ( $dias_es as $key => $label ) {
                    $day_data = null;
                    if ( is_array( $hours ) ) {
                        $lower_key = $dias_es_lower[$key];
                        if ( isset( $hours[$key] ) ) $day_data = $hours[$key];
                        elseif ( isset( $hours[$label] ) ) $day_data = $hours[$label];
                        elseif ( isset( $hours[$lower_key] ) ) $day_data = $hours[$lower_key];
                    }
                    if ( ! empty( $day_data ) ) {
                        if ( is_array( $day_data ) ) $val = ! empty( $day_data['closed'] ) ? 'Cerrado' : esc_html( ( $day_data['open'] ?? '' ) . ' - ' . ( $day_data['close'] ?? '' ) );
                        else $val = esc_html( $day_data );
                        
                        $is_current_day = ($key === strtolower(date('l')));
                        $highlight_class = $is_current_day ? 'color:var(--color-secondary);font-weight:700;' : 'color:var(--color-on-surface-variant);';
                        
                        echo '<div class="bd-hours-row" style="display:flex;justify-content:between;align-items:center;padding:6px 0;border-bottom:1px dashed var(--color-outline-variant);font-size:13px;'.$highlight_class.'">';
                        echo '<span style="font-weight:600;">' . esc_html( $label ) . '</span>';
                        echo '<span style="margin-left:auto;">' . $val . '</span>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
        <?php
        return \ob_get_clean();
    }

    /**
     * Shortcode [bd_business_map] para mostrar el mapa y dirección.
     */
    public function render_business_map( $atts ) {
        $post_id = $this->get_target_post_id( $atts );
        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            return '';
        }
        $lat = \get_post_meta( $post_id, '_babel_lat', true );
        $lng = \get_post_meta( $post_id, '_babel_lng', true );
        $address = \get_post_meta( $post_id, '_babel_address', true );

        if ( ! $lat || ! $lng ) {
            return '';
        }

        \wp_enqueue_style( 'babel-public-css' );
        \wp_enqueue_style( 'leaflet-css' );
        \wp_enqueue_script( 'leaflet-js' );

        \ob_start();
        ?>
        <div class="bd-sidebar-map-section-standalone" style="padding: 20px; background: var(--color-surface-container-lowest,#ffffff); border-radius: 12px; border: 1px solid var(--color-outline-variant);">
            <h3 style="font-size:18px;font-weight:700;color:var(--color-primary);margin:0 0 12px 0;text-align:center;">Ubicación</h3>
            <div id="babel-osm-map-standalone" class="bd-sidebar-map-canvas" style="width:100%;height:200px;border-radius:12px;border:1px solid var(--color-outline-variant);" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>"></div>
            <?php if ( ! empty( $address ) ) : ?>
            <div class="bd-contact-info-item" style="display:flex;align-items:flex-start;gap:12px;margin-top:12px;">
                <div class="bd-icon-wrap" style="width:30px;height:30px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:18px;">pin_drop</span>
                </div>
                <span style="font-size:13px;font-weight:500;color:var(--color-on-surface);line-height:1.4;"><?php echo esc_html( $address ); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var mapEl = document.getElementById('babel-osm-map-standalone');
                if(mapEl && typeof L !== 'undefined') {
                    var lat = parseFloat(mapEl.getAttribute('data-lat'));
                    var lng = parseFloat(mapEl.getAttribute('data-lng'));
                    if(lat && lng) {
                        var map = L.map('babel-osm-map-standalone', { zoomControl: true, dragging: true, scrollWheelZoom: false }).setView([lat, lng], 15);
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(map);
                        L.marker([lat, lng]).addTo(map);
                    }
                }
            });
        </script>
        <?php
        return \ob_get_clean();
    }

    /**
     * Shortcode [bd_business_contact] para los botones de contacto y redes sociales.
     */
    public function render_business_contact( $atts ) {
        $post_id = $this->get_target_post_id( $atts );
        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            return '';
        }

        $phone          = \get_post_meta( $post_id, '_babel_phone', true );
        $whatsapp       = \get_post_meta( $post_id, '_babel_whatsapp', true );
        $email          = \get_post_meta( $post_id, '_babel_email', true );
        $website        = \get_post_meta( $post_id, '_babel_website', true );
        $instagram      = \get_post_meta( $post_id, '_babel_instagram', true );
        $is_institution = \get_post_meta( $post_id, '_babel_is_institution', true );

        if ( ! $phone && ! $whatsapp && ! $email && ! $website && ! $instagram ) {
            return '';
        }

        \wp_enqueue_style( 'babel-public-css' );
        \ob_start();
        ?>
        <div class="bd-sidebar-contact-standalone" style="padding: 20px; background: var(--color-surface-container-lowest,#ffffff); border-radius: 12px; border: 1px solid var(--color-outline-variant);">
            <?php if ( '1' === $is_institution && ! empty( $phone ) ) : ?>
            <a href="tel:<?php echo esc_attr( preg_replace('/[^0-9+]/', '', $phone) ); ?>" class="bd-emergency-btn" style="display:flex;align-items:center;justify-content:center;gap:8px;background:#dc2626;color:#ffffff;text-decoration:none;padding:14px 20px;border-radius:8px;font-weight:700;font-size:16px;margin-bottom:16px;text-align:center;box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); transition: background 0.2s;">
                <span class="material-symbols-outlined">emergency</span>
                Llamar Emergencia
            </a>
            <?php endif; ?>
            <h3 style="font-size:18px;font-weight:700;color:var(--color-primary);margin:0 0 16px 0;text-align:center;">Contacto</h3>
            <div class="bd-contact-info-list" style="display:flex;flex-direction:column;gap:16px;">
                <?php if ( ! empty( $phone ) ) : ?>
                <div class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;">
                    <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
                        <span class="material-symbols-outlined">call</span>
                    </div>
                    <span style="font-size:14px;font-weight:500;color:var(--color-on-surface);"><?php echo esc_html( $phone ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $whatsapp ) ) : ?>
                <a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/', '', $whatsapp) ); ?>" target="_blank" class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
                    <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:#25D366;">
                        <span class="material-symbols-outlined">chat</span>
                    </div>
                    <span style="font-size:14px;font-weight:700;color:#25D366;">WhatsApp Directo</span>
                </a>
                <?php endif; ?>
                <?php if ( ! empty( $email ) ) : ?>
                <div class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;">
                    <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
                        <span class="material-symbols-outlined">mail</span>
                    </div>
                    <span style="font-size:14px;font-weight:500;color:var(--color-on-surface);word-break:break-all;"><?php echo esc_html( $email ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $website ) ) : ?>
                <div class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;">
                    <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
                        <span class="material-symbols-outlined">language</span>
                    </div>
                    <a href="<?php echo esc_url( $website ); ?>" target="_blank" style="font-size:14px;font-weight:500;color:var(--color-primary);text-decoration:none;word-break:break-all;" class="bd-website-link"><?php echo esc_html( str_replace(array('http://','https://'), '', $website) ); ?></a>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $instagram ) ) : ?>
                <div class="bd-contact-info-item" style="display:flex;align-items:center;gap:12px;">
                    <div class="bd-icon-wrap" style="width:36px;height:36px;border-radius:50%;background:var(--color-surface-container);display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
                        <span class="material-symbols-outlined">photo_camera</span>
                    </div>
                    <a href="https://instagram.com/<?php echo esc_attr( $instagram ); ?>" target="_blank" style="font-size:14px;font-weight:500;color:var(--color-primary);text-decoration:none;word-break:break-all;">@<?php echo esc_html( $instagram ); ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return \ob_get_clean();
    }

    /**
     * Renderiza el botón "¿Eres el dueño? Reclama este negocio" para iniciar el flujo de cambio de autor.
     */
    public function render_claim_business() {
        if ( ! is_user_logged_in() ) {
            return '<p class="babel-claim-notice" style="margin-top:15px; font-size:14px; color:#666;">Debes <a href="' . wp_login_url( get_permalink() ) . '" style="color:#000; font-weight:bold; text-decoration:underline;">iniciar sesión</a> para reclamar este negocio.</p>';
        }

        $post_id = get_the_ID();
        if ( ! $post_id || 'babel_business' !== get_post_type( $post_id ) ) {
            return '';
        }

        $current_user_id = get_current_user_id();
        $post = get_post( $post_id );

        // Si el usuario logueado ya es el autor del negocio
        if ( (int) $post->post_author === $current_user_id ) {
            return '<p class="babel-claim-status sdc-text-success" style="margin-top:15px; font-size:14px; color:#2e7d32; font-weight:bold;">✓ Ya eres el dueño de este negocio.</p>';
        }

        // Si ya hay un reclamo pendiente registrado de este usuario
        $pending_user = (int) get_post_meta( $post_id, '_babel_claim_pending_user', true );
        if ( $pending_user === $current_user_id ) {
            return '<p class="babel-claim-status sdc-text-info" style="margin-top:15px; font-size:14px; color:#1565c0;">⏳ Tu solicitud de reclamo está bajo revisión.</p>';
        }

        // Procesar el reclamo cuando se envía el formulario
        if ( isset( $_POST['babel_claim_business_nonce'] ) && wp_verify_nonce( $_POST['babel_claim_business_nonce'], 'babel_claim_business_action' ) ) {
            
            // reCAPTCHA v3 Validation
            $recaptcha_secret = get_option( 'babel_recaptcha_secret_key', '' );
            if ( ! empty( $recaptcha_secret ) ) {
                $token = isset( $_POST['recaptcha_token'] ) ? sanitize_text_field( $_POST['recaptcha_token'] ) : '';
                if ( empty( $token ) ) {
                    return '<p class="babel-claim-status sdc-text-error" style="margin-top:15px; font-size:14px; color:#d32f2f; font-weight:bold;">❌ Falta el token de seguridad reCAPTCHA.</p>';
                }
                $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
                    'body' => array(
                        'secret'   => $recaptcha_secret,
                        'response' => $token,
                    )
                ) );
                if ( is_wp_error( $response ) ) {
                    return '<p class="babel-claim-status sdc-text-error" style="margin-top:15px; font-size:14px; color:#d32f2f; font-weight:bold;">❌ Error al contactar con el servidor de validación.</p>';
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! isset( $body['success'] ) || ! $body['success'] || $body['score'] < 0.5 ) {
                    return '<p class="babel-claim-status sdc-text-error" style="margin-top:15px; font-size:14px; color:#d32f2f; font-weight:bold;">❌ Verificación reCAPTCHA fallida. Eres un bot?</p>';
                }
            }

            update_post_meta( $post_id, '_babel_claim_pending_user', $current_user_id );

            // Notificar al administrador por correo sobre el reclamo pendiente
            $admin_email = get_option( 'admin_email' );
            $subject = '[Babel Directory] Solicitud de Reclamo de Propiedad Pendiente';
            $message = sprintf(
                "El usuario %s ha solicitado la propiedad del negocio '%s'.\n\nNegocio en vivo: %s\nEditar negocio en admin (cambiar autor): %s",
                wp_get_current_user()->display_name,
                $post->post_title,
                get_permalink( $post_id ),
                admin_url( 'post.php?post=' . $post_id . '&action=edit' )
            );
            wp_mail( $admin_email, $subject, $message );

            return '<p class="babel-claim-status sdc-text-info" style="margin-top:15px; font-size:14px; color:#1565c0; font-weight:bold;">⏳ Tu solicitud de reclamo ha sido enviada con éxito y está bajo revisión.</p>';
        }

        ob_start();
        ?>
        <form method="post" action="" class="babel-claim-form" id="babel-claim-form-<?php echo esc_attr($post_id); ?>" style="margin-top: 15px; display:inline-block;">
            <?php wp_nonce_field( 'babel_claim_business_action', 'babel_claim_business_nonce' ); ?>
            <input type="hidden" name="recaptcha_token" class="babel-recaptcha-token" value="">
            <button type="submit" class="babel-btn-claim" style="background:#e5c158; color:#000; padding:10px 20px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; font-size:14px; box-shadow:0 2px 4px rgba(0,0,0,0.1); transition: background 0.2s;">
                ¿Eres el dueño? Reclama este negocio
            </button>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el panel de control del usuario para gestionar sus negocios reclamados/publicados.
     */
    public function render_user_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<p style="padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:8px;">Debes <a href="' . wp_login_url( get_permalink() ) . '" style="color:#000; font-weight:bold; text-decoration:underline;">iniciar sesión</a> para ver tu panel.</p>';
        }

        $current_user_id = get_current_user_id();

        // Procesar la actualización de WhatsApp
        $message = '';
        if ( isset( $_POST['action'] ) && 'bd_update_whatsapp' === $_POST['action'] ) {
            $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
            if ( $post_id && wp_verify_nonce( $_POST['bd_dashboard_nonce'], 'bd_update_whatsapp_' . $post_id ) ) {
                $post = get_post( $post_id );
                if ( $post && (int) $post->post_author === $current_user_id ) {
                    $whatsapp = sanitize_text_field( wp_unslash( $_POST['whatsapp'] ) );
                    update_post_meta( $post_id, '_babel_whatsapp', $whatsapp );
                    update_post_meta( $post_id, '_bd_whatsapp', $whatsapp ); // Fallback
                    $message = '<div class="sdc-alert sdc-alert-success" style="padding:12px; background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:8px; margin-bottom:20px; font-size:14px;">✓ Datos de contacto para "' . esc_html( $post->post_title ) . '" actualizados con éxito.</div>';
                }
            }
        }

        // Consultar negocios asociados al usuario
        $args = array(
            'post_type'      => 'babel_business',
            'post_status'    => array( 'publish', 'pending' ),
            'author'         => $current_user_id,
            'posts_per_page' => -1,
        );
        $query = new \WP_Query( $args );

        ob_start();
        echo $message;
        ?>
        <div class="babel-dashboard-wrapper" style="margin-top:20px; font-family:'Inter', sans-serif;">
            <h2 style="font-size:24px; font-weight:bold; margin-bottom:20px; color:#1a1a1a; border-bottom:1px solid #eee; padding-bottom:10px;">Mis Negocios Reclamados y Publicados</h2>
            <?php if ( $query->have_posts() ) : ?>
                <div style="overflow-x:auto;">
                    <table class="babel-dashboard-table" style="width: 100%; border-collapse: collapse; border: 1px solid #e0e0e0; min-width: 600px;">
                        <thead>
                            <tr style="background: #f5f5f5; border-bottom: 2px solid #ccc; text-align: left;">
                                <th style="padding: 14px 12px; border: 1px solid #e0e0e0; font-weight: 600; color:#333;">Comercio</th>
                                <th style="padding: 14px 12px; border: 1px solid #e0e0e0; font-weight: 600; color:#333;">Estado</th>
                                <th style="padding: 14px 12px; border: 1px solid #e0e0e0; font-weight: 600; color:#333;">Visitas</th>
                                <th style="padding: 14px 12px; border: 1px solid #e0e0e0; font-weight: 600; color:#333;">Número de Contacto (WhatsApp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ( $query->have_posts() ) : $query->the_post(); 
                                $pid = get_the_ID();
                                $views = (int) get_post_meta( $pid, '_babel_views_count', true );
                                $whatsapp = get_post_meta( $pid, '_babel_whatsapp', true );
                                $status = get_post_status( $pid );
                                $status_badge = ( 'publish' === $status ) 
                                    ? '<span style="display:inline-block; padding:4px 8px; background:#d4edda; color:#155724; border-radius:12px; font-size:12px; font-weight:bold;">Activo</span>' 
                                    : '<span style="display:inline-block; padding:4px 8px; background:#fff3cd; color:#856404; border-radius:12px; font-size:12px; font-weight:bold;">Pendiente</span>';
                            ?>
                                <tr style="border-bottom: 1px solid #e0e0e0;">
                                    <td style="padding: 14px 12px; border: 1px solid #e0e0e0; font-weight: 600;">
                                        <a href="<?php the_permalink(); ?>" style="color:#000; text-decoration:none;" target="_blank"><?php the_title(); ?> ↗</a>
                                    </td>
                                    <td style="padding: 14px 12px; border: 1px solid #e0e0e0;"><?php echo $status_badge; ?></td>
                                    <td style="padding: 14px 12px; border: 1px solid #e0e0e0; font-weight: bold;"><?php echo $views; ?></td>
                                    <td style="padding: 14px 12px; border: 1px solid #e0e0e0;">
                                        <form method="post" action="" style="display: flex; gap: 8px; align-items: center; margin: 0;">
                                            <input type="hidden" name="action" value="bd_update_whatsapp">
                                            <input type="hidden" name="post_id" value="<?php echo $pid; ?>">
                                            <?php wp_nonce_field( 'bd_update_whatsapp_' . $pid, 'bd_dashboard_nonce' ); ?>
                                            <input type="text" name="whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="Ej: +56912345678" style="padding: 8px; border: 1px solid #ccc; border-radius: 6px; font-size: 13px; width: 140px; box-sizing: border-box;">
                                            <button type="submit" style="background: #000; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px;">Guardar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p style="color:#666;">Aún no tienes ningún negocio registrado bajo tu propiedad.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Detecta la asignación de un usuario a un negocio reclamado y le notifica por correo.
     */
    public function notify_user_on_claim_approved( $post_id, $post_after, $post_before ) {
        if ( 'babel_business' !== $post_after->post_type ) {
            return;
        }

        // Si el autor del post cambió
        if ( $post_after->post_author != $post_before->post_author ) {
            $pending_claim_user = (int) get_post_meta( $post_id, '_babel_claim_pending_user', true );
            $new_author_id = (int) $post_after->post_author;

            // Si coincide con el usuario reclamante, gatillar aprobación
            if ( $new_author_id === $pending_claim_user ) {
                delete_post_meta( $post_id, '_babel_claim_pending_user' );

                $user = get_userdata( $new_author_id );
                if ( $user ) {
                    $subject = '¡Tu reclamo de negocio ha sido aprobado! - ' . $post_after->post_title;
                    $message = sprintf(
                        "Hola %s,\n\nTu solicitud de reclamo de propiedad para el negocio '%s' ha sido aprobada por nuestro equipo de administración.\n\nYa puedes acceder a tu panel de usuario para actualizar tu número de WhatsApp y hacer seguimiento de las visitas del comercio.\n\nVer tu negocio: %s",
                        $user->display_name,
                        $post_after->post_title,
                        get_permalink( $post_id )
                    );
                    wp_mail( $user->user_email, $subject, $message );
                }
            }
        }
    }

    /**
     * Registra las visitas a las fichas únicas incrementando el meta _babel_views_count.
     */
    public function track_business_view() {
        if ( is_singular( 'babel_business' ) ) {
            $post_id = get_the_ID();
            if ( $post_id ) {
                $views = (int) get_post_meta( $post_id, '_babel_views_count', true );
                update_post_meta( $post_id, '_babel_views_count', $views + 1 );
            }
        }
    }

    /**
     * Shortcode [bd_ad_space] para la inserción dinámica de banners publicitarios.
     *
     * @param array $atts Atributos del shortcode.
     * @return string HTML resultante.
     */
    public function render_ad_space( $atts ) {
        $atts = shortcode_atts( array(
            'position' => 'top_leaderboard', // top_leaderboard, sidebar_ad, in_loop_ad
            'region'   => 'auto',            // auto, slug de region o vacio para global
        ), $atts, 'bd_ad_space' );

        $position = sanitize_text_field( $atts['position'] );
        $region_slug = '';

        // 1. Detección automática de región si está en 'auto'
        if ( 'auto' === $atts['region'] ) {
            if ( \is_tax( 'babel_region' ) ) {
                $term = \get_queried_object();
                if ( $term && ! \is_wp_error( $term ) ) {
                    $region_slug = $term->slug;
                }
            } elseif ( \is_singular( 'babel_business' ) ) {
                $post_id = \get_the_ID();
                $terms = \get_the_terms( $post_id, 'babel_region' );
                if ( $terms && ! \is_wp_error( $terms ) ) {
                    $first_term = reset( $terms );
                    $region_slug = $first_term->slug;
                }
            }
        } elseif ( ! empty( $atts['region'] ) ) {
            $region_slug = sanitize_text_field( $atts['region'] );
        }

        // 2. Consulta de banners activos para la posición y región específicas
        $query_args = array(
            'post_type'      => 'bd_ad_banner',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'rand', // Rotación aleatoria justa
            'meta_query'     => array(
                array(
                    'key'     => '_bd_ad_position',
                    'value'   => $position,
                    'compare' => '=',
                ),
            ),
        );

        if ( ! empty( $region_slug ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'babel_region',
                    'field'    => 'slug',
                    'terms'    => $region_slug,
                ),
            );
        }

        $ad_query = new \WP_Query( $query_args );

        // 3. Fallback: Buscar banners globales si no se encontró con la región
        if ( ! $ad_query->have_posts() && ! empty( $region_slug ) ) {
            unset( $query_args['tax_query'] );
            // Excluir anuncios que estén segmentados a alguna región para priorizar los globales puros
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'babel_region',
                    'operator' => 'NOT EXISTS',
                ),
            );
            $ad_query = new \WP_Query( $query_args );

            // Si tampoco hay globales estrictos, traer cualquier anuncio que coincida con la posición
            if ( ! $ad_query->have_posts() ) {
                unset( $query_args['tax_query'] );
                $ad_query = new \WP_Query( $query_args );
            }
        }

        if ( ! $ad_query->have_posts() ) {
            \wp_reset_postdata();
            return '';
        }

        $ad_post = $ad_query->posts[0];
        $ad_id   = $ad_post->ID;
        \wp_reset_postdata();

        // 4. Incrementar impresiones en el servidor
        $impressions = (int) \get_post_meta( $ad_id, '_bd_ad_impressions', true );
        $impressions++;
        \update_post_meta( $ad_id, '_bd_ad_impressions', $impressions );

        // Obtener datos del anuncio
        $code     = \get_post_meta( $ad_id, '_bd_ad_code', true );
        $image_id = \get_post_meta( $ad_id, '_bd_ad_image_id', true );
        $link     = \get_post_meta( $ad_id, '_bd_ad_link', true );

        // Clases responsivas según posición
        $wrapper_class = 'bd-ad-space bd-ad-space--' . esc_attr( $position );

        \ob_start();
        ?>
        <div class="<?php echo esc_attr( $wrapper_class ); ?>" data-ad-id="<?php echo esc_attr( $ad_id ); ?>" style="margin: 24px auto; text-align: center; max-width: 100%;">
            <span class="bd-ad-label" style="display: block; font-size: 9px; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em; margin-bottom: 6px;"><?php esc_html_e( 'Publicidad', 'babel-directory' ); ?></span>
            <div class="bd-ad-content" style="display: inline-block; max-width: 100%;">
                <?php if ( ! empty( $code ) ) : ?>
                    <!-- Render de script o código alternativo (AdSense, iFrame, etc.) -->
                    <?php echo $code; ?>
                <?php elseif ( $image_id ) : 
                    $image_url = \wp_get_attachment_image_url( $image_id, 'full' );
                    $click_url = \get_rest_url( null, 'babel/v1/ads/click' ) . '?ad_id=' . $ad_id;
                    if ( $image_url ) :
                ?>
                    <!-- Render de banner de imagen nativo con tracker REST -->
                    <a href="<?php echo esc_url( $click_url ); ?>" target="_blank" rel="noopener sponsored" class="bd-ad-link" style="display: block; outline: none; border: none; text-decoration: none;">
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $ad_post->post_title ); ?>" class="bd-ad-image" style="max-width: 100%; height: auto; display: block; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);" loading="lazy" />
                    </a>
                <?php endif; endif; ?>
            </div>
        </div>
        <?php
        return \ob_get_clean();
    }

    /**
     * Shortcode [bd_featured_businesses] para mostrar una grilla de negocios destacados premium.
     *
     * @param array $atts Atributos del shortcode.
     * @return string HTML resultante.
     */
    public function render_featured_businesses( $atts ) {
        wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'limit'  => 4,
            'region' => '', // slug de la region, vacio para global o 'auto'
        ), $atts, 'bd_featured_businesses' );

        $limit = intval( $atts['limit'] );
        $region_slug = '';

        if ( 'auto' === $atts['region'] ) {
            if ( \is_tax( 'babel_region' ) ) {
                $term = \get_queried_object();
                if ( $term && ! \is_wp_error( $term ) ) {
                    $region_slug = $term->slug;
                }
            }
        } elseif ( ! empty( $atts['region'] ) ) {
            $region_slug = sanitize_text_field( $atts['region'] );
        }

        $query_args = array(
            'post_type'      => 'babel_business',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'rand', // Rotación justa
            'meta_query'     => array(
                array(
                    'key'     => '_babel_featured',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );

        if ( ! empty( $region_slug ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'babel_region',
                    'field'    => 'slug',
                    'terms'    => $region_slug,
                ),
            );
        }

        $featured_query = new \WP_Query( $query_args );

        if ( ! $featured_query->have_posts() ) {
            wp_reset_postdata();
            return '';
        }

        ob_start();
        ?>
        <div class="bd-featured-section" style="margin: 32px 0;">
            <div class="sdc-grid-archive">
                <?php while ( $featured_query->have_posts() ) : $featured_query->the_post();
                    $post_id   = get_the_ID();
                    $categorias = get_the_terms( $post_id, 'babel_category' );
                    $regiones   = get_the_terms( $post_id, 'babel_region' );

                    $cat_name   = ( ! empty( $categorias ) && ! \is_wp_error( $categorias ) ) ? esc_html( $categorias[0]->name ) : '';
                    $reg_name   = '';
                    if ( ! empty( $regiones ) && ! \is_wp_error( $regiones ) ) {
                        $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $regiones[0]->name );
                        $reg_name = esc_html( $reg_name );
                    }

                    $price_range = \get_post_meta( $post_id, '_babel_price_range', true );
                    $rating_avg  = (float) \get_post_meta( $post_id, '_babel_rating_avg', true );
                    $rating_count = (int) \get_post_meta( $post_id, '_babel_rating_count', true );
                    $is_verified = \get_post_meta( $post_id, '_babel_verified', true );

                    $thumb_id  = get_post_thumbnail_id( $post_id );
                    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';
                ?>
                    <a href="<?php the_permalink(); ?>" class="babel-biz-card babel-biz-card--featured" aria-label="<?php the_title_attribute(); ?>" style="border: 2px solid var(--color-secondary-fixed-dim,#e9c349); box-shadow: 0 4px 15px rgba(233, 195, 73, 0.15);">
                        
                        <!-- Zona de imagen -->
                        <div class="babel-biz-card__image-wrap">
                            <?php if ( $thumb_url ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>" class="babel-biz-card__image" loading="lazy" />
                            <?php else : ?>
                                <div class="babel-biz-card__placeholder">
                                    <span class="material-symbols-outlined" style="font-size:56px;">store</span>
                                </div>
                            <?php endif; ?>

                            <!-- Badges flotantes -->
                            <div class="babel-biz-card__badges">
                                <span class="babel-biz-card__badge babel-biz-card__badge--featured" style="background: var(--color-secondary-fixed-dim,#e9c349); color: #000000; font-weight: 700;">
                                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">stars</span>
                                    <?php esc_html_e( 'Destacado Premium', 'babel-directory' ); ?>
                                </span>
                                <?php if ( $is_verified ) : ?>
                                    <span class="babel-biz-card__badge babel-biz-card__badge--verified">
                                        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">verified</span>
                                        <?php esc_html_e( 'Verificado', 'babel-directory' ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Cuerpo de la tarjeta -->
                        <div class="babel-biz-card__body">
                            <h3 class="babel-biz-card__title"><?php the_title(); ?></h3>

                            <?php if ( $rating_count > 0 ) : ?>
                                <div class="babel-biz-card__rating">
                                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">star</span>
                                    <span class="babel-biz-card__rating-score"><?php echo esc_html( number_format( $rating_avg, 1 ) ); ?></span>
                                    <span class="babel-biz-card__rating-count">(<?php echo esc_html( $rating_count ); ?>)</span>
                                </div>
                            <?php endif; ?>

                            <?php if ( $cat_name || $reg_name ) : ?>
                                <div class="babel-biz-card__meta">
                                    <?php if ( $cat_name ) : ?>
                                        <span class="babel-biz-card__meta-item">
                                            <span class="material-symbols-outlined">category</span>
                                            <?php echo $cat_name; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ( $cat_name && $reg_name ) : ?>
                                        <span class="babel-biz-card__meta-sep"></span>
                                    <?php endif; ?>
                                    <?php if ( $reg_name ) : ?>
                                        <span class="babel-biz-card__meta-item">
                                            <span class="material-symbols-outlined">location_on</span>
                                            <?php echo $reg_name; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="babel-biz-card__footer">
                                <?php if ( $price_range ) : ?>
                                    <span class="babel-biz-card__price"><?php echo esc_html( $price_range ); ?></span>
                                <?php else : ?>
                                    <span></span>
                                <?php endif; ?>
                                <span class="babel-biz-card__cta" style="color: var(--color-secondary-fixed-dim,#e9c349);">
                                    <?php esc_html_e( 'Ver perfil', 'babel-directory' ); ?>
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_breadcrumbs] para navegación jerárquica
     */
    public function render_breadcrumbs( $atts ) {
        $atts = shortcode_atts( array(
            'separator' => '/',
        ), $atts, 'bd_breadcrumbs' );

        $separator = esc_html( $atts['separator'] );
        $home_url = home_url('/');

        $output = '<nav class="babel-breadcrumbs" aria-label="Breadcrumb">';
        $output .= '<ol>';
        
        // Home
        $output .= '<li><a href="' . esc_url( $home_url ) . '"><span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">home</span> Inicio</a></li>';

        if ( is_tax('babel_region') || is_tax('babel_category') ) {
            $region = get_query_var('babel_region');
            $category = get_query_var('babel_category');

            if ( $region ) {
                $term = get_term_by('slug', $region, 'babel_region');
                if ( $term ) {
                    $output .= '<li><span class="babel-breadcrumbs-separator">' . $separator . '</span></li>';
                    if ( $category ) {
                        $output .= '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a></li>';
                    } else {
                        $output .= '<li aria-current="page">' . esc_html( $term->name ) . '</li>';
                    }
                }
            }

            if ( $category ) {
                $term = get_term_by('slug', $category, 'babel_category');
                if ( $term ) {
                    $output .= '<li><span class="babel-breadcrumbs-separator">' . $separator . '</span></li>';
                    $output .= '<li aria-current="page">' . esc_html( $term->name ) . '</li>';
                }
            }
        } elseif ( is_singular('babel_business') ) {
            $post_id = get_the_ID();
            $regions = wp_get_post_terms( $post_id, 'babel_region' );
            $categories = wp_get_post_terms( $post_id, 'babel_category' );

            if ( ! is_wp_error( $regions ) && ! empty( $regions ) ) {
                $region = $regions[0];
                $output .= '<li><span class="babel-breadcrumbs-separator">' . $separator . '</span></li>';
                $output .= '<li><a href="' . esc_url( get_term_link( $region ) ) . '">' . esc_html( $region->name ) . '</a></li>';
            }

            if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                $category = $categories[0];
                $output .= '<li><span class="babel-breadcrumbs-separator">' . $separator . '</span></li>';
                
                $cat_link = get_term_link( $category );
                if ( ! empty( $regions ) ) {
                    $cat_link = home_url( '/region/' . $regions[0]->slug . '/categoria/' . $category->slug . '/' );
                }
                $output .= '<li><a href="' . esc_url( $cat_link ) . '">' . esc_html( $category->name ) . '</a></li>';
            }

            $output .= '<li><span class="babel-breadcrumbs-separator">' . $separator . '</span></li>';
            $output .= '<li aria-current="page">' . esc_html( get_the_title() ) . '</li>';
        }

        $output .= '</ol>';
        $output .= '</nav>';

        return $output;
    }
}
