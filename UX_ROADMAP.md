# UX Roadmap — soydechile.cl (Babel Directory Plugin)

> **Estado**: Actualizado 2026-05-26 — Hito 92  
> **Aprobado por**: Andy  
> **Arquitectura técnica**: PSR-4 + REST API WP7 · Plugin Agnóstico · Sin dependencia de tema  
> **Repo**: github.com/babel13mkt/babel-listing-services · **Rama**: main  
> **Producción**: soydechile.cl · Plugin activo: `babel-directory-master` en AR1  
> **Tema activo**: Divi (Theme Builder para UI estática) — **soy-de-chile-child desactivado**  
> **Versión actual plugin**: 7.1.6 · **Versión child theme**: N/A

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
# Plugin
rsync -avz --no-p --no-g --no-o --exclude='.git' \
  "/ruta/local/babel-directory-master/" \
  ar1:/home/soydechile/public_html/wp-content/plugins/babel-directory-master/

# Child Theme
rsync -avz --no-p --no-g --no-o \
  "/ruta/local/soy-de-chile-child/" \
  ar1:/home/soydechile/public_html/wp-content/themes/soy-de-chile-child/

# Post-deploy SIEMPRE:
ssh ar1 'chown -R soydechile:soydechile /home/soydechile/public_html/wp-content/plugins/babel-directory-master/'
ssh ar1 'chown -R soydechile:soydechile /home/soydechile/public_html/wp-content/themes/soy-de-chile-child/'
```

### 6. Estilos Globales van en el Child Theme, NO en el Plugin
- Los estilos del **sitio** (header, footer, tipografía global, hero de portada) van en `soy-de-chile-child/assets/sdc-theme.css`.
- Los estilos de los **shortcodes** (tarjetas de negocios, buscador, grilla de regiones) van en `babel-public-v717.css`.
- **PROHIBIDO** mezclar ambos. La separación de responsabilidades es estricta.

---

## El Sistema de Diseño: Stitch Design System

El diseño visual del plugin y del sitio está definido en **Stitch** (herramienta de diseño de Google).  
**Proyecto en Stitch**: "Directorio Babel - Diseño UI" (`projects/13440891265856203657`)  
**Pantalla de referencia principal**: "Perfil Sushi Club - Reestructurado" (`screen: 0ec94fee227b4475b0940bab3ce12968`)

> ⚠️ **El rediseño de portada y cabecera (Hito 92) se implementó directamente en el child theme** por indisponibilidad temporal del MCP de Stitch. El diseño sigue el mismo sistema de tokens Charcoal/Gold del proyecto Stitch. Si Stitch está disponible en una próxima sesión, se puede consultar para iteraciones adicionales.

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

**soydechile.cl** es un superdirectorio de comercios e **instituciones** de todo Chile: negocios, escuelas, universidades, bancos, clínicas, organismos públicos y más.  
Orientado a 3 tipos de usuario:

| Usuario | Contexto | Necesidad |
|---|---|---|
| Chileno local | Sabe dónde está | Busca algo concreto rápido |
| Turista planificando | En casa, antes del viaje | Explorar por región |
| Turista en terreno | Con celular en Chile | Encontrar algo cerca via GPS |

> 📱 **Futura App Móvil (en roadmap):** La arquitectura REST API del plugin está diseñada para alimentar nativamente una futura aplicación móvil. Los endpoints deben mantenerse libres de dependencias visuales de WordPress.

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

### ✅ Completado

| Hito | Componente | Shortcode / Archivo | Descripción |
|---|---|---|---|
| 50-76 | Buscador universal | `[babel_radar_search]` | Búsqueda keyword + GPS |
| 54-62 | Grilla de 16 regiones | `[babel_region_grid]` | Con imágenes de fondo, CSS full-width |
| 76 | Plantilla de página de región | `[bd_region_template]` | Buscador pre-filtrado + pills de categorías |
| 88 | Loop de resultados | `[bd_archive_loop]` | Tarjetas de negocios BEM, unificadas con buscador AJAX |
| 76 | Barra de filtros | `[bd_filter_bar]` | Región + categoría, dinámico |
| 46 | Footer regiones | `[bd_footer_regions]` | Lista para footer |
| 46 | Footer categorías | `[bd_footer_categories]` | Lista para footer |
| 79 | Perfil de negocio | `[bd_business_profile]` | Perfil completo: galería, horarios, contacto, mapa, reseñas |
| 74 | REST API | `/wp-json/babel/v1/search` | Endpoint principal de búsqueda, PSR-4 |
| 74 | Panel Babel | WordPress admin | Formulario de alta + guía de shortcodes |
| 89 | Unificación de diseño | `class-ajax.php` | Fallback AJAX y `[bd_archive_loop]` unificados en CSS BEM |
| 90 | SEO Rutas Estáticas | `rewrite rules` | URLs `/region/X/categoria/Y/` nativas de WP con history API SPA |
| **91** | **Migración a Gutenberg** | `babel-directory.php` + templates | **Divi 5 eliminado. Twenty Twenty-Four activo. Plantillas PHP autónomas `taxonomy-babel_region.php` y `single-babel_business.php` en el plugin.** |
| **92** | **Rediseño Premium Portada + Header** | `soy-de-chile-child/` | **Child theme propio. Header glassmorphism fijo full-width (logo izq. / menú der.). Hero 100svh cinematográfico con eyebrow pill, tipografía Playfair Display, pills de acceso rápido, Google Fonts.** |
| **93** | **Reintegración Divi** | `Divi Theme` | **Reversión del Hito 91 para UI estática. Divi activo como motor visual para gobernar el Header y Footer (Theme Builder). Plugin mantiene inyección de shortcodes.** |

### 🔴 Pendiente (Próximos pasos priorizados)

#### PRIORIDAD 1 — Iteración Visual del Child Theme
- [ ] Subir logo oficial de "Soy de Chile" en formato SVG al Media Library y configurarlo en **Apariencia → Identidad del Sitio** para que aparezca en el header.
- [ ] Revisar/refinar el menú de navegación en **Apariencia → Menus** (o Editor de sitio) para asegurar que los ítems sean los correctos.
- [ ] Evaluar con Stitch (cuando esté disponible) si la portada actual sigue la línea del design system o requiere iteración.
- [ ] Ajustar imagen de fondo del hero: la actual (`costanera_skyline.jpg`) es genérica — considerar imagen de alta resolución de Chile más representativa.

#### PRIORIDAD 2 — Estrategia de Contenido y Carga
- Carga inicial: manual por el equipo de comunidades y regiones de Chile.
- Auto-registro: formulario frontend ya existe en Panel Babel.
- Definir: ¿listado gratuito? ¿plan destacado pago?
- Instituciones: escuelas, universidades, bancos y organismos públicos deben tener su flujo de alta diferenciado.

#### PRIORIDAD 3 — Preparación para Aplicación Móvil (Futura App)
- **Desacoplamiento Headless:** Asegurar que los endpoints REST API (`/wp-json/babel/v1/search`) estén totalmente optimizados, limpios y libres de dependencias visuales de WordPress para poder alimentar de forma nativa a la futura aplicación móvil.
- **Geolocalización GPS Avanzada:** Mantener y validar los campos de metadatos `_babel_lat` y `_babel_lng` para proveer mapas interactivos y ordenamiento por proximidad real dentro de la App móvil.
- **Design System compartido:** Los tokens CSS (`--babel-*`, `--sdc-*`) del plugin y el child theme deberán exportarse como guía de diseño para el equipo mobile.

---

## Arquitectura Técnica Completa

### Stack de Producción (AR1)
```
WordPress 7.x
├── Tema Parent:  twentytwentyfour (Twenty Twenty-Four v1.5 — Block Theme / FSE)
└── Tema Child:   soy-de-chile-child v1.0.2  ← ACTIVO
    ├── style.css                   # Declaración del child theme
    ├── functions.php               # Enqueue estilos + Google Fonts + JS scroll header
    ├── assets/
    │   └── sdc-theme.css           # ← ESTILOS GLOBALES DEL SITIO (header, hero, footer)
    └── parts/
        └── header.html             # Template part FSE (overrides parent)

Plugin:  babel-directory-master v7.1.6
```

### Estructura del Plugin
```
babel-directory-master/
├── babel-directory.php          # Entry point + plugin header + versión
│                                # + filtro template_include (CPT & taxonomías autónomas)
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
│   └── css/babel-public-v717.css # ← ESTILOS DE SHORTCODES (tarjetas, buscador, regiones)
└── templates/
    ├── taxonomy-babel_region.php # Plantilla autónoma para /region/{slug}/
    └── single-babel_business.php # Plantilla autónoma para /negocio/{slug}/
```

**Namespace:** `Babel\Directory` (PSR-4)  
**REST Endpoint:** `GET/POST /wp-json/babel/v1/search`

### Separación de Responsabilidades CSS
| Archivo | Responsabilidad |
|---|---|
| `sdc-theme.css` (child theme) | Header, hero portada, footer global, tipografía base, animaciones del sitio |
| `babel-public-v717.css` (plugin) | Tarjetas de negocios, buscador, grilla de regiones, pills, filtros, perfil |

> ⚠️ **Nunca mezclar.** Los estilos del sitio van en el child theme. Los estilos de los shortcodes van en el plugin.

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

- ❌ No depender de Divi para lógica dinámica (usar siempre shortcodes del plugin). Divi solo gobierna Layout (Header/Footer).
- ❌ No usar `admin-ajax.php` como flujo principal (solo fallback legacy)
- ❌ No hardcodear IPs, credenciales, nombres de negocios o imágenes de placeholder externas
- ❌ No añadir dependencias de jQuery (solo Vanilla JS)
- ❌ No modificar archivos directamente en el servidor sin rsync
- ❌ No usar Tailwind, Bootstrap o cualquier framework CSS externo
- ❌ No crear clases CSS sin antes definirlas en el archivo correcto (`sdc-theme.css` o `babel-public-v717.css`)
- ❌ No asumir que una clase existe por el nombre — verificar siempre en el archivo correspondiente
- ❌ No poner estilos del sitio (header, hero, layout global) dentro del plugin — van en el child theme
- ❌ No activar Twenty Twenty-Four directamente — el tema activo es el child theme `soy-de-chile-child`

---

## Proceso de Despliegue a Producción (AR1)

### A. Despliegue del Plugin (`babel-directory-master`)
```bash
# 1. Verificar estado local
cd /ruta/a/babel-directory-master
git status && git diff --stat

# 2. Commit y push
git add -A && git commit -m "feat: descripción del cambio"
git push origin main

# 3. Sincronizar con AR1
rsync -avz --no-p --no-g --no-o --exclude='.git' \
  "/ruta/local/babel-directory-master/" \
  ar1:/home/soydechile/public_html/wp-content/plugins/babel-directory-master/

# 4. Post-deploy: permisos (CRÍTICO — sin esto el plugin puede desactivarse silenciosamente)
ssh ar1 'chown -R soydechile:soydechile /home/soydechile/public_html/wp-content/plugins/babel-directory-master/ && \
  find /home/soydechile/public_html/wp-content/plugins/babel-directory-master/ -type d -exec chmod 755 {} \; && \
  find /home/soydechile/public_html/wp-content/plugins/babel-directory-master/ -type f -exec chmod 644 {} \;'

# 5. Limpiar caché
ssh ar1 'wp cache flush --allow-root --path=/home/soydechile/public_html && \
  wp transient delete --all --allow-root --path=/home/soydechile/public_html'
```

### B. Despliegue del Child Theme (`soy-de-chile-child`)
```bash
# Ruta local: /Proyectos_Dev/soy-de-chile/soy-de-chile-child/

# 1. Sincronizar
rsync -avz --no-p --no-g --no-o \
  "/ruta/local/soy-de-chile-child/" \
  ar1:/home/soydechile/public_html/wp-content/themes/soy-de-chile-child/

# 2. Post-deploy: permisos
ssh ar1 'chown -R soydechile:soydechile /home/soydechile/public_html/wp-content/themes/soy-de-chile-child/'

# 3. Limpiar caché (invalidar CSS)
ssh ar1 'wp cache flush --allow-root --path=/home/soydechile/public_html'

# El child theme ya está activo — no hace falta activarlo de nuevo
# Para verificar: wp theme list --allow-root --path=/home/soydechile/public_html
```

### C. Verificación rápida post-deploy
```bash
# HTTP 200 y clases del child theme presentes
curl -s -o /dev/null -w "%{http_code}" https://soydechile.cl/
curl -s https://soydechile.cl/ | grep -o 'sdc-site-header\|babel-custom-hero' | sort -u
```

---

*Documento vivo — actualizar con cada hito completado.*  
*Historial completo de hitos: Neo4j → `MATCH (h:Milestone) RETURN h ORDER BY h.number DESC`*

---

## Changelog de Hitos Técnicos Recientes

| Fecha | Hito | Cambio Principal | Versión |
|---|---|---|---|
| 2026-05-29 | **93** | Reintegración parcial de Divi 5 para administrar visualmente cabeceras y pies de página (Theme Builder), desactivando `soy-de-chile-child` tras tomar snapshot de seguridad. | plugin 7.1.6 / divi |
| 2026-05-26 | **92** | Child theme `soy-de-chile-child` creado y activado. Header glassmorphism full-width. Hero 100svh con eyebrow pill dorado, tipografía Playfair Display, Google Fonts, quick pills temáticas. | theme 1.0.2 |
| 2026-05-26 | **91** | Migración completa de Divi 5 a Gutenberg (Twenty Twenty-Four). Plantillas autónomas `taxonomy-babel_region.php` y `single-babel_business.php` en el plugin vía `template_include`. | 7.1.6 |
| 2026-05-26 | **90** | Rutas estáticas SEO `/region/X/categoria/Y/`. History API SPA. | 7.1.5 |
| 2026-05-26 | **89** | Unificación tarjetas AJAX + `[bd_archive_loop]` en CSS BEM. Eliminación `.babel-premium-card`. | 7.1.4 |
| 2026-05-26 | **88** | Reemplazo HTML Tailwind por clases BEM nativas. 25 tokens CSS `--babel-*`. Anti-IA-Drift en UX_ROADMAP. | 7.1.3 |
