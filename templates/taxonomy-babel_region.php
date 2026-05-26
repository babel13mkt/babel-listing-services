<?php
/**
 * Template Name: Babel Region & Category Taxonomy Template
 * Description: Plantilla para renderizar páginas de región y categorías asociadas de forma nativa e independiente del tema.
 *
 * @package Babel_Directory
 */

get_header();
?>

<div class="babel-theme-wrapper" style="width: 100%; min-height: 80vh; background-color: var(--color-surface, #f9f9f9); padding-bottom: 4rem;">
    <?php
    // Renderizar la barra de filtros y el buscador de región de forma autónoma
    echo do_shortcode( '[bd_region_template]' );
    ?>
</div>

<?php
get_footer();
