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

Si el token falta, el token no coincide o la tabla no existe, el modelo debe responder con `no_escribe_bd=true`.
