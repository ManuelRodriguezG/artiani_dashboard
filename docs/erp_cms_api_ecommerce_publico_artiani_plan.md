# CMS / API ecommerce publico Artiani

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-19  
Estado: plan rector vivo para CMS frontend y API publica

## Objetivo

Que el frontend publico Artiani pueda construir la tienda desde API, sin hardcodear textos y sin leer archivos internos del ERP.

El CMS administra:

- marca y datos del negocio
- header y footer
- Home
- banners e imagenes editoriales
- paginas estaticas
- politicas
- categorias SEO
- marcas SEO
- navegacion
- filtros inteligentes

El catalogo/API publica administra:

- catalogo filtrado seguro
- producto por slug
- categorias y marcas publicas
- precios ya calculados por backend
- reglas para no devolver stock exacto ni productos no publicados

## Endpoints existentes base

- `GET /ecommercePublico/configuracion_inicial`
- `GET /ecommercePublico/bootstrap` como alias legacy
- `GET /ecommercePublico/contenido_manifest`
- `GET /ecommercePublico/contenido_pagina?pagina=home`
- `GET /ecommercePublico/politicas`
- `GET /ecommercePublico/politica/{slug}`
- `GET /ecommercePublico/categorias`
- `GET /ecommercePublico/navegacion`
- `GET /ecommercePublico/filtros`
- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`
- `GET /ecommercePublico/seo`
- `GET /ecommercePublico/frontend_handoff`

## Estructura CMS recomendada

El modulo debe vivir bajo `CMS`, no mezclarse como submodulo operativo de Ecommerce.

Rutas operativas:

- `/cms/frontend/home`
- `/cms/frontend/global`
- `/cms/frontend/navegacion`
- `/cms/frontend/categorias`
- `/cms/frontend/marcas`
- `/cms/frontend/paginas`
- `/cms/frontend/politicas`
- `/cms/media`
- `/cms/media_admin_preflight_erp`

Rutas avanzadas:

- `/cms/contenido`
- `/cms/plantillas`
- `/cms/slots`
- `/cms/json`
- `/cms/persistencia`
- `/cms/frontend_plantillas`
- `/cms/frontend_componentes`
- `/cms/frontend_activaciones`

## Decisiones

- El endpoint principal de arranque es `/ecommercePublico/configuracion_inicial`.
- `/ecommercePublico/bootstrap` queda solo como alias legacy.
- `frontend_bootstrap` puede existir como alias futuro si el frontend lo pide, pero no debe reemplazar el nombre principal.
- El frontend consume API publica; no lee docs, archivos ni rutas internas del ERP.
- El CMS no edita catalogo, precios, inventario ni publicaciones de producto.
- Las imagenes se seleccionan desde `CMS > Media / Archivos`; la captura manual de URL es transicional.
- Las categorias y marcas reales vienen del ERP/API publica; CMS solo enriquece visibilidad, imagenes, orden, destacado y SEO.
- Si un filtro publico es invalido, la API devuelve `items=[]` y `total=0`; nunca devuelve todo el catalogo por error.

## Fases

### Fase CMS 1

- `CMS > Frontend > Global`
- `CMS > Frontend > Home`
- `CMS > Frontend > Politicas`
- `CMS > Frontend > Paginas`
- `CMS > Media / Archivos`

### Fase CMS 2

- `CMS > Frontend > Categorias`
- `CMS > Frontend > Marcas`
- imagenes, SEO, orden, visible y destacado
- reutilizacion de imagen ERP o override editorial CMS

### Fase API 3

- `GET /ecommercePublico/marcas`
- `marca_slug` en `/ecommercePublico/catalogo`
- `categoria_id` como alias de categoria
- `path_slug` en categorias
- filtros invalidos devuelven vacio

### Fase API 4

- `GET /ecommercePublico/catalogo_filtros`
- conteos dinamicos segun filtros activos
- categorias y marcas con `disabled=true` cuando no hay resultados

### Fase frontend

- consumir `/ecommercePublico/configuracion_inicial` para layout global
- consumir `/ecommercePublico/categorias` para menu y landings
- consumir `/ecommercePublico/marcas` para `/marca/{slug}`
- consumir `/ecommercePublico/catalogo_filtros` para filtros reales

## Prioridad inmediata

1. Consolidar `CMS > Frontend > Home`.
2. Activar `CMS > Media / Archivos` como biblioteca real con persistencia.
3. Crear `CMS > Frontend > Global`.
4. Crear `CMS > Frontend > Navegacion`.
5. Crear `CMS > Frontend > Categorias`.
6. Crear `CMS > Frontend > Marcas`.
7. Crear paginas estaticas y politicas.
8. Conectar API publica final.

## Media CMS

La biblioteca real de media debe separarse del contenido para poder reutilizar archivos en Home, categorias, marcas, paginas y politicas.

Estado actual:

- `GET /cms/media_admin_preflight_erp`
- `GET /cms/media_admin_listar_erp`
- `POST /cms/media_admin_subir_erp`
- carpeta publica activa: `/assets/media/cms/ecommerce`
- tabla archivos activa: `erp_ecommerce_media_archivos`
- tabla usos activa: `erp_ecommerce_media_usos`
- maximo inicial: 2 MB
- formatos: JPG, PNG y WebP
- requiere `alt_text`
- valida MIME real, extension, dimensiones y hash SHA-256

Endpoints POST que siguen bloqueados hasta cerrar reglas de metadatos/usos:

- `POST /cms/media_admin_actualizar_erp`
- `POST /cms/media_admin_archivar_erp`
- `POST /cms/media_admin_usos_erp`

Guardrail: Media CMS no modifica catalogo, precios ni inventario. El endpoint de subida guarda solo imagen publica controlada; no expone rutas internas del ERP.
