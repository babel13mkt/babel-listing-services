<?php
namespace Babel\Directory;

/**
 * Sistema de Publicación de Negocios desde el Frontend (Babel_Directory_Submission)
 * v7.3.0 — Hito 21: Formulario Completo — Atributos, Horarios, Galería, Meta Keys unificados.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Submission {

    public function __construct() {
        add_shortcode( 'babel_submission_form', array( $this, 'render_submission_form' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_submission_assets' ) );

        add_action( 'wp_ajax_babel_frontend_submission', array( $this, 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_nopriv_babel_frontend_submission', array( $this, 'handle_ajax_not_logged_in' ) );
    }

    public function enqueue_submission_assets() {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'babel_submission_form' ) ) {
            return;
        }

        $client_id = get_option( 'babel_google_client_id', '' );
        if ( ! empty( $client_id ) ) {
            wp_enqueue_script( 'google-gsi-client', 'https://accounts.google.com/gsi/client', array(), null, false );
        }

        wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

        wp_enqueue_script(
            'babel-submission-js',
            BD_URL . 'assets/js/babel-submission.js',
            array( 'leaflet-js' ),
            BD_VERSION,
            true
        );

        wp_localize_script( 'babel-submission-js', 'babel_vars', array(
            'ajax_url'           => admin_url( 'admin-ajax.php' ),
            'submission_nonce'   => wp_create_nonce( 'babel_submission_nonce' ),
            'google_login_nonce' => wp_create_nonce( 'babel_google_login_nonce' ),
            'google_client_id'   => esc_js( $client_id ),
            'is_logged_in'       => is_user_logged_in() ? '1' : '0',
            'current_user'       => $this->get_current_user_data(),
        ) );
    }

    private function get_current_user_data() {
        if ( ! is_user_logged_in() ) {
            return array();
        }
        $user = wp_get_current_user();
        return array(
            'name'   => $user->display_name,
            'email'  => $user->user_email,
            'avatar' => get_user_meta( $user->ID, '_babel_google_avatar', true ) ?: get_avatar_url( $user->ID, array( 'size' => 64 ) ),
        );
    }

    public function render_submission_form() {
        $client_id    = get_option( 'babel_google_client_id', '' );
        $is_logged_in = is_user_logged_in();
        $user_data    = $this->get_current_user_data();

        ob_start();
        ?>
        <div id="babel-submission-wrapper" class="w-full max-w-2xl mx-auto">

            <?php if ( ! $is_logged_in ) : ?>
            <!-- ═══════════════════════════════════════════════
                 ESTADO 1: Login con Google
            ═══════════════════════════════════════════════ -->
            <div id="babel-login-screen" class="bg-white rounded-xl border border-outline-variant/30 shadow-lg overflow-hidden">
                <div class="bg-gradient-to-br from-primary to-[#1a3a7a] px-8 py-12 text-center">
                    <span class="material-symbols-outlined text-white/80 text-6xl mb-4 block">store</span>
                    <h2 class="font-headline-lg text-headline-lg text-white mb-3">Publica Tu Negocio</h2>
                    <p class="font-body-md text-body-md text-white/70 max-w-md mx-auto">Llega a miles de clientes en Chile. Completa tu perfil con toda la información.</p>
                </div>
                <div class="px-8 py-10">
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="material-symbols-outlined text-primary text-xl">visibility</span>
                            </div>
                            <p class="font-label-md text-label-md text-on-surface-variant">Mayor visibilidad</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="material-symbols-outlined text-secondary text-xl">location_on</span>
                            </div>
                            <p class="font-label-md text-label-md text-on-surface-variant">Búsqueda por GPS</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="material-symbols-outlined text-primary text-xl">star</span>
                            </div>
                            <p class="font-label-md text-label-md text-on-surface-variant">Reseñas reales</p>
                        </div>
                    </div>
                    <div class="border-t border-outline-variant/30 pt-8">
                        <p class="text-center font-body-md text-body-md text-on-surface-variant mb-6">Inicia sesión con Google para comenzar.<br><span class="font-label-md text-label-md text-on-surface/60">Es gratis y tarda menos de 30 segundos.</span></p>
                        <?php if ( empty( $client_id ) ) : ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
                            <span class="material-symbols-outlined text-amber-600 text-2xl mb-2 block">settings</span>
                            <p class="font-body-md text-body-md text-amber-800">El sistema de login está en configuración. <strong>Vuelve pronto.</strong></p>
                        </div>
                        <?php else : ?>
                        <div class="flex flex-col items-center gap-4">
                            <div id="babel-google-btn-container">
                                <div id="g_id_onload" data-client_id="<?php echo esc_attr( $client_id ); ?>" data-callback="handleBabelGoogleLogin" data-auto_prompt="false"></div>
                                <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline" data-text="continue_with" data-size="large" data-locale="es" data-width="320"></div>
                            </div>
                            <p class="font-label-md text-label-md text-on-surface/50 text-center text-xs mt-2">Al continuar, aceptas nuestros <a href="/terminos/" class="text-primary hover:underline">Términos de Uso</a></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="babel-login-loading" class="hidden px-8 py-10 text-center">
                    <div class="inline-block w-12 h-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin mb-4"></div>
                    <p class="font-body-md text-body-md text-on-surface-variant">Verificando tu cuenta...</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ═══════════════════════════════════════════════
                 ESTADO 2: Formulario Completo
            ═══════════════════════════════════════════════ -->
            <div id="babel-form-screen" class="<?php echo $is_logged_in ? '' : 'hidden'; ?>">

                <!-- Header usuario -->
                <div id="babel-user-header" class="bg-gradient-to-r from-primary to-[#1a3a7a] rounded-xl px-6 py-5 mb-6 flex items-center gap-4">
                    <img id="babel-user-avatar" src="<?php echo esc_url( $user_data['avatar'] ?? '' ); ?>" alt="Avatar"
                         class="w-12 h-12 rounded-full border-2 border-white/50 object-cover"
                         onerror="this.src='https://ui-avatars.com/api/?name=U&background=00205b&color=fff'">
                    <div class="flex-1">
                        <p class="font-label-md text-label-md text-white/70 uppercase tracking-wider">Publicando como</p>
                        <p id="babel-user-name" class="font-headline-md text-headline-md text-white"><?php echo esc_html( $user_data['name'] ?? '' ); ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-400 text-sm">verified</span>
                        <span class="font-label-md text-label-md text-emerald-400">Verificado</span>
                    </div>
                </div>

                <!-- Formulario -->
                <form id="babel-submission-form" enctype="multipart/form-data" class="space-y-6">

                    <!-- Honeypot -->
                    <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
                        <input type="text" name="babel_website_url" tabindex="-1" autocomplete="off" value="">
                    </div>

                    <!-- ── SECCIÓN 1: Información Básica ── -->
                    <div class="bg-white rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">info</span>
                            </div>
                            <h3 class="font-headline-md text-headline-md text-primary">Información Básica</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">

                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    <span class="material-symbols-outlined text-sm align-middle mr-1">store</span>
                                    Nombre del Comercio <span class="text-secondary">*</span>
                                </label>
                                <input type="text" name="business_name" required placeholder="Ej: Pizzería Don Carlos"
                                       class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                        <span class="material-symbols-outlined text-sm align-middle mr-1">category</span>
                                        Categoría <span class="text-secondary">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="business_category" required
                                                class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200 appearance-none pr-10">
                                            <option value="">Selecciona categoría...</option>
                                            <?php $this->render_taxonomy_options( 'babel_category' ); ?>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-on-surface-variant pointer-events-none text-xl">expand_more</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                        <span class="material-symbols-outlined text-sm align-middle mr-1">map</span>
                                        Región <span class="text-secondary">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="business_region" required
                                                class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200 appearance-none pr-10">
                                            <option value="">Selecciona región...</option>
                                            <?php $this->render_taxonomy_options( 'babel_region' ); ?>
                                        </select>
                                        <span class="material-symbols-outlined absolute right-3 top-3.5 text-on-surface-variant pointer-events-none text-xl">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    <span class="material-symbols-outlined text-sm align-middle mr-1">description</span>
                                    Descripción del Negocio
                                </label>
                                <textarea name="description" rows="4"
                                          placeholder="Describe tu negocio, servicios principales, especialidades, historia..."
                                          class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200 resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ── SECCIÓN 2: Ubicación ── -->
                    <div class="bg-white rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface flex items-center gap-3">
                            <div class="w-8 h-8 bg-secondary rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">location_on</span>
                            </div>
                            <h3 class="font-headline-md text-headline-md text-primary">Ubicación</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">Dirección Física</label>
                                <div class="flex gap-3">
                                    <input type="text" id="babel_address" name="address"
                                           placeholder="Ej: Av. Providencia 1234, Santiago"
                                           class="flex-1 px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200">
                                    <button id="babel-radar-btn" type="button"
                                            class="flex items-center gap-2 bg-primary text-white font-label-md text-label-md px-5 py-3 rounded-lg hover:bg-[#1a3a7a] active:scale-95 transition-all duration-200 whitespace-nowrap shadow-md shadow-primary/20">
                                        <span class="material-symbols-outlined text-lg">my_location</span>
                                        <span id="radar-btn-text">GPS</span>
                                    </button>
                                </div>
                            </div>
                            <div id="babel-map-container" style="height:0; transition: height 0.4s ease; border-radius:8px; overflow:hidden; z-index:1;"></div>
                            <input type="hidden" id="babel_lat" name="babel_lat">
                            <input type="hidden" id="babel_lng" name="babel_lng">
                        </div>
                    </div>

                    <!-- ── SECCIÓN 3: Contacto ── -->
                    <div class="bg-white rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">contact_phone</span>
                            </div>
                            <h3 class="font-headline-md text-headline-md text-primary">Contacto</h3>
                        </div>
                        <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    <span class="material-symbols-outlined text-sm align-middle mr-1">phone</span>
                                    Teléfono
                                </label>
                                <input type="tel" name="phone" placeholder="+56 9 1234 5678"
                                       class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200">
                            </div>
                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    <svg class="inline w-4 h-4 align-middle mr-1 mb-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.528 5.855L0 24l6.335-1.652A11.954 11.954 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.891 0-3.658-.523-5.168-1.428l-.371-.22-3.762.981.999-3.661-.243-.377A9.939 9.939 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                                    WhatsApp
                                </label>
                                <input type="tel" name="whatsapp" placeholder="+56 9 1234 5678"
                                       class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200">
                            </div>
                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    <span class="material-symbols-outlined text-sm align-middle mr-1">alternate_email</span>
                                    Email de Contacto
                                </label>
                                <input type="email" name="email" placeholder="contacto@minegocio.cl"
                                       class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200">
                            </div>
                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    <span class="material-symbols-outlined text-sm align-middle mr-1">language</span>
                                    Sitio Web
                                </label>
                                <input type="url" name="website" placeholder="https://minegocio.cl"
                                       class="w-full px-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    <span class="material-symbols-outlined text-sm align-middle mr-1">photo_camera</span>
                                    Instagram
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 font-body-md text-body-md text-on-surface-variant">@</span>
                                    <input type="text" name="instagram" placeholder="minegocio"
                                           class="w-full pl-8 pr-4 py-3 border border-outline-variant/30 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 focus:border-secondary font-body-md text-body-md text-on-surface transition-all duration-200">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── SECCIÓN 4: Atributos del Negocio ── -->
                    <div class="bg-white rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">checklist</span>
                            </div>
                            <div>
                                <h3 class="font-headline-md text-headline-md text-primary">Servicios y Comodidades</h3>
                                <p class="font-label-md text-label-md text-on-surface-variant">Selecciona los que ofrece tu negocio</p>
                            </div>
                        </div>
                        <div class="px-6 py-5">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <?php
                                $attributes = array(
                                    'wifi'           => array( 'icon' => 'wifi',              'label' => 'WiFi Gratis' ),
                                    'parking'        => array( 'icon' => 'local_parking',      'label' => 'Estacionamiento' ),
                                    'delivery'       => array( 'icon' => 'delivery_dining',    'label' => 'Delivery' ),
                                    'accesibilidad'  => array( 'icon' => 'accessible',         'label' => 'Accesibilidad' ),
                                    'tarjetas'       => array( 'icon' => 'credit_card',        'label' => 'Acepta Tarjetas' ),
                                    'reservas'       => array( 'icon' => 'event_available',    'label' => 'Reservas' ),
                                );
                                foreach ( $attributes as $key => $data ) : ?>
                                <label class="babel-attr-label flex items-center gap-3 p-3 border border-outline-variant/30 rounded-lg cursor-pointer hover:border-primary/40 hover:bg-primary/5 transition-all duration-200 has-[:checked]:border-primary has-[:checked]:bg-primary/10">
                                    <input type="checkbox" name="attr_<?php echo esc_attr( $key ); ?>" value="1"
                                           class="w-4 h-4 accent-primary rounded">
                                    <span class="material-symbols-outlined text-on-surface-variant text-xl has-checked:text-primary"><?php echo esc_html( $data['icon'] ); ?></span>
                                    <span class="font-label-md text-label-md text-on-surface"><?php echo esc_html( $data['label'] ); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── SECCIÓN 5: Horarios ── -->
                    <div class="bg-white rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">schedule</span>
                            </div>
                            <div>
                                <h3 class="font-headline-md text-headline-md text-primary">Horarios de Atención</h3>
                                <p class="font-label-md text-label-md text-on-surface-variant">Opcional — puedes actualizarlos después</p>
                            </div>
                        </div>
                        <div class="px-6 py-5">
                            <div class="space-y-2" id="babel-schedule-grid">
                                <?php
                                $days = array(
                                    'lun' => 'Lunes',
                                    'mar' => 'Martes',
                                    'mie' => 'Miércoles',
                                    'jue' => 'Jueves',
                                    'vie' => 'Viernes',
                                    'sab' => 'Sábado',
                                    'dom' => 'Domingo',
                                );
                                foreach ( $days as $key => $label ) : ?>
                                <div class="flex items-center gap-3 py-2 border-b border-outline-variant/10 last:border-0">
                                    <div class="w-20 flex-shrink-0">
                                        <span class="font-label-md text-label-md text-on-surface"><?php echo esc_html( $label ); ?></span>
                                    </div>
                                    <label class="flex items-center gap-1.5 cursor-pointer flex-shrink-0">
                                        <input type="checkbox" name="horario_cerrado_<?php echo esc_attr( $key ); ?>" value="1"
                                               class="babel-closed-toggle w-3.5 h-3.5 accent-secondary rounded" data-day="<?php echo esc_attr( $key ); ?>">
                                        <span class="font-label-md text-label-md text-on-surface-variant text-xs">Cerrado</span>
                                    </label>
                                    <div class="flex items-center gap-2 flex-1 babel-hours-row" data-day="<?php echo esc_attr( $key ); ?>">
                                        <input type="time" name="horario_abre_<?php echo esc_attr( $key ); ?>" value="09:00"
                                               class="flex-1 px-2 py-1.5 border border-outline-variant/30 rounded-lg text-sm font-body-md text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary/30 focus:border-secondary transition-all">
                                        <span class="font-label-md text-label-md text-on-surface-variant text-xs">a</span>
                                        <input type="time" name="horario_cierra_<?php echo esc_attr( $key ); ?>" value="19:00"
                                               class="flex-1 px-2 py-1.5 border border-outline-variant/30 rounded-lg text-sm font-body-md text-on-surface focus:outline-none focus:ring-1 focus:ring-secondary/30 focus:border-secondary transition-all">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── SECCIÓN 6: Fotos ── -->
                    <div class="bg-white rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface flex items-center gap-3">
                            <div class="w-8 h-8 bg-secondary rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-white text-sm">photo_library</span>
                            </div>
                            <div>
                                <h3 class="font-headline-md text-headline-md text-primary">Fotos del Negocio</h3>
                                <p class="font-label-md text-label-md text-on-surface-variant">1 foto principal obligatoria + hasta 5 adicionales</p>
                            </div>
                        </div>
                        <div class="px-6 py-5 space-y-5">

                            <!-- Foto principal -->
                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    Foto Principal <span class="text-secondary">*</span>
                                </label>
                                <div id="babel-main-photo-drop"
                                     class="border-2 border-dashed border-outline-variant/40 rounded-xl p-6 text-center hover:border-secondary/50 transition-all duration-200 cursor-pointer bg-surface relative">
                                    <div id="babel-main-photo-placeholder">
                                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-2 block">add_photo_alternate</span>
                                        <p class="font-body-md text-body-md text-on-surface-variant">Haz clic o arrastra una imagen aquí</p>
                                        <p class="font-label-md text-label-md text-on-surface/40 mt-1">JPG, PNG o WebP · Máx. 5MB · Recomendado 1200×800px</p>
                                    </div>
                                    <img id="babel-main-photo-preview" src="" alt="Preview" class="hidden w-full max-h-48 object-cover rounded-lg mx-auto">
                                    <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"
                                           id="babel-featured-image" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                </div>
                            </div>

                            <!-- Galería adicional -->
                            <div>
                                <label class="block font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                    Galería Adicional <span class="text-on-surface/40 font-normal normal-case">(hasta 5 fotos)</span>
                                </label>
                                <div id="babel-gallery-previews" class="grid grid-cols-3 md:grid-cols-5 gap-2 mb-3 empty:hidden"></div>
                                <label class="flex items-center justify-center gap-2 border-2 border-dashed border-outline-variant/30 rounded-xl p-4 cursor-pointer hover:border-primary/40 hover:bg-primary/5 transition-all duration-200">
                                    <span class="material-symbols-outlined text-on-surface-variant text-2xl">add_circle</span>
                                    <span class="font-label-md text-label-md text-on-surface-variant">Agregar más fotos</span>
                                    <input type="file" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/webp"
                                           id="babel-gallery-input" class="hidden">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ── BOTÓN DE ENVÍO ── -->
                    <div>
                        <button type="submit" id="babel-submit-btn"
                                class="w-full bg-secondary text-on-secondary font-headline-md text-headline-md py-4 rounded-xl hover:bg-[#c4291f] active:scale-95 transition-all duration-200 shadow-lg shadow-secondary/30 flex items-center justify-center gap-3">
                            <span class="material-symbols-outlined text-xl">send</span>
                            Publicar Mi Negocio
                        </button>
                        <p class="text-center font-label-md text-label-md text-on-surface/50 mt-3">
                            Tu negocio será revisado en 24-48h antes de publicarse
                        </p>
                    </div>

                </form>
            </div>

            <!-- Mensaje de respuesta -->
            <div id="babel-response-message" class="mt-4"></div>

        </div>
        <?php
        return ob_get_clean();
    }

    private function render_taxonomy_options( $taxonomy ) {
        $parent_terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        if ( \is_wp_error( $parent_terms ) || empty( $parent_terms ) ) {
            return;
        }
        foreach ( $parent_terms as $parent ) {
            echo '<option value="' . esc_attr( $parent->term_id ) . '">' . esc_html( $parent->name ) . '</option>';
            $child_terms = get_terms( array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'parent'     => $parent->term_id,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ) );
            if ( ! \is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
                foreach ( $child_terms as $child ) {
                    echo '<option value="' . esc_attr( $child->term_id ) . '">&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . esc_html( $child->name ) . '</option>';
                }
            }
        }
    }

    public function handle_ajax_not_logged_in() {
        wp_send_json_error( array( 'message' => 'Debes iniciar sesión para publicar un negocio.', 'code' => 'not_logged_in' ) );
    }

    public function handle_ajax_submission() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Debes iniciar sesión para publicar.', 'code' => 'not_logged_in' ) );
            return;
        }

        check_ajax_referer( 'babel_submission_nonce', 'security' );

        // CAPA 2: Honeypot
        $honeypot = isset( $_POST['babel_website_url'] ) ? $_POST['babel_website_url'] : '';
        if ( ! empty( $honeypot ) ) {
            wp_send_json_success( array( 'message' => '¡Éxito! Tu comercio ha sido enviado y está en revisión.' ) );
            return;
        }

        $user_id = get_current_user_id();

        // CAPA 3: Rate limiting
        $timestamps      = get_user_meta( $user_id, '_babel_submission_timestamps', true );
        $timestamps      = is_array( $timestamps ) ? $timestamps : array();
        $today_start     = strtotime( 'today midnight' );
        $today_count     = count( array_filter( $timestamps, fn( $ts ) => $ts >= $today_start ) );
        if ( $today_count >= 3 ) {
            wp_send_json_error( array( 'message' => 'Has alcanzado el límite de 3 publicaciones por día.', 'code' => 'rate_limited' ) );
            return;
        }

        // VALIDACIÓN DE ARCHIVOS SUBIDOS (Seguridad y Tamaño)
        $allowed_mimes = array( 'image/jpeg', 'image/png', 'image/webp' );
        $max_size      = 2 * 1024 * 1024; // 2MB

        // 1. Validar Foto Principal
        if ( ! empty( $_FILES['featured_image']['name'] ) ) {
            $file = $_FILES['featured_image'];
            
            if ( $file['error'] !== UPLOAD_ERR_OK ) {
                wp_send_json_error( array( 'message' => 'Error en la subida de la foto principal.' ) );
                return;
            }
            
            // Validar Tamaño
            if ( $file['size'] > $max_size ) {
                wp_send_json_error( array( 'message' => 'La foto principal excede el tamaño máximo permitido de 2MB.' ) );
                return;
            }

            // Validar Tipo de Archivo / MIME Type
            $file_type = wp_check_filetype( $file['name'] );
            if ( ! in_array( $file_type['type'], $allowed_mimes, true ) ) {
                wp_send_json_error( array( 'message' => 'La foto principal debe ser una imagen válida (JPG, PNG o WebP).' ) );
                return;
            }
        }

        // 2. Validar Galería Adicional
        if ( ! empty( $_FILES['gallery_images']['name'][0] ) ) {
            $files      = $_FILES['gallery_images'];
            $file_count = min( count( $files['name'] ), 5 );

            for ( $i = 0; $i < $file_count; $i++ ) {
                if ( empty( $files['name'][$i] ) ) {
                    continue;
                }

                if ( $files['error'][$i] !== UPLOAD_ERR_OK ) {
                    wp_send_json_error( array( 'message' => 'Error en la subida de una imagen de la galería.' ) );
                    return;
                }

                // Validar Tamaño
                if ( $files['size'][$i] > $max_size ) {
                    wp_send_json_error( array( 'message' => 'Una de las imágenes de la galería excede el tamaño máximo permitido de 2MB.' ) );
                    return;
                }

                // Validar Tipo de Archivo / MIME Type
                $file_type = wp_check_filetype( $files['name'][$i] );
                if ( ! in_array( $file_type['type'], $allowed_mimes, true ) ) {
                    wp_send_json_error( array( 'message' => 'Las imágenes de la galería deben ser imágenes válidas (JPG, PNG o WebP).' ) );
                    return;
                }
            }
        }

        $title = isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';
        if ( empty( $title ) ) {
            wp_send_json_error( array( 'message' => 'El nombre del comercio es obligatorio.' ) );
            return;
        }

        // CAPA 4: Moderación
        $post_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
            'post_status'  => 'pending',
            'post_type'    => 'babel_business',
            'post_author'  => $user_id,
        ) );

        if ( \is_wp_error( $post_id ) || 0 === $post_id ) {
            wp_send_json_error( array( 'message' => 'Error al crear el registro.' ) );
            return;
        }

        // Taxonomías
        if ( ! empty( $_POST['business_region'] ) ) {
            wp_set_object_terms( $post_id, intval( $_POST['business_region'] ), 'babel_region' );
        }
        if ( ! empty( $_POST['business_category'] ) ) {
            wp_set_object_terms( $post_id, intval( $_POST['business_category'] ), 'babel_category' );
        }

        // Meta keys de contacto y ubicación
        $metas = array(
            '_babel_phone'     => 'phone',
            '_babel_whatsapp'  => 'whatsapp',
            '_babel_email'     => 'email',
            '_babel_address'   => 'address',
            '_babel_lat'       => 'babel_lat',
            '_babel_lng'       => 'babel_lng',
            '_babel_website'   => 'website',
            '_babel_instagram' => 'instagram',
        );
        foreach ( $metas as $meta_key => $post_key ) {
            if ( ! empty( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
            }
        }

        // Atributos de negocio
        $attribute_keys = array( 'wifi', 'parking', 'delivery', 'accesibilidad', 'tarjetas', 'reservas' );
        foreach ( $attribute_keys as $attr ) {
            $val = ! empty( $_POST[ 'attr_' . $attr ] ) ? '1' : '0';
            update_post_meta( $post_id, '_babel_attr_' . $attr, $val );
        }

        // Horarios — construir array JSON sanitizado
        $days = array( 'lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom' );
        $horarios = array();
        foreach ( $days as $day ) {
            $cerrado = ! empty( $_POST[ 'horario_cerrado_' . $day ] );
            $horarios[ $day ] = array(
                'cerrado' => $cerrado,
                'abre'    => $cerrado ? '' : sanitize_text_field( wp_unslash( $_POST[ 'horario_abre_' . $day ] ?? '09:00' ) ),
                'cierra'  => $cerrado ? '' : sanitize_text_field( wp_unslash( $_POST[ 'horario_cierra_' . $day ] ?? '19:00' ) ),
            );
        }
        update_post_meta( $post_id, '_babel_horarios', wp_json_encode( $horarios ) );

        // Foto principal
        if ( ! empty( $_FILES['featured_image']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            $att_id = media_handle_upload( 'featured_image', $post_id );
            if ( ! \is_wp_error( $att_id ) ) {
                set_post_thumbnail( $post_id, $att_id );
            }
        }

        // Galería múltiple (hasta 5 imágenes)
        if ( ! empty( $_FILES['gallery_images']['name'][0] ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $gallery_ids = array();
            $file_count  = min( count( $_FILES['gallery_images']['name'] ), 5 );

            for ( $i = 0; $i < $file_count; $i++ ) {
                if ( empty( $_FILES['gallery_images']['name'][ $i ] ) ) {
                    continue;
                }
                // Reindexar $_FILES para media_handle_upload
                $_FILES['gallery_single'] = array(
                    'name'     => $_FILES['gallery_images']['name'][ $i ],
                    'type'     => $_FILES['gallery_images']['type'][ $i ],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][ $i ],
                    'error'    => $_FILES['gallery_images']['error'][ $i ],
                    'size'     => $_FILES['gallery_images']['size'][ $i ],
                );
                $gal_id = media_handle_upload( 'gallery_single', $post_id );
                if ( ! \is_wp_error( $gal_id ) ) {
                    $gallery_ids[] = $gal_id;
                }
            }

            if ( ! empty( $gallery_ids ) ) {
                update_post_meta( $post_id, '_babel_gallery', implode( ',', $gallery_ids ) );
            }
        }

        // Datos de autoría
        update_post_meta( $post_id, '_babel_submitted_by', $user_id );
        update_post_meta( $post_id, '_babel_submitted_at', current_time( 'mysql' ) );

        // Actualizar rate limiting
        $timestamps[] = time();
        $cutoff       = time() - ( 30 * DAY_IN_SECONDS );
        $timestamps   = array_values( array_filter( $timestamps, fn( $ts ) => $ts >= $cutoff ) );
        update_user_meta( $user_id, '_babel_submission_timestamps', $timestamps );

        wp_send_json_success( array(
            'message' => '¡Tu negocio fue enviado con éxito! Nuestro equipo lo revisará en las próximas 24-48 horas.',
        ) );
    }
}
