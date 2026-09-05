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

## Actualizacion 2026-09-03 - Migracion URLs y contratos SEO separados

Se agrega una capa explicita para migracion SEO del ecommerce publico. El ERP administra y entrega la informacion; el frontend externo la aplica en runtime/build/hosting.

Endpoints publicos read-only:

- `GET /ecommercePublico/seo_estado`
- `GET /ecommercePublico/seo_urls`
- `GET /ecommercePublico/seo_redirecciones`
- `GET /ecommercePublico/seo_sitemap`
- `GET /ecommercePublico/seo_robots`

Regla canonica: ninguna URL publica SEO debe iniciar con `/ecommercePublico`. Esa ruta es API interna. Las URLs canonicas oficiales son:

- `/`
- `/categorias`
- `/categoria/{path_slug}`
- `/producto/{slug}`
- `/marca/{slug}`
- `/buscar/{termino}`
- `/contacto`
- `/como-comprar`
- `/aviso-de-privacidad`
- `/politicas-cambios`

Decision 2026-09-04: el dominio productivo permanece como `https://artiani.com.mx`. `http://artiani.com.local` es solo entorno local/preview para construir la nueva estructura. La migracion SEO debe mapear rutas viejas del mismo dominio productivo hacia las nuevas URIs, por ejemplo `https://artiani.com.mx/ruta-vieja` -> `https://artiani.com.mx/producto/slug-nuevo`.

El endpoint legacy `GET /ecommercePublico/seo` se conserva por compatibilidad, pero ahora sus rutas sugeridas tambien evitan `/ecommercePublico` para canonical/sitemap.

Tablas propuestas sin aplicar:

- `erp_ecommerce_seo_configuracion`
- `erp_ecommerce_seo_urls`
- `erp_ecommerce_seo_redirecciones`
- `erp_ecommerce_seo_urls_viejas`
- `erp_ecommerce_seo_errores_404`

Endpoints internos read-only para revisar DDL:

- `GET /ecommercePublico/esquema_auditar_seo_migracion`
- `GET /ecommercePublico/esquema_plan_seo_migracion`
- `GET /ecommercePublico/seo_dashboard_erp`
- `POST /ecommercePublico/seo_urls_sincronizar_plan_erp`
- `POST /ecommercePublico/seo_urls_viejas_importar_plan_erp`
- `POST /ecommercePublico/seo_redireccion_plan_erp`

Endpoints internos autorizados para persistencia:

- `POST /ecommercePublico/seo_urls_sincronizar_erp`
  - permiso: `catalogo.editar`
  - token requerido: `ECOMMERCE_SEO_SYNC_URLS_CANONICAS`
  - escribe en `erp_ecommerce_seo_urls`
  - no desactiva URLs ausentes automaticamente
- `POST /ecommercePublico/seo_urls_viejas_importar_erp`
  - permiso: `catalogo.editar`
  - token requerido: `ECOMMERCE_SEO_IMPORTAR_URLS_VIEJAS`
  - escribe en `erp_ecommerce_seo_urls_viejas`
  - no crea redirecciones 301 automaticamente
- `POST /ecommercePublico/seo_redireccion_guardar_erp`
  - permiso: `catalogo.editar`
  - token requerido: `ECOMMERCE_SEO_GUARDAR_REDIRECCION`
  - escribe en `erp_ecommerce_seo_redirecciones`
  - reutiliza la validacion read-only antes de guardar

Consola interna:

- `GET /ecommercePublico/seo_migracion`

La consola permite pegar URLs viejas para generar un plan read-only de importacion y validar redirecciones manuales. Estos planes no insertan datos; devuelven sugerencias, bloqueos y SQL preview para revisar antes de autorizar persistencia. Las escrituras quedan disponibles solo por endpoints con permiso, CSRF, token operativo y tablas SEO aplicadas.

Runbook de aplicacion DDL:

- `docs/erp_ecommerce_seo_migracion_runbook_aplicacion.md`

Queda pendiente aplicar DDL con autorizacion explicita, importar URLs viejas, revisar equivalencias y activar redirecciones 301 reales. El frontend debe aplicar solo redirecciones con `activo=true`, antes de renderizar, y nunca redirigir todo a home si existe una categoria o producto cercano.

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
