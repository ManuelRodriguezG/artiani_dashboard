# ERP Ecommerce publico - SEO para frontend externo

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: guia para consumir `GET /ecommercePublico/seo`.

## Objetivo

El ERP entrega metadatos SEO como contrato JSON. El frontend externo decide como renderizar:

- meta title;
- meta description;
- canonical;
- Open Graph;
- `robots.txt`;
- `sitemap.xml`;
- JSON-LD.

El ERP no renderiza la tienda ni archivos SEO en Fase 1.

## Endpoint

```http
GET http://panel.com.local/ecommercePublico/seo
```

Respuesta principal:

- `depurar.meta`
- `depurar.robots`
- `depurar.sitemap`
- `depurar.json_ld`
- `depurar.guardrails`

## Uso recomendado

En arranque:

1. Consultar `/configuracion`.
2. Consultar `/seo`.
3. Si `meta.canonical_base` viene vacio, no publicar canonical definitivo.
4. Si `sitemap.productos` viene vacio, generar sitemap solo con rutas estaticas o devolver noindex temporal.
5. Generar JSON-LD de producto usando el item real de `/producto/{slug}` y el contrato `json_ld.product_contract`.

## Reglas

- No indexar catalogo real hasta que `green_gate` sea `ok=true`.
- No inventar productos en sitemap.
- No mostrar stock exacto en JSON-LD.
- No incluir precios si `item.precio` es `null`.
- No incluir URLs canonicas definitivas si `url_sitio_publico` no esta configurado.
- No usar fixtures como sitemap real.

## Estados

Mientras `senal_frontend=amarillo_mock_contratos`:

- permitir meta basica;
- evitar sitemap con productos reales;
- usar `noindex` si la web publica ya esta desplegada.

Cuando `senal_frontend=verde_datos_reales`:

- habilitar sitemap de productos publicados;
- permitir indexacion;
- generar JSON-LD por ficha de producto.

## Validacion

Desde el ERP:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
```

