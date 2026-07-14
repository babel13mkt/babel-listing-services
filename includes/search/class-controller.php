<?php
namespace Babel\Directory\Search;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controlador de Búsqueda Geoespacial (Clean Slate)
 *
 * Endpoint: /wp-json/babel/search
 * Elimina duplicación de lógica Haversine en AJAX v1 + REST v1.
 */

class Controller {

    const ENDPOINT = 'babel/search';
    const CACHE_TTL = 1800; // 30 min
    const RATE_LIMIT = 60;
    const RATE_WINDOW = 60;

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        $search_args = [
            'methods'             => 'GET, POST',
            'callback'            => [ $this, 'handle_search' ],
            'permission_callback' => '__return_true',
            'args'                => $this->get_search_schema(),
        ];
        register_rest_route( 'babel', '/search', $search_args );
        register_rest_route( 'babel/v2', '/search', $search_args );
    }

    private function get_search_schema() {
        return [
            'keyword'  => [
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'category' => [
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'region'   => [
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'lat'      => [
                'default'           => 0,
                'sanitize_callback' => function( $param ) { return floatval( $param ); },
            ],
            'lng'      => [
                'default'           => 0,
                'sanitize_callback' => function( $param ) { return floatval( $param ); },
            ],
            'radius'   => [
                'default'           => 50,
                'sanitize_callback' => 'absint',
            ],
            'sort'     => [
                'default'           => 'featured',
                'validate_callback' => function( $param ) {
                    return in_array( $param, [ 'featured', 'rating', 'az', 'distance', 'newest' ], true );
                },
            ],
            'tier'     => [
                'default'           => 2,
                'validate_callback' => function( $param ) {
                    return in_array( $param, [ 1, 2 ], true );
                },
            ],
            'page'     => [
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'default'           => 12,
                'sanitize_callback' => function( $param ) {
                    $val = absint( $param );
                    return min( $val, 50 );
                },
            ],
        ];
    }

    public function handle_search( \WP_REST_Request $request ) {
        $rate = $this->check_rate_limit();
        if ( is_wp_error( $rate ) ) {
            return $rate;
        }

        $params = $request->get_params();

        // Cache key determinística y normalizada (evita colisiones por ruido en params)
        if ( class_exists( '\Babel\Directory\Cache' ) ) {
            $cache_k = \Babel\Directory\Cache::key( 'rest_v2', $params );
        } else {
            $cache_k = 'bd_search_' . md5( wp_json_encode( \Babel\Directory\Cache::normalize_params( $params ) ) );
        }
        $result  = get_transient( $cache_k );
        if ( false !== $result ) {
            return rest_ensure_response( $result );
        }

        $result = $this->execute_search( $params );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        set_transient( $cache_k, $result, self::CACHE_TTL );
        return rest_ensure_response( $result );
    }

    private function execute_search( array $params ) {
        global $wpdb;

        $table_index = $wpdb->prefix . 'babel_geo_index';
        $keyword     = $params['keyword'];
        $cat_slug    = $params['category'];
        $region_slug = $params['region'];
        $lat         = $params['lat'];
        $lng         = $params['lng'];
        $radius      = $params['radius'];
        $sort        = $params['sort'];
        $tier        = $params['tier'];
        $page        = max( 1, $params['page'] );
        $per_page    = max( 1, $params['per_page'] );
        $offset      = ( $page - 1 ) * $per_page;

        $where = [ "idx.tier = $tier" ];
        $join  = " INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID";

        // Categoría (slug → term_id)
        if ( ! empty( $cat_slug ) ) {
            $term = get_term_by( 'slug', $cat_slug, 'babel_category' );
            if ( $term ) {
                $where[] = $wpdb->prepare( "idx.category_id = %d", $term->term_id );
            } else {
                return $this->empty_search_response( $page, $per_page );
            }
        }

        // Región (slug → term_id)
        if ( ! empty( $region_slug ) ) {
            $term = get_term_by( 'slug', $region_slug, 'babel_region' );
            if ( $term ) {
                $where[] = $wpdb->prepare( "idx.region_id = %d", $term->term_id );
            } else {
                return $this->empty_search_response( $page, $per_page );
            }
        }

        // Haversine
        $distance_select = '';
        if ( $lat && $lng ) {
            $haversine = $wpdb->prepare(
                "( 6371 * acos( cos( radians(%f) ) * cos( radians( idx.latitude ) ) * cos( radians( idx.longitude ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( idx.latitude ) ) ) )",
                $lat, $lng, $lat
            );
            $distance_select = ", {$haversine} AS distance";
            if ( $radius > 0 ) {
                $where[] = "({$haversine} <= " . floatval( $radius ) . ")";
            }
        }

        // Ordenamiento (whitelist estricta)
        $allowed_orders = [
            'rating'   => 'idx.is_featured DESC, idx.rating_avg DESC, idx.post_id DESC',
            'az'       => 'idx.is_featured DESC, p.post_title ASC',
            'distance' => ( $lat && $lng ) ? 'idx.is_featured DESC, distance ASC' : 'idx.is_featured DESC, idx.post_id DESC',
            'newest'   => 'idx.is_featured DESC, idx.post_id DESC',
            'featured' => ( $lat && $lng ) ? 'idx.is_featured DESC, distance ASC' : 'idx.is_featured DESC, idx.post_id DESC',
        ];
        $orderby = isset( $allowed_orders[ $sort ] ) ? $allowed_orders[ $sort ] : $allowed_orders['featured'];

        // Keyword search
        if ( ! empty( $keyword ) ) {
            $like_kw = '%' . $wpdb->esc_like( $keyword ) . '%';
            $where[] = $wpdb->prepare(
                "(p.post_title LIKE %s OR p.post_content LIKE %s OR EXISTS (
                    SELECT 1 FROM {$wpdb->term_relationships} tr
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                    WHERE tr.object_id = p.ID
                    AND tt.taxonomy IN ('babel_category', 'babel_region')
                    AND t.name LIKE %s
                ))",
                $like_kw,
                $like_kw,
                $like_kw
            );
        }

        $where_str = implode( ' AND ', $where );

        $sql = "SELECT idx.post_id {$distance_select} FROM {$table_index} idx {$join} WHERE {$where_str} ORDER BY {$orderby} LIMIT {$offset}, {$per_page}";
        $post_ids = $wpdb->get_results( $sql, ARRAY_A );

        // Total
        $total_sql   = "SELECT COUNT(idx.post_id) FROM {$table_index} idx {$join} WHERE {$where_str}";
        $total_posts = (int) $wpdb->get_var( $total_sql );
        $max_pages   = (int) ceil( $total_posts / $per_page );

        // Items
        $items = [];
        if ( $post_ids ) {
            foreach ( $post_ids as $row ) {
                $item = $this->format_business_card( (int) $row['post_id'] );
                if ( $item ) {
                    if ( isset( $row['distance'] ) ) {
                        $item['distance_km'] = round( (float) $row['distance'], 1 );
                    }
                    $items[] = $item;
                }
            }
        }

        return [
            'success' => true,
            'data'    => [
                'items'      => $items,
                'total'      => $total_posts,
                'page'       => $page,
                'per_page'   => $per_page,
                'max_pages'  => $max_pages,
                'has_more'   => $page < $max_pages,
            ],
        ];
    }

    private function empty_search_response( $page, $per_page ) {
        return [
            'success' => true,
            'data'    => [
                'items'     => [],
                'total'     => 0,
                'page'      => $page,
                'per_page'  => $per_page,
                'max_pages' => 0,
                'has_more'  => false,
            ],
        ];
    }

    // ──────────────────────────────────────────────
    //  RATE LIMITING
    // ──────────────────────────────────────────────

    private function check_rate_limit() {
        $ip    = $this->get_client_ip();
        $key   = 'bd_ratelimit_' . md5( $ip );
        $count = get_transient( $key );

        if ( false === $count ) {
            set_transient( $key, 1, self::RATE_WINDOW );
            return true;
        }

        if ( $count >= self::RATE_LIMIT ) {
            return new \WP_Error(
                'rate_limit_exceeded',
                sprintf( 'Has excedido el límite de %d peticiones por minuto.', self::RATE_LIMIT ),
                [ 'status' => 429, 'retry_after' => self::RATE_WINDOW ]
            );
        }

        set_transient( $key, $count + 1, self::RATE_WINDOW );
        return true;
    }

    private function get_client_ip() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            $ip = filter_var( $_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP );
        }
        if ( ! $ip && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
            $ip = filter_var( trim( $ips[0] ), FILTER_VALIDATE_IP );
        }
        if ( ! $ip && isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
        }
        return $ip ? $ip : '0.0.0.0';
    }

    // ──────────────────────────────────────────────
    //  FORMATO DE DATOS
    // ──────────────────────────────────────────────

    private function format_business_card( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return null;
        }

        global $wpdb;
        $table_index = $wpdb->prefix . 'babel_geo_index';
        $index       = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table_index} WHERE post_id = %d AND tier = 2", $post_id ),
            ARRAY_A
        );

        $item = [
            'id'         => $post_id,
            'name'       => get_the_title( $post ),
            'slug'       => $post->post_name,
            'url'        => get_permalink( $post ),
            'excerpt'    => wp_strip_all_tags( get_the_excerpt( $post ) ),
            'is_verified'=> false,
            'is_featured'=> false,
            'rating'     => [ 'average' => 0, 'count' => 0 ],
            'image'      => null,
            'categories' => [],
            'regions'    => [],
        ];

        if ( $index ) {
            $item['is_verified'] = (bool) $index['is_verified'];
            $item['is_featured'] = (bool) $index['is_featured'];
            $item['rating']['average'] = isset( $index['rating_avg'] ) ? round( (float) $index['rating_avg'], 2 ) : 0;
        }

        $rating_count = get_post_meta( $post_id, '_babel_rating_count', true );
        if ( $rating_count !== '' ) {
            $item['rating']['count'] = (int) $rating_count;
        }

        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            $item['image'] = [
                'thumb'  => wp_get_attachment_image_url( $thumb_id, 'thumbnail' ),
                'medium' => wp_get_attachment_image_url( $thumb_id, 'medium' ),
                'large'  => wp_get_attachment_image_url( $thumb_id, 'large' ),
            ];
        }

        $categories = wp_get_post_terms( $post_id, 'babel_category', [ 'fields' => 'all' ] );
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            foreach ( $categories as $cat ) {
                $item['categories'][] = [
                    'id'   => (int) $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ];
            }
        }

        $regions = wp_get_post_terms( $post_id, 'babel_region', [ 'fields' => 'all' ] );
        if ( ! empty( $regions ) && ! is_wp_error( $regions ) ) {
            foreach ( $regions as $reg ) {
                $item['regions'][] = [
                    'id'   => (int) $reg->term_id,
                    'name' => $reg->name,
                    'slug' => $reg->slug,
                ];
            }
        }

        return $item;
    }
}
