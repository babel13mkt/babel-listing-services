<?php
/**
 * Sistema de Gestión de Reseñas y Calificaciones (Babel_Directory_Reviews)
 * v7.0.0 — Hito 9: Recálculo de Promedios e Indexación Síncrona.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

namespace Babel\Directory;


class Reviews {

    /**
     * Constructor de la clase. Registra los hooks y filtros necesarios.
     */
    public function __construct() {
        // Habilitar comentarios nativos para el CPT de negocios
        add_filter( 'comments_open', array( $this, 'open_reviews_for_listings' ), 10, 2 );

        // Simplificar y adaptar los campos del formulario de comentarios
        add_filter( 'comment_form_default_fields', array( $this, 'simplify_comment_form_fields' ) );
        add_filter( 'comment_form_defaults', array( $this, 'customize_comment_form_labels' ) );

        // Agregar campo de selección de estrellas al formulario
        add_action( 'comment_form_logged_in_after', array( $this, 'add_rating_field_to_form' ) );
        add_action( 'comment_form_after_fields', array( $this, 'add_rating_field_to_form' ) );

        // Guardar la calificación (rating) al publicar y setear tipo de comentario
        add_action( 'comment_post', array( $this, 'save_comment_rating' ), 10, 3 );

        // Recalcular y sincronizar al cambiar estado de moderación (aprobación)
        add_action( 'transition_comment_status', array( $this, 'sync_post_rating_on_status_change' ), 10, 3 );

        // Recalcular y sincronizar al eliminar una reseña
        add_action( 'deleted_comment', array( $this, 'sync_post_rating_on_delete' ) );
    }

    /**
     * Asegura que los comentarios (reseñas) estén abiertos para el CPT babel_business.
     */
    public function open_reviews_for_listings( $open, $post_id ) {
        if ( get_post_type( $post_id ) === 'babel_business' ) {
            return true;
        }
        return $open;
    }

    /**
     * Simplifica el formulario nativo removiendo el campo de URL/Sitio Web.
     */
    public function simplify_comment_form_fields( $fields ) {
        if ( get_post_type() !== 'babel_business' ) {
            return $fields;
        }
        unset( $fields['url'] );
        unset( $fields['cookies'] );
        return $fields;
    }

    /**
     * Personaliza las etiquetas del formulario para orientarlo a Reseñas.
     */
    public function customize_comment_form_labels( $defaults ) {
        if ( get_post_type() !== 'babel_business' ) {
            return $defaults;
        }
        $defaults['title_reply']  = esc_html__( 'Deja tu reseña', 'babel-directory' );
        $defaults['label_submit'] = esc_html__( 'Publicar Reseña', 'babel-directory' );
        return $defaults;
    }

    /**
     * Agrega la estructura HTML para el selector de calificación por estrellas.
     */
    public function add_rating_field_to_form() {
        if ( get_post_type() !== 'babel_business' ) {
            return;
        }
        ?>
        <div class="bd-rating-selector-wrap">
            <label class="bd-rating-label">
                <?php esc_html_e( '¿Qué calificación le das a este negocio?', 'babel-directory' ); ?>
            </label>
            <div class="bd-stars-input">
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <label>
                        <input type="radio" name="babel_rating" value="<?php echo $i; ?>" required />
                        <?php echo $i; ?> ⭐
                    </label>
                <?php endfor; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Guarda la calificación en los metadatos del comentario y actualiza el tipo a 'babel_review'.
     */
    public function save_comment_rating( $comment_id, $comment_approved, $commentdata ) {
        $post_id = $commentdata['comment_post_ID'];

        if ( get_post_type( $post_id ) !== 'babel_business' ) {
            return;
        }

        // Capturar calificación del formulario (1 al 5)
        $rating = isset( $_POST['babel_rating'] ) ? intval( $_POST['babel_rating'] ) : 0;
        if ( $rating < 1 || $rating > 5 ) {
            $rating = 5; // Fallback por defecto a 5 estrellas
        }

        // Guardar el rating en los metadatos del comentario
        update_comment_meta( $comment_id, '_babel_rating', $rating );

        // Forzar actualización segura del tipo de comentario a 'babel_review' en base de datos
        global $wpdb;
        $wpdb->update(
            $wpdb->comments,
            array( 'comment_type' => 'babel_review' ),
            array( 'comment_ID' => $comment_id ),
            array( '%s' ),
            array( '%d' )
        );

        // Si el comentario se aprueba automáticamente, recalcular promedio de inmediato
        if ( $comment_approved === 1 || $comment_approved === '1' ) {
            $this->recalculate_post_rating_average( $post_id );
        }
    }

    /**
     * Recalcula el promedio al cambiar el estado del comentario (aprobación/desaprobación).
     */
    public function sync_post_rating_on_status_change( $new_status, $old_status, $comment ) {
        if ( $comment->comment_type === 'babel_review' ) {
            $this->recalculate_post_rating_average( $comment->comment_post_ID );
        }
    }

    /**
     * Recalcula el promedio al eliminar físicamente una reseña.
     */
    public function sync_post_rating_on_delete( $comment_id ) {
        $comment = get_comment( $comment_id );
        if ( $comment && $comment->comment_type === 'babel_review' ) {
            $this->recalculate_post_rating_average( $comment->comment_post_ID );
        }
    }

    /**
     * Recalcula el promedio matemático exacto de las calificaciones aprobadas del negocio.
     * Actualiza el metadato del post y sincroniza en caliente con la tabla del buscador rápido.
     *
     * @param int $post_id ID del negocio.
     */
    private function recalculate_post_rating_average( $post_id ) {
        global $wpdb;

        // Obtener la sumatoria y cantidad de ratings aprobados de tipo 'babel_review'
        $stats = $wpdb->get_row( $wpdb->prepare( "
            SELECT 
                COUNT(m.meta_value) as count, 
                AVG(CAST(m.meta_value AS DECIMAL(3,2))) as average 
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->commentmeta} m ON c.comment_ID = m.comment_ID
            WHERE c.comment_post_ID = %d 
            AND c.comment_approved = '1' 
            AND c.comment_type = 'babel_review'
            AND m.meta_key = '_babel_rating'
        ", $post_id ) );

        $count   = intval( $stats->count );
        $average = $count > 0 ? round( floatval( $stats->average ), 2 ) : 0.00;

        // Actualizar metadatos nativos del negocio
        update_post_meta( $post_id, '_babel_rating_avg', $average );
        update_post_meta( $post_id, '_babel_review_count', $count );

        // Sincronizar en caliente con la tabla personalizada wp_bd_search_index
        if ( class_exists( 'Babel_Directory_Search_Index' ) ) {
            $index = new Babel_Directory_Search_Index();
            $post = get_post( $post_id );
            if ( $post ) {
                $index->sync_business_to_index( $post_id, $post, true );
            }
        }
    }
}
