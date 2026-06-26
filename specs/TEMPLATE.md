# PLANTILLA DE ESPECIFICACIÓN TÉCNICA — Babel Directory

> **Versión:** 1.0  
> **Propósito:** Plantilla obligatoria para toda especificación de nueva funcionalidad en Babel Directory.  
> **Regla:** Ningún código PHP/JS se crea o modifica sin un spec aprobado en `specs/`.

---

## 1. IDENTIFICACIÓN

| Campo | Valor |
|-------|-------|
| **Spec ID** | `XX-nombre-corto` |
| **Nombre** | Nombre completo de la funcionalidad |
| **Autor** | [Nombre] |
| **Fecha** | YYYY-MM-DD |
| **Estado** | `Borrador | Para revisión | Aprobado | Implementado` |
| **Prioridad** | `Alta | Media | Baja` |
| **Dependencias** | Spec IDs o issues que deben completarse antes |

---

## 2. RESUMEN EJECUTIVO

Descripción de alto nivel (2-3 párrafos) que explique:

- ¿Qué problema resuelve?
- ¿Por qué es necesaria ahora?
- ¿Qué impacto de negocio tiene?
- ¿Cómo se alinea con el modelo de negocio (municipalidades, Pymes, monetización)?

---

## 3. MODELO DE NEGOCIO

### 3.1 Propuesta de Valor
¿Qué valor entrega al usuario/cliente?

### 3.2 Estrategia de Monetización
- Pricing, planes,周期, descuentos
- ¿Cómo se integra con WooCommerce existente (BABEL-PRO, BABEL-PREMIUM)?
- ¿Requiere nuevo producto WooCommerce?

### 3.3 Segmento Objetivo
- ¿A quién va dirigido? (Pymes, municipalidades, instituciones)
- ¿Diferenciación por rol/plan?

---

## 4. ESTRUCTURA DE DATOS

### 4.1 Post Types / Taxonomías / Meta Keys

Listar todos los cambios necesarios en la estructura de datos:

**Nuevos Meta Keys:**
| Meta Key | Tipo | CPT | Descripción | Default |
|----------|------|-----|-------------|---------|
| `_babel_ejemplo` | `string` | `babel_business` | Descripción del campo | `''` |

**Meta Keys existentes que se modifican:**
| Meta Key | Cambio | Razón |
|----------|--------|-------|
| `_babel_existente` | Se agrega nuevo valor posible | ... |

### 4.2 Tablas Personalizadas (si aplica)

**Nueva tabla o modificación:**
```sql
-- Si se agrega columna a tabla existente
ALTER TABLE wp_bd_search_index ADD COLUMN ...
```

### 4.3 Schema REST API

```json
{
  "endpoint": "/wp-json/babel/v1/...",
  "method": "GET|POST",
  "params": {},
  "response": {}
}
```

---

## 5. FLUJOS DE USUARIO

### 5.1 Flujo Principal

```
[Paso 1] → [Paso 2] → [Paso 3] → [Paso 4]
```

Descripción detallada de cada paso.

### 5.2 Flujo Administrativo (Backend)

```
[Admin paso 1] → [Admin paso 2] → ...
```

### 5.3 Flujo de Pago / Upgrade (si aplica)

```
Usuario → Frontend Dashboard → Selecciona plan → WooCommerce Checkout → Webhook → Activación
```

### 5.4 Casos Borde

| Caso | Comportamiento Esperado |
|------|------------------------|
| Usuario sin plan | ... |
| Pago fallido | ... |
| Producto WooCommerce no existe | Auto-creación (patrón existente en `class-frontend-dashboard.php`) |
| Expiración | ... |
| Downgrade | ... |
| Multi-slot concurrente | ... |

---

## 6. UI / UX

### 6.1 Componentes Visuales

Listar cada nuevo shortcode o modificación:

| Shortcode / Componente | Propósito | Atributos | ¿Nuevo o existente? |
|------------------------|-----------|-----------|---------------------|
| `[bd_ejemplo]` | Descripción | `limit`, `region` | Nuevo |

### 6.2 Estados de UI

| Estado | Descripción | Visual |
|--------|-------------|--------|
| Vacío (empty) | No hay datos | Mensaje + CTA |
| Carga (loading) | Cargando datos | Skeleton/spinner |
| Error | Fallo en datos | Mensaje + retry |
| Sin permisos | Usuario no autorizado | Mensaje + login CTA |

### 6.3 Design Tokens Utilizados

Listar tokens de `DESIGN_TOKENS.md` que aplican:
- Colores: `--babel-color-*`
- Tipografía: `--babel-font-*`
- Sombras: `cardFeatured`, etc.
- Radios: `--babel-radius-*`

### 6.4 Responsive

- Desktop (>981px):
- Tablet (768-981px):
- Mobile (<768px):

---

## 7. LÓGICA DE NEGOCIO

### 7.1 Reglas de Negocio

Lista numerada de reglas imperativas:

1. **Regla 1:** ...
2. **Regla 2:** ...
3. **Regla 3:** ...

### 7.2 Algoritmos / Cálculos

Descripción de lógica no trivial (ordenamiento, priorización, rotación, expiración).

### 7.3 Cache Strategy

- Transients: claves, duración, invalidación
- Object cache: ¿aplica?

---

## 8. INTEGRACIONES

### 8.1 WooCommerce

- ¿Nuevos productos? (SKU, precio, tipo)
- ¿Hooks existentes a aprovechar? (`woocommerce_order_status_completed`)
- ¿Auto-creación de productos?

### 8.2 Pasarela de Pago

- WebPay / MercadoPago: ¿cambios en webhooks?
- Meta keys de pago a registrar

### 8.3 Cron / Programación

| Evento | Schedule | Acción |
|--------|----------|--------|
| `babel_featured_expiration` | `daily` | Revisar expiraciones y desmarcar |

---

## 9. SEGURIDAD

- **Nonces:** ¿Se requiere nuevo nonce?
- **Capabilities:** ¿Nuevos roles/capabilities?
- **Sanitización:** ¿Campos a sanitizar?
- **Rate limiting:** ¿Aplica?
- **Webhook signatures:** ¿HMAC existente suficiente?

---

## 10. MIGRACIÓN Y BACKWARD COMPATIBILITY

- ¿Cambios en tabla existente?
- ¿Meta keys existentes que cambian de semántica?
- ¿Shortcodes existentes que modifican su comportamiento?
- ¿REST API que modifica respuesta existente?
- Script de migración (si aplica)

---

## 11. CRITERIOS DE ACEPTACIÓN

### 11.1 Funcionales (QA)

| ID | Criterio | Cómo se prueba |
|----|----------|----------------|
| AC-01 | ... | ... |
| AC-02 | ... | ... |
| AC-03 | ... | ... |
| AC-04 | ... | ... |

### 11.2 No Funcionales

- Rendimiento: ... (ej: <200ms en listado)
- Compatibilidad: ... (ej: Divi 5, Twenty Twenty-Four, REST API headless)
- Accesibilidad: ... (ej: ARIA labels, contraste)
- Idiomas: ... (ej: español, textos traducibles con `__()`)

### 11.3 Seguridad

| ID | Criterio |
|----|----------|
| SEC-01 | ... |

---

## 12. ARCHIVOS A MODIFICAR / CREAR

### Nuevos Archivos
- `includes/class-featured-listings.php`
- `assets/js/babel-featured.js`

### Archivos a Modificar
- `babel-directory.php` — Registrar nuevo componente
- `includes/class-cpt.php` — Nuevos meta keys en REST
- `includes/class-shortcodes.php` — Nuevo shortcode
- `includes/class-search-index.php` — Nuevos campos de índice
- `includes/class-frontend-dashboard.php` — Upgrade UI
- `assets/css/babel-public.css` — Nuevos estilos

### Archivos Sin Cambios (confirmar)
- N/A

---

## 13. NOTAS TÉCNICAS

### 13.1 Patrones a Seguir
- Namespace `Babel\Directory` (PSR-4)
- Singleton pattern consistente
- REST API sobre `admin-ajax.php`
- Vanilla JS sin jQuery
- CSS BEM sin Tailwind
- Textos traducibles con `__()`, `esc_html__()`, etc.

### 13.2 Pitfalls Conocidos
- ...

### 13.3 Decisiones de Diseño
- ...

---

## 14. CHANGELOG

| Fecha | Versión | Cambio |
|-------|---------|--------|
| YYYY-MM-DD | 1.0 | Creación inicial |

---

*Esta plantilla es obligatoria. No se aceptan specs que omitan secciones sin justificación documentada.*
