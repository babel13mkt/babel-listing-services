# UX Roadmap — soydechile.cl (Babel Directory Plugin)

> **Estado**: Actualizado 2026-05-26  
> **Aprobado por**: Andy  
> **Arquitectura técnica**: PSR-4 + REST API WP7 · Plugin Agnóstico · Sin dependencia de tema  
> **Repo**: github.com/babel13mkt/babel-listing-services · **Rama**: main  
> **Producción**: soydechile.cl · Plugin activo: `babel-directory-master` en AR1  
> **Versión actual**: 7.1.2

---

## ⚠️ REGLAS ABSOLUTAS (Para cualquier IA que lea este documento)

Estas reglas NO son negociables. Deben respetarse en TODA modificación al plugin:

### 1. El Plugin es 100% Agnóstico de Framework
- **PROHIBIDO** usar Tailwind CSS, Bootstrap, Bulma, o cualquier framework CSS externo.
- **PROHIBIDO** añadir `<script src="cdn.tailwindcss.com">` o similares.
- **PROHIBIDO** usar clases utilitarias de Tailwind (`text-gray-500`, `bg-blue-600`, `flex`, `grid`, etc.) en el HTML del plugin.
- **CORRECTO**: Usar clases BEM propias del plugin (`.babel-card`, `.babel-card__title`) + CSS en `babel-public.css`.

### 2. Los Tokens de Diseño se Resuelven en CSS Nativo
- El plugin usa clases semánticas del **Stitch Design System** que se mapean a variables CSS nativas definidas en `babel-public.css`.
- Clases como `.bg-surface`, `.text-primary`, `.font-body-md` son **utility classes propias del plugin** definidas en `babel-public.css`, NO son Tailwind.
- **Si una clase no está definida en `babel-public.css`, no existe.** No asumas que Tailwind la resolverá.

### 3. Zero Hardcoding
- **PROHIBIDO** hardcodear nombres de negocios, imágenes de Unsplash/Google, emails, teléfonos, o cualquier dato estático en el PHP.
- **PROHIBIDO** hardcodear IPs, URLs de servidor, o credenciales.
- **CORRECTO**: Todo dato se lee dinámicamente de `get_post_meta()`, `get_the_*()` o de la configuración de WordPress.

### 4. Estándares WordPress 7 / PHP 8.x
- Usar `wp_json_encode()` en lugar de `json_encode()`.
- Siempre validar con `is_array()` antes de `json_decode()`.
- Usar `esc_html()`, `esc_url()`, `esc_attr()` en TODO output.
- Usar `wp_kses_post()` para contenido HTML permitido.
- Usar `REST API` (`/wp-json/babel/v1/`) en lugar de `admin-ajax.php`.
- **PROHIBIDO** añadir dependencias de jQuery (usar Vanilla JS).

### 5. Nunca Modificar Directamente en el Servidor
```
Flujo obligatorio: Editar local → commit GitHub → rsync a AR1
```
```bash
rsync -avz --no-p --no-g --no-o --exclude='.git' \
  "/ruta/local/babel-directory-master/" \
  ar1:/home/soydechile/public_html/wp-content/plugins/babel-directory-master/

# Post-deploy SIEMPRE:
ssh ar1 'chown -R soydechile:soydechile /home/soydechile/public_html/wp-content/plugins/babel-directory-master/'
```

---

## El Sistema de Diseño: Stitch Design System

El diseño visual del plugin está definido en **Stitch** (herramienta de diseño de Google).  
**Proyecto en Stitch**: "Directorio Babel - Diseño UI" (`projects/13440891265856203657`)  
**Pantalla de referencia principal**: "Perfil Sushi Club - Reestructurado" (`screen: 0ec94fee227b4475b0940bab3ce12968`)

### Design Tokens del Sistema

Los siguientes tokens se resuelven como variables CSS en `babel-public.css`:

```css
/* Paleta de colores — Charcoal/Gold Premium */
--color-primary: #000000;           /* Textos principales, botones CTA */
--color-secondary: #735c00;         /* Acento cálido (dorado oscuro) */
--color-secondary-fixed: #ffe088;   /* Fondo badges destacados */
--color-secondary-fixed-dim: #e9c349; /* Iconos, estrellas, bordes activos */
--color-surface: #f9f9f9;           /* Fondo base de la app */
--color-surface-container-lowest: #ffffff;
--color-surface-container-low: #f3f3f3;
--color-surface-container: #eeeeee;
--color-surface-container-high: #e8e8e8;
--color-on-surface: #1a1c1c;        /* Texto sobre surface */
--color-on-surface-variant: #444748; /* Texto secundario */
--color-outline-variant: #c4c7c7;   /* Bordes sutiles */

/* Tipografía */
/* Display: Playfair Display (serif, premium) */
/* Body: Inter (sans-serif, legible) */
/* Labels: Montserrat (sans-serif, estructurado) */
```

### Clases Semánticas del Plugin (definidas en babel-public.css)

Estas clases son NATIVAS del plugin y deben estar declaradas en `babel-public.css`:

```
.babel-surface         → background: var(--color-surface)
.babel-text-primary    → color: var(--color-primary)
.babel-text-secondary  → color: var(--color-on-surface-variant)
.babel-card            → card base con border + shadow + rounded
.babel-badge           → badge/pill component
.babel-btn-primary     → botón CTA principal
.babel-icon-btn        → botón circular con icono Material Symbol
```

### Fuentes y Recursos
```html
<!-- Fuentes (encoladas por class-assets.php, NO inline) -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600
  &family=Montserrat:wght@500;600;700
  &family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<!-- Material Symbols (iconos, encolados por class-assets.php) -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
```

---

## Visión del Producto

**soydechile.cl** es un superdirectorio de comercios e instituciones de todo Chile.  
Orientado a 3 tipos de usuario:

| Usuario | Contexto | Necesidad |
|---|---|---|
| Chileno local | Sabe dónde está | Busca algo concreto rápido |
| Turista planificando | En casa, antes del viaje | Explorar por región |
| Turista en terreno | Con celular en Chile | Encontrar algo cerca via GPS |

---

## Flujo de Navegación Aprobado

```
PORTADA
├─ [Buscador universal + GPS] → /buscar/?keyword=X&lat=Y&lng=Z
└─ [Grilla 16 Regiones] → /region/{slug}/
       ├─ Buscador pre-filtrado por región
       ├─ Categorías como PILLS horizontales (NO grilla de imágenes)
       ├─ Negocios destacados de la región
       └─ [Clic categoría] → /region/{slug}/categoria/{cat-slug}/
              └─ Lista de negocios + mapa
```

---

## Estado de Implementación

### ✅ Completado

| Componente | Shortcode | Descripción |
|---|---|---|
| Buscador universal | `[babel_radar_search]` | Búsqueda keyword + GPS |
| Grilla de 16 regiones | `[babel_region_grid]` | Con imágenes de fondo, CSS full-width |
| Plantilla de página de región | `[bd_region_template]` | Buscador pre-filtrado + pills de categorías |
| Loop de resultados | `[bd_archive_loop]` | Tarjetas de negocios para Divi Theme Builder |
| Barra de filtros | `[bd_filter_bar]` | Región + categoría, dinámico |
| Footer regiones | `[bd_footer_regions]` | Lista para footer |
| Footer categorías | `[bd_footer_categories]` | Lista para footer |
| Perfil de negocio | `[bd_business_profile]` | Perfil completo: galería, horarios, contacto, mapa, reseñas |
| REST API | `/wp-json/babel/v1/search` | Endpoint principal de búsqueda |
| Panel Babel | WordPress admin | Formulario de alta + guía de shortcodes |
| Unificación de diseño | UI de Tarjetas | `[bd_archive_loop]` y fallback AJAX unificados en CSS BEM |
| SEO de Contenido | Rutas estáticas | URLs `/region/X/categoria/Y/` nativas de WP con history API SPA |

### Plantillas Divi Theme Builder Activas

| ID | Plantilla | Cobertura |
|---|---|---|
| 351 | Plantilla Predeterminada | Global (fallback) |
| 352 | Todas las Páginas de Regiones | `archive:taxonomy:babel_region:all` |
| 369 | Páginas de Categoría | `archive:taxonomy:babel_category:all` |
| 355 | Todos los Negocios (CPT) | `singular:post_type:babel_business:all` |
| 370 | Página de Búsqueda | `search:all` |

### 🔴 Pendiente (Próximos pasos priorizados)

#### PRIORIDAD 1 — Refinamiento Visual del Divi Theme Builder
- Las plantillas de "Páginas de Categoría" (ID 369) y "Página de Búsqueda" (ID 370) tienen la estructura técnica correcta pero necesitan refinamiento visual en el Divi Visual Builder (hero section, colores, tipografía).
- **Acción**: Ingresar al Divi Theme Builder desde el admin de WordPress y diseñar el layout visual de cada plantilla.

#### PRIORIDAD 2 — Estrategia de Contenido
- Carga inicial: manual por el equipo.
- Auto-registro: formulario frontend ya existe en Panel Babel.
- Definir: ¿listado gratuito? ¿plan destacado pago?

---

## Arquitectura Técnica del Plugin

```
babel-directory-master/
├── babel-directory.php          # Entry point + plugin header + versión
├── includes/
│   ├── autoloader.php           # PSR-4 autoloader nativo
│   ├── class-cpt.php            # Custom Post Type: babel_business
│   ├── class-shortcodes.php     # Todos los shortcodes frontend ← ARCHIVO PRINCIPAL
│   ├── class-ajax.php           # Lógica de filtrado (usada por REST)
│   ├── class-assets.php         # Enqueue scripts/styles + wp_localize_script
│   ├── class-admin.php          # Panel Babel (admin WordPress)
│   ├── class-metaboxes.php      # Campos del perfil de negocio
│   ├── class-submission.php     # Auto-registro frontend
│   ├── class-reviews.php        # Sistema de reseñas
│   ├── class-search-index.php   # Índice de búsqueda
│   ├── class-taxonomy-images.php# Imágenes para regiones/categorías
│   └── api/
│       └── class-rest-endpoints.php  # /wp-json/babel/v1/search
├── assets/
│   ├── js/babel-public.js       # Vanilla JS: buscador SPA + GPS + pills
│   └── css/babel-public.css     # ← FUENTE DE VERDAD DE TODOS LOS ESTILOS
└── templates/
    └── parts/                   # Partials de templates
```

**Namespace:** `Babel\Directory` (PSR-4)  
**REST Endpoint:** `GET/POST /wp-json/babel/v1/search`

### Meta Keys del CPT babel_business

Los campos del perfil de negocio se leen con `get_post_meta($post_id, $key, true)`:

```
_babel_description      Descripción larga del negocio
_babel_phone            Teléfono (formato +56 9 XXXX XXXX)
_babel_whatsapp         Número WhatsApp (solo dígitos)
_babel_email            Email de contacto
_babel_website          URL del sitio web
_babel_address          Dirección física
_babel_lat / _babel_lng Coordenadas para mapa embed
_babel_price_range      Rango de precio (ej: "$$")
_babel_biz_type         Tipo de negocio
_babel_hours            JSON con horarios por día
_babel_gallery          IDs de imágenes separados por coma
_babel_amenities        JSON con amenidades/características
_babel_instagram        Handle de Instagram (sin @)
_babel_facebook         Slug de Facebook
_babel_tiktok           Handle de TikTok (sin @)
_babel_youtube_channel  URL canal YouTube
_babel_twitter          Handle de Twitter/X (sin @)
_babel_linkedin         Slug de LinkedIn
_babel_razon_social     Razón social legal
_babel_rut              RUT de la empresa
_babel_founded_year     Año de fundación
_babel_verified         "1" si está verificado
_babel_featured         "1" si es negocio destacado
_babel_rating_avg       Promedio de calificación
_babel_rating_count     Número de reseñas
```

---

## Lo que NUNCA Hacer

- ❌ No crear módulos nativos de Divi (React/TS) — el plugin debe ser agnóstico
- ❌ No usar `admin-ajax.php` como flujo principal (solo fallback legacy)
- ❌ No hardcodear IPs, credenciales, nombres de negocios o imágenes de placeholder externas
- ❌ No añadir dependencias de jQuery (solo Vanilla JS)
- ❌ No modificar archivos directamente en el servidor sin rsync
- ❌ No usar Tailwind, Bootstrap o cualquier framework CSS externo
- ❌ No crear clases CSS sin antes definirlas en `babel-public.css`
- ❌ No asumir que una clase existe por el nombre — verificar siempre en `babel-public.css`

---

## Proceso de Despliegue a Producción (AR1)

```bash
# 1. Verificar estado local
cd /ruta/a/babel-directory-master
git status && git diff --stat

# 2. Commit y push
git add -A && git commit -m "feat: descripción del cambio"
git push origin main

# 3. Sincronizar con AR1 (nunca modificar directamente en el servidor)
rsync -avz --no-p --no-g --no-o --exclude='.git' \
  "/ruta/local/babel-directory-master/" \
  ar1:/home/soydechile/public_html/wp-content/plugins/babel-directory-master/

# 4. Post-deploy SIEMPRE (sin esto el plugin puede desactivarse silenciosamente)
ssh ar1 'chown -R soydechile:soydechile /home/soydechile/public_html/wp-content/plugins/babel-directory-master/ && \
  find /home/soydechile/public_html/wp-content/plugins/babel-directory-master/ -type d -exec chmod 755 {} \; && \
  find /home/soydechile/public_html/wp-content/plugins/babel-directory-master/ -type f -exec chmod 644 {} \;'

# 5. Limpiar caché de WordPress
ssh ar1 'wp cache flush --allow-root --path=/home/soydechile/public_html && \
  wp transient delete --all --allow-root --path=/home/soydechile/public_html'

# 6. Si se modificó CSS — purgar caché Divi
ssh ar1 'wp eval "et_fb_delete_builder_assets();" --allow-root --path=/home/soydechile/public_html'
```

---

*Documento vivo — actualizar con cada hito completado.*  
*Historial completo de hitos: Neo4j → `MATCH (h:Milestone) RETURN h ORDER BY h.number DESC`*
