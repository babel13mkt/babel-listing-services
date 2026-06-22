<?php
namespace Babel\Directory;

/**
 * Frontend Registration — Multi-step Business Registration Form
 * v8.2.0 — Shortcode [bd_register_business]
 *
 * 4-step wizard: Business Data → Contact → Location (Leaflet) → Logo/Gallery.
 * Vanilla JS only (no jQuery). BEM CSS. PSR-4. Namespace Babel\Directory.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend_Registration {

	/**
	 * Rendered step count (internal).
	 */
	const TOTAL_STEPS = 4;

	// ──────────────────────────────────────────────────────────────
	// BOOTSTRAP
	// ──────────────────────────────────────────────────────────────

	public function __construct() {
		add_shortcode( 'bd_register_business', array( $this, 'render_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_bd_frontend_register', array( $this, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_bd_frontend_register', array( $this, 'handle_not_logged_in' ) );

		// Notify admin when a business goes pending.
		add_action( 'transition_post_status', array( $this, 'notify_admin_on_pending' ), 10, 3 );
	}

	// ──────────────────────────────────────────────────────────────
	// ASSETS
	// ──────────────────────────────────────────────────────────────

	public function enqueue_assets() {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'bd_register_business' ) ) {
			return;
		}

		// Leaflet (map, step 3).
		wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

		// Plugin public CSS (already registered by Assets class).
		wp_enqueue_style( 'babel-public-css' );

		// Frontend registration script.
		wp_enqueue_script(
			'bd-frontend-registration-js',
			BD_URL . 'assets/js/bd-frontend-registration.js',
			array( 'leaflet-js' ),
			BD_VERSION,
			true
		);

		wp_localize_script( 'bd-frontend-registration-js', 'bd_reg_vars', array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'registration_nonce' => wp_create_nonce( 'bd_registration_nonce' ),
			'is_logged_in'     => is_user_logged_in() ? '1' : '0',
			'current_user'     => $this->get_current_user_data(),
			'strings'          => array(
				'required'       => __( 'Este campo es obligatorio.', 'babel-directory' ),
				'invalid_email'  => __( 'Ingresa un email válido.', 'babel-directory' ),
				'invalid_url'    => __( 'Ingresa una URL válida.', 'babel-directory' ),
				'submit'         => __( 'Enviar registro', 'babel-directory' ),
				'submitting'     => __( 'Enviando…', 'babel-directory' ),
				'success'        => __( '¡Tu negocio fue enviado! Lo revisaremos en 24-48h.', 'babel-directory' ),
				'error'          => __( 'Error de conexión. Intenta nuevamente.', 'babel-directory' ),
				'prev'           => __( 'Anterior', 'babel-directory' ),
				'next'           => __( 'Siguiente', 'babel-directory' ),
				'step_of'        => __( 'Paso', 'babel-directory' ),
				'of'             => __( 'de', 'babel-directory' ),
				'gps_not_supported' => __( 'Tu navegador no soporta geolocalización.', 'babel-directory' ),
				'gps_error'      => __( 'No se pudo obtener tu ubicación.', 'babel-directory' ),
				'max_photos'     => __( 'Máximo 5 fotos en la galería.', 'babel-directory' ),
				'file_too_large' => __( 'La imagen excede el máximo de 5MB.', 'babel-directory' ),
				'invalid_type'   => __( 'Solo se permiten JPG, PNG o WebP.', 'babel-directory' ),
			),
		) );
	}

	private function get_current_user_data() {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$user = wp_get_current_user();
		return array(
			'id'    => $user->ID,
			'name'  => $user->display_name,
			'email' => $user->user_email,
		);
	}

	// ──────────────────────────────────────────────────────────────
	// SHORTCODE OUTPUT
	// ──────────────────────────────────────────────────────────────

	public function render_form() {
		if ( ! is_user_logged_in() ) {
			return $this->render_login_notice();
		}

		ob_start();
		?>
		<div id="bd-registration-wrapper" class="bd-reg">

			<!-- Progress bar -->
			<div class="bd-reg__progress" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="<?php echo self::TOTAL_STEPS; ?>">
				<span class="bd-reg__progress-label">
					<?php esc_html_e( 'Paso', 'babel-directory' ); ?>
					<span class="bd-reg__step-current">1</span>
					<?php esc_html_e( 'de', 'babel-directory' ); ?>
					<?php echo (int) self::TOTAL_STEPS; ?>
				</span>
				<div class="bd-reg__progress-bar">
					<span class="bd-reg__progress-fill" style="width:25%"></span>
				</div>
			</div>

			<!-- Response message -->
			<div id="bd-reg-response" class="bd-reg__response" hidden></div>

			<!-- ═══════════════════════════════════════════
			     MULTI-STEP FORM
			     ═══════════════════════════════════════════ -->
			<form id="bd-reg-form" class="bd-reg__form" enctype="multipart/form-data" novalidate>

				<?php wp_nonce_field( 'bd_registration_nonce', 'bd_registration_nonce_field' ); ?>
				<!-- Honeypot -->
				<div style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
					<input type="text" name="bd_hp_website" tabindex="-1" autocomplete="off">
				</div>

				<!-- ── STEP 1: Business Data ── -->
				<fieldset class="bd-reg__step bd-reg__step--active" data-step="1">
					<legend class="bd-reg__step-title">
						<span class="bd-reg__step-number">1</span>
						<?php esc_html_e( 'Datos del Negocio', 'babel-directory' ); ?>
					</legend>

					<div class="bd-reg__field">
						<label for="bd_business_name" class="bd-reg__label">
							<?php esc_html_e( 'Nombre del Negocio', 'babel-directory' ); ?> <span class="bd-reg__required" aria-label="<?php esc_attr_e( 'Obligatorio', 'babel-directory' ); ?>">*</span>
						</label>
						<input type="text" id="bd_business_name" name="business_name" class="bd-reg__input" required placeholder="<?php esc_attr_e( 'Ej: Pizzería Don Carlos', 'babel-directory' ); ?>">
						<span class="bd-reg__error" hidden></span>
					</div>

					<div class="bd-reg__field">
						<label for="bd_description" class="bd-reg__label"><?php esc_html_e( 'Descripción', 'babel-directory' ); ?></label>
						<textarea id="bd_description" name="description" class="bd-reg__textarea" rows="4" placeholder="<?php esc_attr_e( 'Describe tu negocio, servicios, especialidades…', 'babel-directory' ); ?>"></textarea>
					</div>

					<div class="bd-reg__row">
						<div class="bd-reg__field">
							<label for="bd_business_region" class="bd-reg__label">
								<?php esc_html_e( 'Región', 'babel-directory' ); ?> <span class="bd-reg__required" aria-label="<?php esc_attr_e( 'Obligatorio', 'babel-directory' ); ?>">*</span>
							</label>
							<select id="bd_business_region" name="business_region" class="bd-reg__select" required>
								<option value=""><?php esc_html_e( 'Selecciona región…', 'babel-directory' ); ?></option>
								<?php $this->render_taxonomy_options( 'babel_region' ); ?>
							</select>
							<span class="bd-reg__error" hidden></span>
						</div>

						<div class="bd-reg__field">
							<label for="bd_business_category" class="bd-reg__label">
								<?php esc_html_e( 'Categoría', 'babel-directory' ); ?> <span class="bd-reg__required" aria-label="<?php esc_attr_e( 'Obligatorio', 'babel-directory' ); ?>">*</span>
							</label>
							<select id="bd_business_category" name="business_category" class="bd-reg__select" required>
								<option value=""><?php esc_html_e( 'Selecciona categoría…', 'babel-directory' ); ?></option>
								<?php $this->render_taxonomy_options( 'babel_category' ); ?>
							</select>
							<span class="bd-reg__error" hidden></span>
						</div>
					</div>
				</fieldset>

				<!-- ── STEP 2: Contact ── -->
				<fieldset class="bd-reg__step" data-step="2" hidden>
					<legend class="bd-reg__step-title">
						<span class="bd-reg__step-number">2</span>
						<?php esc_html_e( 'Datos de Contacto', 'babel-directory' ); ?>
					</legend>

					<div class="bd-reg__row">
						<div class="bd-reg__field">
							<label for="bd_email" class="bd-reg__label">
								<?php esc_html_e( 'Email', 'babel-directory' ); ?> <span class="bd-reg__required" aria-label="<?php esc_attr_e( 'Obligatorio', 'babel-directory' ); ?>">*</span>
							</label>
							<input type="email" id="bd_email" name="email" class="bd-reg__input" required placeholder="contacto@minegocio.cl">
							<span class="bd-reg__error" hidden></span>
						</div>

						<div class="bd-reg__field">
							<label for="bd_phone" class="bd-reg__label"><?php esc_html_e( 'Teléfono', 'babel-directory' ); ?></label>
							<input type="tel" id="bd_phone" name="phone" class="bd-reg__input" placeholder="+56 9 1234 5678">
						</div>
					</div>

					<div class="bd-reg__field">
						<label for="bd_address" class="bd-reg__label"><?php esc_html_e( 'Dirección', 'babel-directory' ); ?></label>
						<input type="text" id="bd_address" name="address" class="bd-reg__input" placeholder="<?php esc_attr_e( 'Ej: Av. Providencia 1234, Santiago', 'babel-directory' ); ?>">
					</div>

					<div class="bd-reg__field">
						<label for="bd_website" class="bd-reg__label"><?php esc_html_e( 'Sitio Web', 'babel-directory' ); ?></label>
						<input type="url" id="bd_website" name="website" class="bd-reg__input" placeholder="https://minegocio.cl">
						<span class="bd-reg__error" hidden></span>
					</div>
				</fieldset>

				<!-- ── STEP 3: Location (Leaflet) ── -->
				<fieldset class="bd-reg__step" data-step="3" hidden>
					<legend class="bd-reg__step-title">
						<span class="bd-reg__step-number">3</span>
						<?php esc_html_e( 'Ubicación', 'babel-directory' ); ?>
					</legend>

					<div class="bd-reg__field">
						<label class="bd-reg__label"><?php esc_html_e( 'Marca la ubicación en el mapa', 'babel-directory' ); ?></label>
						<div class="bd-reg__map-actions">
							<button type="button" id="bd-gps-btn" class="bd-reg__btn bd-reg__btn--secondary">
								📍 <?php esc_html_e( 'Usar mi ubicación (GPS)', 'babel-directory' ); ?>
							</button>
						</div>
						<div id="bd-map" class="bd-reg__map"></div>
						<input type="hidden" id="bd_lat" name="babel_lat">
						<input type="hidden" id="bd_lng" name="babel_lng">
					</div>

					<div class="bd-reg__row">
						<div class="bd-reg__field">
							<label for="bd_lat_display" class="bd-reg__label"><?php esc_html_e( 'Latitud', 'babel-directory' ); ?></label>
							<input type="text" id="bd_lat_display" class="bd-reg__input" readonly placeholder="—">
						</div>
						<div class="bd-reg__field">
							<label for="bd_lng_display" class="bd-reg__label"><?php esc_html_e( 'Longitud', 'babel-directory' ); ?></label>
							<input type="text" id="bd_lng_display" class="bd-reg__input" readonly placeholder="—">
						</div>
					</div>
				</fieldset>

				<!-- ── STEP 4: Logo & Gallery ── -->
				<fieldset class="bd-reg__step" data-step="4" hidden>
					<legend class="bd-reg__step-title">
						<span class="bd-reg__step-number">4</span>
						<?php esc_html_e( 'Logo y Galería de Imágenes', 'babel-directory' ); ?>
					</legend>

					<!-- Logo -->
					<div class="bd-reg__field">
						<label class="bd-reg__label">
							<?php esc_html_e( 'Logo / Foto Principal', 'babel-directory' ); ?>
						</label>
						<div id="bd-logo-dropzone" class="bd-reg__dropzone">
							<div class="bd-reg__dropzone-placeholder">
								<span class="bd-reg__dropzone-icon">🖼️</span>
								<p><?php esc_html_e( 'Haz clic aquí o arrastra una imagen', 'babel-directory' ); ?></p>
								<small>JPG, PNG, WebP · <?php esc_html_e( 'Máx.', 'babel-directory' ); ?> 5MB</small>
							</div>
							<img id="bd-logo-preview" class="bd-reg__dropzone-preview" hidden alt="">
							<input type="file" id="bd-logo-input" name="featured_image" accept="image/jpeg,image/png,image/webp" class="bd-reg__dropzone-input">
						</div>
						<span class="bd-reg__error" hidden></span>
					</div>

					<!-- Gallery -->
					<div class="bd-reg__field">
						<label class="bd-reg__label">
							<?php esc_html_e( 'Galería de Imágenes', 'babel-directory' ); ?>
							<span class="bd-reg__hint">— <?php esc_html_e( 'hasta 5 fotos', 'babel-directory' ); ?></span>
						</label>
						<div id="bd-gallery-previews" class="bd-reg__gallery"></div>
						<label for="bd-gallery-input" class="bd-reg__btn bd-reg__btn--outline bd-reg__btn--small bd-reg__gallery-add">
							+ <?php esc_html_e( 'Agregar fotos', 'babel-directory' ); ?>
						</label>
						<input type="file" id="bd-gallery-input" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/webp" class="bd-reg__sr-only">
					</div>
				</fieldset>

				<!-- ── Navigation ── -->
				<div class="bd-reg__nav">
					<button type="button" id="bd-prev-btn" class="bd-reg__btn bd-reg__btn--secondary" hidden>
						<?php esc_html_e( '← Anterior', 'babel-directory' ); ?>
					</button>

					<button type="button" id="bd-next-btn" class="bd-reg__btn bd-reg__btn--primary">
						<?php esc_html_e( 'Siguiente →', 'babel-directory' ); ?>
					</button>

					<button type="submit" id="bd-submit-btn" class="bd-reg__btn bd-reg__btn--primary" hidden>
						<?php esc_html_e( 'Enviar registro', 'babel-directory' ); ?>
					</button>
				</div>

			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Message shown when user is not logged in.
	 */
	private function render_login_notice() {
		return '<div class="bd-reg bd-reg__login-notice">' .
			'<p>' . esc_html__( 'Debes iniciar sesión para registrar un negocio.', 'babel-directory' ) . '</p>' .
			'<p><a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="bd-reg__btn bd-reg__btn--primary">' .
			esc_html__( 'Iniciar sesión', 'babel-directory' ) . '</a></p>' .
		'</div>';
	}

	/**
	 * Render hierarchical <option> elements for a taxonomy.
	 */
	private function render_taxonomy_options( $taxonomy ) {
		$parent_terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'parent'     => 0,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );
		if ( is_wp_error( $parent_terms ) || empty( $parent_terms ) ) {
			return;
		}
		foreach ( $parent_terms as $parent ) {
			echo '<option value="' . esc_attr( $parent->term_id ) . '">' . esc_html( $parent->name ) . '</option>';
			$child_terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'parent'     => $parent->term_id,
				'orderby'    => 'name',
				'order'      => 'ASC',
			) );
			if ( ! is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
				foreach ( $child_terms as $child ) {
					echo '<option value="' . esc_attr( $child->term_id ) . '">&nbsp;&nbsp;&mdash;&nbsp;' . esc_html( $child->name ) . '</option>';
				}
			}
		}
	}

	// ──────────────────────────────────────────────────────────────
	// AJAX HANDLERS
	// ──────────────────────────────────────────────────────────────

	public function handle_not_logged_in() {
		wp_send_json_success( array(
			'success' => false,
			'message' => __( 'Debes iniciar sesión para registrar un negocio.', 'babel-directory' ),
			'code'    => 'not_logged_in',
		) );
	}

	public function handle_submission() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_success( array(
				'success' => false,
				'message' => __( 'Sesión expirada. Inicia sesión nuevamente.', 'babel-directory' ),
				'code'    => 'not_logged_in',
			) );
			return;
		}

		// Nonce verification.
		check_ajax_referer( 'bd_registration_nonce', 'security' );

		// Honeypot check.
		if ( ! empty( $_POST['bd_hp_website'] ) ) {
			wp_send_json_success( array(
				'success' => true,
				'message' => __( '¡Éxito! Tu negocio ha sido enviado.', 'babel-directory' ),
			) );
			return;
		}

		$user_id = get_current_user_id();

		// ── Validate required fields ──
		$business_name = isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';
		if ( empty( $business_name ) ) {
			wp_send_json_success( array(
				'success' => false,
				'message' => __( 'El nombre del negocio es obligatorio.', 'babel-directory' ),
			) );
			return;
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_success( array(
				'success' => false,
				'message' => __( 'El email es obligatorio y debe ser válido.', 'babel-directory' ),
			) );
			return;
		}

		$region_id   = ! empty( $_POST['business_region'] ) ? intval( $_POST['business_region'] ) : 0;
		$category_id = ! empty( $_POST['business_category'] ) ? intval( $_POST['business_category'] ) : 0;

		// ── Validate files ──
		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/webp' );
		$max_size      = 5 * 1024 * 1024; // 5MB.

		if ( ! empty( $_FILES['featured_image']['name'] ) ) {
			$file = $_FILES['featured_image'];
			if ( $file['error'] !== UPLOAD_ERR_OK ) {
				wp_send_json_success( array( 'success' => false, 'message' => __( 'Error al subir el logo.', 'babel-directory' ) ) );
				return;
			}
			if ( $file['size'] > $max_size ) {
				wp_send_json_success( array( 'success' => false, 'message' => __( 'El logo excede el máximo de 5MB.', 'babel-directory' ) ) );
				return;
			}
			$ft = wp_check_filetype( $file['name'] );
			if ( ! in_array( $ft['type'], $allowed_mimes, true ) ) {
				wp_send_json_success( array( 'success' => false, 'message' => __( 'Formato de logo no válido.', 'babel-directory' ) ) );
				return;
			}
		}

		if ( ! empty( $_FILES['gallery_images']['name'][0] ) ) {
			$count = min( count( $_FILES['gallery_images']['name'] ), 5 );
			for ( $i = 0; $i < $count; $i++ ) {
				if ( $_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK ) {
					wp_send_json_success( array( 'success' => false, 'message' => __( 'Error al subir una imagen de la galería.', 'babel-directory' ) ) );
					return;
				}
				if ( $_FILES['gallery_images']['size'][$i] > $max_size ) {
					wp_send_json_success( array( 'success' => false, 'message' => __( 'Una imagen de la galería excede 5MB.', 'babel-directory' ) ) );
					return;
				}
				$ft = wp_check_filetype( $_FILES['gallery_images']['name'][$i] );
				if ( ! in_array( $ft['type'], $allowed_mimes, true ) ) {
					wp_send_json_success( array( 'success' => false, 'message' => __( 'Formato no válido en la galería.', 'babel-directory' ) ) );
					return;
				}
			}
		}

		// ── Rate limiting (3 per day) ──
		$timestamps = get_user_meta( $user_id, '_bd_registration_timestamps', true );
		$timestamps = is_array( $timestamps ) ? $timestamps : array();
		$today_start = strtotime( 'today midnight' );
		$today_count = count( array_filter( $timestamps, function( $ts ) use ( $today_start ) {
			return $ts >= $today_start;
		} ) );
		if ( $today_count >= 3 ) {
			wp_send_json_success( array(
				'success' => false,
				'message' => __( 'Has alcanzado el límite de 3 registros por día.', 'babel-directory' ),
				'code'    => 'rate_limited',
			) );
			return;
		}

		// ── Create post ──
		$post_id = wp_insert_post( array(
			'post_title'   => $business_name,
			'post_content' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			'post_status'  => 'pending',
			'post_type'    => 'babel_business',
			'post_author'  => $user_id,
		), true );

		if ( is_wp_error( $post_id ) || 0 === $post_id ) {
			wp_send_json_success( array(
				'success' => false,
				'message' => __( 'Error al crear el registro. Intenta nuevamente.', 'babel-directory' ),
			) );
			return;
		}

		// ── Taxonomies ──
		if ( $region_id ) {
			wp_set_object_terms( $post_id, $region_id, 'babel_region' );
		}
		if ( $category_id ) {
			wp_set_object_terms( $post_id, $category_id, 'babel_category' );
		}

		// ── Meta fields ──
		$text_metas = array(
			'_babel_email'   => 'email',
			'_babel_phone'   => 'phone',
			'_babel_address' => 'address',
			'_babel_website' => 'website',
		);
		foreach ( $text_metas as $meta_key => $post_key ) {
			if ( ! empty( $_POST[ $post_key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
			}
		}

		if ( ! empty( $_POST['babel_lat'] ) ) {
			update_post_meta( $post_id, '_babel_lat', sanitize_text_field( wp_unslash( $_POST['babel_lat'] ) ) );
		}
		if ( ! empty( $_POST['babel_lng'] ) ) {
			update_post_meta( $post_id, '_babel_lng', sanitize_text_field( wp_unslash( $_POST['babel_lng'] ) ) );
		}

		// ── Featured image ──
		if ( ! empty( $_FILES['featured_image']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$att_id = media_handle_upload( 'featured_image', $post_id );
			if ( ! is_wp_error( $att_id ) ) {
				set_post_thumbnail( $post_id, $att_id );
			}
		}

		// ── Gallery ──
		if ( ! empty( $_FILES['gallery_images']['name'][0] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			$gallery_ids = array();
			$file_count  = min( count( $_FILES['gallery_images']['name'] ), 5 );
			for ( $i = 0; $i < $file_count; $i++ ) {
				if ( empty( $_FILES['gallery_images']['name'][ $i ] ) ) continue;
				$_FILES['_bd_g_single'] = array(
					'name'     => $_FILES['gallery_images']['name'][ $i ],
					'type'     => $_FILES['gallery_images']['type'][ $i ],
					'tmp_name' => $_FILES['gallery_images']['tmp_name'][ $i ],
					'error'    => $_FILES['gallery_images']['error'][ $i ],
					'size'     => $_FILES['gallery_images']['size'][ $i ],
				);
				$gal_id = media_handle_upload( '_bd_g_single', $post_id );
				if ( ! is_wp_error( $gal_id ) ) {
					$gallery_ids[] = $gal_id;
				}
			}
			if ( ! empty( $gallery_ids ) ) {
				update_post_meta( $post_id, '_babel_gallery', implode( ',', $gallery_ids ) );
			}
		}

		// ── Authorship meta ──
		update_post_meta( $post_id, '_bd_registration_submitter', $user_id );
		update_post_meta( $post_id, '_bd_registration_date', current_time( 'mysql' ) );

		// ── Rate-limit tracking ──
		$timestamps[] = time();
		$cutoff       = time() - ( 30 * DAY_IN_SECONDS );
		$timestamps   = array_values( array_filter( $timestamps, function( $ts ) use ( $cutoff ) {
			return $ts >= $cutoff;
		} ) );
		update_user_meta( $user_id, '_bd_registration_timestamps', $timestamps );

		// ── Search index sync ──
		if ( class_exists( 'Babel\\Directory\\Search_Index' ) ) {
			$indexer = new \Babel\Directory\Search_Index();
			$indexer->sync_business_to_index( $post_id, get_post( $post_id ), true );
		}

		// ── Send confirmation email to user ──
		$user = wp_get_current_user();
		$user_email = $user->user_email;
		if ( $user_email ) {
			$subject = sprintf(
				/* translators: %s: business name */
				__( '[%s] Tu negocio "%s" fue recibido', 'babel-directory' ),
				get_bloginfo( 'name' ),
				$business_name
			);
			$message = sprintf(
				"Hola %s,\n\n".
				"Hemos recibido tu registro de negocio \"%s\" exitosamente.\n\n".
				"Nuestro equipo lo revisará en las próximas 24-48 horas.\n".
				"Recibirás otro correo cuando sea aprobado.\n\n".
				"Gracias por registrarte en %s.\n\n".
				"— El equipo de %s",
				$user->display_name,
				$business_name,
				get_bloginfo( 'name' ),
				get_bloginfo( 'name' )
			);
			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
			wp_mail( $user_email, $subject, $message, $headers );
		}

		// ── Send notification to admin ──
		$this->notify_admin_on_pending( 'pending', 'new', get_post( $post_id ) );

		wp_send_json_success( array(
			'success' => true,
			'message' => __( '¡Tu negocio fue enviado con éxito! Nuestro equipo lo revisará en las próximas 24-48 horas. Recibirás un correo de confirmación.', 'babel-directory' ),
			'post_id' => $post_id,
		) );
	}

	/**
	 * Notify admin when a babel_business transitions to 'pending'.
	 */
	public function notify_admin_on_pending( $new_status, $old_status, $post ) {
		if ( 'babel_business' !== $post->post_type ) {
			return;
		}
		if ( 'pending' !== $new_status || 'pending' === $old_status ) {
			return;
		}

		$admin_email = get_option( 'admin_email' );
		if ( ! $admin_email ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: business name */
			__( '[%s] Nuevo negocio pendiente de revisión: %s', 'babel-directory' ),
			get_bloginfo( 'name' ),
			$post->post_title
		);

		$edit_link = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );

		$message = sprintf(
			__( "Un nuevo negocio ha sido registrado y está pendiente de revisión.\n\n".
			"Nombre: %s\n".
			"Autor: %s\n".
			"Región: %s\n".
			"Categoría: %s\n\n".
			"Revisar en el administrador:\n%s\n\n".
			"Para aprobar, cambia el estado de \"Pendiente\" a \"Publicado\".", 'babel-directory' ),
			$post->post_title,
			get_the_author_meta( 'display_name', $post->post_author ),
			$this->get_term_names( $post->ID, 'babel_region' ),
			$this->get_term_names( $post->ID, 'babel_category' ),
			$edit_link
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		wp_mail( $admin_email, $subject, $message, $headers );
	}

	/**
	 */
	private function get_term_names( $post_id, $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '—';
		}
		return implode( ', ', $terms );
	}
}
