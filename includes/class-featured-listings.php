<?php
/**
 * Featured Listings — Sistema de Listados Patrocinados
 *
 * Maneja la lógica de expiración, compra, cron y helpers para el sistema
 * de featured listings (patrocinados por tiempo limitado).
 *
 * @package Babel_Directory
 * @subpackage Featured_Listings
 * @since 8.3.0
 */

namespace Babel\Directory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Featured_Listings {

	/**
	 * Duraciones permitidas en días.
	 *
	 * @var array
	 */
	private $allowed_durations = array( 7, 30, 90 );

	/**
	 * SKUs de WooCommerce para featured listings.
	 *
	 * @var array
	 */
	private $featured_skus = array(
		7  => 'BABEL-FEATURED-7D',
		30 => 'BABEL-FEATURED-30D',
		90 => 'BABEL-FEATURED-90D',
	);

	/**
	 * Constructor — registra hooks y cron.
	 */
	public function __construct() {
		// Cron de expiración diaria
		add_action( 'babel_featured_expiration', array( $this, 'expire_featured_listings' ) );
		add_action( 'babel_featured_reminder', array( $this, 'send_expiration_reminders' ) );

		// Hook: cuando se actualiza un negocio, sincronizar featured flag
		add_action( 'update_post_metadata', array( $this, 'maybe_sync_featured_flag' ), 10, 5 );

		// Activar cron events si no están programados
		if ( ! wp_next_scheduled( 'babel_featured_expiration' ) ) {
			wp_schedule_event( time(), 'daily', 'babel_featured_expiration' );
		}
		if ( ! wp_next_scheduled( 'babel_featured_reminder' ) ) {
			wp_schedule_event( time(), 'daily', 'babel_featured_reminder' );
		}
	}

	/**
	 * Calcula la nueva fecha de expiración para un negocio.
	 *
	 * Regla: si ya tiene featured activo, suma los días. Si no, parte desde ahora.
	 *
	 * @param int    $post_id       ID del negocio.
	 * @param int    $duration_days Duración en días (7, 30 o 90).
	 * @return string Fecha ISO 8601 de expiración.
	 */
	public function calculate_featured_expires( $post_id, $duration_days ) {
		$duration_days = absint( $duration_days );
		if ( ! in_array( $duration_days, $this->allowed_durations, true ) ) {
			$duration_days = 30; // Default seguro
		}

		$current_expires = get_post_meta( $post_id, '_babel_featured_expires', true );
		$now             = current_time( 'timestamp' );

		if ( ! empty( $current_expires ) ) {
			$current_ts = strtotime( $current_expires );
			if ( $current_ts > $now ) {
				// Ya está featured y no expuerto: extender desde la fecha actual
				return date( 'Y-m-d H:i:s', strtotime( "+{$duration_days} days", $current_ts ) );
			}
		}

		// No está featured o ya expiró: empezar desde ahora
		return date( 'Y-m-d H:i:s', strtotime( "+{$duration_days} days", $now ) );
	}

	/**
	 * Activa un featured listing para un negocio.
	 *
	 * @param int    $post_id       ID del negocio.
	 * @param int    $duration_days Duración en días.
	 * @param string $sku           SKU de WooCommerce comprado.
	 * @return bool Success.
	 */
	public function activate_featured( $post_id, $duration_days, $sku = '' ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || get_post_type( $post_id ) !== 'babel_business' ) {
			return false;
		}

		// No activar si el negocio no está publicado
		if ( get_post_status( $post_id ) !== 'publish' ) {
			return false;
		}

		$new_expires = $this->calculate_featured_expires( $post_id, $duration_days );

		update_post_meta( $post_id, '_babel_featured_expires', $new_expires );
		update_post_meta( $post_id, '_babel_featured', '1' );
		update_post_meta( $post_id, '_babel_is_featured', '1' );

		if ( ! empty( $sku ) ) {
			update_post_meta( $post_id, '_babel_featured_plan', $sku );
		}
		update_post_meta( $post_id, '_babel_featured_purchased_at', current_time( 'mysql' ) );

		// Sincronizar search index
		do_action( 'save_post_babel_business', $post_id, get_post( $post_id ), true );

		return true;
	}

	/**
	 * Desactiva un featured listing.
	 *
	 * @param int    $post_id    ID del negocio.
	 * @param string $reason     Razón de desactivación ('expired', 'manual', 'downgrade').
	 * @return bool Success.
	 */
	public function deactivate_featured( $post_id, $reason = 'expired' ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return false;
		}

		// No desactivar si es Premium (featured permanente)
		$plan_type = get_post_meta( $post_id, '_babel_plan_type', true );
		if ( $plan_type === 'premium' ) {
			return false;
		}

		update_post_meta( $post_id, '_babel_featured', '0' );
		update_post_meta( $post_id, '_babel_is_featured', '0' );

		// Sincronizar search index
		do_action( 'save_post_babel_business', $post_id, get_post( $post_id ), true );

		/**
		 * Hook para acciones post-desactivación.
		 *
		 * @param int    $post_id ID del negocio.
		 * @param string $reason  Razón.
		 */
		do_action( 'babel_featured_deactivated', $post_id, $reason );

		return true;
	}

	/**
	 * Verifica si un negocio está actualmente featured.
	 *
	 * @param int $post_id ID del negocio.
	 * @return bool
	 */
	public function is_featured( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return false;
		}

		// Premium siempre es featured
		$plan_type = get_post_meta( $post_id, '_babel_plan_type', true );
		if ( $plan_type === 'premium' ) {
			return true;
		}

		$expires = get_post_meta( $post_id, '_babel_featured_expires', true );
		if ( empty( $expires ) ) {
			return false;
		}

		$expires_ts = strtotime( $expires );
		$now_ts     = current_time( 'timestamp' );

		return $expires_ts > $now_ts;
	}

	/**
	 * Retorna información completa del featured status de un negocio.
	 *
	 * @param int $post_id ID del negocio.
	 * @return array
	 */
	public function get_featured_status( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return array();
		}

		$expires    = get_post_meta( $post_id, '_babel_featured_expires', true );
		$plan       = get_post_meta( $post_id, '_babel_featured_plan', true );
		$purchased  = get_post_meta( $post_id, '_babel_featured_purchased_at', true );
		$impressions = (int) get_post_meta( $post_id, '_babel_featured_impressions', true );
		$clicks     = (int) get_post_meta( $post_id, '_babel_featured_clicks', true );
		$is_featured = $this->is_featured( $post_id );

		$days_remaining = 0;
		if ( ! empty( $expires ) && $is_featured ) {
			$expires_ts = strtotime( $expires );
			$now_ts     = current_time( 'timestamp' );
			$days_remaining = max( 0, floor( ( $expires_ts - $now_ts ) / DAY_IN_SECONDS ) );
		}

		return array(
			'is_featured'     => $is_featured,
			'expires'         => $expires,
			'days_remaining'  => $days_remaining,
			'plan'            => $plan,
			'purchased_at'    => $purchased,
			'impressions'     => $impressions,
			'clicks'          => $clicks,
		);
	}

	/**
	 * Cron diario: expira featured listings vencidos.
	 *
	 * Regla: no expirar negocios con plan Premium.
	 */
	public function expire_featured_listings() {
		global $wpdb;

		// Buscar negocios con featured_expires en el pasado y featured activo
		$expired = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm_expires ON p.ID = pm_expires.post_id AND pm_expires.meta_key = %s
				INNER JOIN {$wpdb->postmeta} pm_featured ON p.ID = pm_featured.post_id AND pm_featured.meta_key = %s AND pm_featured.meta_value = '1'
				WHERE p.post_type = 'babel_business'
				AND p.post_status = 'publish'
				AND pm_expires.meta_value < %s",
				'_babel_featured_expires',
				'_babel_featured',
				current_time( 'mysql' )
			)
		);

		if ( empty( $expired ) ) {
			return;
		}

		$expired_count = 0;
		foreach ( $expired as $post_id ) {
			// Verificar que no sea Premium
			$plan_type = get_post_meta( $post_id, '_babel_plan_type', true );
			if ( $plan_type === 'premium' ) {
				continue;
			}

			$this->deactivate_featured( $post_id, 'expired' );
			$expired_count++;

			// Enviar notificación al negocio
			$this->notify_expiration( $post_id );
		}

		if ( $expired_count > 0 ) {
			/**
			 * Log de expiraciones procesadas.
			 */
			do_action( 'babel_featured_expired_batch', $expired_count, $expired );
		}
	}

	/**
	 * Cron diario: envía recordatorios 3 días antes de expirar.
	 */
	public function send_expiration_reminders() {
		global $wpdb;

		$reminder_date = date( 'Y-m-d H:i:s', strtotime( '+3 days', current_time( 'timestamp' ) ) );

		$upcoming = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm_expires ON p.ID = pm_expires.post_id AND pm_expires.meta_key = %s
				WHERE p.post_type = 'babel_business'
				AND p.post_status = 'publish'
				AND pm_expires.meta_value <= %s
				AND pm_expires.meta_value > %s",
				'_babel_featured_expires',
				$reminder_date,
				current_time( 'mysql' )
			)
		);

		if ( empty( $upcoming ) ) {
			return;
		}

		foreach ( $upcoming as $post_id ) {
			// No recordar a Premium
			$plan_type = get_post_meta( $post_id, '_babel_plan_type', true );
			if ( $plan_type === 'premium' ) {
				continue;
			}

			// Verificar que no se envió ya (meta _babel_featured_reminder_sent)
			$reminder_sent = get_post_meta( $post_id, '_babel_featured_reminder_sent', true );
			if ( $reminder_sent === $reminder_date ) {
				continue;
			}

			$this->notify_reminder( $post_id );
			update_post_meta( $post_id, '_babel_featured_reminder_sent', $reminder_date );
		}
	}

	/**
	 * Envía email de expiración al dueño del negocio.
	 *
	 * @param int $post_id ID del negocio.
	 */
	private function notify_expiration( $post_id ) {
		$post   = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: business name */
			__( 'Tu destacado en Soy de Chile ha expirado: %s', 'babel-directory' ),
			$post->post_title
		);

		$message = sprintf(
			/* translators: 1: business name, 2: site name, 3: dashboard URL */
			__( "Hola,\n\nTu listado patrocinado para \"%1\$s\" ha expirado en %2\$s.\n\nPara seguir apareciendo destacado, renueva tu featured listing desde tu panel de control:\n%3\$s\n\n¡No pierdas visibilidad!\n— Equipo Soy de Chile", 'babel-directory' ),
			$post->post_title,
			get_bloginfo( 'name' ),
			home_url( '/mi-cuenta/' )
		);

		wp_mail( $author->user_email, $subject, $message );
	}

	/**
	 * Envía email de recordatorio al dueño del negocio.
	 *
	 * @param int $post_id ID del negocio.
	 */
	private function notify_reminder( $post_id ) {
		$post   = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author ) {
			return;
		}

		$status   = $this->get_featured_status( $post_id );
		$days     = $status['days_remaining'];
		$expires  = $status['expires'];

		$subject = sprintf(
			/* translators: 1: business name, 2: days remaining */
			__( 'Tu destacado expira en %2$d días: %1$s', 'babel-directory' ),
			$post->post_title,
			$days
		);

		$message = sprintf(
			/* translators: 1: business name, 2: expiration date, 3: dashboard URL */
			__( "Hola,\n\nTu listado patrocinado para \"%1\$s\" expira el %2\$s.\n\nRenueva ahora para no perder tu posición destacada:\n%3\$s\n\n— Equipo Soy de Chile", 'babel-directory' ),
			$post->post_title,
			date_i18n( get_option( 'date_format' ), strtotime( $expires ) ),
			home_url( '/mi-cuenta/' )
		);

		wp_mail( $author->user_email, $subject, $message );
	}

	/**
	 * Retorna el SKU correspondiente a una duración.
	 *
	 * @param int $duration_days Días.
	 * @return string SKU o string vacío.
	 */
	public function get_sku_for_duration( $duration_days ) {
		$duration_days = absint( $duration_days );
		return isset( $this->featured_skus[ $duration_days ] ) ? $this->featured_skus[ $duration_days ] : '';
	}

	/**
	 * Retorna las duraciones permitidas.
	 *
	 * @return array
	 */
	public function get_allowed_durations() {
		return $this->allowed_durations;
	}

	/**
	 * Verifica si un negocio puede comprar featured.
	 *
	 * Regla: debe tener plan base activo (no Gratis, no pending).
	 *
	 * @param int $post_id ID del negocio.
	 * @return bool|\WP_Error True si puede, WP_Error si no.
	 */
	public function can_purchase_featured( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return new \WP_Error( 'invalid_business', __( 'Negocio no válido.', 'babel-directory' ) );
		}

		// Premium ya tiene featured permanente
		$plan_type = get_post_meta( $post_id, '_babel_plan_type', true );
		if ( $plan_type === 'premium' ) {
			return new \WP_Error( 'already_premium', __( 'Tu plan Premium ya incluye destacado permanente.', 'babel-directory' ) );
		}

		// Debe estar publicado
		if ( get_post_status( $post_id ) !== 'publish' ) {
			return new \WP_Error( 'not_published', __( 'Tu negocio debe estar publicado para destacar.', 'babel-directory' ) );
		}

		// Debe tener plan base (pro o gratis con negocio publicado)
		// Nota: negocios gratis SÍ pueden comprar featured (barrera baja)
		return true;
	}

	/**
	 * Incrementa contador de impresiones.
	 *
	 * @param int $post_id ID del negocio.
	 */
	public function track_impression( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		if ( ! $this->is_featured( $post_id ) ) {
			return;
		}

		$current = (int) get_post_meta( $post_id, '_babel_featured_impressions', true );
		update_post_meta( $post_id, '_babel_featured_impressions', $current + 1 );
	}

	/**
	 * Incrementa contador de clics.
	 *
	 * @param int $post_id ID del negocio.
	 */
	public function track_click( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		if ( ! $this->is_featured( $post_id ) ) {
			return;
		}

		$current = (int) get_post_meta( $post_id, '_babel_featured_clicks', true );
		update_post_meta( $post_id, '_babel_featured_clicks', $current + 1 );
	}

	/**
	 * Hook: sincronizar featured flag cuando se actualiza metadata.
	 *
	 * @param null   $check      Whether to allow updating metadata.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return null
	 */
	public function maybe_sync_featured_flag( $check, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key !== '_babel_featured_expires' ) {
			return $check;
		}

		if ( get_post_type( $object_id ) !== 'babel_business' ) {
			return $check;
		}

		// Forzar re-sync del search index
		if ( ! empty( $meta_value ) ) {
			$expires_ts = strtotime( $meta_value );
			$now_ts     = current_time( 'timestamp' );

			if ( $expires_ts > $now_ts ) {
				update_post_meta( $object_id, '_babel_featured', '1' );
				update_post_meta( $object_id, '_babel_is_featured', '1' );
			} else {
				// No desactivar aquí si es Premium (el cron lo maneja)
				$plan_type = get_post_meta( $object_id, '_babel_plan_type', true );
				if ( $plan_type !== 'premium' ) {
					update_post_meta( $object_id, '_babel_featured', '0' );
					update_post_meta( $object_id, '_babel_is_featured', '0' );
				}
			}
		}

		return $check;
	}

	/**
	 * Retorna los precios configurados para cada duración.
	 *
	 * Lee de opciones del admin, con defaults si no están configurados.
	 *
	 * @return array Array de duración => precio.
	 */
	public function get_pricing() {
		$defaults = array(
			7  => 4990,
			30 => 9990,
			90 => 19990,
		);

		$pricing = get_option( 'babel_featured_pricing', array() );

		return wp_parse_args( $pricing, $defaults );
	}

	/**
	 * Retorna el precio formateado para mostrar en UI.
	 *
	 * @param int $duration_days Duración en días.
	 * @return string Precio formateado (ej: "$4.990").
	 */
	public function get_formatted_price( $duration_days ) {
		$pricing      = $this->get_pricing();
		$price       = isset( $pricing[ $duration_days ] ) ? $pricing[ $duration_days ] : 0;
		return '$' . number_format( $price, 0, ',', '.' );
	}
}
