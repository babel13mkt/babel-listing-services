# CODE REVIEW — Babel Directory Plugin
**Fecha:** 2026-06-22  
**Archivos revisados:** 14 (4 nuevos, 10 modificados)  
**Estándares:** WordPress Coding Standards, PHP 8.x, PSR-4, BEM CSS

---

## RESUMEN EJECUTO

| Severidad | Cantidad |
|-----------|----------|
| 🔴 CRITICAL | 6 |
| 🟠 WARNING | 14 |
| 🔵 INFO | 11 |

---

## 1. includes/class-cache.php (NUEVO)

### 🟠 WARNING — SQL Injection por interpolación directa de string
**Línea 39-40:** El método `clear_all_transients()` usa interpolación directa en la query SQL:
```php
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_bd\\_%'" );
```
**Problema:** Aunque el prefijo `bd_` es fijo, la forma correcta en WordPress es usar `$wpdb->prepare()`. Además, los backslashes escapados con `\\` pueden no generar el `%` literal correcto en todos los motores MySQL.  
**Recomendación:**
```php
$wpdb->query( $wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
    $wpdb->esc_like( '_transient_bd_' ) . '%'
) );
```

### 🔵 INFO — Falta de prefix consistente en transients
**Línea 24, 31:** Usa `'bd_'` como prefijo, pero `class-magic-link.php` usa `'bd_magic_'` y `class-security.php` usa `'bd_rl_'`. Esto es inconsistente pero funcional. Considerar definir una constante `BD_TRANSIENT_PREFIX`.

### 🔵 INFO — Visibilidad de clase sin `final` ni `abstract`
**Línea 13:** La clase `Cache` no declara `final`. Si no está pensada para herencia, marcar como `final` para prevenir extensión accidental.

---

## 2. includes/class-cli.php (NUEVO)

### 🟠 WARNING — Uso de `class_exists` con string en vez de `::class`
**Línea 27, 50:** Usa `'Babel\\Directory\\Search_Index'` y `'Babel\\Directory\\Cache'` como strings.  
**Recomendación:** Usar `Search_Index::class` y `Cache::class` para mejor análisis estático y refactoring.

### 🟠 WARNING — Backslash escapado en strings de WP-CLI
**Línea 24, 28, 37, 48, 51, 53, 55:** Usa `\\WP_CLI::log(...)`. Dentro de un `if ( defined('WP_CLI') && WP_CLI )` esto es técnicamente correcto, pero el uso de `\` global namespace prefix es redundante cuando ya se está en el namespace global.  
**Recomendación:** Usar `WP_CLI::log(...)` directamente (sin backslash) ya que el archivo está dentro de un namespace pero la condición garantiza que WP-CLI está cargado.

### 🔵 INFO — Falta de tipo de retorno en métodos
**Línea 23, 47:** Los métodos `index_rebuild` y `clear_cache` no declaran `: void`.

---

## 3. includes/class-magic-link.php (NUEVO)

### 🔴 CRITICAL — Token expuesto en URL (GET parameter)
**Línea 45-48:** El token se pasa como query parameter en la URL:
```php
$magic_url = add_query_arg( array(
    'babel_magic_login' => $token,
    'email'             => rawurlencode( $email )
), home_url( '/' ) );
```
**Problema:** Los query parameters se guardan en logs de servidor, historial del navegador, headers de referencia (Referer), y herramientas de analytics. Un token de autenticación NO debe ir en la URL.  
**Recomendación:** Usar un endpoint REST dedicado con el token en el body (POST), o al menos usar `hash_equals` con un token hasheado en el transient (guardar `wp_hash($token)` en vez del token plano).

### 🔴 CRITICAL — Username predecible con colisión débil
**Línea 100-104:** 
```php
$username = sanitize_user( current( explode( '@', $email_from_url ) ), true );
if ( username_exists( $username ) ) {
    $username .= rand( 100, 999 );
}
```
**Problema:** `rand(100, 999)` es criptográficamente débil y genera solo 900 valores posibles. Un atacante puede pre-registrar todos los variantes. Además, `current()` en PHP 8.x con `explode` puede comportarse inesperadamente.  
**Recomendación:** Usar `wp_generate_password( 12, false )` como username o `wp_rand( 100000, 999999 )`.

### 🟠 WARNING — `wp_die` sin escaping del mensaje
**Línea 82, 110, 126:** Los mensajes de `wp_die()` son estáticos (seguros), pero en la línea 82 el mensaje incluye datos indirectos. Aceptable, pero considerar usar `esc_html__()` para internacionalización.

### 🟠 WARNING — `verify_magic_link` en hook `init` sin early return
**Línea 75-129:** El método `verify_magic_link()` se ejecuta en CADA carga de página (hook `init`). Aunque tiene un `if` que verifica `$_GET`, esto añade overhead en cada request.  
**Recomendación:** Mover a un endpoint REST dedicado o usar `template_redirect` con condición más específica.

### 🔵 INFO — Falta de rate limiting en solicitud de magic link
**Línea 26-70:** No hay rate limiting en `request_magic_link()`. Un atacante puede abusar del endpoint AJAX para enviar correos masivos.  
**Recomendación:** Integrar con `Security::check_rate_limit()` (ya existe en el plugin).

### 🔵 INFO — `home_url('/')` hardcodeado para redirección
**Línea 122:** `wp_safe_redirect( home_url( '/mi-cuenta/' ) )` — la ruta `/mi-cuenta/` está hardcodeada.  
**Recomendación:** Usar una opción configurable o constante.

---

## 4. includes/class-security.php (NUEVO)

### 🔴 CRITICAL — IP spoofing sin validación
**Línea 51-62:** `get_client_ip()` lee headers HTTP directamente sin sanitizar:
```php
if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
    return $_SERVER['HTTP_CF_CONNECTING_IP'];
}
```
**Problema:** Los headers `HTTP_CF_CONNECTING_IP`, `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR` pueden ser falsificados por el cliente si no se está detrás de Cloudflare. Un atacante puede spoofear su IP para evadir el rate limiting.  
**Recomendación:** Sanitizar con `FILTER_VALIDATE_IP`:
```php
$ip = filter_var( $_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP );
if ( $ip ) return $ip;
```

### 🟠 WARNING — `set_transient` no es atómico para rate limiting
**Línea 36, 44:** El patrón `get_transient` → increment → `set_transient` no es atómico. En tráfico concurrente, dos requests pueden leer el mismo valor y ambos incrementar, excediendo el límite.  
**Recomendación:** Para rate limiting de alta precisión, usar `wp_cache_add()` con atomic increment o un lock.

### 🔵 INFO — Método `__construct` vacío
**Línea 15-17:** Constructor vacío es innecesario y puede eliminarse.

---

## 5. includes/class-admin.php (MODIFICADO)

### 🔴 CRITICAL — XSS en renderizado de SPA
**Línea 431:** 
```php
<input type="checkbox" class="sdc-bulk-select-item" value="<?php echo get_the_ID(); ?>">
```
**Problema:** `get_the_ID()` se imprime sin `esc_attr()`. Aunque `get_the_ID()` retorna un int, la falta de escaping es un patrón peligroso.  
**Recomendación:** `value="<?php echo esc_attr( get_the_ID() ); ?>"`

### 🟠 WARNING — Nonce inconsistency entre endpoints AJAX
**Línea 155, 182, 316, 360:** Se usan dos nonces diferentes: `babel_admin_nonce` y `sdc_quick_action_nonce`. Esto es correcto para separación de permisos, pero el nombre `sdc_` es inconsistente con el prefijo `babel_` del resto del plugin.  
**Recomendación:** Unificar a `babel_` prefix para consistencia.

### 🟠 WARNING — `post_status => 'publish'` hardcodeado en SPA
**Línea 204:** `ajax_save_business()` siempre publica directamente:
```php
'post_status' => 'publish', // Autopublicar desde la SPA del admin
```
**Problema:** Esto es intencional para el admin, pero no hay verificación de que el usuario sea realmente un administrador más allá de `edit_posts`. Un autor podría autopublicar.  
**Recomendación:** Mantener `manage_options` como capability check para autopublicar, o añadir un comentario TODO.

### 🟠 WARNING — Inline CSS excesivo en SPA
**Líneas 410-436, 549, 576, 594, etc.:** Múltiples bloques de `style="..."` inline. Esto viola la separación de concerns y dificulta el mantenimiento.  
**Recomendación:** Mover todo el CSS a `babel-admin.css` y usar clases BEM.

### 🟠 WARNING — JavaScript inline sin `wp_add_inline_script`
**Línea 1228-1286:** El JavaScript de subtabs y copy-to-clipboard está directamente en el HTML.  
**Recomendación:** Mover a un archivo JS separado o usar `wp_add_inline_script()`.

### 🔵 INFO — `register_settings` no registra opciones de reCAPTCHA
**Línea 86-108:** Se añaden secciones y campos de reCAPTCHA pero no se llaman `register_setting()` para `babel_recaptcha_site_key` y `babel_recaptcha_secret_key`. Esto significa que `update_option()` en `ajax_save_settings()` funciona, pero la Settings API no sanitiza automáticamente estos campos.  
**Recomendación:** Añadir `register_setting()` con `sanitize_callback` para estas opciones.

### 🔵 INFO — Falta de `absint` en `post_id` de quick action
**Línea 337:** `$pid = intval( $pid )` — correcto, pero `absint()` es la función WordPress idiomática.

---

## 6. includes/class-assets.php (MODIFICADO)

### 🟠 WARNING — Google Fonts cargado globalmente sin `preconnect`
**Línea 43-55:** Las fuentes de Google se cargan en `register_public_assets()` que se ejecuta en cada page load. Esto añade requests HTTP adicionales en páginas que no usan el plugin.  
**Recomendación:** Cargar fuentes solo cuando se detecte un shortcode activo, o usar `wp_enqueue_style` condicional.

### 🟠 WARNING — CDN externo (unpkg.com) sin SRI
**Línea 60, 67, 39, 40:** Leaflet.js y CSS se cargan desde `unpkg.com` sin Subresource Integrity (SRI).  
**Recomendación:** Bundlear localmente o añadir `integrity` y `crossorigin` attributes.

### 🟠 WARNING — `recaptcha_site_key` expuesto en `wp_localize_script`
**Línea 133:** La site key de reCAPTCHA se expone en el objeto JS global `babel_vars`. Aunque la site key es pública por diseño, esto confunde la superficie de ataque. Aceptable pero documentar.

### 🔵 INFO — `force_enqueue_babel_css` carga CSS globalmente
**Línea 175-192:** El CSS se encola globalmente en todas las páginas. Esto puede causar conflictos con temas.  
**Recomendación:** Considerar cargar solo en páginas con shortcodes del plugin.

---

## 7. includes/class-geolocation.php (MODIFICADO)

### 🔴 CRITICAL — IP sin validación antes de concatenación en URL
**Línea 60:**
```php
$url = 'https://ip-api.com/json/' . $ip . '?lang=es&fields=status,regionName,countryCode';
```
**Problema:** `$ip` viene de `$_SERVER` sin validar con `FILTER_VALIDATE_IP`. Un atacante con control sobre headers HTTP puede inyectar parámetros adicionales en la URL (SSRF).  
**Recomendación:**
```php
if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
    return false;
}
```

### 🟠 WARNING — `setcookie` después de `wp_send_json_success`
**Línea 24, 38:** `setcookie()` se llama después de `wp_send_json_*()` en algunos campos. `setcookie()` requiere que no se haya enviado output. En AJAX esto generalmente funciona, pero es frágil.  
**Recomendación:** Llamar `setcookie()` ANTES de `wp_send_json_*()`.

### 🟠 WARNING — Duplicación de `get_client_ip()` con `class-security.php`
**Línea 45-56:** Este método es prácticamente idéntico a `Security::get_client_ip()`.  
**Recomendación:** Usar `Security::get_client_ip()` directamente para evitar duplicación.

### 🔵 INFO — API key de ip-api.com sin autenticación
**Línea 60:** La API gratuita tiene límite de 45 requests/minuto. Sin key, el sitio puede quedar sin geolocalización en tráfico alto.  
**Recomendación:** Considerar cache agresivo o API con key.

---

## 8. includes/class-metaboxes.php (MODIFICADO)

### 🟠 WARNING — `render_spa_editor` es `static` pero usa `self::render_spa_editor_scripts`
**Línea 2508, 2905:** `render_spa_editor` es `static` pero instancia lógica compleja. Esto mezcla patrones.  
**Recomendación:** O hacer toda la clase estática o usar instancia.

### 🟠 WARNING — `babel_biz_type` select usa `name="babel_biz_type"` pero `$_POST` lee `babel_biz_type`
**Línea 1112:** El `<select>` tiene `name="babel_biz_type"` pero en `save_business_meta()` línea 2444 lee `$_POST['babel_biz_type']`. Esto es correcto, pero el campo usa un nombre sin prefijo `_` lo que puede confundir con meta keys. Aceptable pero inconsistente.

### 🔵 INFO — CSS inline en metaboxes (líneas 395-1010)
**Problema:** ~600 líneas de CSS inline en el HTML del metabox. Esto es difícil de mantener y no se puede cachear.  
**Recomendación:** Mover a `babel-admin.css`.

### 🔵 INFO — `render_ad_banner_meta` permite HTML sin filtrar para usuarios sin `unfiltered_html`
**Línea 3415:** Correctamente usa `wp_kses_post` para usuarios sin `unfiltered_html`. ✅ Buena práctica.

---

## 9. includes/class-payments.php (MODIFICADO)

### 🔴 CRITICAL — Verificación de signature duplicada pero con posible bypass
**Línea 40-57 (`handle_webhook`) y 146-165 (`check_webhook_permission`):** La verificación de signature se hace DOS veces (en `check_webhook_permission` y en `handle_webhook`). Si `check_webhook_permission` retorna `WP_Error`, WordPress retorna 401 ANTES de llegar a `handle_webhook`. Sin embargo, si `check_webhook_permission` retorna `true` pero la signature es inválida en `handle_webhook`, el código retorna 401 manualmente. Esto es redundante pero seguro.  
**Problema real:** Si no hay signature header, `check_webhook_permission` retorna `WP_Error` (401) y el webhook nunca se procesa. Esto es correcto para producción, pero durante testing sin signature, los webhooks legítimos serán rechazados.  
**Recomendación:** Añadir un flag de configuración para modo debug/testing.

### 🟠 WARNING — `error_log` con datos sensibles
**Línea 62:**
```php
error_log( '[Babel Directory Webhook] Payload recibido: ' . print_r( $params, true ) );
```
**Problema:** Los params del webhook pueden contener datos personales del comprador (email, nombre, etc.). Esto viola GDPR y políticas de privacidad.  
**Recomendación:** Solo loggear el ID de la transacción, nunca datos personales. O usar `error_log` condicional solo en modo debug.

### 🔵 INFO — `priceRange` hardcodeado como `$$`
**Línea 579:** `$schema['priceRange'] = '$$';` — Valor por defecto hardcodeado. Aceptable pero documentar.

---

## 10. includes/class-seo.php (MODIFICADO)

### 🟠 WARNING — `og:site_name` hardcodeado
**Línea 120:**
```php
echo '<meta property="og:site_name" content="Soy de Chile" />' . "\n";
```
**Problema:** "Soy de Chile" está hardcodeado. Debería usar `get_bloginfo('name')`.  
**Recomendación:** `echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />';`

### 🟠 WARNING — `og:type` usa `business.business` no estándar
**Línea 241:** `'type' => 'business.business'` — Este no es un valor válido de og:type. Los valores válidos son `website`, `article`, etc.  
**Recomendación:** Cambiar a `'website'` o `'business.business'` → `'business.business'` no existe en la spec de Open Graph. Usar `'website'`.

### 🔵 INFO — `get_seo_data()` duplica lógica de `filter_document_title`
**Línea 54-100 vs 193-309:** La lógica de títulos se duplica entre `filter_document_title()` y `get_seo_data()`.  
**Recomendación:** Extraer a un método privado compartido.

---

## 11. includes/class-submission.php (MODIFICADO)

### 🟠 WARNING — `edit_id` sin nonce verification en formulario
**Línea 202:**
```php
<input type="hidden" name="edit_id" value="<?php echo esc_attr( isset( $_GET['edit_id'] ) ? intval( $_GET['edit_id'] ) : 0 ); ?>">
```
**Problema:** El `edit_id` viene de `$_GET` sin nonce. Aunque `handle_ajax_submission()` verifica ownership del post, un atacante podría engañar a un usuario para que envíe un formulario con un `edit_id` diferente.  
**Recomendación:** Añadir un nonce específico para edición o verificar con `wp_verify_nonce`.

### 🟠 WARNING — `babel_website_url` honeypot sin sanitización del nombre
**Línea 198:** El campo honeypot se llama `babel_website_url` — un nombre que parece legítimo. Los bots avanzados pueden reconocerlo.  
**Recomendación:** Usar un nombre más genérico como `website_url` o `url_field`.

### 🔵 INFO — `render_taxonomy_options` hace queries en cada render
**Línea 508-534:** `get_terms()` se llama cada vez que se renderiza el formulario. En páginas con múltiples formularios, esto es redundante.  
**Recomendación:** Cachear con `wp_cache_get/set` o transients.

### 🔵 INFO — `handle_ajax_submission` es muy largo (~320 líneas)
**Línea 540-865:** El método tiene validación, sanitización, upload de archivos, taxonomías, meta keys, rate limiting, etc.  
**Recomendación:** Extraer a métodos privados separados para cada responsabilidad.

---

## 12. templates/parts/listing-card.php (MODIFICADO)

### 🟠 WARNING — Meta keys usan prefijo `_bd_` (sistema viejo)
**Línea 12-18:** Usa `_bd_direccion`, `_bd_telefono`, `_bd_whatsapp`, etc. en vez de `_babel_address`, `_babel_phone`, etc.  
**Problema:** Inconsistencia con el nuevo sistema de meta keys. Los datos pueden no mostrarse si solo se guardan con el nuevo sistema.  
**Recomendación:** Usar la función helper `babel_meta()` (como en `single-sidebar.php`) para leer con fallback.

### 🟠 WARNING — Función `bd_format_region_name` definida en template
**Línea 33-40:** La función helper se define directamente en el template con `function_exists()` guard.  
**Recomendación:** Mover a un archivo de helpers o a una clase utilitaria.

### 🔵 INFO — Imagen por hardcodeada
**Línea 30:** `BD_URL . 'assets/images/default-hero.jpg'` — Correcto uso de constante, pero la ruta de la imagen por defecto debería ser configurable.

---

## 13. templates/parts/single-hero.php (MODIFICADO)

### 🟠 WARNING — Función `render_stars` definida en template
**Línea 19-38:** La función `render_stars()` se define directamente en el template.  
**Recomendación:** Mover a un helper class o función utilitaria.

### 🟠 WARNING — `render_stars` no escapa el output
**Línea 53:** `<?php echo render_stars( $rating ); ?>` — La función retorna HTML crudo (★, ☆, `<strong>`).  
**Recomendación:** Usar `wp_kses()` para permitir solo `<strong>` y texto, o marcar con `// phpcs:ignore` si es intencional.

### 🔵 INFO — `Sin Categoría` hardcodeado en español
**Línea 10:** `'Sin Categoría'` — Debería usar `__()` para internacionalización.

---

## 14. templates/parts/single-sidebar.php (MODIFICADO)

### 🟠 WARNING — Función `babel_meta` definida en template
**Línea 11-17:** La función helper `babel_meta()` se define en el template. Si múltiples templates la definen, puede causar fatal error por redeclaración.  
**Recomendación:** Mover a un archivo de helpers con `function_exists()` guard o a una clase utilitaria.

### 🟠 WARNING — `date('w')` sin timezone consideration
**Línea 138:** `intval( date( 'w' ) )` usa el timezone del servidor, no necesariamente el de Chile.  
**Recomendación:** Usar `current_time( 'w' )` o `wp_date( 'w' )` para respetar el timezone de WordPress.

### 🔵 INFO — Inline CSS en template
**Línea 76, 91, 99, 104, 110, 117, 140:** Múltiples bloques de `style="..."` inline.  
**Recomendación:** Mover a CSS con clases BEM.

---

## RESUMEN DE HALLAZGOS POR CATEGORÍA

### Seguridad (CRITICAL)
1. **class-magic-link.php:45** — Token de autenticación en URL (GET)
2. **class-magic-link.php:100** — Username colisión débil con `rand()`
3. **class-security.php:52** — IP spoofing sin `FILTER_VALIDATE_IP`
4. **class-geolocation.php:60** — SSRF por IP no validada en URL concatenada
5. **class-payments.php:40** — Verificación de signature redundante pero sin modo debug
6. **class-admin.php:431** — `get_the_ID()` sin `esc_attr()`

### Consistencia de Namespace
- Todos los archivos usan correctamente `namespace Babel\Directory;` ✅
- `class-cli.php` declara la clase dentro de `if ( defined('WP_CLI') && WP_CLI )` — aceptable pero no estándar PSR-4
- No se usa `::class` notation en `class_exists()` calls

### BEM CSS
- **class-metaboxes.php:** Usa BEM correctamente (`.bd-card`, `.bd-card-title`, `.bd-field-group`) ✅
- **class-admin.php:** NO usa BEM — clases como `.sdc-header`, `.sdc-tab-btn`, `.sdc-card` mezcladas con inline styles
- **Templates:** No usan BEM consistentemente — mezcla de `.bd-card`, `.bd-sidebar-card`, `.bd-btn-whatsapp`

### Zero Hardcoding
- **class-seo.php:120** — "Soy de Chile" hardcodeado en `og:site_name`
- **class-magic-link.php:122** — `/mi-cuenta/` hardcodeado
- **class-submission.php:493** — "24-48h" hardcodeado en texto
- **single-hero.php:10** — "Sin Categoría" hardcodeado sin i18n

### PSR-4
- Todos los archivos están en `includes/` con namespace `Babel\Directory` ✅
- Los templates en `templates/parts/` no usan namespace (correcto para templates WordPress) ✅
- No se usa autoloading PSR-4 explícito (depende de WordPress) — Aceptable

---

## RECOMENDACIONES PRIORITARIAS

1. **Inmediato:** Validar IPs con `FILTER_VALIDATE_IP` en `class-security.php` y `class-geolocation.php`
2. **Inmediato:** Mover token de magic link de URL a POST body o usar tokens hasheados
3. **Inmediato:** Escapar `get_the_ID()` en `class-admin.php:431`
4. **Alta:** Unificar prefijo de nonces a `babel_`
5. **Alta:** Mover funciones helper de templates a una clase utilitaria
6. **Media:** Eliminar inline CSS/JS y mover a archivos separados
7. **Media:** Añadir `register_setting()` para opciones de reCAPTCHA
8. **Baja:** Añadir tipos de retorno `: void` a métodos que no retornan
