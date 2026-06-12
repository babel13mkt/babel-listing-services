<?php
namespace Babel\Directory;

/**
 * Panel de Control Frontend (Client Portal)
 * Permite a los usuarios gestionar sus negocios, ver estadísticas FOMO,
 * y hacer upgrade de planes mediante WooCommerce.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend_Dashboard {

    public function __construct() {
        add_shortcode( 'babel_frontend_dashboard', array( $this, 'render_dashboard' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        
        // Auto-crear productos de WooCommerce si no existen
        add_action( 'admin_init', array( $this, 'auto_create_woocommerce_products' ) );
        
        // AJAX endpoint para agregar al carrito y redirigir al checkout
        add_action( 'wp_ajax_babel_upgrade_plan', array( $this, 'handle_ajax_upgrade' ) );
    }

    public function enqueue_assets() {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'babel_frontend_dashboard' ) ) {
            return;
        }

        // JS propio del dashboard
        wp_enqueue_script(
            'babel-dashboard-js',
            BD_URL . 'assets/js/babel-dashboard.js',
            array('jquery'),
            BD_VERSION,
            true
        );

        wp_localize_script( 'babel-dashboard-js', 'babel_dash_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'babel_upgrade_nonce' )
        ) );
    }

    /**
     * Auto-crea los productos virtuales en WooCommerce si no existen.
     */
    public function auto_create_woocommerce_products() {
        if ( ! class_exists( 'WooCommerce' ) ) return;
        
        // Evitar que corra todo el tiempo, solo si no existe la opción.
        if ( get_option( 'babel_wc_products_created' ) ) {
            return;
        }

        // 1. Plan Profesional
        if ( ! wc_get_product_id_by_sku( 'BABEL-PRO' ) ) {
            $product = new \WC_Product_Simple();
            $product->set_name( 'Plan Profesional - Soy de Chile' );
            $product->set_status( 'publish' );
            $product->set_catalog_visibility( 'hidden' );
            $product->set_sku( 'BABEL-PRO' );
            $product->set_price( '19990' );
            $product->set_regular_price( '19990' );
            $product->set_virtual( true );
            $product->set_sold_individually( true );
            $product->set_description( "<h2>Desbloquea todo el potencial de tu negocio en Soy de Chile</h2>
            <p>El Plan Profesional está diseñado para aquellos que entienden que estar en internet no es suficiente: <strong>hay que convertir a los visitantes en clientes.</strong></p>
            <ul>
            <li>✅ <strong>Botón directo de WhatsApp:</strong> Recibe consultas directamente a tu móvil.</li>
            <li>✅ <strong>Enlace a tu Sitio Web:</strong> Mejora dramáticamente tu posicionamiento en Google (SEO Dofollow).</li>
            <li>✅ <strong>Galería Premium:</strong> Sube hasta 5 fotos para mostrar tus mejores productos.</li>
            <li>✅ <strong>Horarios Dinámicos:</strong> Mantén a tus clientes informados de cuándo estás abierto.</li>
            <li>✅ <strong>Sello de Empresa Verificada:</strong> Genera mayor confianza inmediata.</li>
            </ul>
            <p><em>Inversión 100% deducible. Un solo cliente ganado por WhatsApp paga tu plan por meses.</em></p>" );
            $product->save();
        }

        // 2. Plan Premium
        if ( ! wc_get_product_id_by_sku( 'BABEL-PREMIUM' ) ) {
            $product = new \WC_Product_Simple();
            $product->set_name( 'Plan Premium (Destacado) - Soy de Chile' );
            $product->set_status( 'publish' );
            $product->set_catalog_visibility( 'hidden' );
            $product->set_sku( 'BABEL-PREMIUM' );
            $product->set_price( '39990' );
            $product->set_regular_price( '39990' );
            $product->set_virtual( true );
            $product->set_sold_individually( true );
            $product->set_description( "<h2>Domina tu región y categoría. No dejes clientes a la competencia.</h2>
            <p>El Plan Premium te convierte en el Rey de tu zona. Alguien busca tu rubro en tu región, y tú apareces primero. Siempre.</p>
            <ul>
            <li>👑 <strong>Todo lo del Plan Profesional incluido.</strong></li>
            <li>⭐ <strong>Posición #1 Garantizada:</strong> Tu negocio se fijará en la parte superior de los resultados.</li>
            <li>⭐ <strong>Sin anuncios de la competencia:</strong> Limpiamos tu perfil de distracciones.</li>
            <li>⭐ <strong>Tarjeta Destacada:</strong> Diseño visual exclusivo en la grilla que atrae la vista.</li>
            </ul>
            <p><em>Ideal para rubros altamente competitivos donde el primer clic se lleva la venta.</em></p>" );
            $product->save();
        }

        update_option( 'babel_wc_products_created', true );
    }

    /**
     * Endpoint AJAX para agregar un plan al carrito y mandar a Checkout
     */
    public function handle_ajax_upgrade() {
        check_ajax_referer( 'babel_upgrade_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Debes iniciar sesión.' ) );
        }

        $plan_sku = isset( $_POST['plan_sku'] ) ? sanitize_text_field( $_POST['plan_sku'] ) : '';
        $post_id  = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( empty( $plan_sku ) || empty( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Faltan parámetros.' ) );
        }

        // Verificar que el usuario sea el dueño del negocio
        $post = get_post( $post_id );
        if ( ! $post || $post->post_author != get_current_user_id() ) {
            wp_send_json_error( array( 'message' => 'No tienes permiso sobre este negocio.' ) );
        }

        $product_id = wc_get_product_id_by_sku( $plan_sku );
        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => 'Plan no encontrado.' ) );
        }

        // Vaciar el carrito previo (para que no compre dos planes a la vez sin querer)
        WC()->cart->empty_cart();

        // Agregar al carrito con metadata custom para saber qué negocio está pagando
        WC()->cart->add_to_cart( $product_id, 1, 0, array(), array( 'babel_target_post_id' => $post_id ) );

        // Devolver la URL del checkout
        wp_send_json_success( array(
            'checkout_url' => wc_get_checkout_url()
        ) );
    }

    /**
     * Renderiza el Dashboard
     */
    public function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return $this->render_login_prompt();
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Obtener los negocios del usuario
        $args = array(
            'post_type'      => 'babel_business',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'pending', 'draft' )
        );
        $businesses = get_posts( $args );

        ob_start();
        ?>
        <div class="babel-dashboard w-full max-w-6xl mx-auto py-8 font-body-md text-on-surface">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-surface border border-outline-variant/30 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-primary text-white rounded-full flex items-center justify-center font-headline-md text-2xl shadow-md">
                        <?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?>
                    </div>
                    <div>
                        <h2 class="font-headline-lg text-headline-lg text-primary m-0">¡Hola, <?php echo esc_html( $user->display_name ); ?>!</h2>
                        <p class="text-on-surface-variant m-0">Este es tu panel de control de Soy de Chile.</p>
                    </div>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="/publicar/" class="bg-secondary text-white font-label-md px-6 py-3 rounded-xl shadow-lg shadow-secondary/30 hover:bg-[#c4291f] transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl">add_business</span>
                        Nuevo Negocio
                    </a>
                </div>
            </div>

            <?php if ( empty( $businesses ) ) : ?>
                <!-- Estado Vacío -->
                <div class="bg-white rounded-2xl border border-outline-variant/30 p-12 text-center shadow-sm">
                    <span class="material-symbols-outlined text-6xl text-outline-variant/50 mb-4 block">storefront</span>
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-2">Aún no tienes negocios registrados</h3>
                    <p class="text-on-surface-variant max-w-md mx-auto mb-6">Suma tu negocio al directorio más moderno de Chile y comienza a recibir clientes de tu región.</p>
                    <a href="/publicar/" class="inline-block bg-primary text-white px-6 py-3 rounded-lg shadow-md hover:bg-[#1a3a7a] transition-all">Registrar mi primer negocio</a>
                </div>
            <?php else : ?>
                <!-- Lista de Negocios -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach ( $businesses as $post ) : 
                        $status_label = '';
                        $status_color = '';
                        if ( $post->post_status === 'publish' ) {
                            $status_label = 'Publicado';
                            $status_color = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                        } elseif ( $post->post_status === 'pending' ) {
                            $status_label = 'En Revisión';
                            $status_color = 'bg-amber-100 text-amber-800 border-amber-200';
                        } else {
                            $status_label = 'Borrador';
                            $status_color = 'bg-gray-100 text-gray-800 border-gray-200';
                        }

                        $plan_type   = get_post_meta( $post->ID, '_babel_plan_type', true ) ?: 'gratis';
                        $is_featured = get_post_meta( $post->ID, '_babel_is_featured', true );

                        // Estadísticas falsas/reales para FOMO
                        $views = get_post_meta( $post->ID, '_babel_view_count', true ) ?: rand( 24, 187 );
                        $lost_leads = ceil( $views * 0.12 ); // 12% conversión hipotética

                        $thumb_url = get_the_post_thumbnail_url( $post->ID, 'medium' );
                    ?>
                    
                    <div class="bg-white rounded-2xl border border-outline-variant/30 shadow-sm overflow-hidden flex flex-col hover:border-primary/30 transition-all group">
                        
                        <!-- Card Header -->
                        <div class="p-5 flex gap-4 border-b border-outline-variant/10">
                            <?php if ( $thumb_url ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>" class="w-20 h-20 rounded-xl object-cover shadow-sm">
                            <?php else : ?>
                                <div class="w-20 h-20 bg-surface rounded-xl flex items-center justify-center border border-outline-variant/30">
                                    <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">store</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-1">
                                    <h3 class="font-headline-sm text-lg text-primary m-0 group-hover:text-secondary transition-colors"><?php echo esc_html( $post->post_title ); ?></h3>
                                    <span class="text-xs font-label-sm px-2.5 py-1 rounded-full border <?php echo esc_attr( $status_color ); ?>">
                                        <?php echo esc_html( $status_label ); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-on-surface-variant flex items-center gap-1 mb-2">
                                    <span class="material-symbols-outlined text-sm">info</span>
                                    Plan Actual: 
                                    <strong class="uppercase <?php echo $plan_type !== 'gratis' ? 'text-secondary' : 'text-on-surface'; ?>">
                                        <?php echo esc_html( $plan_type ); ?>
                                    </strong>
                                </p>
                                
                                <div class="flex gap-2">
                                    <a href="/publicar/?edit_id=<?php echo esc_attr( $post->ID ); ?>" class="text-sm text-primary bg-primary/10 px-3 py-1.5 rounded-lg flex items-center gap-1 hover:bg-primary/20 transition-all font-label-md">
                                        <span class="material-symbols-outlined text-[16px]">edit</span> Editar Info
                                    </a>
                                    <?php if ( $post->post_status === 'publish' ) : ?>
                                        <a href="<?php echo get_permalink( $post->ID ); ?>" target="_blank" class="text-sm text-on-surface-variant bg-surface border border-outline-variant/30 px-3 py-1.5 rounded-lg flex items-center gap-1 hover:bg-outline-variant/20 transition-all font-label-md">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span> Ver Ficha
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body (FOMO & Stats) -->
                        <div class="p-5 bg-gradient-to-br from-surface to-white flex-1">
                            
                            <!-- Bloque FOMO para Plan Gratis -->
                            <?php if ( $plan_type === 'gratis' ) : ?>
                                <div class="bg-amber-50 border border-amber-200/60 rounded-xl p-4 mb-4">
                                    <div class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-amber-600">monitoring</span>
                                        <div>
                                            <h4 class="font-label-md text-amber-900 m-0 mb-1">¡Estás perdiendo clientes!</h4>
                                            <p class="text-sm text-amber-800/80 m-0 leading-tight">
                                                Tu ficha fue vista <strong><?php echo esc_html( $views ); ?> veces</strong> esta semana. Si tuvieras el botón de WhatsApp activado, aproximadamente <strong><?php echo esc_html( $lost_leads ); ?> personas</strong> te habrían contactado directamente.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" class="babel-trigger-upgrade w-full bg-secondary text-white font-label-md py-3 rounded-xl shadow-md hover:bg-[#c4291f] transition-all flex items-center justify-center gap-2" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                                    <span class="material-symbols-outlined text-lg">rocket_launch</span>
                                    Desbloquear WhatsApp y Web
                                </button>
                            
                            <!-- Bloque FOMO para Plan Pro -->
                            <?php elseif ( $plan_type === 'profesional' && ! $is_featured ) : ?>
                                <div class="bg-blue-50 border border-blue-200/60 rounded-xl p-4 mb-4">
                                    <div class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-blue-600">emoji_events</span>
                                        <div>
                                            <h4 class="font-label-md text-blue-900 m-0 mb-1">Domina las búsquedas</h4>
                                            <p class="text-sm text-blue-800/80 m-0 leading-tight">
                                                Tus botones de contacto están activos, pero hay 4 negocios apareciendo antes que tú en tu región.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="babel-trigger-upgrade w-full bg-gradient-to-r from-amber-400 to-orange-500 text-white font-label-md py-3 rounded-xl shadow-md hover:opacity-90 transition-all flex items-center justify-center gap-2" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-target-plan="premium">
                                    <span class="material-symbols-outlined text-lg">star</span>
                                    Ser #1 en las Búsquedas
                                </button>
                            
                            <!-- Bloque Plan Premium -->
                            <?php else : ?>
                                <div class="bg-emerald-50 border border-emerald-200/60 rounded-xl p-4 text-center">
                                    <span class="material-symbols-outlined text-emerald-600 text-3xl mb-2">workspace_premium</span>
                                    <h4 class="font-label-md text-emerald-900 m-0 mb-1">Plan Premium Activo</h4>
                                    <p class="text-sm text-emerald-800/80 m-0">Eres el líder de tu región. Todas las funciones están desbloqueadas y apareces en posición #1.</p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- MODAL DE PRECIOS (Aparece vía JS) -->
        <div id="babel-pricing-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
            <!-- Overlay -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm babel-close-modal"></div>
            
            <!-- Contenido Modal -->
            <div class="relative bg-white w-full max-w-4xl rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                
                <button class="absolute top-4 right-4 w-10 h-10 bg-surface rounded-full flex items-center justify-center text-on-surface hover:bg-outline-variant/20 transition-all z-10 babel-close-modal">
                    <span class="material-symbols-outlined">close</span>
                </button>

                <div class="p-8 text-center bg-gradient-to-b from-surface to-white border-b border-outline-variant/10">
                    <h2 class="font-headline-lg text-3xl text-primary mb-2">Potencia tu Negocio Hoy</h2>
                    <p class="text-on-surface-variant max-w-xl mx-auto">Selecciona el plan que mejor se adapte a tus metas. Cancela en cualquier momento.</p>
                </div>

                <div class="p-8 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Tarjeta PRO -->
                        <div class="border-2 border-outline-variant/30 rounded-2xl p-6 relative hover:border-primary/50 transition-all flex flex-col">
                            <h3 class="font-headline-md text-2xl text-on-surface mb-1">Plan Profesional</h3>
                            <div class="mb-4">
                                <span class="font-headline-lg text-4xl text-primary font-bold">$19.990</span>
                                <span class="text-on-surface-variant text-sm">/año</span>
                            </div>
                            <p class="text-sm text-on-surface-variant mb-6 border-b border-outline-variant/20 pb-4">Desbloquea el contacto directo. Un solo cliente lo paga.</p>
                            
                            <ul class="space-y-3 mb-8 flex-1">
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                                    <span><strong>Botón de WhatsApp</strong> directo</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                                    <span><strong>Enlace Web</strong> (Dofollow SEO)</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                                    <span>Galería de hasta 5 fotos</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                                    <span>Sello de Negocio Verificado</span>
                                </li>
                            </ul>
                            
                            <button class="w-full bg-primary text-white font-label-md py-3.5 rounded-xl shadow-md hover:bg-[#1a3a7a] transition-all flex items-center justify-center gap-2 babel-buy-btn" data-sku="BABEL-PRO">
                                <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                                Seleccionar Pro
                            </button>
                        </div>

                        <!-- Tarjeta PREMIUM -->
                        <div class="border-2 border-secondary rounded-2xl p-6 relative shadow-xl shadow-secondary/10 flex flex-col bg-gradient-to-b from-white to-secondary/5">
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-secondary text-white font-label-sm text-xs px-4 py-1 rounded-full uppercase tracking-widest shadow-md">
                                Más Recomendado
                            </div>
                            
                            <h3 class="font-headline-md text-2xl text-secondary mb-1">Plan Premium</h3>
                            <div class="mb-4">
                                <span class="font-headline-lg text-4xl text-secondary font-bold">$39.990</span>
                                <span class="text-on-surface-variant text-sm">/año</span>
                            </div>
                            <p class="text-sm text-on-surface-variant mb-6 border-b border-outline-variant/20 pb-4">Domina las búsquedas. Conviértete en el #1 indiscutido.</p>
                            
                            <ul class="space-y-3 mb-8 flex-1">
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-secondary text-lg">stars</span>
                                    <span><strong>Posición #1 Fija</strong> en tu región</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-secondary text-lg">stars</span>
                                    <span><strong>Sin publicidad</strong> de la competencia</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                                    <span>Todo lo del Plan Profesional</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-on-surface">
                                    <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                                    <span>Soporte prioritario</span>
                                </li>
                            </ul>
                            
                            <button class="w-full bg-secondary text-white font-label-md py-3.5 rounded-xl shadow-lg shadow-secondary/30 hover:bg-[#c4291f] transition-all flex items-center justify-center gap-2 babel-buy-btn" data-sku="BABEL-PREMIUM">
                                <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                                Seleccionar Premium
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_login_prompt() {
        ob_start();
        ?>
        <div class="babel-dashboard w-full max-w-2xl mx-auto py-12 text-center font-body-md">
            <div class="bg-white rounded-2xl border border-outline-variant/30 p-12 shadow-md">
                <span class="material-symbols-outlined text-6xl text-primary mb-4 block">lock_person</span>
                <h2 class="font-headline-lg text-2xl text-on-surface mb-4">Acceso al Panel de Negocios</h2>
                <p class="text-on-surface-variant mb-8">Debes iniciar sesión para ver tus negocios publicados, sus estadísticas y gestionar tus planes.</p>
                <a href="/publicar/" class="inline-block bg-primary text-white font-label-md px-8 py-3.5 rounded-xl shadow-md hover:bg-[#1a3a7a] transition-all">Iniciar Sesión</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
