<?php
/**
 * Template Name: Babel Business Single Template
 * Description: Plantilla para renderizar el perfil de negocio (CPT babel_business) de forma nativa e independiente del tema.
 *
 * @package Babel_Directory
 */

get_header();
?>

<div class="babel-theme-wrapper" style="width: 100%; min-height: 80vh; background-color: var(--color-surface, #f9f9f9); padding-top: 2rem; padding-bottom: 4rem;">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            echo do_shortcode( '[bd_business_profile]' );
        endwhile;
    endif;
    ?>
</div>

<?php
get_footer();
