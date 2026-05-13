<?php
/**
 * Sistema de Reseñas (BD_Reviews)
 * v3.2 — Social Features & Blindaje PHP 8.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_Reviews {

    public function __construct() {
        // Habilitar comentarios nativos para el CPT
        add_filter( 'comments_open', array( $this, 'open_reviews_for_listings' ), 10, 2 );
        
        // Filtro Anti-Spam: Bloquear caracteres cirílicos (Hito 5)
        add_filter( 'preprocess_comment', array( $this, 'block_cyrillic_spam' ) );

        // Simplificar formulario para invitados (Hito 6)
        add_filter( 'comment_form_default_fields', array( $this, 'simplify_comment_form_fields' ) );
        add_filter( 'comment_form_defaults', array( $this, 'customize_comment_form_labels' ) );

        // Agregar campo de rating al formulario
        add_action( 'comment_form_logged_in_after', array( $this, 'add_rating_field_to_form' ) );
        add_action( 'comment_form_after_fields', array( $this, 'add_rating_field_to_form' ) );

        // Guardar el rating y setear el tipo de comentario
        add_action( 'comment_post', array( $this, 'save_comment_rating' ), 10, 3 );
        
        // Actualizar reputación del post cuando cambia un comentario
        add_action( 'transition_comment_status', array( $this, 'sync_post_rating_on_status_change' ), 10, 3 );
        add_action( 'deleted_comment', array( $this, 'sync_post_rating_on_delete' ) );

        // Acciones AJAX Social (Hito 6)
        add_action( 'wp_ajax_bd_review_action', array( $this, 'handle_review_action_ajax' ) );
        add_action( 'wp_ajax_nopriv_bd_review_action', array( $this, 'handle_review_action_ajax' ) );

        // Filtrar argumentos de wp_list_comments
        add_filter( 'wp_list_comments_args', array( $this, 'customize_comment_list' ) );
    }

    /**
     * Simplifica los campos del formulario (Nombre y Email solamente)
     */
    public function simplify_comment_form_fields( $fields ) {
        if ( get_post_type() !== 'directorio_negocio' ) return $fields;

        // Quitamos URL y el checkbox de cookies
        unset( $fields['url'] );
        unset( $fields['cookies'] );

        return $fields;
    }

    public function customize_comment_form_labels( $defaults ) {
        if ( get_post_type() !== 'directorio_negocio' ) return $defaults;

        $defaults['title_reply'] = 'Deja tu reseña';
        $defaults['label_submit'] = 'Publicar Reseña';
        return $defaults;
    }

    public function block_cyrillic_spam( $commentdata ) {
        if ( preg_match( '/[\x{0400}-\x{04FF}]/u', $commentdata['comment_content'] ) ) {
            wp_die( 'Error: Tu comentario contiene caracteres no permitidos (Filtro Anti-Spam).' );
        }
        return $commentdata;
    }

    public function customize_comment_list( $args ) {
        if ( is_singular( 'directorio_negocio' ) ) {
            $args['callback'] = array( $this, 'render_review_callback' );
            $args['type']     = 'bd_review';
        }
        return $args;
    }

    /**
     * Renderizado Premium de Reseñas con Acciones Sociales
     */
    public function render_review_callback( $comment, $args, $depth ) {
        $rating = get_comment_meta( $comment->comment_ID, '_bd_rating', true );
        $helpful = intval( get_comment_meta( $comment->comment_ID, '_bd_helpful', true ) );
        ?>
        <li <?php comment_class( 'bd-review-item' ); ?> id="comment-<?php comment_ID(); ?>">
            <div class="bd-review-card">
                <div class="bd-review-avatar">
                    <?php echo get_avatar( $comment, 64 ); ?>
                </div>
                <div class="bd-review-body">
                    <div class="bd-review-header">
                        <div class="bd-review-info">
                            <h4 class="bd-review-author"><?php echo get_comment_author(); ?></h4>
                            <span class="bd-review-date"><?php echo get_comment_date(); ?></span>
                        </div>
                        <?php if ( $rating ) : ?>
                            <div class="bd-review-stars">
                                <?php BD_Frontend::render_stars( floatval( $rating ) ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="bd-review-content">
                        <?php comment_text(); ?>
                    </div>
                    
                    <!-- Acciones Sociales (Hito 6) -->
                    <div class="bd-review-actions">
                        <button class="bd-helpful" data-id="<?php comment_ID(); ?>">
                            <i class="far fa-thumbs-up"></i> Es útil (<span class="count"><?php echo $helpful; ?></span>)
                        </button>
                        <button class="bd-report" data-id="<?php comment_ID(); ?>">
                            <i class="far fa-flag"></i> Reportar
                        </button>
                    </div>
                </div>
            </div>
        </li>
        <?php
    }

    /**
     * Manejador AJAX para Helpful y Report
     */
    public function handle_review_action_ajax() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bd_ajax_nonce' ) ) {
            wp_send_json_error( 'Error de seguridad.' );
        }

        $review_id = intval( $_POST['review_id'] );
        $type      = sanitize_text_field( $_POST['action_type'] );

        if ( ! $review_id ) wp_send_json_error( 'ID de reseña inválido.' );

        if ( $type === 'helpful' ) {
            // Evitar votos múltiples vía cookie (básico)
            $cookie_name = 'bd_voted_' . $review_id;
            if ( isset( $_COOKIE[$cookie_name] ) ) {
                wp_send_json_error( 'Ya has votado esta reseña.' );
            }

            $current = intval( get_comment_meta( $review_id, '_bd_helpful', true ) );
            $new     = $current + 1;
            update_comment_meta( $review_id, '_bd_helpful', $new );

            setcookie( $cookie_name, '1', time() + ( DAY_IN_SECONDS * 30 ), '/' );
            wp_send_json_success( array( 'new_count' => $new ) );

        } elseif ( $type === 'report' ) {
            update_comment_meta( $review_id, '_bd_reported', '1' );
            
            // Notificar al admin (opcional pero recomendado)
            $admin_email = get_option( 'admin_email' );
            $review_url  = admin_url( 'comment.php?action=editcomment&c=' . $review_id );
            wp_mail( 
                $admin_email, 
                'Reseña Reportada - Babel Directory', 
                "Una reseña ha sido reportada como inapropiada.\n\nLink para moderar: $review_url" 
            );

            wp_send_json_success( 'Reporte recibido.' );
        }

        wp_send_json_error( 'Acción no reconocida.' );
    }

    public function open_reviews_for_listings( $open, $post_id ) {
        if ( get_post_type( $post_id ) === 'directorio_negocio' ) {
            return true;
        }
        return $open;
    }

    public function add_rating_field_to_form() {
        if ( get_post_type() !== 'directorio_negocio' ) return;

        echo '<div class="bd-rating-selector-wrap">';
        echo '<label class="bd-rating-label">' . esc_html__( '¿Qué te pareció este negocio?', 'babel-directory' ) . '</label>';
        echo '<div class="bd-stars-input">';
        for ( $i = 5; $i >= 1; $i-- ) {
            echo '<input type="radio" id="star' . $i . '" name="bd_rating" value="' . $i . '" required />';
            echo '<label for="star' . $i . '" title="' . $i . ' estrellas"><i class="fas fa-star"></i></label>';
        }
        echo '</div>';
        echo '</div>';
    }

    public function save_comment_rating( $comment_id, $comment_approved, $commentdata ) {
        if ( isset( $_POST['bd_rating'] ) ) {
            $rating = intval( $_POST['bd_rating'] );
            update_comment_meta( $comment_id, '_bd_rating', $rating );
            
            global $wpdb;
            $wpdb->update( 
                $wpdb->comments, 
                array( 'comment_type' => 'bd_review' ), 
                array( 'comment_ID' => $comment_id ) 
            );

            if ( $comment_approved == 1 ) {
                $this->update_post_rating_cache( $commentdata['comment_post_ID'] );
            }
        }
    }

    public function sync_post_rating_on_status_change( $new_status, $old_status, $comment ) {
        if ( $comment->comment_type === 'bd_review' ) {
            $this->update_post_rating_cache( $comment->comment_post_ID );
        }
    }

    public function sync_post_rating_on_delete( $comment_id ) {
        $comment = get_comment( $comment_id );
        if ( $comment && $comment->comment_type === 'bd_review' ) {
            $this->update_post_rating_cache( $comment->comment_post_ID );
        }
    }

    private function update_post_rating_cache( $post_id ) {
        global $wpdb;

        $stats = $wpdb->get_row( $wpdb->prepare( "
            SELECT 
                COUNT(m.meta_value) as count, 
                AVG(m.meta_value) as average 
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->commentmeta} m ON c.comment_ID = m.comment_ID
            WHERE c.comment_post_ID = %d 
            AND c.comment_approved = '1' 
            AND c.comment_type = 'bd_review'
            AND m.meta_key = '_bd_rating'
        ", $post_id ) );

        $count   = intval( $stats->count );
        $average = round( floatval( $stats->average ), 1 );

        update_post_meta( $post_id, '_bd_reputacion', $average );
        update_post_meta( $post_id, '_bd_review_count', $count );
    }
}
