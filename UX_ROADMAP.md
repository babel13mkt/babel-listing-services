# UX Roadmap — soydechile.cl (Babel Directory Plugin)

> **Estado**: Aprobado por Andy · **Fecha**: 2026-05-23
> **Arquitectura técnica**: PSR-4 + REST API WP7 · Plugin Agnóstico · Sin dependencia de tema
> **Repo**: github.com/babel13mkt/babel-listing-services · **Rama**: main
> **Producción**: soydechile.cl · Plugin activo: `babel-directory-master`

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

## Decisiones de Diseño Clave

### ✅ Ya implementado
- Buscador único que busca todo (keyword + geo)
- Grilla de 16 regiones en portada con imagen de fondo
- Shortcodes agnósticos: `[babel_radar_search]`, `[babel_region_grid]`, `[bd_archive_loop]`, `[bd_filter_bar]`, `[bd_footer_regions]`, `[bd_footer_categories]`
- REST API endpoint: `/wp-json/babel/v1/search`
- Panel Babel en WordPress admin con formulario de alta de negocios
- Registro de negocios: manual (admin) + auto-registro (frontend)

### 🔴 Pendiente (Próximos pasos priorizados)

#### PRIORIDAD 1 — Página de Región (lo más urgente)
- Las páginas `/region/{slug}/` actualmente llegan al loop genérico de taxonomía de WP
- **Diseño aprobado**: Buscador pre-filtrado + pills de categorías (scroll horizontal) + cards de negocios destacados
- **Cómo construirlo**: Shortcode `[bd_filter_bar region="auto"]` que detecte la región del contexto actual + Divi Theme Builder para el layout

#### PRIORIDAD 2 — SEO de Contenido
- Las URLs `/buscar/?keyword=X` no son indexables por Google (SPA)
- Necesitar páginas estáticas tipo `/completos/santiago/` o `/region/maule/categoria/restaurantes/`
- Solución: generar archive pages por taxonomía cruzada (región + categoría)

#### PRIORIDAD 3 — Perfil de Negocio Rico
- Cada CPT `babel_business` necesita: fotos galería, horarios, teléfono clickeable, WhatsApp, mapa embed, reseñas
- El CPT y metaboxes ya existen — falta el template visual (Divi Theme Builder)

#### PRIORIDAD 4 — Estrategia de Contenido
- Carga inicial: manual por el equipo
- Auto-registro: formulario frontend ya existe en Panel Babel
- Definir: ¿listado gratuito? ¿plan destacado pago?

---

## Arquitectura Técnica del Plugin

```
babel-directory-master/
├── babel-directory.php          # Entry point + plugin header
├── includes/
│   ├── autoloader.php           # PSR-4 autoloader nativo
│   ├── class-cpt.php            # Custom Post Type: babel_business
│   ├── class-shortcodes.php     # Todos los shortcodes frontend
│   ├── class-ajax.php           # Lógica de filtrado (usada por REST)
│   ├── class-assets.php         # Enqueue scripts/styles + wp_localize
│   ├── class-admin.php          # Panel Babel (admin WordPress)
│   ├── class-metaboxes.php      # Campos del perfil de negocio
│   ├── class-submission.php     # Auto-registro frontend
│   ├── class-reviews.php        # Sistema de reseñas
│   ├── class-search-index.php   # Índice de búsqueda
│   ├── class-taxonomy-images.php# Imágenes para regiones/categorías
│   └── api/
│       └── class-rest-endpoints.php  # /wp-json/babel/v1/search
├── assets/
│   ├── js/babel-public.js       # Vanilla JS: buscador SPA + GPS
│   └── css/babel-public.css     # Estilos frontend
└── templates/
    └── parts/                   # Partials de templates
```

**Namespace:** `Babel\Directory` (PSR-4)
**REST Endpoint:** `GET/POST /wp-json/babel/v1/search`
**Shortcodes disponibles:**
- `[babel_radar_search]` — Buscador completo con GPS
- `[babel_region_grid columns="4" rows="4"]` — Grilla de regiones
- `[bd_archive_loop]` — Loop de resultados para Divi Theme Builder
- `[bd_filter_bar]` — Barra de filtros (región + categoría)
- `[bd_footer_regions]` — Lista de regiones para footer
- `[bd_footer_categories]` — Lista de categorías para footer

---

## Protocolo de Despliegue (AR1)

```bash
# Desde Mac Local — requiere aprobación explícita de Andy
rsync -avz --no-p --no-g --no-o --exclude='.git' \
  "/ruta/local/babel-directory-master/" \
  ar1:/home/soydechile/public_html/wp-content/plugins/babel-directory-master/

# Post-deploy (siempre)
ssh ar1 'chown -R soydechile:soydechile /home/.../babel-directory-master/'
```

> ⚠️ **REGLA**: Nunca modificar directamente en el servidor. Siempre: editar local → commit GitHub → rsync a AR1.

---

## Lo que NO hacer

- ❌ No crear módulos nativos de Divi (React/TS) — el plugin debe ser agnóstico
- ❌ No usar `admin-ajax.php` como flujo principal (solo fallback)
- ❌ No hardcodear IPs o credenciales en el código
- ❌ No añadir dependencia de jQuery
- ❌ No modificar archivos directamente en el servidor sin rsync

---

*Documento vivo — actualizar con cada hito completado.*
