# Listados Patrocinados (Featured Listings)

> **Versión:** 1.0  
> **Propósito:** Especificación técnica para el sistema de listados patrocinados pagados en Babel Directory.  
> **Regla:** Ningún código PHP/JS se crea o modifica sin este spec aprobado.

---

## 1. IDENTIFICACIÓN

| Campo | Valor |
|-------|-------|
| **Spec ID** | `01-featured-listings` |
| **Nombre** | Listados Patrocinados (Featured Listings) |
| **Autor** | Builder (Hermes Agent) |
| **Fecha** | 2026-06-26 |
| **Estado** | `Para revisión` |
| **Prioridad** | `Alta` |
| **Dependencias** | Ninguna (sistema de pagos WooCommerce existente) |

---

## 2. RESUMEN EJECUTIVO

Actualmente Babel Directory tiene un flag binario `_babel_featured` que los administradores asignan manualmente desde el backend y que solo se activa con el plan WooCommerce "Premium". **No existe un sistema de listados patrocinados como producto comercial.** Los negocios no pueden comprar destacarse por tiempo limitado, no hay auto-expiraciones, y no hay tiers de visibilidad.

Este spec define un sistema completo de **Featured Listings autogestionable**: los negocios pagan por destacarse en el directorio por un período determinado (7, 30 o 90 días), aparecen con prioridad visual en búsquedas y listados, y el sistema maneja automáticamente expiraciones, rotación y renovación. Esto desbloquea una fuente de ingresos recurrente sin depender del plan Premium completo.

**Alignment con el modelo de negocio:**
- **Pymes:** Pueden comprar visibilidad temporal sin comprometerse a un plan completo (barrera de entrada más baja).
- **Municipalidades:** Pueden patrocinar instituciones de su comuna como avisos institucionales masivos.
- **Monetización:** Ingreso directo por slot, con precios escalonados por duración.

---

## 3. MODELO DE NEGOCIO

### 3.1 Propuesta de Valor

**Para el negocio:**
- Aparece antes que la competencia en resultados de búsqueda y listados por categoría/región.
- Destaca visualmente con badge "Patrocinado ⭐", borde dorado y mayor tamaño de tarjeta.
- Puede elegir cuánto tiempo destacarse (7, 30, 90 días).
- Mide el impacto: contador de vistas e impresiones durante el período patrocinado.
- Sin compromiso: no requiere upgrade de plan base. Un negocio con plan "Gratis" puede comprar un featured listing por 7 días.

**Para el directorio:**
- Nuevo flujo de ingresos sin canibalizar los planes WooCommerce existentes.
- Mayor engagement de negocios que rotan su visibilidad.
- Datos de demanda: qué categorías/regiones tienen más patrocinios.

### 3.2 Estrategia de Monetización

**Productos WooCommerce existentes vs. nuevos:**

| Producto SKU | Precio Actual | Propósito |
|-------------|---------------|-----------|
| `BABEL-PRO` | $19.990 CLP | Plan base: WhatsApp, Web, Galería, Horarios, Verificado |
| `BABEL-PREMIUM` | (sin precio fijo) | Todo lo anterior + destacado permanente |

La solución NO requiere nuevo producto WooCommerce. En su lugar usa **WooCommerce como pasarela de checkout** pero con pedidos de un solo ítem virtual (one-time payment, no suscripción) cuyo SKU refleja la duración. Alternativamente, se puede usar el webhook de pagos existente (`class-payments.php`) con un parámetro `product_type=featured` + `duration_days=N`.

**Tiers de precio propuestos (referencia, configurables desde el admin):**

| Duración | Precio Sugerido | SKU WooCommerce |
|----------|----------------|-----------------|
| 7 días | $4.990 CLP | `BABEL-FEATURED-7D` |
| 30 días | $9.990 CLP | `BABEL-FEATURED-30D` |
| 90 días | $19.990 CLP | `BABEL-FEATURED-90D` |

**Regla de negocio clave:** Un negocio puede tener múltiples featured listings simultáneamente (comprar 90 días + renovar antes de que expire = featured continuo). Cada compra suma días al `_babel_featured_expires` existente (no reinicia).

### 3.3 Segmento Objetivo

| Segmento | Need | Comportamiento de compra |
|----------|------|--------------------------|
| Pyme sin plan | Quiere probar visibilidad antes de comprometerse | 7 días (bajo riesgo) |
| Pyme con plan Profesional | Quiere destacar en temporada alta | 30 días, renovable |
| Pyme con plan Premium | Ya está destacado permanentemente | No aplica (mostrar desactivado en UI) |
| Municipalidad | Aviso institucional masivo para una comuna | Compra múltiple de slots para instituciones |

---

## 4. ESTRUCTURA DE DATOS

### 4.1 Meta Keys

**Nuevos Meta Keys:**

| Meta Key | Tipo | CPT | Descripción | Default |
|----------|------|-----|-------------|---------|
| `_babel_featured_expires` | `string` | `babel_business` | Fecha ISO 8601 de expiración del featured listing (vacío = no featured). Se almancena como string porque llega serializado desde REST y se parsea con `strtotime()`. | `''` |
| `_babel_featured_plan` | `string` | `babel_business` | SKU del último featured comprado (`BABEL-FEATURED-7D`, `BABEL-FEATURED-30D`, `BABEL-FEATURED-90D`) | `''` |
| `_babel_featured_purchased_at` | `string` | `babel_business` | Fecha ISO 8601 de la última compra de featured | `''` |
| `_babel_featured_impressions` | `integer` | `babel_business` | Contador de impresiones durante período featured | `0` |
| `_babel_featured_clicks` | `integer` | `babel_business` | Contador de clics desde tarjeta featured | `0` |

**Meta Keys existentes que se modifican:**

| Meta Key | Cambio | Razón |
|----------|--------|-------|
| `_babel_featured` | Se depreca como flag booleano. Ahora es dinámico: `'1'` si `_babel_featured_expires` > now y el negocio tiene plan base publicado. Se mantiene como cache flag para consultas rápidas. | Separar el concepto "es featured" (bool) de "hasta cuándo es featured" (fecha). |
| `_babel_is_featured` | Misma lógica que `_babel_featured`. Mantener sincronizado. | Backward compatibility con REST API, search index y shortcodes. |

### 4.2 Tabla Personalizada (Search Index)

**Modificación a `wp_bd_search_index`:**

```sql
ALTER TABLE wp_bd_search_index 
  ADD COLUMN featured_expires DATETIME DEFAULT NULL AFTER is_featured,
  ADD INDEX idx_featured_expires (featured_expires);
```

Esto permite consultas SQL eficientes como:
```sql
SELECT * FROM wp_bd_search_index 
WHERE is_featured = 1 AND (featured_expires IS NULL OR featured_expires > NOW())
ORDER BY featured_expires ASC, rating_avg DESC;
```

### 4.3 Schema REST API

**GET /wp-json/babel/v1/business/{id} — respuesta ampliada:**

```json
{
  "id": 123,
  "is_featured": true,
  "featured": {
    "expires": "2026-08-26T00:00:00+00:00",
    "plan": "BABEL-FEATURED-30D",
    "purchased_at": "2026-07-27T00:00:00+00:00",
    "impressions": 1542,
    "clicks": 38
  }
}
```

**GET /wp-json/babel/v1/search — filtro nuevo:**

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `featured` | `string` | `'all'` | `'all'` = todos, `'only'` = solo featured, `'exclude'` = excluir featured |

**NUEVO: POST /wp-json/babel/v1/featured/purchase**

```json
{
  "business_id": 123,
  "duration_days": 30,
  "return_url": "https://soydechile.cl/mi-cuenta/"
}
```

Respuesta:
```json
{
  "success": true,
  "checkout_url": "https://soydechile.cl/checkout/?add-to-cart=456&babel_featured=123&babel_duration=30"
}
```

**NUEVO: GET /wp-json/babel/v1/featured/status**

Para consultar desde el Frontend Dashboard del usuario si su negocio es eligible para featured y su estado actual.

---

## 5. FLUJOS DE USUARIO

### 5.1 Flujo Principal — Compra de Featured Listing

```
[1] Negocio logueado → [2] Frontend Dashboard ("Mi Cuenta")
    → [3] Sección "Destacar mi negocio" (solo si plan actual es Gratis o Profesional)
    → [4] Selecciona duración: 7, 30 o 90 días (con precios visibles)
    → [5] Click "Pagar y Destacar"
    → [6] POST /babel/v1/featured/purchase
    → [7] WooCommerce: agrega al carrito el producto virtual correspondiente
    → [8] Redirige a WooCommerce Checkout
    → [9] Pago completado (WebPay/MercadoPago)
    → [10] Webhook → Actualiza _babel_featured_expires (suma días)
              → Sincroniza _babel_featured = '1'
              → Sincroniza search index
    → [11] Redirige de vuelta a Dashboard con mensaje "✅ ¡Tu negocio ahora está destacado!"
```

### 5.2 Flujo Administrativo (Backend — WordPress Admin)

```
[1] Admin va a Negocios → [2] Edita un negocio
    → [3] Metabox "Featured Listing" (nuevo panel dentro del admin)
        - Fecha de expiración actual (solo lectura)
        - Botón "Extender N días" (manual, para cortesía/ajustes)
        - Historial de compras featured
        - Impresiones y clics (solo lectura)
    → [4] Opción global en Panel Babel → Ajustes → Featured Listings
        - Precios por duración (7d/30d/90d)
        - Activar/desactivar sistema
```

### 5.3 Flujo de Pago / Upgrade

```
Usuario → Frontend Dashboard /negocio/{id}/ → Click "Destacar" 
  → Selecciona duración 
  → WooCommerce: crea order con producto virtual BABEL-FEATURED-{N}D
  → Checkout estándar (igual que planes)
  → Order completed hook (woocommerce_order_status_completed)
    → class-woocommerce-integration.php: detecta SKU "BABEL-FEATURED-*"
    → Calcula nueva fecha de expiración (max(current_expires, now) + N días)
    → Actualiza meta keys
    → Sincroniza search index
    → Envía email al negocio confirmando activación
```

### 5.4 Casos Borde

| Caso | Comportamiento Esperado |
|------|------------------------|
| Negocio ya está featured (no expirado) | Al comprar, suma los días al expires actual (acumulativo). No reinicia. |
| Negocio tiene plan Premium | Ocultar opción de featured (ya está destacado permanentemente). Mostrar mensaje "Tu plan Premium ya incluye destacado permanente". |
| Pago fallido o rechazado | No se ejecuta ninguna actualización. El webhook de payments.php solo procesa approved/authorized. |
| Producto WooCommerce no existe | Auto-creación siguiendo patrón de `class-frontend-dashboard.php::auto_create_woocommerce_products()` |
| Expiración nocturna | Cron diario `babel_featured_expiration` revisa todos los `_babel_featured_expires`. Los que están en pasado → `_babel_featured = '0'`, `_babel_is_featured = '0'`, sincronizar search index. |
| Featured listing expira estando el negocio en plan Premium | No afecta: el plan Premium mantiene featured=true independientemente. El cron debe verificar `_babel_plan_type` antes de desmarcar. |
| Usuario no logueado intenta comprar | Redirigir a login (Google Auth / Microsoft Auth / Magic Link existentes). |
| Negocio no tiene plan activo (pending) | No puede comprar featured. Debe completar registro/pago del plan base primero. |
| Compra múltiple simultánea (2 pestañas) | Solo se procesa el webhook que llega primero (idempotente gracias a que suma días en lugar de setear). |
| Precios cambian en admin | Los precios en WooCommerce se actualizan automáticamente si se cambian desde el producto. Los precios mostrados en UI deben leerse de `wc_get_product()` dinámicamente. |

---

## 6. UI / UX

### 6.1 Componentes Visuales

| Shortcode / Componente | Propósito | Atributos | ¿Nuevo o existente? |
|------------------------|-----------|-----------|---------------------|
| `[bd_featured_listings]` | Reemplaza a `[bd_featured_businesses]` con lógica de expiración y rotación. Muestra hasta `limit` negocios featured (priorizando los próximos a expirar para maximizar valor). | `limit="6"`, `region=""`, `category=""`, `rotation="true"` | **Modificado** — reemplaza al existente `[bd_featured_businesses]` |
| Sección "Destacar mi negocio" | Panel en Frontend Dashboard para comprar featured | N/A (integración en renderizado existente) | **Nuevo** — dentro del existing `[babel_frontend_dashboard]` |
| Badge "Patrocinado" flotante | Aparece sobre la tarjeta del negocio cuando está en featured listing | N/A | **Modificado** — cambia texto de "Destacado Premium" a "Patrocinado ⭐" con tooltip "Expira en X días" |
| Metabox "Featured Listing" admin | Panel en edición de negocio (WP Admin) para gestión manual | N/A | **Nuevo** — en `class-metaboxes.php` |
| Página de Settings Featured | Panel Babel → Ajustes | N/A | **Nuevo** — en `class-admin.php` |

### 6.2 Estados de UI

| Estado | Descripción | Visual |
|--------|-------------|--------|
| Vacío (no hay featured) | No hay negocios patrocinados en esta región/categoría | Se oculta la sección completa (mismo comportamiento actual) |
| Carga (loading) | Consultando featured listings por API | Cards skeleton con shimmer dorado |
| Error | Fallo en consulta | Mensaje silencioso "No pudimos cargar los destacados" |
| Sin plan | Negocio sin plan base | Botón "Primero activa tu negocio" → redirige a registro/upgrade |
| Ya featured | Negocio ya está destacado | Badge "✅ Destacado hasta el {fecha}" + botón "Extender" |
| Premium | Negocio con plan Premium | Badge "⭐ Destacado (Plan Premium)" sin botón de compra |

### 6.3 Design Tokens Utilizados

- **Colores:**
  - `--babel-color-secondary-fixed-dim: #e9c349` — Borde dorado de tarjeta featured
  - `--babel-color-secondary-fixed: #ffe088` — Background de badge "Patrocinado"
  - `--babel-color-on-secondary-fixed: #241a00` — Texto sobre badge
  - `accentGold: #ffb703` — Estrella del badge
  - `accentGoldDark: #fb8500` — Gradiente decorativo

- **Sombras:**
  - `cardFeatured: 0 4px 20px rgba(255,183,3,0.06)` — Sombra sutil dorada
  - `premium: 0 20px 40px rgba(0,0,0,0.08)` — Sombra hover de tarjeta featured

- **Radios:** `--babel-radius-lg` (tarjetas), `--babel-radius-md` (badges)

- **Tipografía:** `--babel-font-label` para badges, `--babel-font-display` para títulos

### 6.4 Responsive

| Breakpoint | Comportamiento |
|------------|----------------|
| Desktop (>981px) | Featured section: horizontal carousel de 4 cards o grid de 3-4 columnas. Badge tooltip en hover. |
| Tablet (768-981px) | Grid de 2 columnas. Tooltip en tap. |
| Mobile (<768px) | Grid de 1 columna (full-width cards). Badge siempre visible. Texto "Patrocinado" + icono estrella. |

---

## 7. LÓGICA DE NEGOCIO

### 7.1 Reglas de Negocio

1. **Un featured listing es independiente del plan.** Un negocio con plan Gratis puede comprar featured. Un negocio con plan Profesional también. El plan Premium incluye featured permanente (no necesita comprar).

2. **La expiración se calcula por fecha, no por contador.** No hay límite de vistas. El negocio paga por tiempo, no por impresiones (fase 1). En futura iteración se podría implementar límite de impresiones.

3. **Acumulación de días.** Al comprar un nuevo featured, se suma la duración al `_babel_featured_expires` actual. Si expires > now, se extiende. Si expires < now, se usa `now + N días`.

4. **Orden de prioridad en búsquedas:** Featured primero, ordenados por fecha de expiración ascendente (los que expiran antes aparecen primero, incentiva renovación). El resto de negocios después por rating.

5. **Máximo de slots visibles por página:** 3 featured como máximo antes de resultados orgánicos. Si hay más de 3, rotan usando `ORDER BY featured_expires ASC LIMIT 3` y el resto se mezcla con resultados normales pero manteniendo badge.

6. **Auto-exclusión del plan Premium:** Si `_babel_plan_type === 'premium'`, el sistema trata `_babel_featured_expires` como "permanentemente activo" y nunca expira por cron.

7. **Renovación sin interrupción:** Si el negocio compra antes de que expire, no hay ventana de "no featured". La transición es seamless.

### 7.2 Algoritmos

**Cálculo de nueva fecha de expiración:**

```
function calculate_featured_expires(business_id, duration_days):
    current_expires = get_post_meta(business_id, '_babel_featured_expires')
    now = current_time('mysql')
    
    if current_expires AND strtotime(current_expires) > strtotime(now):
        // Acumular: sumar días a la fecha actual de expiración
        base = current_expires
    else:
        // Empezar desde ahora
        base = now
    
    new_expires = date('Y-m-d H:i:s', strtotime(base + ' +' + duration_days + ' days'))
    return new_expires
```

**Cron de expiración:**

```
function expire_featured_listings():
    businesses = query(
        post_type = 'babel_business',
        post_status = 'publish',
        meta_query = [
            key = '_babel_featured_expires',
            value = current_time('mysql'),
            compare = '<',
            type = 'DATETIME'
        ],
        meta_query = [
            key = '_babel_featured',
            value = '1',
            compare = '='
        ]
    )
    
    for each business in businesses:
        plan_type = get_post_meta(business.id, '_babel_plan_type')
        if plan_type == 'premium':
            continue  // No expirar negocios Premium
        
        update_post_meta(business.id, '_babel_featured', '0')
        update_post_meta(business.id, '_babel_is_featured', '0')
        sync_search_index(business.id)
        notify_business(business.id, "Tu destacado ha expirado. ¡Renueva ahora!")
```

**Ordenamiento de featured en listados:**

```sql
-- En consultas de search/categoría/región:
-- Los featured siempre primero, ordenados por próxima expiración (los que expiran antes primero)
-- Esto maximiza el valor del slot e incentiva renovación
ORDER BY idx.is_featured DESC, 
         CASE WHEN idx.featured_expires IS NOT NULL THEN idx.featured_expires ELSE '9999-12-31' END ASC,
         idx.rating_avg DESC
```

### 7.3 Cache Strategy

| Clave Transient | Duración | Invalidación |
|-----------------|----------|-------------|
| `bd_featured_{region_id}_{category_id}` | 1 hora | Al comprar/expirar featured en esa región |
| `bd_featured_count` | 1 hora | Al comprar/expirar cualquier featured |

**Invalidación:** Hooks en `save_post_babel_business` (ya existe `invalidate_region_transients` en `class-cpt.php`) más un hook específico `babel_featured_updated`.

---

## 8. INTEGRACIONES

### 8.1 WooCommerce

**Nuevos productos (auto-creación en `class-frontend-dashboard.php`):**

| SKU | Nombre | Tipo | Precio (default) |
|-----|--------|------|------------------|
| `BABEL-FEATURED-7D` | "Destacado 7 Días - Soy de Chile" | Simple, Virtual | $4.990 |
| `BABEL-FEATURED-30D` | "Destacado 30 Días - Soy de Chile" | Simple, Virtual | $9.990 |
| `BABEL-FEATURED-90D` | "Destacado 90 Días - Soy de Chile" | Simple, Virtual | $19.990 |

**Hook existente a aprovechar:** `woocommerce_order_status_completed` en `class-woocommerce-integration.php` ya procesa SKUs. Se extiende para detectar SKUs `BABEL-FEATURED-*`.

**Meta de order item:** `babel_featured_business_id` + `babel_featured_duration` (similar a `babel_target_post_id` existente).

### 8.2 Pasarela de Pago

- WebPay y MercadoPago: Sin cambios. El webhook de `class-payments.php` procesa el pago genéricamente y cambia `pending → publish` si aplica. La lógica de featured se maneja en `woocommerce_order_status_completed`.
- Meta keys de pago: `_babel_featured_payment_{order_id}` para auditoría.

### 8.3 Cron / Programación

| Evento | Schedule | Acción |
|--------|----------|--------|
| `babel_featured_expiration` | `wp_schedule_event(daily)` | Revisar todos los `_babel_featured_expires` < now y `_babel_featured = '1'`, desactivar si no son Premium. Enviar email de aviso 3 días antes de expirar. |
| `babel_featured_reminder` | `wp_schedule_event(daily)` | 3 días antes de expirar, enviar email recordatorio al negocio. |

---

## 9. SEGURIDAD

| Aspecto | Implementación |
|---------|---------------|
| Nonces | Nuevo nonce `babel_featured_nonce` para el flujo de compra AJAX |
| Capabilities | `edit_posts` para acceder a featured dashboard; `manage_options` para settings admin |
| Sanitización | `absint()` para business_id, `intval()` para duration_days |
| Rate limiting | Endpoint REST de featured usa el rate limiting existente (60 req/min) |
| Webhook signatures | HMAC existente es suficiente; no se agrega nuevo webhook |
| SQL Injection | Uso de `$wpdb->prepare()` en consultas a search index |

---

## 10. MIGRACIÓN Y BACKWARD COMPATIBILITY

- **Meta key `_babel_featured`**: Se mantiene como flag de cache. El setter ahora es dinámico basado en `_babel_featured_expires`. Script de migración única: los negocios existentes con `_babel_featured = '1'` reciben `_babel_featured_expires = '9999-12-31 23:59:59'` (featured permanente legacy).

- **Shortcode `[bd_featured_businesses]`**: Se mantiene como alias de `[bd_featured_listings]` para no romper layouts de Divi existentes.

- **REST API `/babel/v1/search`**: No cambia estructura de respuesta. El campo `is_featured` sigue siendo booleano. Se agrega campo `featured` (objeto) solo si `_expand=featured`.

- **Search Index**: La columna `featured_expires` es nullable y opcional. Consultas existentes que no la usan siguen funcionando.

---

## 11. CRITERIOS DE ACEPTACIÓN

### 11.1 Funcionales (QA)

| ID | Criterio | Cómo se prueba |
|----|----------|----------------|
| AC-01 | Negocio sin plan base NO puede comprar featured | Frontend Dashboard: ocultar sección. POST a endpoint: devolver error 403. |
| AC-02 | Negocio con plan Gratis PUEDE comprar featured 7/30/90 días | Dashboard muestra opciones con precios. Checkout llega a WooCommerce. |
| AC-03 | Negocio con plan Profesional PUEDE comprar featured | Mismo flujo que AC-02. |
| AC-04 | Negocio con plan Premium NO puede comprar featured (ya incluido) | Dashboard muestra "Tu plan Premium ya incluye destacado permanente". |
| AC-05 | Compra de 7 días: `_babel_featured_expires` = now + 7 días | Verificar en base de datos inmediatamente después de webhook. |
| AC-06 | Compra de 30 días cuando ya hay featured activo: se suman los días | expires_anterior + 30 días. Verificar en DB. |
| AC-07 | Cron diario desactiva featured expirados | Forzar cron, verificar `_babel_featured='0'` y search index sync. |
| AC-08 | Cron NO desactiva featured de negocios Premium aunque hayan expirado | Verificar que `_babel_featured` sigue `'1'` para Premium. |
| AC-09 | Tarjeta featured muestra badge "Patrocinado ⭐" + fecha de expiración en tooltip | Inspección visual. |
| AC-10 | Featured aparecen primero en resultados de búsqueda | Consultar `/babel/v1/search`, verificar `ORDER BY is_featured DESC`. |
| AC-11 | Máximo 3 featured antes de resultados orgánicos | Verificar en listado con 5+ negocios featured. |
| AC-12 | Al expirar, el negocio recibe email de notificación | Verificar `wp_mail` log o mailtrap. |
| AC-13 | Email recordatorio 3 días antes de expirar | Configurar expires a +3 días, forzar cron, verificar envío. |
| AC-14 | Auto-creación de productos WooCommerce si no existen | Desinstalar productos, recargar admin, verificar que se crean. |
| AC-15 | Los precios en UI se leen dinámicamente de WooCommerce | Cambiar precio del producto en WC → UI refleja cambio. |

### 11.2 No Funcionales

- **Rendimiento:** Consulta de featured listings < 100ms (usando search index + columna featured_expires indexada).
- **Compatibilidad:** Shortcode funciona en Divi 5, Twenty Twenty-Four, y REST API headless.
- **Accesibilidad:** Badge "Patrocinado" con `aria-label`, tooltips accesibles, contraste suficiente en badge dorado (gold on dark bg cumple WCAG AA).
- **Idiomas:** Todos los textos nuevos con `__()`, `esc_html__()` (español como locale default).
- **No breaking changes:** Negocios existentes con featured manual no pierden su estado.

### 11.3 Seguridad

| ID | Criterio |
|----|----------|
| SEC-01 | Endpoint POST `/babel/v1/featured/purchase` requiere autenticación vía nonce + cookie de WP |
| SEC-02 | `absint()` en business_id, `intval()` en duration_days validados contra rangos permitidos [7, 30, 90] |
| SEC-03 | Solo el dueño del negocio o un admin puede comprar featured para ese negocio |
| SEC-04 | Precios de WooCommerce no son editables desde el frontend (solo desde admin de WC) |

---

## 12. ARCHIVOS A MODIFICAR / CREAR

### Nuevos Archivos
- `includes/class-featured-listings.php` — Lógica principal: expiración, compra, cron, helpers
- `assets/css/babel-featured.css` — Estilos específicos para featured (si no se integran en babel-public.css)
- `assets/js/babel-featured.js` — Interacciones frontend de featured (contador regresivo, tooltip, compra)

### Archivos a Modificar
- `babel-directory.php` — Registrar `Babel\Directory\Featured_Listings` en `init_components()`
- `includes/class-cpt.php` — Registrar nuevos meta keys `_babel_featured_expires`, `_babel_featured_plan`, etc. en `register_meta_fields()`
- `includes/class-shortcodes.php` — Modificar `render_featured_businesses()` → `render_featured_listings()` con nueva lógica + alias backward compat
- `includes/class-search-index.php` — Agregar `featured_expires` a `create_table()`, `sync_business_to_index()`, `sync_institution_to_index()`
- `includes/class-woocommerce-integration.php` — Extender `process_plan_activation()` para detectar SKUs `BABEL-FEATURED-*`
- `includes/class-frontend-dashboard.php` — Agregar sección "Destacar mi negocio" + auto-crear productos featured en `auto_create_woocommerce_products()`
- `includes/class-metaboxes.php` — Agregar metabox "Featured Listing" en admin con estado, historial, acciones manuales
- `includes/class-admin.php` — Agregar sección de configuración de precios desde Panel Babel
- `includes/api/class-rest-endpoints.php` — Nuevos endpoints `/featured/purchase` y `/featured/status`
- `assets/css/babel-public.css` — Nuevas clases CSS:
  - `.babel-biz-card--sponsored` (variante de tarjeta featured con timer)
  - `.bd-featured-badge--sponsored` (badge con "Patrocinado" + tooltip)
  - `.bd-featured-timer` (contador de expiración)

### Archivos Sin Cambios (confirmar)
- `includes/class-payments.php` — No se modifica. El webhook genérico sigue funcionando igual.
- `includes/class-ajax.php` — No se modifica.
- `includes/class-assets.php` — Se enqueuean nuevos assets desde `class-featured-listings.php` usando hooks existentes.
- `includes/class-reviews.php` — No se modifica.
- `templates/*.php` — No se modifican. Los cambios visuales son vía shortcodes.

---

## 13. NOTAS TÉCNICAS

### 13.1 Patrones a Seguir

- **Namespace:** `Babel\Directory` (PSR-4, autoloader existente)
- **Singleton:** No necesario para esta clase (no hay ganancia en cacheo interno)
- **Hooks:** `add_action`/`add_filter` en constructor
- **REST API:** Preferir `/wp-json/babel/v1/` sobre `admin-ajax.php`
- **Vanilla JS:** Sin jQuery
- **CSS BEM:** Clases como `.babel-biz-card--featured`, `.babel-biz-card__badge--sponsored`
- **Textos:** `__('Texto', 'babel-directory')` para traducción
- **Auto-creación de productos:** Seguir patrón de `auto_create_woocommerce_products()` en `class-frontend-dashboard.php`

### 13.2 Pitfalls Conocidos

- **Meta key lookup vs. search index:** El flag `_babel_featured` puede quedar desincronizado con `_babel_featured_expires` si el cron falla. Siempre consultar `featured_expires` directamente en search index para queries de listado.
- **Timezones:** Usar `current_time('mysql')` (hora del sitio WordPress) para todas las comparaciones, no `date()` de PHP (hora del servidor).
- **Transients en featured:** Si se usa transient cache, la expiración en tiempo real (cron) y la UI pueden diferir hasta 1 hora. Aceptable para fase 1.
- **WooCommerce auto-create race condition:** Si dos admins cargan la página de settings simultáneamente, se intenta crear el producto dos veces. `wc_get_product_id_by_sku()` previene duplicados.
- **Premium + featured overlap:** La regla "Premium = featured permanente" debe verificarse en TODOS los puntos de consulta (search, shortcode, cron, API), no solo en compra.

### 13.3 Decisiones de Diseño

- **¿Por qué usar `_babel_featured_expires` en lugar de `_babel_featured = timestamp`?** Porque queremos mantener `_babel_featured` como flag booleano para backward compatibility con cientos de líneas de código existente que hacen `get_post_meta($id, '_babel_featured', true)`. La fecha de expiración es un campo adicional.
- **¿Por qué no Subscription de WooCommerce?** Por simplicidad. Fase 1 es one-time payment por duración fija. Las renovaciones son compras separadas. En fase 2 se puede migrar a suscripción recurrente si hay demanda.
- **¿Por qué featured_expires en search index y no solo en postmeta?** Porque todas las consultas de listado pasan por `wp_bd_search_index`. Tener la fecha ahí evita JOINs costosos con `wp_postmeta`. Es una columna indexada que hace las queries `ORDER BY featured_expires` muy eficientes.

---

## 14. CHANGELOG

| Fecha | Versión | Cambio |
|-------|---------|--------|
| 2026-06-26 | 1.0 | Creación inicial del spec |

---

*Este spec sigue la plantilla `specs/TEMPLATE.md`. Pendiente de aprobación por Andy antes de implementar.*