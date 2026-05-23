<?php
namespace Babel\Directory;

/**
 * Soporte de imágenes nativas para taxonomías (BD_Taxonomy_Images)
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}
class Taxonomy_Images {

    public function __construct() {
        $taxonomies = array( "babel_region", "babel_category" );

        foreach ( $taxonomies as $taxonomy ) {
            add_action( $taxonomy . "_add_form_fields", array( $this, "add_category_image" ), 10, 2 );
            add_action( $taxonomy . "_edit_form_fields", array( $this, "update_category_image" ), 10, 2 );
            add_action( "create_" . $taxonomy, array( $this, "save_category_image" ), 10, 2 );
            add_action( "edited_" . $taxonomy, array( $this, "save_category_image" ), 10, 2 );
        }

        add_action( "admin_enqueue_scripts", array( $this, "load_media" ) );
    }

    public function load_media() {
        wp_enqueue_media();
    }

    public function add_category_image( $taxonomy ) {
        ?>
        <div class="form-field term-group">
            <label for="bd_term_image_id">Imagen Geográfica (Precisión)</label>
            <input type="hidden" id="bd_term_image_id" name="bd_term_image_id" class="custom_media_url" value="">
            <div id="category-image-wrapper"></div>
            <p>
                <input type="button" class="button button-secondary bd_tax_media_button" id="bd_tax_media_button" name="bd_tax_media_button" value="Seleccionar Imagen">
                <input type="button" class="button button-secondary bd_tax_media_remove" id="bd_tax_media_remove" name="bd_tax_media_remove" value="Remover">
            </p>
        </div>
        <?php
        $this->add_script();
    }

    public function update_category_image( $term, $taxonomy ) {
        $image_id = get_term_meta( $term->term_id, "bd_term_image_id", true );
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row"><label for="bd_term_image_id">Imagen Geográfica (Precisión)</label></th>
            <td>
                <input type="hidden" id="bd_term_image_id" name="bd_term_image_id" value="<?php echo esc_attr( $image_id ); ?>">
                <div id="category-image-wrapper">
                    <?php if ( $image_id ) { echo wp_get_attachment_image( $image_id, "thumbnail" ); } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary bd_tax_media_button" id="bd_tax_media_button" name="bd_tax_media_button" value="Cambiar Imagen">
                    <input type="button" class="button button-secondary bd_tax_media_remove" id="bd_tax_media_remove" name="bd_tax_media_remove" value="Remover">
                </p>
            </td>
        </tr>
        <?php
        $this->add_script();
    }

    public function save_category_image( $term_id, $tt_id ) {
        if ( isset( $_POST["bd_term_image_id"] ) && "" !== $_POST["bd_term_image_id"] ) {
            update_term_meta( $term_id, "bd_term_image_id", absint( $_POST["bd_term_image_id"] ) );
        } else {
            delete_term_meta( $term_id, "bd_term_image_id" );
        }
    }

    private function add_script() {
        ?>
        <script>
            jQuery(document).ready(function($) {
                var frame;
                $(document).on("click", ".bd_tax_media_button", function(e) {
                    e.preventDefault();
                    if (frame) { frame.open(); return; }
                    frame = wp.media({
                        title: "Seleccionar Imagen Geográfica",
                        button: { text: "Usar esta imagen" },
                        multiple: false
                    });
                    frame.on("select", function() {
                        var attachment = frame.state().get("selection").first().toJSON();
                        $("#bd_term_image_id").val(attachment.id);
                        $("#category-image-wrapper").html("<img src=\"" + attachment.url + "\" style=\"max-width:150px;display:block;\">");
                    });
                    frame.open();
                });
                $(document).on("click", ".bd_tax_media_remove", function(e) {
                    e.preventDefault();
                    $("#bd_term_image_id").val("");
                    $("#category-image-wrapper").html("");
                });
            });
        </script>
        <?php
    }
}
