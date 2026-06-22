# 🎨 DESIGN TOKENS — Babel Directory

> **Design System:** Stitch Design System "Directorio Babel"
> **Proyecto:** projects/13440891265856203657
> **Archivo fuente:** `assets/css/babel-public-v717.css`
> **Versión:** 7.1.7
> **Última actualización:** 2026-06-22

---

## 📋 Tabla de Contenidos

1. [Colores](#1-colores)
2. [Tipografía](#2-tipografía)
3. [Espaciado](#3-espaciado)
4. [Sombras](#4-sombras)
5. [Bordes y Radios](#5-bordes-y-radios)
6. [Layout](#6-layout)
7. [Opacidad](#7-opacidad)
8. [Z-Index](#8-z-index)
9. [Animación](#9-animación)
10. [Equivalencias Mobile](#10-equivalencias-mobile-swift--kotlin)
11. [Ejemplos de Uso](#11-ejemplos-de-uso)

---

## 1. Colores

### 1.1 Paleta Principal — Charcoal/Gold Premium

Tokens con prefijo `--babel-color-*` declarados en `:root`.

| Token CSS | Valor | Uso | Swift | Kotlin |
|---|---|---|---|---|
| `--babel-color-primary` | `#000000` | Negro charcoal — títulos, botones dark | `UIColor(0,0,0)` | `Color(0xFF000000)` |
| `--babel-color-on-primary` | `#ffffff` | Texto sobre fondo primario | `UIColor.white` | `Color.White` |
| `--babel-color-secondary` | `#735c00` | Dorado oscuro — botones registro, focus | `UIColor(115,92,0)` | `Color(0xFF735C00)` |
| `--babel-color-on-secondary` | `#ffffff` | Texto sobre fondo secundario | `UIColor.white` | `Color.White` |
| `--babel-color-secondary-fixed` | `#ffe088` | Dorado claro — backgrounds de badges | `UIColor(255,224,136)` | `Color(0xFFFFE088)` |
| `--babel-color-secondary-fixed-dim` | `#e9c349` | Dorado medio — estrellas rating | `UIColor(233,195,73)` | `Color(0xFFE9C349)` |
| `--babel-color-on-secondary-fixed` | `#241a00` | Texto sobre dorado fijo (badge featured) | `UIColor(36,26,0)` | `Color(0xFF241A00)` |

### 1.2 Superficies (Surface Tokens)

| Token CSS | Valor | Uso | Swift | Kotlin |
|---|---|---|---|---|
| `--babel-color-surface` | `#f9f9f9` | Superficie base | `UIColor(249,249,249)` | `Color(0xFFF9F9F9)` |
| `--babel-color-surface-container-lowest` | `#ffffff` | Tarjetas blancas puras | `UIColor.white` | `Color.White` |
| `--babel-color-surface-container-low` | `#f3f3f3` | Fondos secundarios | `UIColor(243,243,243)` | `Color(0xFFF3F3F3)` |
| `--babel-color-surface-container` | `#eeeeee` | Placeholder backgrounds | `UIColor(238,238,238)` | `Color(0xFFEEEEEE)` |
| `--babel-color-surface-container-high` | `#e8e8e8` | Botones secundarios bg | `UIColor(232,232,232)` | `Color(0xFFE8E8E8)` |
| `--babel-color-surface-container-highest` | `#e2e2e2` | Barra de progreso bg | `UIColor(226,226,226)` | `Color(0xFFE2E2E2)` |
| `--babel-color-surface-dim` | `#dadada` | Superficie atenuada | `UIColor(218,218,218)` | `Color(0xFFDADADA)` |

### 1.3 Texto sobre Superficie

| Token CSS | Valor | Uso | Swift | Kotlin |
|---|---|---|---|---|
| `--babel-color-on-surface` | `#1a1c1c` | Texto principal | `UIColor(26,28,28)` | `Color(0xFF1A1C1C)` |
| `--babel-color-on-surface-variant` | `#444748` | Texto secundario, meta | `UIColor(68,71,72)` | `Color(0xFF444748)` |
| `--babel-color-outline` | `#747878` | Bordes principales, hints | `UIColor(116,120,120)` | `Color(0xFF747878)` |
| `--babel-color-outline-variant` | `#c4c7c7` | Bordes sutiles, separadores | `UIColor(196,199,199)` | `Color(0xFFC4C7C7)` |
| `--babel-color-error` | `#ba1a1a` | Error — formularios, alertas | `UIColor(186,26,26)` | `Color(0xFFBA1A1A)` |
| `--babel-color-on-error` | `#ffffff` | Texto sobre fondo de error | `UIColor.white` | `Color.White` |

### 1.4 Colores Inline (sin variable CSS propia)

| Nombre | Valor | Uso | Swift | Kotlin |
|---|---|---|---|---|
| `accentBlue` | `#219ebc` | Botones primarios, links, focus rings | `UIColor(33,158,188)` | `Color(0xFF219EBC)` |
| `accentBlueDark` | `#023047` | Hover de botones primarios, hero overlay | `UIColor(2,48,71)` | `Color(0xFF023047)` |
| `accentGold` | `#ffb703` | Badges featured, estrellas rating | `UIColor(255,183,3)` | `Color(0xFFFFB703)` |
| `accentGoldDark` | `#fb8500` | Gradiente de badges featured | `UIColor(251,133,0)` | `Color(0xFFFB8500)` |
| `accentGreen` | `#25d366` | WhatsApp green | `UIColor(37,211,102)` | `Color(0xFF25D366)` |
| `accentGreenDark` | `#128c7e` | WhatsApp green hover | `UIColor(18,140,126)` | `Color(0xFF128C7E)` |
| `accentEmerald` | `#10b981` | Badge verificado | `UIColor(16,185,129)` | `Color(0xFF10B981)` |
| `accentRed` | `#ef4444` | Pin ubicación, botón eliminar | `UIColor(239,68,68)` | `Color(0xFFEF4444)` |
| `accentRedDark` | `#dc2626` | Hover botón eliminar | `UIColor(220,38,38)` | `Color(0xFFDC2626)` |
| `accentSky` | `#0ea5e9` | Radar active, slider accent | `UIColor(14,165,233)` | `Color(0xFF0EA5E9)` |
| `accentSkyDark` | `#0369a1` | Texto radar active | `UIColor(3,105,161)` | `Color(0xFF0369A1)` |
| `accentIndigo` | `#4f46e5` | Botón booking gradient | `UIColor(79,70,229)` | `Color(0xFF4F46E5)` |
| `accentBlueFilter` | `#0066ff` | Botón buscar filter bar | `UIColor(0,102,255)` | `Color(0xFF0066FF)` |
| `textDark` | `#1f2937` | Texto oscuro — títulos fallback | `UIColor(31,41,55)` | `Color(0xFF1F2937)` |
| `textMuted` | `#6b7280` | Texto apagado — excerpts | `UIColor(107,114,128)` | `Color(0xFF6B7280)` |
| `textLight` | `#9ca3af` | Texto claro — contadores | `UIColor(156,163,175)` | `Color(0xFF9CA3AF)` |
| `bgLight` | `#f3f4f6` | Fondo claro — inputs, badges | `UIColor(243,244,246)` | `Color(0xFFF3F4F6)` |
| `bgLighter` | `#f8fafc` | Fondo más claro — perfil wrapper | `UIColor(248,250,252)` | `Color(0xFFF8FAFC)` |
| `borderLight` | `#e5e7eb` | Borde claro — inputs, tarjetas | `UIColor(229,231,235)` | `Color(0xFFE5E7EB)` |
| `borderLighter` | `#eaeaea` | Borde ultra claro — empty states | `UIColor(234,234,234)` | `Color(0xFFEAEAEA)` |
| `regionDark` | `#131313` | Overlay regiones — gradiente oscuro | `UIColor(19,19,19)` | `Color(0xFF131313)` |
| `regionGold` | `#f2ca50` | Línea decorativa regiones | `UIColor(242,202,80)` | `Color(0xFFF2CA50)` |
| `regionText` | `#e4e2e1` | Texto sobre región oscura | `UIColor(228,226,225)` | `Color(0xFFE4E2E1)` |

### 1.5 Child Theme — Soy de Chile (sdc-*)

Tokens del child theme usados en el plugin vía `var(--sdc-*)`.

| Token CSS | Valor | Uso en Plugin | Swift | Kotlin |
|---|---|---|---|---|
| `--sdc-white` | `#ffffff` | Search form background | `UIColor.white` | `Color.White` |
| `--sdc-gray-50` | `#f9fafb` | Hover items autocomplete | `UIColor(249,250,251)` | `Color(0xFFF9FAFB)` |
| `--sdc-gray-100` | `#f3f4f6` | Badge tipo autocomplete | `UIColor(243,244,246)` | `Color(0xFFF3F4F6)` |
| `--sdc-text` | `#1a1c1c` | Color items autocomplete | `UIColor(26,28,28)` | `Color(0xFF1A1C1C)` |
| `--sdc-text-muted` | `#444748` | Color tipo en autocomplete | `UIColor(68,71,72)` | `Color(0xFF444748)` |
| `--sdc-border` | `#e2e8f0` | Bordes inputs, tarjetas SaaS | `UIColor(226,232,240)` | `Color(0xFFE2E8F0)` |
| `--sdc-blue` | `#219ebc` | Iconos upload metaboxes | `UIColor(33,158,188)` | `Color(0xFF219EBC)` |
| `--sdc-shadow-md` | *(heredado)* | Sombra search form | Ver sombras | Ver sombras |

### 1.6 Colores de Estado

| Estado | Texto | BG | Swift Text | Swift BG |
|---|---|---|---|---|
| Open | `#166534` | `#dcfce7` | `Color(0xFF166534)` | `Color(0xFFDCFCE7)` |
| Closed | `#991b1b` | `#fee2e2` | `Color(0xFF991B1B)` | `Color(0xFFFEE2E2)` |
| Alert Success | `#166534` | `#f0fdf4` | `Color(0xFF166534)` | `Color(0xFFF0FDF4)` |
| Alert Error | `#991b1b` | `#fef2f2` | `Color(0xFF991B1B)` | `Color(0xFFFEF2F2)` |
| Notice Success | `#1e7e34` | `#e6f7ed` | `Color(0xFF1E7E34)` | `Color(0xFFE6F7ED)` |
| Notice Error | `#c81e1e` | `#fde8e8` | `Color(0xFFC81E1E)` | `Color(0xFFFDE8E8)` |

### 1.7 Colores de Redes Sociales

| Red | Valor | Swift | Kotlin |
|---|---|---|---|
| Instagram | `#e1306c` | `Color(0xFFE1306C)` | `Color(0xFFE1306C)` |
| Facebook | `#1877f2` | `Color(0xFF1877F2)` | `Color(0xFF1877F2)` |
| LinkedIn | `#0077b5` | `Color(0xFF0077B5)` | `Color(0xFF0077B5)` |
| TikTok | `#000000` | `Color.Black` | `Color.Black` |
| X (Twitter) | `#000000` | `Color.Black` | `Color.Black` |
| Pinterest | `#bd081c` | `Color(0xFFBD081C)` | `Color(0xFFBD081C)` |
| YouTube | `#ff0000` | `Color(0xFFFF0000)` | `Color(0xFFFF0000)` |

---

## 2. Tipografía

### 2.1 Familias Tipográficas

| Token CSS | Valor | Uso Principal |
|---|---|---|
| `--babel-font-display` | `'Playfair Display', Georgia, serif` | Títulos de negocio, hero, secciones |
| `--babel-font-body` | `'Inter', system-ui, sans-serif` | Texto general, descripciones, inputs |
| `--babel-font-label` | `'Montserrat', system-ui, sans-serif` | Badges, botones, labels, navegación |

> **Nota:** El perfil premium usa `'Outfit'` como fuente primaria con fallback a `'Inter'`.

#### Equivalencias Mobile

```swift
// Swift (iOS)
let displayFont = UIFont(name: "PlayfairDisplay-Regular", size: 22)
let bodyFont = UIFont(name: "Inter-Regular", size: 15)
let labelFont = UIFont(name: "Montserrat-SemiBold", size: 14)
```

```kotlin
// Kotlin (Android)
val displayFont = FontFamily(Font(R.font.playfair_display))
val bodyFont = FontFamily(Font(R.font.inter))
val labelFont = FontFamily(Font(R.font.montserrat))
```

### 2.2 Tamaños de Fuente

| Nombre | Valor | Uso |
|---|---|---|
| `heroTitle` | `clamp(28px, 5vw, 52px)` | Título hero región — responsive |
| `homepageHero` | `3.5rem` (56px) | Título hero homepage |
| `profileName` | `28px` | Nombre negocio en perfil |
| `cardTitle` | `19px` | Título tarjeta negocio |
| `cardTitleFallback` | `18px` | Título tarjeta fallback |
| `sectionTitle` | `24px` | Título sección single |
| `sectionSubtitle` | `20px` | Subtítulo sección single |
| `sectionSubtitleProfile` | `18px` | Subtítulo sección perfil |
| `regionTitle` | `22px` | Título tarjeta región |
| `regionTitleGrid` | `14px` | Título región grilla compacta |
| `bodyLarge` | `17px` | Descripción single business |
| `bodyMedium` | `16px` | Empty state, filter input |
| `body` | `15px` | Texto base — inputs, descripciones |
| `bodySmall` | `14px` | Meta, breadcrumbs, pagination |
| `caption` | `13px` | Badges, rating count, price |
| `captionSmall` | `12px` | Meta separators |
| `eyebrow` | `12px` | Texto sobre título hero |
| `badge` | `10px` | Badge text |
| `button` | `14px` | Botón estándar |
| `buttonSmall` | `13px` | Botón pequeño |
| `buttonLarge` | `16px` | Botón grande (submit) |
| `stepTitle` | `20px` | Título paso form multi-step |
| `inputText` | `15px` | Texto de input |
| `inputTextMobile` | `13px` | Input en mobile |
| `autocomplete` | `15px` | Item de autocompletado |
| `statusBadge` | `11px` | Badge estado (abierto/cerrado) |
| `formLabel` | `13px` | Label de formulario |
| `errorText` | `12px` | Error de campo |
| `alertText` | `14px` | Texto de alerta |
| `excerpt` | `13.5px` | Extracto de tarjeta |

### 2.3 Pesos Tipográficos

| Nombre | Valor | Uso | Swift | Kotlin |
|---|---|---|---|---|
| `regular` | `400` | Texto regular | `.regular` | `FontWeight.Normal` |
| `medium` | `500` | Texto medio — links | `.medium` | `FontWeight.Medium` |
| `semibold` | `600` | Labels, meta, nav | `.semibold` | `FontWeight.SemiBold` |
| `bold` | `700` | Títulos, badges, botones | `.bold` | `FontWeight.Bold` |
| `extrabold` | `800` | Hero titles, section titles | `.heavy` | `FontWeight.ExtraBold` |

### 2.4 Letter Spacing

| Nombre | Valor | Uso |
|---|---|---|
| `tight` | `-0.01em` | Títulos display |
| `normal` | `0` | Texto base |
| `wide` | `0.05em` | Precio, semibold labels |
| `wider` | `0.06em` | Precio tarjeta |
| `widest` | `0.07em` | Badges |
| `cta` | `0.09em` | CTA text |
| `uppercase` | `0.1em` | Labels uppercase |
| `eyebrow` | `0.2em` | Explore link regiones |
| `nav` | `0.5px` | Navegación header |

### 2.5 Line Heights

| Nombre | Valor | Uso |
|---|---|---|
| `tight` | `1.1` | Hero title |
| `snug` | `1.2` | Profile name |
| `normal` | `1.25` | Display font |
| `relaxed` | `1.3` | Card title |
| `body` | `1.35` | Card title fallback |
| `paragraph` | `1.5` | Body text, inputs |
| `loose` | `1.6` | Description, empty state |

---

## 3. Espaciado

### 3.1 Tokens de Espaciado Base

| Token CSS | Valor | Uso |
|---|---|---|
| `--babel-space-unit` | `8px` | Unidad base |
| `--babel-gutter` | `32px` | Gutter entre columnas |
| `--babel-margin-mobile` | `20px` | Margen lateral mobile |
| `--babel-margin-desktop` | `64px` | Margen lateral desktop |

### 3.2 Escala de Espaciado

| Nombre | Valor | Uso Común |
|---|---|---|
| `xxs` | `4px` | Gap badges, separadores |
| `xs` | `6px` | Gap meta items, rating stars |
| `sm` | `8px` | Grid gap regiones, gap form groups |
| `md` | `10px` | Gap pills, gap footer |
| `lg` | `12px` | Gap taxonomy chips, gap contact buttons |
| `xl` | `14px` | Padding top footer tarjeta |
| `2xl` | `16px` | Region grid gap, carousel gap |
| `3xl` | `20px` | Card body padding, margin bottom secciones |
| `4xl` | `24px` | Card grid gap, hero padding bottom |
| `5xl` | `28px` | Cats section padding top |
| `6xl` | `32px` | Pagination margin top, profile section margin |
| `7xl` | `40px` | Hero content padding top |
| `8xl` | `48px` | Empty state padding |
| `9xl` | `60px` | Results wrap padding bottom |
| `10xl` | `80px` | Radar wrapper padding top, region card padding |

### 3.3 Gap de Grids

| Nombre | Valor | Componente |
|---|---|---|
| `cardGrid` | `24px` | Grilla de tarjetas de negocio |
| `regionGrid` | `16px` | Grilla de regiones |
| `carousel` | `16px` | Carrusel de regiones |
| `form` | `20px` | Grupos de formulario |
| `profileGrid` | `32px` | Grid de perfil (main + sidebar) |
| `taxChips` | `10px` | Chips de taxonomía |
| `catPills` | `10px` | Pills de categoría |
| `badges` | `4px` | Badges en tarjeta |
| `meta` | `6px` | Meta de tarjeta |
| `body` | `10px` | Cuerpo de tarjeta |
| `footer` | `8px` | Footer de tarjeta |
| `contact` | `14px` | Botones de contacto |
| `social` | `12px` | Iconos sociales |
| `amenities` | `12px` | Grid de amenidades |

---

## 4. Sombras

### 4.1 Sombras de Tarjetas

| Nombre | Valor | Uso |
|---|---|---|
| `card` | `0 4px 20px rgba(0,0,0,0.06)` | Tarjeta negocio default |
| `cardHover` | `0 14px 36px rgba(0,0,0,0.11)` | Tarjeta negocio hover |
| `cardFallback` | `0 4px 20px rgba(0,0,0,0.03)` | Tarjeta fallback |
| `cardFallbackHover` | `0 12px 30px rgba(0,0,0,0.07)` | Tarjeta fallback hover |
| `cardFeatured` | `0 4px 20px rgba(255,183,3,0.06)` | Tarjeta featured (dorada) |
| `premium` | `0 20px 40px rgba(0,0,0,0.08)` | Clase .bd-premium-shadow |

### 4.2 Sombras de Formularios y Búsqueda

| Nombre | Valor | Uso |
|---|---|---|
| `searchForm` | `0 10px 30px rgba(0,0,0,0.05)` | Search form wrapper |
| `searchFormHero` | `0 20px 50px rgba(0,0,0,0.2)` | Search form en hero (glass) |
| `filterBar` | `0 10px 30px rgba(0,0,0,0.05)` | Filter bar |
| `filterBarSticky` | `0 4px 6px -1px rgba(0,0,0,0.1)` | Filter bar sticky mobile |
| `autocomplete` | `0 10px 30px rgba(0,0,0,0.1)` | Dropdown autocompletado |

### 4.3 Sombras de Botones

| Nombre | Valor | Uso |
|---|---|---|
| `buttonPrimary` | `0 2px 8px rgba(33,158,188,0.15)` | Botón primario (azul) |
| `buttonPrimaryHover` | `0 4px 12px rgba(2,48,71,0.25)` | Botón primario hover |
| `buttonWhatsapp` | `0 2px 8px rgba(37,211,102,0.15)` | Botón WhatsApp |
| `buttonSubmit` | `0 4px 14px rgba(33,158,188,0.25)` | Botón submit |
| `buttonFilter` | `0 4px 12px rgba(0,102,255,0.2)` | Botón filter bar |
| `buttonBooking` | `0 4px 12px rgba(79,70,229,0.2)` | Botón booking (índigo) |

### 4.4 Sombras de Componentes Especiales

| Nombre | Valor | Uso |
|---|---|---|
| `profile` | `0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05)` | Perfil premium wrapper |
| `profileLg` | `0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -4px rgba(0,0,0,0.05)` | Galería hover perfil |
| `profileFeatured` | `0 10px 30px rgba(255,183,3,0.1)` | Perfil featured |
| `profileLightbox` | `0 25px 50px -12px rgba(0,0,0,0.5)` | Imagen lightbox |
| `regionCard` | `0 10px 30px rgba(0,0,0,0.3)` | Tarjeta región |
| `radarWrapper` | `0 20px 50px rgba(0,0,0,0.2)` | Wrapper radar |

### 4.5 Sombras de Inputs

| Nombre | Valor | Uso |
|---|---|---|
| `inputFocus` | `0 0 0 4px rgba(33,158,188,0.12), 0 4px 12px rgba(0,0,0,0.03)` | Input focus ring |
| `inputInset` | `0 2px 4px rgba(0,0,0,0.01) inset` | Input inner shadow |
| `inputError` | `0 0 0 3px rgba(186,26,26,0.1)` | Input error ring |

---

## 5. Bordes y Radios

### 5.1 Border Radius (Variables CSS)

| Token CSS | Valor | Uso General |
|---|---|---|
| `--babel-radius-sm` | `0.25rem` (4px) | Inputs, botones registro |
| `--babel-radius-md` | `0.5rem` (8px) | Tarjetas fallback, badges |
| `--babel-radius-lg` | `1rem` (16px) | Tarjetas negocio, regiones |
| `--babel-radius-xl` | `1.5rem` (24px) | Componentes grandes |
| `--babel-radius-full` | `9999px` | Pills, badges, avatares |

### 5.2 Border Radius Inline (por componente)

| Componente | Radio |
|---|---|
| Input búsqueda | `10px` |
| Input búsqueda mobile | `30px` |
| Botón acción tarjeta | `8px` |
| Badge | `6px` |
| Tarjeta fallback | `16px` |
| Imagen tarjeta | `12px` |
| Search form | `50px` |
| Filter bar | `50px` (mobile: `20px`) |
| Submit button | `40px` (mobile: `30px`) |
| Autocomplete dropdown | `16px` |
| Mapa | `8px` |
| Estado vacío | `8px` |
| Paginación | `6px` |
| Región card (Stitch) | `8px` |
| Radar wrapper | `30px` |
| Perfil wrapper | `16px` |
| Perfil logo | `12px` |
| Perfil button | `12px` |
| Carousel button | `9999px` (círculo) |
| Pill / Institution chip | `50px` / `9999px` |
| Quick pill | `30px` |
| Social icon | `50%` (círculo) |
| Step number | `50%` (círculo) |
| Progress bar | `3px` |

### 5.3 Anchos de Borde

| Nombre | Valor | Uso |
|---|---|---|
| `thin` | `1px` | Bordes estándar |
| `medium` | `1.5px` | Pills active border |
| `thick` | `2px` | Dropzone dashed, focus rings |

### 5.4 Estilos de Borde

| Nombre | Valor | Uso |
|---|---|---|
| `solid` | `solid` | Bordes estándar |
| `dashed` | `dashed` | Empty states, dropzone, hours dividers |
| `none` | `none` | Inputs en filter bar |

---

## 6. Layout

### 6.1 Contenedor

| Token CSS | Valor | Uso |
|---|---|---|
| `--babel-container-max` | `1200px` | `max-width` de contenedores principales |

### 6.2 Grid

| Token CSS | Valor | Uso |
|---|---|---|
| `--babel-grid-cols` | `4` | Columnas de grilla de regiones (desktop) |

### 6.3 Breakpoints

| Nombre | Valor | Uso |
|---|---|---|
| `mobile` | `480px` | Mobile — región grid 1 columna |
| `tablet` | `768px` | Tablet — breakpoint general |
| `desktop` | `981px` | Desktop — breakpoint Divi (3 colas tarjetas) |
| `desktopLg` | `1024px` | Desktop grande — perfil grid |

---

## 7. Opacidad

| Nombre | Valor | Uso |
|---|---|---|
| `glass` | `0.8` | Header glass background |
| `loading` | `0.5` | Estado de carga |
| `disabled` | `0.6` | Elemento deshabilitado |
| `hover` | `0.7` | Icono en hover |
| `muted` | `0.5` | Separador breadcrumb |
| `backdrop` | `0.98` | Backdrop glassmorphism |

---

## 8. Z-Index

| Nombre | Valor | Uso |
|---|---|---|
| `lightbox` | `999999` | Lightbox modal |
| `dropdown` | `9999` | Dropdown autocompletado |
| `sticky` | `999` | Filter bar sticky |
| `searchForm` | `100` | Search form wrapper |
| `badge` | `10` | Badge flotante sobre imagen |
| `carousel` | `10` | Botones de carrusel |

---

## 9. Animación

### 9.1 Duraciones

| Nombre | Valor | Uso |
|---|---|---|
| `fast` | `0.15s` | Hover colors |
| `normal` | `0.2s` | Buttons, inputs |
| `medium` | `0.25s` | Cards, shadows |
| `slow` | `0.3s` | Loading states |
| `slower` | `0.35s` | Card hover fallback |
| `slowest` | `0.45s` | Image scale |
| `glacial` | `0.5s` | Region cards |
| `verySlow` | `0.6s` | Logo scale |
| `ultraSlow` | `0.7s` | Decorative line |
| `megaSlow` | `0.8s` | Region bg |
| `heroZoom` | `8s` | Hero background zoom |
| `radarSpin` | `1.5s` | Radar rotation |
| `rotateRadar` | `10s` | Radar background rotation |

### 9.2 Easing

| Nombre | Valor | Uso |
|---|---|---|
| `linear` | `linear` | Animaciones lineales |
| `ease` | `ease` | Transiciones generales |
| `cubicSmooth` | `cubic-bezier(0.4, 0, 0.2, 1)` | Inputs, botones |
| `cubicBounce` | `cubic-bezier(0.25, 0.8, 0.25, 1)` | Cards, carousel |
| `cubicSnappy` | `cubic-bezier(0.16, 1, 0.3, 1)` | Dropdown slide |
| `cubicPremium` | `cubic-bezier(0.165, 0.84, 0.44, 1)` | Region cards, hero |
| `cubicRipple` | `cubic-bezier(0.24, 0, 0.38, 1)` | Radar ripple |

---

## 10. Equivalencias Mobile (Swift / Kotlin)

### 10.1 Colores como Constantes

```swift
// Swift — BabelColors.swift
import UIKit
struct BabelColors {
    static let primary = UIColor(red: 0/255, green: 0/255, blue: 0/255, alpha: 1)
    static let secondary = UIColor(red: 115/255, green: 92/255, blue: 0/255, alpha: 1)
    static let surface = UIColor(red: 249/255, green: 249/255, blue: 249/255, alpha: 1)
    static let onSurface = UIColor(red: 26/255, green: 28/255, blue: 28/255, alpha: 1)
    static let onSurfaceVariant = UIColor(red: 68/255, green: 71/255, blue: 72/255, alpha: 1)
    static let error = UIColor(red: 186/255, green: 26/255, blue: 26/255, alpha: 1)
    static let accentBlue = UIColor(red: 33/255, green: 158/255, blue: 188/255, alpha: 1)
    static let accentBlueDark = UIColor(red: 2/255, green: 48/255, blue: 71/255, alpha: 1)
    static let accentGold = UIColor(red: 255/255, green: 183/255, blue: 3/255, alpha: 1)
    static let accentGreen = UIColor(red: 37/255, green: 211/255, blue: 102/255, alpha: 1)
    static let accentEmerald = UIColor(red: 16/255, green: 185/255, blue: 129/255, alpha: 1)
    static let accentRed = UIColor(red: 239/255, green: 68/255, blue: 68/255, alpha: 1)
    static let accentSky = UIColor(red: 14/255, green: 165/255, blue: 233/255, alpha: 1)
    static let accentIndigo = UIColor(red: 79/255, green: 70/255, blue: 229/255, alpha: 1)
    static let accentBlueFilter = UIColor(red: 0/255, green: 102/255, blue: 255/255, alpha: 1)
    static let regionDark = UIColor(red: 19/255, green: 19/255, blue: 19/255, alpha: 1)
    static let regionGold = UIColor(red: 242/255, green: 202/255, blue: 80/255, alpha: 1)
    // Child Theme (sdc-*)
    static let sdcWhite = UIColor.white
    static let sdcGray50 = UIColor(red: 249/255, green: 250/255, blue: 251/255, alpha: 1)
    static let sdcGray100 = UIColor(red: 243/255, green: 244/255, blue: 246/255, alpha: 1)
    static let sdcText = UIColor(red: 26/255, green: 28/255, blue: 28/255, alpha: 1)
    static let sdcTextMuted = UIColor(red: 68/255, green: 71/255, blue: 72/255, alpha: 1)
    static let sdcBorder = UIColor(red: 226/255, green: 232/255, blue: 240/255, alpha: 1)
    static let sdcBlue = UIColor(red: 33/255, green: 158/255, blue: 188/255, alpha: 1)
}
```

```kotlin
// Kotlin — BabelColors.kt
import androidx.compose.ui.graphics.Color
object BabelColors {
    val primary = Color(0xFF000000)
    val secondary = Color(0xFF735C00)
    val surface = Color(0xFFF9F9F9)
    val onSurface = Color(0xFF1A1C1C)
    val onSurfaceVariant = Color(0xFF444748)
    val error = Color(0xFFBA1A1A)
    val accentBlue = Color(0xFF219EBC)
    val accentBlueDark = Color(0xFF023047)
    val accentGold = Color(0xFFFFB703)
    val accentGreen = Color(0xFF25D366)
    val accentEmerald = Color(0xFF10B981)
    val accentRed = Color(0xFFEF4444)
    val accentSky = Color(0xFF0EA5E9)
    val accentIndigo = Color(0xFF4F46E5)
    val accentBlueFilter = Color(0xFF0066FF)
    val regionDark = Color(0xFF131313)
    val regionGold = Color(0xFFF2CA50)
    // Child Theme (sdc-*)
    val sdcWhite = Color.White
    val sdcGray50 = Color(0xFFF9FAFB)
    val sdcGray100 = Color(0xFFF3F4F6)
    val sdcText = Color(0xFF1A1C1C)
    val sdcTextMuted = Color(0xFF444748)
    val sdcBorder = Color(0xFFE2E8F0)
    val sdcBlue = Color(0xFF219EBC)
}
```

### 10.2 Espaciado

```swift
// Swift — BabelSpacing.swift
struct BabelSpacing {
    static let unit: CGFloat = 8
    static let xxs: CGFloat = 4
    static let xs: CGFloat = 6
    static let sm: CGFloat = 8
    static let md: CGFloat = 10
    static let lg: CGFloat = 12
    static let xl: CGFloat = 14
    static let xxl: CGFloat = 16
    static let xxxl: CGFloat = 20
    static let x4l: CGFloat = 24
    static let gutter: CGFloat = 32
    static let marginMobile: CGFloat = 20
    static let marginDesktop: CGFloat = 64
    static let containerMax: CGFloat = 1200
}
```

```kotlin
// Kotlin — BabelSpacing.kt
import androidx.compose.ui.unit.dp
object BabelSpacing {
    val unit = 8.dp
    val xxs = 4.dp; val xs = 6.dp; val sm = 8.dp; val md = 10.dp
    val lg = 12.dp; val xl = 14.dp; val xxl = 16.dp; val xxxl = 20.dp
    val x4l = 24.dp; val gutter = 32.dp
    val marginMobile = 20.dp; val marginDesktop = 64.dp
    val containerMax = 1200.dp
}
```

### 10.3 Tipografía

```swift
// Swift — BabelTypography.swift
struct BabelTypography {
    static let display = Font.custom("Playfair Display", size: 22)
    static let displayLarge = Font.custom("Playfair Display", size: 28)
    static let body = Font.custom("Inter", size: 15)
    static let bodyMedium = Font.custom("Inter", size: 14)
    static let bodySmall = Font.custom("Inter", size: 13)
    static let label = Font.custom("Montserrat", size: 14).weight(.semibold)
    static let labelSmall = Font.custom("Montserrat", size: 12).weight(.semibold)
    static let caption = Font.custom("Inter", size: 12)
    static let badge = Font.custom("Montserrat", size: 10).weight(.bold)
    static let button = Font.custom("Montserrat", size: 14).weight(.semibold)
}
```

```kotlin
// Kotlin — BabelTypography.kt
object BabelTypography {
    val displayFont = FontFamily(Font(R.font.playfair_display))
    val bodyFont = FontFamily(Font(R.font.inter))
    val labelFont = FontFamily(Font(R.font.montserrat))
    val h1 = TextStyle(fontFamily = displayFont, fontSize = 52.sp, fontWeight = FontWeight.ExtraBold)
    val h2 = TextStyle(fontFamily = displayFont, fontSize = 28.sp, fontWeight = FontWeight.Bold)
    val h3 = TextStyle(fontFamily = displayFont, fontSize = 22.sp, fontWeight = FontWeight.SemiBold)
    val body = TextStyle(fontFamily = bodyFont, fontSize = 15.sp, fontWeight = FontWeight.Normal)
    val bodyMedium = TextStyle(fontFamily = bodyFont, fontSize = 14.sp, fontWeight = FontWeight.Medium)
    val caption = TextStyle(fontFamily = bodyFont, fontSize = 12.sp, fontWeight = FontWeight.Normal)
    val label = TextStyle(fontFamily = labelFont, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
    val badge = TextStyle(fontFamily = labelFont, fontSize = 10.sp, fontWeight = FontWeight.Bold)
    val button = TextStyle(fontFamily = labelFont, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
}
```

---

## 11. Ejemplos de Uso

### 11.1 Tarjeta de Negocio (HTML/CSS)

```html
<div class="babel-biz-card">
  <div class="babel-biz-card__image-wrap">
    <img class="babel-biz-card__image" src="business.jpg" alt="Negocio">
    <div class="babel-biz-card__badges">
      <span class="babel-biz-card__badge babel-biz-card__badge--featured">⭐ Destacado</span>
      <span class="babel-biz-card__badge babel-biz-card__badge--verified">✓ Verificado</span>
    </div>
  </div>
  <div class="babel-biz-card__body">
    <h3 class="babel-biz-card__title">Restaurante El Olivo</h3>
    <div class="babel-biz-card__rating">
      ★★★★☆ <span class="babel-biz-card__rating-score">4.5</span>
      <span class="babel-biz-card__rating-count">(128)</span>
    </div>
    <div class="babel-biz-card__meta">
      <span class="babel-biz-card__meta-item">🍣 Sushi</span>
      <span class="babel-biz-card__meta-sep"></span>
      <span class="babel-biz-card__meta-item">📍 Santiago</span>
    </div>
    <div class="babel-biz-card__footer">
      <span class="babel-biz-card__price">$$$</span>
      <a class="babel-biz-card__cta" href="#">Ver más →</a>
    </div>
  </div>
</div>
```

```css
.babel-biz-card {
  background: var(--babel-color-surface-container-lowest); /* #ffffff */
  border: 1px solid rgba(196, 199, 199, 0.4);
  border-radius: var(--babel-radius-lg); /* 1rem */
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.babel-biz-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.11);
}
.babel-biz-card__title {
  font-family: var(--babel-font-display); /* Playfair Display */
  font-size: 19px;
  font-weight: 600;
  color: var(--babel-color-primary); /* #000000 */
}
.babel-biz-card__badge {
  border-radius: var(--babel-radius-full); /* 9999px */
  font-family: var(--babel-font-label); /* Montserrat */
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}
```

### 11.2 Botón Primario (SwiftUI)

```swift
struct BabelPrimaryButton: View {
    let title: String
    let action: () -> Void
    var body: some View {
        Button(action: action) {
            Text(title)
                .font(.custom("Montserrat", size: 14).weight(.semibold))
                .foregroundColor(.white)
                .padding(.horizontal, 28)
                .padding(.vertical, 12)
                .background(BabelColors.accentBlue)
                .cornerRadius(10)
                .shadow(color: Color(red: 33/255, green: 158/255, blue: 188/255).opacity(0.15), radius: 8, y: 2)
        }
    }
}
```

### 11.3 Botón Primario (Jetpack Compose)

```kotlin
@Composable
fun BabelPrimaryButton(text: String, onClick: () -> Unit) {
    Button(
        onClick = onClick,
        modifier = Modifier
            .shadow(2.dp, RoundedCornerShape(10.dp))
            .background(BabelColors.accentBlue, RoundedCornerShape(10.dp))
            .padding(horizontal = 28.dp, vertical = 12.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = BabelColors.accentBlue,
            contentColor = Color.White
        )
    ) {
        Text(
            text = text,
            fontFamily = BabelTypography.labelFont,
            fontSize = 14.sp,
            fontWeight = FontWeight.SemiBold,
            letterSpacing = 0.5.sp
        )
    }
}
```

### 11.4 Tarjeta de Negocio (SwiftUI)

```swift
struct BusinessCardView: View {
    let business: Business
    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            ZStack(alignment: .topTrailing) {
                AsyncImage(url: business.imageURL) { image in
                    image.resizable().aspectRatio(contentMode: .fill)
                } placeholder: {
                    Rectangle().fill(Color(red: 238/255, green: 238/255, blue: 238/255))
                }
                .frame(height: 220).clipped()
                if business.isFeatured {
                    Text("⭐ Destacado")
                        .font(.custom("Montserrat", size: 10).weight(.bold))
                        .foregroundColor(Color(red: 36/255, green: 26/255, blue: 0/255))
                        .padding(.horizontal, 10).padding(.vertical, 4)
                        .background(Color(red: 233/255, green: 195/255, blue: 73/255).opacity(0.92))
                        .cornerRadius(9999)
                        .padding(12)
                }
            }
            VStack(alignment: .leading, spacing: 10) {
                Text(business.name)
                    .font(.custom("Playfair Display", size: 19).weight(.semibold))
                    .foregroundColor(.black)
                HStack(spacing: 5) {
                    Text(String(repeating: "★", count: Int(business.rating)))
                        .foregroundColor(Color(red: 233/255, green: 195/255, blue: 73/255))
                        .font(.system(size: 13))
                    Text("\(business.rating, specifier: "%.1f")")
                        .font(.custom("Montserrat", size: 13).weight(.bold))
                        .foregroundColor(Color(red: 26/255, green: 28/255, blue: 28/255))
                    Text("(\(business.reviewCount))")
                        .font(.custom("Montserrat", size: 12))
                        .foregroundColor(Color(red: 68/255, green: 71/255, blue: 72/255))
                }
                HStack(spacing: 6) {
                    Text(business.category).font(.custom("Inter", size: 12))
                        .foregroundColor(Color(red: 68/255, green: 71/255, blue: 72/255))
                    Circle().fill(Color(red: 196/255, green: 199/255, blue: 199/255)).frame(width: 3, height: 3)
                    Text(business.region).font(.custom("Inter", size: 12))
                        .foregroundColor(Color(red: 68/255, green: 71/255, blue: 72/255))
                }
                HStack {
                    Text(business.priceLevel)
                        .font(.custom("Montserrat", size: 12).weight(.semibold))
                        .foregroundColor(Color(red: 68/255, green: 71/255, blue: 72/255))
                    Spacer()
                    Text("Ver más →")
                        .font(.custom("Montserrat", size: 11).weight(.bold))
                        .foregroundColor(.black).tracking(0.09)
                }
                .padding(.top, 14)
                .overlay(Rectangle().fill(Color(red: 196/255, green: 199/255, blue: 199/255).opacity(0.35)).frame(height: 1), alignment: .top)
            }.padding(20)
        }
        .background(Color.white)
        .cornerRadius(16)
        .shadow(color: .black.opacity(0.06), radius: 20, y: 4)
    }
}
```

### 11.5 Formulario de Búsqueda (CSS)

```css
.babel-search-form-wrapper {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px) saturate(180%);
  border: 1px solid rgba(209, 213, 219, 0.4);
  border-radius: 50px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  padding: 8px 8px 8px 16px;
}
.babel-search-submit-btn {
  background-color: var(--sdc-blue, #219ebc);
  color: #ffffff;
  border-radius: 10px;
  font-family: var(--babel-font-label);
  font-size: 15px;
  font-weight: 700;
  padding: 12px 28px;
  box-shadow: 0 4px 14px rgba(33, 158, 188, 0.25);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.babel-search-submit-btn:hover {
  background-color: #023047;
  box-shadow: 0 6px 20px rgba(2, 48, 71, 0.3);
}
```

### 11.6 Tarjeta de Región (CSS)

```css
.babel-region-card {
  border-radius: var(--babel-radius-lg, 1rem);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(242, 202, 80, 0.1);
  transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.babel-region-card:hover {
  border-color: rgba(242, 202, 80, 0.4);
  transform: translateY(-4px);
  box-shadow: 0 15px 35px rgba(242, 202, 80, 0.08), 0 5px 15px rgba(0, 0, 0, 0.4);
}
.babel-region-title {
  font-family: var(--babel-font-display);
  font-size: 22px;
  color: #e4e2e1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}
```

---

## 📝 Notas

- **Tokens `--babel-*`**: Declarados en `:root` de `babel-public-v717.css`. Son los tokens principales del plugin.
- **Tokens `--sdc-*`**: Provienen del child theme "Soy de Chile". Se usan en el plugin vía `var(--sdc-*)`. Incluyen: `--sdc-white`, `--sdc-gray-50`, `--sdc-gray-100`, `--sdc-text`, `--sdc-text-muted`, `--sdc-border`, `--sdc-blue`, `--sdc-shadow-md`.
- **Tokens `--bd-*`**: Variables locales del perfil premium (`.bd-profile-wrapper`). Incluyen: `--bd-primary`, `--bd-primary-hover`, `--bd-bg`, `--bd-border`, `--bd-text`, `--bd-text-muted`, `--bd-shadow`, `--bd-shadow-lg`, `--bd-radius-sm/md/lg`.
- **Tokens `--md-sys-*`**: Alias legacy para compatibilidad con clases existentes. Resuelven a los tokens `--babel-*` vía `var()`.
- **Colores inline**: Muchos colores se usan directamente en propiedades CSS sin variable propia. Todos están documentados en la sección "Colores Inline".
- **Fuentes requeridas**: Playfair Display, Inter, Montserrat (Google Fonts). Opcional: Outfit (perfil premium).
