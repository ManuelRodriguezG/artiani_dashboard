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

## Actualizacion 2026-09-14 - SEO por ficha de producto

`GET /ecommercePublico/producto/{slug}` entrega `depurar.seo` listo para que el frontend renderice metadatos de la ficha sin leer archivos internos del ERP ni recalcular reglas de Catalogo.

Campos principales:

- `title`
- `description`
- `canonical`
- `canonical_path`
- `canonical_url`
- `robots`
- `og_type`
- `og_title`
- `og_description`
- `og_image`
- `og_image_width`
- `og_image_height`
- `og_image_alt`
- `twitter_card`
- `json_ld`

Reglas:

- `canonical` y `canonical_path` son rutas publicas, por ejemplo `/producto/{slug}`.
- `canonical_url` y `og_image` son absolutas cuando hay dominio productivo/configuracion SEO disponible.
- `og_type` para producto es `product`.
- `twitter_card` recomendado es `summary_large_image`.
- Actualizacion IA Codex GPT-6, 2026-09-25: `item.descripcion_publica` contiene solo contenido editorial saneado. Un campo vacio es intencional y no usa fallback del Catalogo ERP; `item.descripcion_publica_fuente=publicacion_ecommerce`. Los metadatos SEO se mantienen planos y cortos. Ver `docs/erp_ecommerce_descripciones_agrupacion.md` para contrato y despliegue pendiente.
- Para producto agrupado o variantes, el frontend debe usar `depurar.grupo_producto`, `depurar.variantes` y `depurar.fase_2.resumen_ui.mostrar_variantes`; no debe deducir agrupaciones leyendo tablas internas.

## Actualizacion 2026-09-03 - Migracion URLs y contratos SEO separados

Se agrega una capa explicita para migracion SEO del ecommerce publico. El ERP administra y entrega la informacion; el frontend externo la aplica en runtime/build/hosting.

Endpoints publicos read-only:

- `GET /ecommercePublico/seo_estado`
- `GET /ecommercePublico/seo_urls`
- `GET /ecommercePublico/seo_redirecciones`
- `GET /ecommercePublico/seo_sitemap`
- `GET /ecommercePublico/seo_robots`

Actualizacion 2026-09-17 - URLs 410:

- `GET /ecommercePublico/seo_redirecciones` devuelve redirecciones reales en `depurar.redirecciones` y URLs descontinuadas en `depurar.gone` / `depurar.urls_410`.
- El frontend debe aplicar `status=410` antes de renderizar y responder HTTP `410 Gone` real.
- Las URLs 410 no llevan destino, no deben generar canonical y no entran al sitemap.
- Las reglas 410 se guardan desde la mesa SEO con el mismo endpoint interno de regla manual, usando `status=410`, `tipo=gone` y destino vacio.

Actualizacion 2026-09-17 - Verificacion local:

- `GET /ecommercePublico/seo_verificacion` abre una vista independiente para probar reglas SEO contra `http://artiani.com.local`.
- `GET /ecommercePublico/seo_verificacion_erp` consolida reglas 301/410 y sitemap, convierte las URLs productivas a frontend local y hace pruebas HTTP read-only.
- La tabla de reglas verifica que cada origen viejo responda el status esperado y, en 301/302/308, que el `Location` apunte al path canonico nuevo.
- La tabla de sitemap muestra las URLs indexables que hoy entregaria `/ecommercePublico/seo_sitemap`; sirve para revisar cuales entraran a `/sitemap.xml` en produccion.
- Si el frontend local aun no implementa la ejecucion de 301/410, esta vista mostrara `Revisar`; eso significa que falta integrar el frontend, no necesariamente que la regla del ERP este mal.
- La vista permite marcar URLs como probadas en base de datos con `erp_ecommerce_seo_verificaciones`; no depende de `localStorage`.
- `GET /ecommercePublico/seo_verificaciones_persistidas_erp` consulta las marcas compartidas y `POST /ecommercePublico/seo_verificacion_guardar_erp` guarda/quita la marca operativa.
- Si la tabla `erp_ecommerce_seo_verificaciones` aun no existe, la verificacion sigue funcionando en modo lectura, pero los checks quedan bloqueados hasta aplicar el plan de esquema SEO.
- Las reglas 301/410 se validan en marcas separadas: `regla_origen` confirma que la URL vieja responde 301/410 segun corresponda, y `regla_destino` confirma que la URL nueva abre correctamente.
- Por compatibilidad, la clave historica `regla|origen|status|destino` representa la prueba de destino nuevo; asi las marcas ya aprobadas no se pierden.
- Al marcar un destino nuevo como probado, la UI marca tambien los destinos identicos presentes en la muestra cargada para acelerar revision.
- La verificacion carga por defecto hasta 1000 reglas SEO y permite subir a 1500; el KPI muestra `mostradas / disponibles` para evitar confundir muestra con total.
- El sitemap ya no toma productos desde `catalogoPublico()` porque ese endpoint pagina a maximo 60 productos; usa una consulta SEO directa de publicaciones publicadas/activas, con SKU/producto activo, precio vigente si aplica y sin fraccionarios.
- La verificacion de sitemap carga por defecto hasta 2000 URLs y permite subir a 5000; el KPI tambien muestra `mostradas / disponibles`.

Actualizacion 2026-09-18 - Gobierno Catalogo Ecommerce:

- `GET /ecommercePublico/catalogo_gobierno` abre una vista interna read-only para gobernar la relacion entre Catalogo ERP y Ecommerce publico.
- `GET /ecommercePublico/catalogo_gobierno_erp` entrega tablero, filtros, publicaciones, alertas y resumen SEO sin escribir BD.
- Catalogo ERP es la fuente de verdad operativa: producto, SKU, marca, categoria, imagenes, inventario y listas de precios.
- Ecommerce es la capa publica/SEO/comercial: publicacion, nombre publico, slug, URL, canonical, visibilidad, cotizacion, WhatsApp y decisiones editoriales.
- Cambiar nombre en Catalogo ERP no cambia automaticamente el slug publico.
- Los cambios de slug deben hacerse desde la capa Ecommerce/SEO, conservando historial o redireccion cuando aplique.
- Los precios ecommerce se leen desde listas ERP activas; una publicacion normal sin precio activo debe aparecer como alerta critica.
- La vista nueva no ejecuta DDL, no publica productos y no cambia redirecciones; deriva tablero, alertas y filtros desde la auditoria de publicabilidad existente.
- Las alertas de Catalogo Ecommerce se sincronizan con `erp_notificaciones` mediante accion explicita protegida por `catalogo.editar`; la consulta normal sigue siendo solo lectura.
- La sincronizacion crea o actualiza notificaciones por huella, pero no resuelve automaticamente alertas antiguas para no ocultar trabajo por cambios de filtro, paginacion o muestra.
- Vistas relacionadas: `/ecommercePublico/publicaciones`, `/ecommercePublico/seo_migracion` y `/ecommercePublico/seo_verificacion`.

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
