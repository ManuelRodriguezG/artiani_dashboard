# ERP Ecommerce - Runbook SEO/migracion URLs

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-03  
Estado: guia operativa; no ejecutar apply sin autorizacion explicita.

## Objetivo

Aplicar el esquema SEO/migracion para administrar URLs canonicas, redirecciones 301, URLs viejas importadas y 404 detectados desde el ERP, dejando que el frontend publico materialice redirecciones, `robots.txt` y `sitemap.xml`.

## Decision dominio y URIs 2026-09-04

El dominio publico no cambia:

```text
https://artiani.com.mx
```

La migracion SEO es de rutas viejas a rutas nuevas dentro del mismo dominio. El frontend puede seguir construyendose y probandose en local, pero canonical, sitemap y redirecciones productivas deben apuntar al dominio final `https://artiani.com.mx`.

Ejemplos:

```text
https://artiani.com.mx/ruta-vieja.html  ->  https://artiani.com.mx/producto/slug-nuevo
https://artiani.com.mx/categoria-vieja  ->  https://artiani.com.mx/categoria/perros/accesorios
```

Reglas:

- `http://artiani.com.local` es ambiente local/preview, no canonical productivo.
- `https://artiani.com.mx` es el dominio canonico final.
- Las redirecciones se guardan por `path` (`/ruta-vieja.html` -> `/producto/slug-nuevo`) para que el frontend las aplique en el mismo host.
- No redirigir todo a home; buscar producto/categoria/marca equivalente antes de usar fallback.
- No publicar sitemap productivo con base local.

## Preflight read-only

Ejecutar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_migracion_schema_readonly.php --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_migracion_schema_sql_readonly.php --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

Validar:

- `ok=true`
- `modo=read-only`
- `ddl.total=5`
- `ddl_total=5` en salida SQL
- `sha256_sql` queda registrado
- `seo.urls_sin_api_interna=true`
- `seo.sitemap_sin_api_interna=true`

Validar candados de persistencia sin tokens reales:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_migracion_persistencia_guardrails_readonly.php
```

Resultado esperado:

- `ok=true`
- `guardrails.no_escribe_bd=true`
- `importacion_sin_token_bloqueada=true`
- `redireccion_sin_token_bloqueada=true`
- `plan_redireccion_bloquea_api=true`

## Aplicacion autorizada

Ejecutar solo con autorizacion textual:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_migracion_schema_apply_authorized.php --autorizar=ECOMMERCE_SEO_MIGRACION_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

El script debe devolver:

- `modo=apply_authorized`
- `ok=true`
- `ejecutado=true`

Antes de ejecutar, confirmar que las tablas del SQL son solo:

- `erp_ecommerce_seo_configuracion`
- `erp_ecommerce_seo_urls`
- `erp_ecommerce_seo_redirecciones`
- `erp_ecommerce_seo_urls_viejas`
- `erp_ecommerce_seo_errores_404`

## Postcheck

Ejecutar:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_migracion_schema_postcheck_readonly.php
```

Resultado esperado:

- `senal_schema_postcheck=esquema_seo_migracion_completo`
- `tablas_faltantes=0`
- `seo.sin_api_interna=true`

## No hacer durante esta aplicacion

- No importar URLs viejas todavia.
- No aprobar redirecciones 301 todavia.
- No generar redirecciones masivas automaticas.
- No tocar frontend publico.
- No editar `.htaccess`.
- No redirigir todo a home.

## Siguiente paso despues de aplicar

Usar `/ecommercePublico/seo_migracion` para pegar URLs viejas, revisar sugerencias y aprobar redirecciones en una fase separada con auditoria.

## Aplicacion ejecutada 2026-09-03

Respaldo usado:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260903_antes_ecommerce_seo_migracion.sql
```

Resultado:

- `ok=true`
- `modo=apply_authorized`
- `ejecutado=true`
- tablas antes: `5` faltantes
- tablas despues: `0` faltantes
- `senal_schema_postcheck=esquema_seo_migracion_completo`

Tablas creadas:

- `erp_ecommerce_seo_configuracion`
- `erp_ecommerce_seo_urls`
- `erp_ecommerce_seo_redirecciones`
- `erp_ecommerce_seo_urls_viejas`
- `erp_ecommerce_seo_errores_404`

Guardrails confirmados:

- no se importaron URLs viejas;
- no se crearon redirecciones;
- no se activaron 301;
- no se toco frontend publico;
- no se toco inventario.

## Persistencia despues del DDL

La persistencia de URLs viejas y redirecciones esta separada del DDL:

- Sincronizar URLs canonicas: `POST /ecommercePublico/seo_urls_sincronizar_erp`
  - permiso: `catalogo.editar`
  - token: `ECOMMERCE_SEO_SYNC_URLS_CANONICAS`
  - tabla requerida: `erp_ecommerce_seo_urls`
  - no desactiva URLs ausentes automaticamente
  - plan final read-only 2026-09-03: `260` canonicas, `260` nuevas, `0` bloqueadas
  - sincronizacion ejecutada 2026-09-03: `260` canonicas insertadas, `0` internas, `0` omitidas
  - ajuste dominio 2026-09-04: endpoints vivos usan `https://artiani.com.mx`; snapshot persistido requiere nueva sincronizacion (`270` canonicas: `10` nuevas, `260` actualizar, `0` bloqueadas)
  - resync ejecutada 2026-09-05 con respaldo `C:\xampp\panel_db_backups\artianilocal_panel_20260905_antes_ecommerce_seo_resync_canonicas.sql`: `270` activas, `270` productivas, `0` locales, `0` internas
- Importar URLs viejas: `POST /ecommercePublico/seo_urls_viejas_importar_erp`
  - permiso: `catalogo.editar`
  - token: `ECOMMERCE_SEO_IMPORTAR_URLS_VIEJAS`
  - tabla requerida: `erp_ecommerce_seo_urls_viejas`
  - no crea redirecciones automaticamente
- Guardar redireccion aprobada: `POST /ecommercePublico/seo_redireccion_guardar_erp`
  - permiso: `catalogo.editar`
  - token: `ECOMMERCE_SEO_GUARDAR_REDIRECCION`
  - tabla requerida: `erp_ecommerce_seo_redirecciones`
  - valida origen/destino/status antes de escribir
- Curar nombre publico y slug de producto: `POST /ecommercePublico/seo_producto_slug_guardar_erp`
  - permiso: `catalogo.editar`
  - token: `ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA`
  - tabla requerida: `erp_ecommerce_publicaciones`
  - el slug ya vive en `erp_ecommerce_publicaciones.slug`
  - cambiar nombre no recalcula slug automaticamente
  - si el slug cambia, se registra 301 `/producto/{slug_anterior}` -> `/producto/{slug_nuevo}` cuando `erp_ecommerce_seo_redirecciones` existe

Si el token falta, el token no coincide o la tabla no existe, el modelo debe responder con `no_escribe_bd=true`.

## Mesa de productos y slugs

Usar `/ecommercePublico/seo_migracion`, seccion `Productos y slugs publicos`.

Flujo recomendado:

- Buscar por SKU, nombre o slug.
- Seleccionar producto con el lapiz.
- Ajustar `Nombre publico`.
- Mantener el slug si la URL publicada ya esta bien.
- Usar la estrella solo cuando se quiera recalcular el slug desde el nombre.
- Validar antes de guardar; si el slug cambia, revisar la 301 sugerida.
- Guardar con `ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA`.
- Si una URL anterior sugerida corresponde al producto, usar la flecha para llenar `Redireccion manual`; guardar esa 301 aparte con `ECOMMERCE_SEO_GUARDAR_REDIRECCION`.

Guardrail SEO:

- Las sugerencias de URLs anteriores no crean relaciones ni redirecciones por si solas.
- Una URL vieja identica a la nueva no requiere 301.
- No usar `http://artiani.com.local` como canonical; solo sirve como preview local.

## URLs indexadas desde Google

Cuando Google/Search Console entregue Excel con URLs indexadas actuales, convertirlo a reporte local read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_importar_excel_indexadas_readonly.php "C:\Users\aleja\Downloads\urls indexadas actuales.xlsx"
```

Resultado 2026-09-09:

- archivo generado: `storage/tmp/ecommerce_seo_urls_indexadas_google_20260910_044027.json`
- total URLs indexadas: `1000`
- productos: `913`
- categorias: `60`
- paquetes: `24`
- URLs con `undefined`: `62`

Regla operativa:

- Las URLs indexadas por Google tienen prioridad sobre el crawl general porque ya representan superficie SEO viva.
- En `/ecommercePublico/seo_migracion`, la revision de URLs anteriores puede alternar fuente entre `Indexadas Google` y `Crawl / relaciones`.
- El buscador dentro del editor de producto consulta primero las URLs indexadas de Google para asignar manualmente la redireccion correcta.
- Flujo principal de revision: URL vieja indexada -> revisar hasta 3 URLs nuevas candidatas -> abrir producto nuevo sugerido para revisar nombre/slug -> llenar redireccion manual si corresponde.
- La comparacion usa tokens del path/nombre y SKU cuando existe; si el SKU viejo no coincide con el identificador nuevo, el nombre sigue aportando score para encontrar equivalencias.

## Analisis URLs viejas 2026-09-05

Regla de migracion confirmada por negocio: el dominio productivo se mantiene como `https://artiani.com.mx`; solo cambian las URIs hacia la nueva estructura construida en local. Las URLs viejas deben analizarse como paths antiguos del mismo dominio, no como migracion de dominio.

Extraccion read-only desde sitio vivo:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_crawl_urls_viejas_readonly.php --base=https://artiani.com.mx --max=350 --delay_ms=80
```

Archivos generados:

- `storage/tmp/ecommerce_seo_urls_viejas_crawl_20260906_015158.json`
- `storage/tmp/ecommerce_seo_urls_viejas_crawl_20260906_015158.csv`
- `storage/tmp/ecommerce_seo_urls_viejas_import_20260906_015158.txt`

Resultado del crawl:

- `350` URLs detectadas.
- `330` con HTTP `200`.
- `20` con HTTP `500`.
- Tipos detectados por crawler: `221` producto legacy, `73` categoria legacy, `21` clasificacion, `32` otro, `1` home, `1` contacto, `1` sitemap.

Plan de equivalencias read-only con heuristica estricta:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_urls_viejas_plan_desde_archivo_readonly.php --archivo=storage\tmp\ecommerce_seo_urls_viejas_import_20260906_015158.txt
```

Archivos generados:

- `storage/tmp/ecommerce_seo_urls_viejas_plan_20260905_175808.json`
- `storage/tmp/ecommerce_seo_urls_viejas_plan_20260905_175808.csv`

Resultado del plan:

- `350` URLs unicas evaluadas.
- `110` con sugerencia de destino.
- `240` sin equivalente confiable.
- Confianza: `3` exacta, `14` media, `333` baja.

Reporte enriquecido read-only para revision:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_urls_viejas_reporte_enriquecido_readonly.php --plan=storage\tmp\ecommerce_seo_urls_viejas_plan_20260905_175808.json --crawl=storage\tmp\ecommerce_seo_urls_viejas_crawl_20260906_015158.json
```

Archivos generados:

- `storage/tmp/ecommerce_seo_urls_viejas_reporte_enriquecido_20260906_015954.json`
- `storage/tmp/ecommerce_seo_urls_viejas_reporte_enriquecido_20260906_015954.csv`

Criterio operativo:

- `aprobar_301_candidato`: solo equivalencias exactas/alta confianza; hoy son `3` rutas simples (`/`, `/index.html`, `/contacto`).
- `validar_301_candidato`: sugerencias medias; hoy son `14`, principalmente productos/categorias que deben revisarse por variante, marca y tamano antes de crear 301.
- `revisar_manual`: URLs indexables sin equivalencia confiable; hoy son `270`.
- `excluir_o_410`: rutas de plantilla vieja o errores HTTP; hoy son `63`. No convertir automaticamente a home.

No se importaron URLs viejas a BD y no se crearon redirecciones 301 durante este analisis.

## Mesa de revision en ERP

La vista `/ecommercePublico/seo_migracion` incluye la seccion `Revision de URLs anteriores`.

Fuente de datos:

- Lee el ultimo `storage/tmp/ecommerce_seo_urls_viejas_reporte_enriquecido_*.json`.
- No rastrea sitios desde la vista.
- No escribe BD.
- No crea redirecciones.

Flujo recomendado:

- Filtrar por `validar_301_candidato` para revisar primero los destinos con sugerencia.
- Abrir el link de preview local (`http://artiani.com.local` + path nuevo) y confirmar producto/categoria.
- Usar el boton de flecha para copiar origen/destino al formulario de redireccion manual.
- Validar y guardar 301 solo con token `ECOMMERCE_SEO_GUARDAR_REDIRECCION`.
- Ocultar en UI las URLs descartadas; ese descarte vive en `localStorage` del navegador y no sustituye una decision persistente futura.

Si una URL vieja corresponde a producto descontinuado o ruta de plantilla vieja, no redirigir automaticamente a home. Preferir categoria cercana, 410 o revision manual segun el caso.

## Relacion automatica viejo contra nuevo 2026-09-05

Se intento consultar `http://artiani.com.local/` desde consola para rastrear el frontend local, pero no respondio dentro de `10` segundos (`status=0`, timeout). Para no bloquear la revision, la relacion se genero contra el inventario canonico nuevo del ERP, que representa las URIs publicas actuales que despues usara `https://artiani.com.mx`.

Comando ejecutado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_seo_relacionar_urls_readonly.php --viejas=storage\tmp\ecommerce_seo_urls_viejas_reporte_enriquecido_20260906_015954.json --nuevas=erp --limite_nuevas=500
```

Archivos generados:

- `storage/tmp/ecommerce_seo_urls_relaciones_20260905_232005.json`
- `storage/tmp/ecommerce_seo_urls_relaciones_20260905_232005.csv`

Resultado actualizado 2026-09-07:

- URLs viejas evaluadas: `350`.
- URLs nuevas comparadas: `272`.
- Relaciones potenciales encontradas: `35`.
- Sin candidato: `315`.
- Acciones sugeridas: `2` sin redireccion necesaria, `33` validar 301 candidato, `252` revisar manual, `63` excluir o 410.

La vista `/ecommercePublico/seo_migracion` prioriza el ultimo `storage/tmp/ecommerce_seo_urls_relaciones_*.json` si existe. Cada URL vieja puede mostrar hasta tres sugerencias nuevas, con `score`, confianza, motivo y link de preview local.

Regla: si `path_original` y `url_destino_sugerida` son la misma URI, no se crea 301. La URL debe seguir respondiendo normal en el frontend nuevo y se clasifica como `sin_redireccion_necesaria`.
