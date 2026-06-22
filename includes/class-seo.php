<?php
namespace Babel\Directory;

/**
 * Clase de SEO y Marcado de Esquemas (Schema Markup)
 * Provee metadatos dinámicos, Open Graph, Twitter Cards y JSON-LD estructurado
 * de forma compatible con cachés estáticas sin dependencias externas.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class SEO {

    /**
     * Constructor de la clase.
     * Registra los ganchos necesarios para títulos y cabeceras.
     */
    public function __construct() {
        // Modificar los componentes del título
        add_filter( 'document_title_parts', array( $this, 'filter_document_title' ), 99999 );
        add_filter( 'pre_get_document_title', array( $this, 'filter_pre_get_document_title' ), 99999 );

        // Inyectar metatags (Meta description, Open Graph, Twitter Card, Canonical)
        add_action( 'wp_head', array( $this, 'inject_seo_meta' ), 1 );

        // Inyectar Schema Markup en formato JSON-LD
        add_action( 'wp_head', array( $this, 'inject_schema_markup' ), 2 );
    }

    /**
     * Filtra el título usando pre_get_document_title para forzar el override sobre temas/plugins.
     *
     * @param string $title Título original.
     * @return string Título modificado.
     */
    public function filter_pre_get_document_title( $title ) {
        $parts = $this->filter_document_title( array( 'title' => '' ) );
        if ( ! empty( $parts['title'] ) ) {
            return $parts['title'] . ' | ' . get_bloginfo( 'name' );
        }
        return $title;
    }

    /**
     * Filtra las partes del título del documento de WordPress para las vistas del plugin.
     *
     * @param array $title_parts Partes del título.
     * @return array Partes del título modificadas.
     */
    public function filter_document_title( $title_parts ) {
        if ( is_singular( 'babel_business' ) ) {
            $post_id = get_the_ID();
            $title = get_the_title( $post_id );
            
            $categories = wp_get_post_terms( $post_id, 'babel_category' );
            $regions = wp_get_post_terms( $post_id, 'babel_region' );
            
            $cat_name = ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0]->name : '';
            $reg_name = '';
            if ( ! empty( $regions ) && ! is_wp_error( $regions ) ) {
                $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $regions[0]->name );
            }
            
            if ( $cat_name && $reg_name ) {
                $title_parts['title'] = sprintf( '%s - %s en %s', $title, $cat_name, $reg_name );
            } elseif ( $reg_name ) {
                $title_parts['title'] = sprintf( '%s en %s', $title, $reg_name );
            } else {
                $title_parts['title'] = $title;
            }
        } elseif ( is_tax( 'babel_region' ) || is_tax( 'babel_category' ) ) {
            $region_slug = get_query_var( 'babel_region' );
            $category_slug = get_query_var( 'babel_category' );
            
            if ( $region_slug && $category_slug ) {
                $region = get_term_by( 'slug', $region_slug, 'babel_region' );
                $category = get_term_by( 'slug', $category_slug, 'babel_category' );
                if ( $region && $category ) {
                    $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $region->name );
                    $title_parts['title'] = sprintf( '%s en %s', $category->name, $reg_name );
                }
            } elseif ( $region_slug ) {
                $region = get_term_by( 'slug', $region_slug, 'babel_region' );
                if ( $region ) {
                    $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $region->name );
                    $title_parts['title'] = sprintf( 'Negocios y Servicios en %s', $reg_name );
                }
            } elseif ( $category_slug ) {
                $category = get_term_by( 'slug', $category_slug, 'babel_category' );
                if ( $category ) {
                    $title_parts['title'] = sprintf( '%s en Chile', $category->name );
                }
            }
        }
        return $title_parts;
    }

    /**
     * Inyecta metaetiquetas de descripción, Open Graph, Twitter Card y Canonical URL en el head.
     */
    public function inject_seo_meta() {
        $meta = $this->get_seo_data();
        if ( empty( $meta ) ) {
            return;
        }

        echo "\n<!-- Babel Directory SEO Optimization -->\n";
        if ( ! empty( $meta['description'] ) ) {
            echo '<meta name="description" content="' . esc_attr( $meta['description'] ) . '" />' . "\n";
        }
        if ( ! empty( $meta['canonical'] ) ) {
            echo '<link rel="canonical" href="' . esc_url( $meta['canonical'] ) . '" />' . "\n";
        }

        // Open Graph
        echo '<meta property="og:site_name" content="Soy de Chile" />' . "\n";
        if ( ! empty( $meta['title'] ) ) {
            echo '<meta property="og:title" content="' . esc_attr( $meta['title'] ) . '" />' . "\n";
        }
        if ( ! empty( $meta['description'] ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $meta['description'] ) . '" />' . "\n";
        }
        if ( ! empty( $meta['url'] ) ) {
            echo '<meta property="og:url" content="' . esc_url( $meta['url'] ) . '" />' . "\n";
        }
        if ( ! empty( $meta['type'] ) ) {
            echo '<meta property="og:type" content="' . esc_attr( $meta['type'] ) . '" />' . "\n";
        }
        if ( ! empty( $meta['image'] ) ) {
            echo '<meta property="og:image" content="' . esc_url( $meta['image'] ) . '" />' . "\n";
        }

        // Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        if ( ! empty( $meta['title'] ) ) {
            echo '<meta name="twitter:title" content="' . esc_attr( $meta['title'] ) . '" />' . "\n";
        }
        if ( ! empty( $meta['description'] ) ) {
            echo '<meta name="twitter:description" content="' . esc_attr( $meta['description'] ) . '" />' . "\n";
        }
        if ( ! empty( $meta['image'] ) ) {
            echo '<meta name="twitter:image" content="' . esc_url( $meta['image'] ) . '" />' . "\n";
        }
        echo "<!-- End Babel Directory SEO Optimization -->\n\n";
    }

    /**
     * Inyecta esquemas estructurados JSON-LD en el head.
     */
    public function inject_schema_markup() {
        $schemas = array();

        // 1. BreadcrumbList Schema
        $breadcrumbs = $this->get_breadcrumbs_schema();
        if ( ! empty( $breadcrumbs ) ) {
            $schemas[] = $breadcrumbs;
        }

        // 2. LocalBusiness or specific business CPT Schema
        if ( is_singular( 'babel_business' ) ) {
            $business_schema = $this->get_business_schema();
            if ( ! empty( $business_schema ) ) {
                $schemas[] = $business_schema;
            }
        }

        // 3. ItemList Schema for archives / taxonomy pages
        if ( is_tax( 'babel_region' ) || is_tax( 'babel_category' ) ) {
            $item_list_schema = $this->get_item_list_schema();
            if ( ! empty( $item_list_schema ) ) {
                $schemas[] = $item_list_schema;
            }
        }

        if ( ! empty( $schemas ) ) {
            echo "\n<!-- Babel Directory JSON-LD Schema -->\n";
            foreach ( $schemas as $schema ) {
                echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
            }
            echo "<!-- End Babel Directory JSON-LD Schema -->\n\n";
        }
    }

    /**
     * Genera datos de SEO recopilados para la vista actual.
     *
     * @return array Datos de SEO (title, description, canonical, image, url, type).
     */
    private function get_seo_data() {
        $data = array();

        if ( is_singular( 'babel_business' ) ) {
            $post_id = get_the_ID();
            $title = get_the_title( $post_id );
            
            $categories = wp_get_post_terms( $post_id, 'babel_category' );
            $regions = wp_get_post_terms( $post_id, 'babel_region' );
            
            $cat_name = ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0]->name : '';
            $reg_name = '';
            if ( ! empty( $regions ) && ! is_wp_error( $regions ) ) {
                $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $regions[0]->name );
            }

            // Metas básicas
            if ( $cat_name && $reg_name ) {
                $data['title'] = sprintf( '%s - %s en %s | Soy de Chile', $title, $cat_name, $reg_name );
            } elseif ( $reg_name ) {
                $data['title'] = sprintf( '%s en %s | Soy de Chile', $title, $reg_name );
            } else {
                $data['title'] = $title . ' | Soy de Chile';
            }

            // Description
            $desc = get_post_meta( $post_id, '_babel_description', true );
            if ( empty( $desc ) ) {
                $post = get_post( $post_id );
                $desc = ! empty( $post->post_content ) ? wp_strip_all_tags( $post->post_content ) : '';
            }

            if ( empty( $desc ) ) {
                $address = get_post_meta( $post_id, '_babel_address', true );
                $phone = get_post_meta( $post_id, '_babel_phone', true );
                $desc = sprintf(
                    'Encuentra toda la información sobre %s%s en %s: dirección %s, teléfono %s, horarios, mapa y opiniones. Descubre más en Soy de Chile.',
                    $title,
                    $cat_name ? ' (' . $cat_name . ')' : '',
                    $reg_name ? $reg_name : 'Chile',
                    $address ? 'en ' . $address : '',
                    $phone ? $phone : ''
                );
            }
            $data['description'] = wp_html_excerpt( $desc, 155, '...' );

            $data['canonical'] = get_permalink( $post_id );
            $data['url'] = $data['canonical'];
            $data['type'] = 'business.business';

            // Image
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $data['image'] = wp_get_attachment_image_url( $thumb_id, 'large' );
            } else {
                // Check gallery
                $gallery_meta = get_post_meta( $post_id, '_babel_gallery', true );
                if ( ! empty( $gallery_meta ) ) {
                    $gallery_ids = is_array( $gallery_meta ) ? $gallery_meta : explode( ',', $gallery_meta );
                    if ( ! empty( $gallery_ids[0] ) ) {
                        $data['image'] = wp_get_attachment_image_url( $gallery_ids[0], 'large' );
                    }
                }
            }
        } elseif ( is_tax( 'babel_region' ) || is_tax( 'babel_category' ) ) {
            $region_slug = get_query_var( 'babel_region' );
            $category_slug = get_query_var( 'babel_category' );
            
            $reg_name = '';
            $cat_name = '';
            $term_obj = null;

            if ( $region_slug ) {
                $region = get_term_by( 'slug', $region_slug, 'babel_region' );
                if ( $region ) {
                    $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $region->name );
                    $term_obj = $region;
                }
            }

            if ( $category_slug ) {
                $category = get_term_by( 'slug', $category_slug, 'babel_category' );
                if ( $category ) {
                    $cat_name = $category->name;
                    if ( ! $term_obj ) {
                        $term_obj = $category;
                    }
                }
            }

            if ( $region_slug && $category_slug ) {
                $data['title'] = sprintf( '%s en %s | Soy de Chile', $cat_name, $reg_name );
                $data['description'] = sprintf( 'Encuentra los mejores comercios, servicios y locales de %s en la zona de %s, Chile. Revisa horarios, direcciones, WhatsApp y teléfonos.', $cat_name, $reg_name );
                $data['canonical'] = home_url( '/region/' . $region_slug . '/categoria/' . $category_slug . '/' );
            } elseif ( $region_slug ) {
                $data['title'] = sprintf( 'Negocios y Servicios en %s | Soy de Chile', $reg_name );
                $data['description'] = sprintf( 'Guía comercial y directorio completo de instituciones, negocios y servicios en %s, Chile. Contactos directos, ubicaciones y horarios.', $reg_name );
                $data['canonical'] = get_term_link( $term_obj );
            } elseif ( $category_slug ) {
                $data['title'] = sprintf( '%s en Chile | Soy de Chile', $cat_name );
                $data['description'] = sprintf( 'Directorio y guía nacional de %s en Chile. Encuentra ubicaciones, contactos telefónicos y WhatsApp de locales comerciales.', $cat_name );
                $data['canonical'] = get_term_link( $term_obj );
            }

            $data['url'] = $data['canonical'];
            $data['type'] = 'website';

            // Check taxonomy image if class exists
            if ( $term_obj && class_exists( '\Babel\Directory\Taxonomy_Images' ) ) {
                $image_id = get_term_meta( $term_obj->term_id, 'babel_taxonomy_image', true );
                if ( $image_id ) {
                    $data['image'] = wp_get_attachment_image_url( $image_id, 'large' );
                }
            }
        }

        return $data;
    }

    /**
     * Genera el marcado Schema BreadcrumbList basado en la navegación actual.
     */
    private function get_breadcrumbs_schema() {
        $items = array();
        
        // Home
        $items[] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Inicio',
            'item' => home_url( '/' )
        );

        if ( is_tax( 'babel_region' ) || is_tax( 'babel_category' ) ) {
            $region_slug = get_query_var( 'babel_region' );
            $category_slug = get_query_var( 'babel_category' );
            
            $pos = 2;
            if ( $region_slug ) {
                $region = get_term_by( 'slug', $region_slug, 'babel_region' );
                if ( $region ) {
                    $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $region->name );
                    $items[] = array(
                        '@type' => 'ListItem',
                        'position' => $pos++,
                        'name' => $reg_name,
                        'item' => get_term_link( $region )
                    );
                }
            }

            if ( $category_slug ) {
                $category = get_term_by( 'slug', $category_slug, 'babel_category' );
                if ( $category ) {
                    $url = get_term_link( $category );
                    if ( $region_slug ) {
                        $url = home_url( '/region/' . $region_slug . '/categoria/' . $category_slug . '/' );
                    }
                    $items[] = array(
                        '@type' => 'ListItem',
                        'position' => $pos++,
                        'name' => $category->name,
                        'item' => $url
                    );
                }
            }
        } elseif ( is_singular( 'babel_business' ) ) {
            $post_id = get_the_ID();
            $regions = wp_get_post_terms( $post_id, 'babel_region' );
            $categories = wp_get_post_terms( $post_id, 'babel_category' );

            $pos = 2;
            $first_region_slug = '';
            if ( ! is_wp_error( $regions ) && ! empty( $regions ) ) {
                $region = $regions[0];
                $first_region_slug = $region->slug;
                $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $region->name );
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $pos++,
                    'name' => $reg_name,
                    'item' => get_term_link( $region )
                );
            }

            if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                $category = $categories[0];
                $url = get_term_link( $category );
                if ( $first_region_slug ) {
                    $url = home_url( '/region/' . $first_region_slug . '/categoria/' . $category->slug . '/' );
                }
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $pos++,
                    'name' => $category->name,
                    'item' => $url
                );
            }

            // Post Title
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => get_the_title( $post_id ),
                'item' => get_permalink( $post_id )
            );
        } else {
            return array();
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        );
    }

    /**
     * Genera el marcado Schema LocalBusiness para la ficha de negocio actual.
     */
    private function get_business_schema() {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return array();
        }

        // Mapear categorías a tipos específicos de Schema.org
        $schema_type = 'LocalBusiness';
        $categories = wp_get_post_terms( $post_id, 'babel_category' );
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            $cat_slug = strtolower( $categories[0]->slug );
            $mappings = array(
                'restaurante' => 'Restaurant',
                'restaurant' => 'Restaurant',
                'comida' => 'FoodEstablishment',
                'hotel' => 'Hotel',
                'alojamiento' => 'LodgingBusiness',
                'clinica' => 'MedicalBusiness',
                'salud' => 'MedicalBusiness',
                'dentista' => 'Dentist',
                'colegio' => 'School',
                'escuela' => 'School',
                'universidad' => 'EducationalOrganization',
                'educacion' => 'EducationalOrganization',
                'banco' => 'FinancialService',
                'finanzas' => 'FinancialService',
                'gimnasio' => 'ExerciseGym',
                'belleza' => 'BeautySalon',
                'peluqueria' => 'HairSalon',
                'automotriz' => 'AutoRepair',
                'tienda' => 'Store',
                'comercio' => 'Store',
            );
            foreach ( $mappings as $keyword => $type ) {
                if ( strpos( $cat_slug, $keyword ) !== false ) {
                    $schema_type = $type;
                    break;
                }
            }
        }

        $regions = wp_get_post_terms( $post_id, 'babel_region' );
        $comuna = '';
        $region_name = '';

        if ( ! is_wp_error( $regions ) && ! empty( $regions ) ) {
            // Asumimos que la taxonomía tiene estructura jerárquica: Región (Padre) -> Comuna (Hijo)
            // Si el término tiene un padre, es Comuna.
            $term = $regions[0];
            if ( $term->parent != 0 ) {
                $comuna = $term->name;
                $parent_term = get_term( $term->parent, 'babel_region' );
                if ( $parent_term && ! is_wp_error( $parent_term ) ) {
                    $region_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $parent_term->name );
                }
            } else {
                $region_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $term->name );
            }
        }

        // Obtener metadatos
        $phone = get_post_meta( $post_id, '_babel_phone', true );
        $whatsapp = get_post_meta( $post_id, '_babel_whatsapp', true );
        $address = get_post_meta( $post_id, '_babel_address', true );
        $lat = get_post_meta( $post_id, '_babel_lat', true );
        $lng = get_post_meta( $post_id, '_babel_lng', true );
        $website = get_post_meta( $post_id, '_babel_website', true );
        $price_range = get_post_meta( $post_id, '_babel_price_range', true );
        $desc = get_post_meta( $post_id, '_babel_description', true );
        
        // Calificaciones
        $rating_avg = get_post_meta( $post_id, '_babel_rating_avg', true );
        $rating_count = get_post_meta( $post_id, '_babel_review_count', true );

        // Redes sociales
        $same_as = array();
        if ( ! empty( $website ) ) {
            $same_as[] = esc_url_raw( $website );
        }
        $instagram = get_post_meta( $post_id, '_babel_instagram', true );
        if ( ! empty( $instagram ) ) {
            $same_as[] = 'https://instagram.com/' . sanitize_text_field( $instagram );
        }
        $facebook = get_post_meta( $post_id, '_babel_facebook', true );
        if ( ! empty( $facebook ) ) {
            $same_as[] = 'https://facebook.com/' . sanitize_text_field( $facebook );
        }
        $linkedin = get_post_meta( $post_id, '_babel_linkedin', true );
        if ( ! empty( $linkedin ) ) {
            $same_as[] = 'https://linkedin.com/company/' . sanitize_text_field( $linkedin );
        }
        $tiktok = get_post_meta( $post_id, '_babel_tiktok', true );
        if ( ! empty( $tiktok ) ) {
            $same_as[] = 'https://tiktok.com/@' . sanitize_text_field( $tiktok );
        }
        $youtube = get_post_meta( $post_id, '_babel_youtube_channel', true );
        if ( ! empty( $youtube ) ) {
            $same_as[] = esc_url_raw( $youtube );
        }
        $twitter = get_post_meta( $post_id, '_babel_twitter', true );
        if ( ! empty( $twitter ) ) {
            $same_as[] = 'https://x.com/' . sanitize_text_field( $twitter );
        }

        // Imagen/Logotipo
        $image_url = '';
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            $image_url = wp_get_attachment_image_url( $thumb_id, 'full' );
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'name' => get_the_title( $post_id ),
            'url' => get_permalink( $post_id ),
        );

        if ( ! empty( $image_url ) ) {
            $schema['image'] = $image_url;
            $schema['logo'] = $image_url;
        }

        if ( ! empty( $desc ) ) {
            $schema['description'] = wp_strip_all_tags( $desc );
        } else {
            $post = get_post( $post_id );
            if ( ! empty( $post->post_content ) ) {
                $schema['description'] = wp_strip_all_tags( $post->post_content );
            }
        }

        if ( ! empty( $phone ) ) {
            $schema['telephone'] = $phone;
        }

        if ( ! empty( $whatsapp ) ) {
            $schema['contactPoint'] = array(
                '@type' => 'ContactPoint',
                'telephone' => '+' . preg_replace('/[^0-9]/', '', $whatsapp),
                'contactType' => 'customer service',
                'availableLanguage' => 'Spanish'
            );
        }

        if ( ! empty( $address ) ) {
            $schema['address'] = array(
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => $comuna ? $comuna : $region_name,
                'addressRegion' => $region_name,
                'addressCountry' => 'CL'
            );
        }

        if ( ! empty( $lat ) && ! empty( $lng ) ) {
            $schema['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => floatval( $lat ),
                'longitude' => floatval( $lng )
            );
        }

        if ( ! empty( $price_range ) ) {
            $schema['priceRange'] = $price_range;
        } else {
            $schema['priceRange'] = '$$';
        }

        if ( ! empty( $same_as ) ) {
            $schema['sameAs'] = $same_as;
        }

        // Horarios
        $hours_meta = get_post_meta( $post_id, '_babel_hours', true );
        if ( ! empty( $hours_meta ) ) {
            $hours = is_array( $hours_meta ) ? $hours_meta : json_decode( $hours_meta, true );
            if ( is_array( $hours ) ) {
                $dias_map = array(
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday',
                    'lunes' => 'Monday',
                    'martes' => 'Tuesday',
                    'miercoles' => 'Wednesday',
                    'jueves' => 'Thursday',
                    'viernes' => 'Friday',
                    'sabado' => 'Saturday',
                    'domingo' => 'Sunday'
                );
                $hours_spec = array();
                foreach ( $hours as $day => $data ) {
                    $day_lower = strtolower( $day );
                    if ( isset( $dias_map[ $day_lower ] ) ) {
                        if ( is_array( $data ) ) {
                            if ( ! empty( $data['closed'] ) ) {
                                continue;
                            }
                            if ( ! empty( $data['open'] ) && ! empty( $data['close'] ) ) {
                                $hours_spec[] = array(
                                    '@type' => 'OpeningHoursSpecification',
                                    'dayOfWeek' => $dias_map[ $day_lower ],
                                    'opens' => $data['open'],
                                    'closes' => $data['close']
                                );
                            }
                        } elseif ( is_string( $data ) && strpos( $data, '-' ) !== false ) {
                            $parts = explode( '-', $data );
                            $open = trim( $parts[0] );
                            $close = trim( $parts[1] );
                            if ( preg_match( '/\d{2}:\d{2}/', $open ) && preg_match( '/\d{2}:\d{2}/', $close ) ) {
                                $hours_spec[] = array(
                                    '@type' => 'OpeningHoursSpecification',
                                    'dayOfWeek' => $dias_map[ $day_lower ],
                                    'opens' => $open,
                                    'closes' => $close
                                );
                            }
                        }
                    }
                }
                if ( ! empty( $hours_spec ) ) {
                    $schema['openingHoursSpecification'] = $hours_spec;
                }
            }
        }

        // Reseñas agregadas
        if ( ! empty( $rating_avg ) && ! empty( $rating_count ) && floatval( $rating_avg ) > 0 && intval( $rating_count ) > 0 ) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => floatval( $rating_avg ),
                'ratingCount' => intval( $rating_count ),
                'bestRating' => '5',
                'worstRating' => '1'
            );
        }

        return $schema;
    }

    /**
     * Genera el marcado Schema ItemList para listas de taxonomía/resultados.
     */
    private function get_item_list_schema() {
        if ( ! have_posts() ) {
            return array();
        }

        $items = array();
        $pos = 1;

        global $wp_query;
        $posts = $wp_query->posts;

        if ( empty( $posts ) ) {
            return array();
        }

        foreach ( $posts as $p ) {
            if ( 'babel_business' === $p->post_type ) {
                $items[] = array(
                    '@type' => 'ListItem',
                    'position' => $pos++,
                    'url' => get_permalink( $p->ID ),
                    'name' => $p->post_title
                );
            }
        }

        if ( empty( $items ) ) {
            return array();
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $items
        );
    }
}
