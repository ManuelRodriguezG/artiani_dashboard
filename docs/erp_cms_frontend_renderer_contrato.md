# CMS - Contrato frontend renderer

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-11  
Estado: Contrato read-only inicial para implementar frontend ecommerce

Plan builder visual Wokiee/Artiani: `docs/erp_cms_visual_builder_wokiee_plan.md`

## Proposito

Este documento explica como el frontend ecommerce debe consumir el CMS del ERP para renderizar contenido y plantillas de vista sin leer archivos internos del panel.

## Regla principal

El frontend publico consume solo endpoints publicos:

- `/ecommercePublico/configuracion_inicial`
- `/ecommercePublico/contenido_manifest`
- `/ecommercePublico/contenido_pagina`

El frontend no debe consumir rutas internas `/cms/*`.

## Primer render recomendado

Endpoint:

```http
GET /ecommercePublico/configuracion_inicial
```

Campos relevantes:

```json
{
  "depurar": {
    "contenido_inicial": {
      "home": {
        "pagina": "home",
        "plantilla": "artiani_default",
        "plantilla_vista": {},
        "slots": [],
        "resumen": {},
        "fuente": "default_readonly"
      }
    }
  }
}
```

Uso:

1. Cargar configuracion inicial.
2. Tomar `depurar.contenido_inicial.home`.
3. Renderizar `plantilla_vista.secciones`.
4. Para cada seccion, buscar su contenido en `slots`.
5. Usar componentes frontend predefinidos.

## Navegacion o refresco de pagina

Endpoint:

```http
GET /ecommercePublico/contenido_manifest
GET /ecommercePublico/contenido_pagina?pagina=home
GET /ecommercePublico/contenido_pagina?pagina=categoria&categoria=peces
GET /ecommercePublico/contenido_pagina?pagina=catalogo
```

`contenido_manifest` permite descubrir `plantillas_vista` y `componentes_frontend` disponibles en modo read-only. `contenido_pagina` entrega la plantilla activa para la pagina solicitada junto con sus slots y bloques.

Campos relevantes:

```json
{
  "depurar": {
    "pagina": "home",
    "plantilla": "artiani_default",
    "plantilla_vista": {
      "codigo": "wokiee_home_default",
      "layout": "storefront_wokiee_v1",
      "secciones": [
        {
          "slot": "home.hero",
          "componente": "HeroSlider",
          "variante": "full_width",
          "orden": 1
        }
      ]
    },
    "slots": [
      {
        "slot": "home.hero",
        "bloques": []
      }
    ]
  }
}
```

## Renderer frontend

El frontend debe tener un mapa local de componentes permitidos:

```js
const componentes = {
  HeroSlider,
  PromoStrip,
  CategoryGrid,
  ProductCarousel,
  ImageCardGrid,
  SafeHtmlBlock
};
```

Pseudocodigo:

```js
function renderPagina(cms) {
  const plantillaVista = cms.plantilla_vista;
  const slots = cms.slots || [];

  return plantillaVista.secciones
    .sort((a, b) => a.orden - b.orden)
    .map((seccion) => {
      const Componente = componentes[seccion.componente];
      const slotContenido = slots.find((slot) => slot.slot === seccion.slot);

      if (!Componente) {
        return null;
      }

      return Componente({
        variante: seccion.variante,
        slot: seccion.slot,
        bloques: slotContenido ? slotContenido.bloques || [] : []
      });
    });
}
```

## Componentes iniciales

- `HeroSlider`
- `PromoStrip`
- `CategoryGrid`
- `ProductCarousel`
- `ImageCardGrid`
- `SafeHtmlBlock`

## Compatibilidad esperada

| Componente | Bloques esperados | Slots comunes |
| --- | --- | --- |
| `HeroSlider` | `hero_banner`, `category_banner` | `home.hero`, `categoria.banner` |
| `PromoStrip` | `promo_strip` | `home.promo`, `catalogo.encabezado` |
| `CategoryGrid` | `image_card_grid` | `home.categorias` |
| `ProductCarousel` | `product_collection` | `home.destacados`, `categoria.productos` |
| `ImageCardGrid` | `image_card_grid` | `home.categorias`, `home.promo` |
| `SafeHtmlBlock` | `content_html_safe` | `catalogo.encabezado` |

## Guardrails

El frontend debe respetar:

- No ejecutar HTML libre como script.
- No cargar JS desde el CMS.
- No cargar CSS arbitrario desde el CMS.
- No leer archivos internos del ERP.
- No llamar endpoints internos `/cms/*`.
- Ignorar componentes desconocidos con fallback seguro.
- Mostrar estado vacio si un slot no tiene bloques.
- Usar `/ecommercePublico/catalogo` para resolver colecciones de productos.

## Relacion entre CMS Contenido y CMS Frontend

- `slots`: espacios editoriales.
- `bloques`: contenido editable.
- `plantilla_vista`: layout y orden visual.
- `componente`: pieza frontend preprogramada.
- `variante`: modo visual permitido.

Ejemplo:

```json
{
  "slot": "home.destacados",
  "componente": "ProductCarousel",
  "variante": "compact_cards",
  "bloques": [
    {
      "tipo": "product_collection",
      "source": {
        "endpoint": "/ecommercePublico/catalogo?destacado=1&limite=8"
      }
    }
  ]
}
```

## Estado actual

El contrato esta disponible en modo default/read-only:

- No lee contenido publicado desde BD todavia.
- No refleja publicaciones reales del CMS.
- Usa defaults seguros para probar el renderer.
- La persistencia real queda pendiente de respaldo y DDL autorizado.
