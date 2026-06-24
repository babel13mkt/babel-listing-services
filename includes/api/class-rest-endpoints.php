<?php
/**
 * Controladores REST API headless para Babel Directory.
 *
 * Proporciona endpoints JSON limpios, sin HTML ni dependencias visuales
 * de WordPress/Divi, diseñados para consumo por apps móviles y SPAs.
 *
 * @package Babel\Directory\Api
 * @since   9.0.0
 */

namespace Babel\Directory\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rest_Endpoints
 *
 * Registra y maneja todos los endpoints REST API del plugin.
 * Cada respuesta es JSON puro sin markup HTML, con rate limiting
 * y caching vía transients.
 */
class Rest_Endpoints {

	/**
	 * Namespace de la API.
	 *
	 * @var string
	 */
	const NAMESPACE = 'babel/v1';

	/**
	 * Duración del caché en segundos (1 hora).
	 *
	 * @var int
	 */
	const CACHE_TTL = 3600;

	/**
	 * Máximo de resultados por página.
	 *
	 * @var int
	 */
	const MAX_PER_PAGE = 50;

	/**
	 * Límite de peticiones por minuto para rate limiting.
	 *
	 * @var int
	 */
	const RATE_LIMIT = 60;

	/**
	 * Ventana de tiempo para rate limiting en segundos.
	 *
	 * @var int
	 */
	const RATE_WINDOW = 60;

	/**
	 * Constructor. Registra los hooks de la API.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registra todas las rutas REST del plugin.
	 *
	 * @return void
	 */
	public function register_routes() {

		// GET /wp-json/babel/v1/search
		register_rest_route( self::NAMESPACE, '/search', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_search' ),
			'permission_callback' => '__return_true',
			'args'                => $this->get_search_schema(),
		) );

		// GET /wp-json/babel/v1/business/{id}
		register_rest_route( self::NAMESPACE, '/business/(?P<id>\d+)', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_business' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && intval( $param ) > 0;
					},
					'sanitize_callback' => 'absint',
				),
			),
		) );

		// GET /wp-json/babel/v1/regions
		register_rest_route( self::NAMESPACE, '/regions', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_regions' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'parent' => array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		) );

		// GET /wp-json/babel/v1/categories
		register_rest_route( self::NAMESPACE, '/categories', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_categories' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'parent' => array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		) );

		// GET /wp-json/babel/v1/suggestions
		register_rest_route( self::NAMESPACE, '/suggestions', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_suggestions' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'q' => array(
					'required'          => true,
					'validate_callback' => function( $param ) {
						return is_string( $param ) && strlen( trim( $param ) ) >= 2;
					},
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );
	}

	/**
	 * Define el esquema de parámetros para el endpoint de búsqueda.
	 *
	 * @return array Esquema de parámetros.
	 */
	private function get_search_schema() {
		return array(
			'keyword'  => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'category' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'region'   => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'lat'      => array(
				'default'           => 0,
				'sanitize_callback' => function( $param ) { return floatval( $param ); },
			),
			'lng'      => array(
				'default'           => 0,
				'sanitize_callback' => function( $param ) { return floatval( $param ); },
			),
			'radius'   => array(
				'default'           => 50,
				'sanitize_callback' => 'absint',
			),
			'sort'     => array(
				'default'           => 'featured',
				'validate_callback' => function( $param ) {
					return in_array( $param, array( 'featured', 'rating', 'az', 'distance', 'newest' ), true );
				},
			),
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'default'           => 12,
				'sanitize_callback' => function( $param ) {
					$val = absint( $param );
					return min( $val, self::MAX_PER_PAGE );
				},
			),
		);
	}

	// ──────────────────────────────────────────────
	//  RATE LIMITING
	// ──────────────────────────────────────────────

	/**
	 * Verifica rate limiting basado en IP del cliente.
	 *
	 * Usa transients para trackear peticiones por ventana de tiempo.
	 * Retorna WP_Error si se excede el límite.
	 *
	 * @return true|\WP_Error True si está permitido, WP_Error si excede.
	 */
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
				sprintf(
					/* translators: %d: número de peticiones permitidas por minuto */
					__( 'Has excedido el límite de %d peticiones por minuto. Intenta de nuevo en unos segundos.', 'babel-directory' ),
					self::RATE_LIMIT
				),
				array(
					'status'    => 429,
					'retry_after' => self::RATE_WINDOW,
				)
			);
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );
		return true;
	}

	/**
	 * Obtiene la IP real del cliente detrás de proxies/CDN.
	 *
	 * @return string IP del cliente.
	 */
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
	//  CACHE HELPERS
	// ──────────────────────────────────────────────

	/**
	 * Genera una clave de cache estable a partir de parámetros.
	 *
	 * @param string $prefix  Prefijo de la clave.
	 * @param array  $params  Parámetros que identifican la consulta.
	 * @return string Clave de transient.
	 */
	private function cache_key( $prefix, array $params ) {
		return 'bd_api_' . $prefix . '_' . md5( wp_json_encode( $params ) );
	}

	/**
	 * Obtiene datos del cache o ejecuta el callback y los guarda.
	 *
	 * @param string   $key      Clave de transient.
	 * @param callable $callback Función que genera los datos.
	 * @param int      $ttl      Tiempo de vida en segundos.
	 * @return mixed Datos cacheados o frescos.
	 */
	private function cached_get( $key, callable $callback, $ttl = self::CACHE_TTL ) {
		$data = get_transient( $key );
		if ( false !== $data ) {
			return $data;
		}
		$data = $callback();
		if ( ! is_wp_error( $data ) && null !== $data ) {
			set_transient( $key, $data, $ttl );
		}
		return $data;
	}

	// ──────────────────────────────────────────────
	//  ENDPOINT: SEARCH
	// ──────────────────────────────────────────────

	/**
	 * GET /wp-json/babel/v1/search
	 *
	 * Busca negocios con filtros por keyword, categoría, región y coordenadas.
	 * Retorna JSON limpio sin HTML, con metadatos de paginación.
	 *
	 * Parámetros:
	 *   - keyword  (string)  Término de búsqueda.
	 *   - category (string)  Slug de babel_category.
	 *   - region   (string)  Slug de babel_region.
	 *   - lat      (float)   Latitud para búsqueda geoespacial.
	 *   - lng      (float)   Longitud para búsqueda geoespacial.
	 *   - radius   (int)     Radio en km (default 50).
	 *   - sort     (string)  Orden: featured | rating | az | distance | newest.
	 *   - page     (int)     Página actual (default 1).
	 *   - per_page (int)     Resultados por página (default 12, max 50).
	 *
	 * @param \WP_REST_Request $request Petición REST.
	 * @return \WP_REST_Response|\WP_Error Respuesta JSON o error.
	 */
	public function handle_search( \WP_REST_Request $request ) {
		$rate = $this->check_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$params = $request->get_params();

		// Intentar cache.
		$cache_k = $this->cache_key( 'search', $params );
		$result  = $this->cached_get( $cache_k, function() use ( $params ) {
			return $this->execute_search( $params );
		}, 1800 ); // 30 min cache para búsquedas.

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Ejecuta la consulta de búsqueda contra la tabla de índice.
	 *
	 * @param array $params Parámetros de búsqueda sanitizados.
	 * @return array|\WP_Error Datos de respuesta o error.
	 */
	private function execute_search( array $params ) {
		global $wpdb;

		$table_index  = $wpdb->prefix . 'bd_search_index';
		$keyword      = $params['keyword'];
		$cat_slug     = $params['category'];
		$region_slug  = $params['region'];
		$lat          = $params['lat'];
		$lng          = $params['lng'];
		$radius       = $params['radius'];
		$sort         = $params['sort'];
		$page         = max( 1, $params['page'] );
		$per_page     = max( 1, $params['per_page'] );
		$offset       = ( $page - 1 ) * $per_page;

		$where = array( "p.post_status = 'publish'" );
		$join  = " INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID";

		// Filtro por categoría (slug → term_id).
		if ( ! empty( $cat_slug ) ) {
			$term = get_term_by( 'slug', $cat_slug, 'babel_category' );
			if ( $term ) {
				$where[] = $wpdb->prepare( "idx.category_id = %d", $term->term_id );
			} else {
				// Categoría no encontrada → sin resultados.
				return $this->empty_search_response( $page, $per_page );
			}
		}

		// Filtro por región (slug → term_id).
		if ( ! empty( $region_slug ) ) {
			$term = get_term_by( 'slug', $region_slug, 'babel_region' );
			if ( $term ) {
				$where[] = $wpdb->prepare( "idx.region_id = %d", $term->term_id );
			} else {
				return $this->empty_search_response( $page, $per_page );
			}
		}

		// Geolocalización Haversine.
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

		// Ordenamiento (whitelist estricta).
		// Cuando hay coordenadas, el orden por defecto es por distancia (más cercanos primero)
		$allowed_orders = array(
			'rating'   => 'idx.is_featured DESC, idx.rating_avg DESC, idx.post_id DESC',
			'az'       => 'idx.is_featured DESC, p.post_title ASC',
			'distance' => ( $lat && $lng ) ? 'idx.is_featured DESC, distance ASC' : 'idx.is_featured DESC, idx.post_id DESC',
			'newest'   => 'idx.is_featured DESC, idx.post_id DESC',
			'featured' => ( $lat && $lng ) ? 'idx.is_featured DESC, distance ASC' : 'idx.is_featured DESC, idx.post_id DESC',
		);
		$orderby = isset( $allowed_orders[ $sort ] ) ? $allowed_orders[ $sort ] : $allowed_orders['featured'];

		// Búsqueda por keyword.
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

		// Consulta de resultados.
		$sql = "SELECT idx.post_id {$distance_select} FROM {$table_index} idx {$join} WHERE {$where_str} ORDER BY {$orderby} LIMIT {$offset}, {$per_page}";
		$post_ids = $wpdb->get_results( $sql, ARRAY_A );

		// Consulta de total.
		$total_sql   = "SELECT COUNT(idx.post_id) FROM {$table_index} idx {$join} WHERE {$where_str}";
		$total_posts = (int) $wpdb->get_var( $total_sql );
		$max_pages   = (int) ceil( $total_posts / $per_page );

		// Construir items limpios.
		$items = array();
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

		return array(
			'success' => true,
			'data'    => array(
				'items'      => $items,
				'total'      => $total_posts,
				'page'       => $page,
				'per_page'   => $per_page,
				'max_pages'  => $max_pages,
				'has_more'   => $page < $max_pages,
			),
		);
	}

	/**
	 * Retorna una respuesta vacía de búsqueda con estructura consistente.
	 *
	 * @param int $page     Página actual.
	 * @param int $per_page Resultados por página.
	 * @return array Respuesta vacía normalizada.
	 */
	private function empty_search_response( $page, $per_page ) {
		return array(
			'success' => true,
			'data'    => array(
				'items'     => array(),
				'total'     => 0,
				'page'      => $page,
				'per_page'  => $per_page,
				'max_pages' => 0,
				'has_more'  => false,
			),
		);
	}

	// ──────────────────────────────────────────────
	//  ENDPOINT: BUSINESS SINGLE
	// ──────────────────────────────────────────────

	/**
	 * GET /wp-json/babel/v1/business/{id}
	 *
	 * Retorna el perfil completo de un negocio con todos sus metadatos.
	 * JSON limpio sin HTML, sin shortcodes, sin dependencias de tema.
	 *
	 * @param \WP_REST_Request $request Petición REST con parámetro 'id'.
	 * @return \WP_REST_Response|\WP_Error Perfil del negocio o error 404.
	 */
	public function handle_business( \WP_REST_Request $request ) {
		$rate = $this->check_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$post_id = (int) $request->get_param( 'id' );

		$cache_k = $this->cache_key( 'business', array( 'id' => $post_id ) );
		$result  = $this->cached_get( $cache_k, function() use ( $post_id ) {
			return $this->get_business_profile( $post_id );
		}, self::CACHE_TTL );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $result,
		) );
	}

	/**
	 * Construye el perfil completo de un negocio.
	 *
	 * @param int $post_id ID del post.
	 * @return array|\WP_Error Perfil o error si no existe.
	 */
	private function get_business_profile( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'babel_business' || $post->post_status !== 'publish' ) {
			return new \WP_Error(
				'business_not_found',
				__( 'Negocio no encontrado.', 'babel-directory' ),
				array( 'status' => 404 )
			);
		}

		global $wpdb;
		$table_index = $wpdb->prefix . 'bd_search_index';
		$index_row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_index} WHERE post_id = %d", $post_id ),
			ARRAY_A
		);

		// Datos base del post (sin HTML).
		$profile = array(
			'id'          => $post_id,
			'name'        => get_the_title( $post ),
			'slug'        => $post->post_name,
			'description' => wp_strip_all_tags( $post->post_content ),
			'excerpt'     => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'url'         => get_permalink( $post ),
			'created_at'  => $post->post_date,
			'updated_at'  => $post->post_modified,
		);

		// Imagen destacada.
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$profile['image'] = array(
				'id'    => (int) $thumb_id,
				'thumb' => wp_get_attachment_image_url( $thumb_id, 'thumbnail' ),
				'medium'=> wp_get_attachment_image_url( $thumb_id, 'medium' ),
				'large' => wp_get_attachment_image_url( $thumb_id, 'large' ),
				'full'  => wp_get_attachment_image_url( $thumb_id, 'full' ),
			);
		} else {
			$profile['image'] = null;
		}

		// Galería.
		$gallery_ids = get_post_meta( $post_id, '_babel_gallery', true );
		$profile['gallery'] = array();
		if ( ! empty( $gallery_ids ) ) {
			$ids = array_filter( array_map( 'absint', explode( ',', $gallery_ids ) ) );
			foreach ( $ids as $gid ) {
				$url = wp_get_attachment_image_url( $gid, 'large' );
				if ( $url ) {
					$profile['gallery'][] = array(
						'id'   => $gid,
						'url'  => $url,
						'thumb'=> wp_get_attachment_image_url( $gid, 'thumbnail' ),
					);
				}
			}
		}

		// Metadatos de contacto y ubicación.
		$meta_fields = array(
			'phone'     => '_babel_phone',
			'whatsapp'  => '_babel_whatsapp',
			'email'     => '_babel_email',
			'address'   => '_babel_address',
			'maps_url'  => '_babel_maps',
			'gmaps_url' => '_babel_gmaps',
			'website'   => '_babel_website',
			'instagram' => '_babel_instagram',
			'facebook'  => '_babel_facebook',
			'linkedin'  => '_babel_linkedin',
			'hours'     => '_babel_hours',
			'price_range' => '_babel_price_range',
			'latitude'  => '_babel_latitude',
			'longitude' => '_babel_longitude',
		);

		$profile['contact'] = array();
		foreach ( $meta_fields as $key => $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );
			if ( $value !== '' && $value !== false ) {
				$profile['contact'][ $key ] = $value;
			}
		}

		// Coordenadas desde el índice (más fiable).
		if ( $index_row ) {
			$profile['contact']['latitude']  = isset( $index_row['latitude'] ) ? (float) $index_row['latitude'] : null;
			$profile['contact']['longitude'] = isset( $index_row['longitude'] ) ? (float) $index_row['longitude'] : null;
		}

		// Flags.
		$profile['is_verified'] = (bool) get_post_meta( $post_id, '_babel_is_verified', true );
		$profile['is_featured'] = (bool) get_post_meta( $post_id, '_babel_is_featured', true );
		$profile['is_institution'] = (bool) get_post_meta( $post_id, '_babel_is_institution', true );

		// Rating.
		$rating_avg   = get_post_meta( $post_id, '_babel_rating_avg', true );
		$rating_count = get_post_meta( $post_id, '_babel_rating_count', true );
		$profile['rating'] = array(
			'average' => ( $rating_avg !== '' ) ? round( (float) $rating_avg, 2 ) : 0,
			'count'   => ( $rating_count !== '' ) ? (int) $rating_count : 0,
		);

		// Tags.
		$tags = get_post_meta( $post_id, '_babel_biz_tags', true );
		$profile['tags'] = ! empty( $tags ) ? array_map( 'trim', explode( ',', $tags ) ) : array();

		// Categorías.
		$categories = wp_get_post_terms( $post_id, 'babel_category', array( 'fields' => 'all' ) );
		$profile['categories'] = array();
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				$profile['categories'][] = array(
					'id'    => (int) $cat->term_id,
					'name'  => $cat->name,
					'slug'  => $cat->slug,
					'parent'=> (int) $cat->parent,
				);
			}
		}

		// Regiones.
		$regions = wp_get_post_terms( $post_id, 'babel_region', array( 'fields' => 'all' ) );
		$profile['regions'] = array();
		if ( ! empty( $regions ) && ! is_wp_error( $regions ) ) {
			foreach ( $regions as $reg ) {
				$profile['regions'][] = array(
					'id'    => (int) $reg->term_id,
					'name'  => $reg->name,
					'slug'  => $reg->slug,
					'parent'=> (int) $reg->parent,
				);
			}
		}

		return $profile;
	}

	// ──────────────────────────────────────────────
	//  ENDPOINT: REGIONS
	// ──────────────────────────────────────────────

	/**
	 * GET /wp-json/babel/v1/regions
	 *
	 * Lista regiones/comunas con conteo de negocios publicados.
	 * Soporta filtrar por región padre (parent).
	 *
	 * Parámetros:
	 *   - parent (int) ID de la región padre (0 = raíz).
	 *
	 * @param \WP_REST_Request $request Petición REST.
	 * @return \WP_REST\Response Lista de regiones con conteos.
	 */
	public function handle_regions( \WP_REST_Request $request ) {
		$rate = $this->check_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$parent  = (int) $request->get_param( 'parent' );
		$params  = array( 'parent' => $parent );
		$cache_k = $this->cache_key( 'regions', $params );

		$result = $this->cached_get( $cache_k, function() use ( $parent ) {
			return $this->get_regions_with_counts( $parent );
		}, self::CACHE_TTL );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $result,
		) );
	}

	/**
	 * Obtiene regiones con el conteo de negocios asociados.
	 *
	 * @param int $parent_id ID del término padre (0 para raíz).
	 * @return array Lista de regiones con conteos.
	 */
	private function get_regions_with_counts( $parent_id ) {
		global $wpdb;

		$table_index = $wpdb->prefix . 'bd_search_index';

		$terms = get_terms( array(
			'taxonomy'   => 'babel_region',
			'hide_empty' => false,
			'parent'     => $parent_id,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$regions = array();
		foreach ( $terms as $term ) {
			// Conteo de negocios en esta región.
			$count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(DISTINCT idx.post_id)
				FROM {$table_index} idx
				INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID
				WHERE idx.region_id = %d AND p.post_status = 'publish'",
				$term->term_id
			) );

			// Conteo de hijos.
			$children = get_terms( array(
				'taxonomy'   => 'babel_region',
				'hide_empty' => false,
				'parent'     => $term->term_id,
				'fields'     => 'count',
			) );

			$regions[] = array(
				'id'            => (int) $term->term_id,
				'name'          => $term->name,
				'slug'          => $term->slug,
				'description'   => wp_strip_all_tags( $term->description ),
				'parent'        => (int) $term->parent,
				'business_count'=> $count,
				'children_count'=> is_wp_error( $children ) ? 0 : (int) $children,
			);
		}

		// Ordenar por conteo descendente.
		usort( $regions, function( $a, $b ) {
			return $b['business_count'] - $a['business_count'];
		} );

		return $regions;
	}

	// ──────────────────────────────────────────────
	//  ENDPOINT: CATEGORIES
	// ──────────────────────────────────────────────

	/**
	 * GET /wp-json/babel/v1/categories
	 *
	 * Lista categorías de negocio con conteo de negocios publicados.
	 * Soporta filtrar por categoría padre (parent).
	 *
	 * Parámetros:
	 *   - parent (int) ID de la categoría padre (0 = raíz).
	 *
	 * @param \WP_REST_Request $request Petición REST.
	 * @return \WP_REST_Response Lista de categorías con conteos.
	 */
	public function handle_categories( \WP_REST_Request $request ) {
		$rate = $this->check_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$parent  = (int) $request->get_param( 'parent' );
		$params  = array( 'parent' => $parent );
		$cache_k = $this->cache_key( 'categories', $params );

		$result = $this->cached_get( $cache_k, function() use ( $parent ) {
			return $this->get_categories_with_counts( $parent );
		}, self::CACHE_TTL );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $result,
		) );
	}

	/**
	 * Obtiene categorías con el conteo de negocios asociados.
	 *
	 * @param int $parent_id ID del término padre (0 para raíz).
	 * @return array Lista de categorías con conteos.
	 */
	private function get_categories_with_counts( $parent_id ) {
		global $wpdb;

		$table_index = $wpdb->prefix . 'bd_search_index';

		$terms = get_terms( array(
			'taxonomy'   => 'babel_category',
			'hide_empty' => false,
			'parent'     => $parent_id,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$categories = array();
		foreach ( $terms as $term ) {
			$count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(DISTINCT idx.post_id)
				FROM {$table_index} idx
				INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID
				WHERE idx.category_id = %d AND p.post_status = 'publish'",
				$term->term_id
			) );

			$children = get_terms( array(
				'taxonomy'   => 'babel_category',
				'hide_empty' => false,
				'parent'     => $term->term_id,
				'fields'     => 'count',
			) );

			$categories[] = array(
				'id'            => (int) $term->term_id,
				'name'          => $term->name,
				'slug'          => $term->slug,
				'description'   => wp_strip_all_tags( $term->description ),
				'parent'        => (int) $term->parent,
				'business_count'=> $count,
				'children_count'=> is_wp_error( $children ) ? 0 : (int) $children,
			);
		}

		// Ordenar por conteo descendente.
		usort( $categories, function( $a, $b ) {
			return $b['business_count'] - $a['business_count'];
		} );

		return $categories;
	}

	// ──────────────────────────────────────────────
	//  ENDPOINT: SUGGESTIONS
	// ──────────────────────────────────────────────

	/**
	 * GET /wp-json/babel/v1/suggestions
	 *
	 * Endpoint rápido de autocompletado predictivo.
	 * Busca coincidencias en categorías, regiones y títulos de negocio.
	 *
	 * Parámetros:
	 *   - q (string) Término de búsqueda (mínimo 2 caracteres).
	 *
	 * @param \WP_REST_Request $request Petición REST.
	 * @return \WP_REST_Response Lista de sugerencias.
	 */
	public function handle_suggestions( \WP_REST_Request $request ) {
		$rate = $this->check_rate_limit();
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$q = sanitize_text_field( $request->get_param( 'q' ) );

		if ( empty( $q ) || strlen( $q ) < 2 ) {
			return rest_ensure_response( array(
				'success' => true,
				'data'    => array(),
			) );
		}

		$cache_k = $this->cache_key( 'suggestions', array( 'q' => $q ) );
		$result  = $this->cached_get( $cache_k, function() use ( $q ) {
			return $this->execute_suggestions( $q );
		}, 900 ); // 15 min cache para sugerencias.

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $result,
		) );
	}

	/**
	 * Ejecuta la consulta de sugerencias.
	 *
	 * @param string $q Término de búsqueda.
	 * @return array Lista de sugerencias.
	 */
	private function execute_suggestions( $q ) {
		global $wpdb;

		$like_q   = '%' . $wpdb->esc_like( $q ) . '%';
		$results  = array();

		// 1. Categorías (prioridad alta).
		$cats = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.name AS label, 'category' AS type, t.slug AS value
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.taxonomy = 'babel_category' AND t.name LIKE %s
			LIMIT 3",
			$like_q
		), ARRAY_A );

		if ( $cats ) {
			$results = array_merge( $results, $cats );
		}

		// 2. Regiones.
		$regs = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.name AS label, 'region' AS type, t.slug AS value
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			WHERE tt.taxonomy = 'babel_region' AND t.name LIKE %s
			LIMIT 2",
			$like_q
		), ARRAY_A );

		if ( $regs ) {
			$results = array_merge( $results, $regs );
		}

		// 3. Negocios.
		$bizs = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_title AS label, 'business' AS type, post_title AS value
			FROM {$wpdb->posts}
			WHERE post_type = 'babel_business' AND post_status = 'publish' AND post_title LIKE %s
			LIMIT 5",
			$like_q
		), ARRAY_A );

		if ( $bizs ) {
			$results = array_merge( $results, $bizs );
		}

		return $results;
	}

	// ──────────────────────────────────────────────
	//  FORMATEO DE DATOS
	// ──────────────────────────────────────────────

	/**
	 * Formatea un negocio para la respuesta de búsqueda (card resumida).
	 *
	 * Retorna solo los datos necesarios para una tarjeta de listado,
	 * sin HTML, sin shortcodes, sin dependencias visuales.
	 *
	 * @param int $post_id ID del post de negocio.
	 * @return array|null Datos formateados o null si no es válido.
	 */
	private function format_business_card( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return null;
		}

		global $wpdb;
		$table_index = $wpdb->prefix . 'bd_search_index';
		$index       = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_index} WHERE post_id = %d", $post_id ),
			ARRAY_A
		);

		$item = array(
			'id'         => $post_id,
			'name'       => get_the_title( $post ),
			'slug'       => $post->post_name,
			'url'        => get_permalink( $post ),
			'excerpt'    => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'is_verified'=> false,
			'is_featured'=> false,
			'rating'     => array(
				'average' => 0,
				'count'   => 0,
			),
			'image'      => null,
			'categories' => array(),
			'regions'    => array(),
		);

		// Flags desde índice.
		if ( $index ) {
			$item['is_verified'] = (bool) $index['is_verified'];
			$item['is_featured'] = (bool) $index['is_featured'];
			$item['rating']['average'] = isset( $index['rating_avg'] ) ? round( (float) $index['rating_avg'], 2 ) : 0;
		}

		// Rating count desde meta.
		$rating_count = get_post_meta( $post_id, '_babel_rating_count', true );
		if ( $rating_count !== '' ) {
			$item['rating']['count'] = (int) $rating_count;
		}

		// Imagen destacada.
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$item['image'] = array(
				'thumb' => wp_get_attachment_image_url( $thumb_id, 'thumbnail' ),
				'medium'=> wp_get_attachment_image_url( $thumb_id, 'medium' ),
				'large' => wp_get_attachment_image_url( $thumb_id, 'large' ),
			);
		}

		// Categorías.
		$categories = wp_get_post_terms( $post_id, 'babel_category', array( 'fields' => 'all' ) );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				$item['categories'][] = array(
					'id'   => (int) $cat->term_id,
					'name' => $cat->name,
					'slug' => $cat->slug,
				);
			}
		}

		// Regiones.
		$regions = wp_get_post_terms( $post_id, 'babel_region', array( 'fields' => 'all' ) );
		if ( ! empty( $regions ) && ! is_wp_error( $regions ) ) {
			foreach ( $regions as $reg ) {
				$item['regions'][] = array(
					'id'   => (int) $reg->term_id,
					'name' => $reg->name,
					'slug' => $reg->slug,
				);
			}
		}

		return $item;
	}
}
