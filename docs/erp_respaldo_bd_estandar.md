# ERP - Estandar de respaldos de base de datos

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-17  
Estado: criterio operativo transversal para cambios con escritura o DDL.

## Ruta estandar local

Los respaldos externos al proyecto deben guardarse fuera de:

```text
C:\xampp\htdocs\panel_de_control
```

Ruta estandar local:

```text
C:\xampp\panel_db_backups
```

Esta ruta ya existe en el entorno XAMPP local y contiene respaldos historicos del ERP.

## Convencion de nombres

Formato recomendado:

```text
{base}_{proyecto}_{yyyymmdd_HHmmss}_antes_{modulo}_{accion}.sql
```

Ejemplo para ecommerce publico Fase 1:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_antes_ecommerce_publico_fase1.sql
```

Si se requiere mas precision:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_153000_antes_ecommerce_publico_fase1.sql
```

## Reglas

- No guardar respaldos dentro del repo.
- No guardar respaldos en `storage/`, `public/` ni `docs/`.
- No ejecutar DDL ni scripts `apply_authorized` sin respaldo externo.
- Para cambios por modulo, reutilizar siempre `C:\xampp\panel_db_backups`.
- En documentacion y comandos usar la ruta completa del respaldo o una referencia externa verificable.
- No exponer credenciales de `app/config/mysql.php` en documentos ni respuestas.
- Para no llenar el servidor, los respaldos de productivo pueden generarse desde la computadora local y almacenarse en `C:\xampp\panel_db_backups`.

## Respaldo productivo post-activacion

Fecha: 2026-08-25

```text
archivo=C:\xampp\panel_db_backups\productivo_artianicom_sys_panel_20260825_225108_antes_promocion_completa.sql
tamano_bytes=38935169
sha256=d2dea52e468c0bfbcd95ce7654dbbdc02369c86b6d5b99bcf898bfb722587c05
validado=si
dentro_repo=no
```

## Validacion minima

Antes de aplicar cambios con escritura:

```bash
C:\xampp\mysql\bin\mysqldump.exe --host=localhost --user=root --result-file=C:\xampp\panel_db_backups\NOMBRE_RESPALDO.sql artianilocal
```

Despues de generar el respaldo:

- confirmar que el archivo existe;
- confirmar que el tamano es mayor a `0`;
- usar esa ruta completa en los scripts `apply_authorized`.

## Uso en ecommerce publico

Para ERP Ecommerce publico Fase 1, la referencia de respaldo debe pasarse como:

```text
--respaldo=C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_HHmmss_antes_ecommerce_publico_fase1.sql
```

La activacion autorizada sigue documentada en:

```text
docs/erp_ecommerce_publico_orden_activacion_autorizada.md
```

## Respaldos generados

Ecommerce publico Fase 1:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=28561352
```

Listas de precios por segmentos CRM:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=28578042
```

Sistema configuracion SYS:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260723_171000_antes_sistema_configuracion.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=30669797
```

CMS ecommerce persistencia:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260812_094259_antes_cms_ecommerce_persistencia.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=34843878
```

TMS Delivery permisos base:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_permisos.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=32809505
```

TMS Delivery schema base:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_delivery_schema.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=32811490
```

Compras - Abastecimiento en solicitudes:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260729_antes_compras_abastecimiento_solicitudes.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=33459643
```

TMS Delivery UAT manual:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260725_antes_tms_uat_manual.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=32820083
```

TMS Delivery POS real:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260729_204819_antes_tms_pos_real.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=33532718
```

Panel Proyectos - permisos base:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260725_205500_antes_proyectos_permisos.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=32798256
```

Panel Proyectos - esquema base:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260725_205500_antes_proyectos_schema.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=32798256
```

Inventario - Reclasificacion schema:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260808_204014_antes_inv_reclasificacion_schema.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=34624244
```

Migraciones BD - esquema tecnico:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260804_131239_antes_migracion_bd_schema.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=34020111
sha256=1b61571123a04d75fa4adad2d027daca0e2a5413ba27f2066ec5a97c79912625
preflight_esquema=puede_aplicar
```
## Catalogo - Apertura de empaques

Fecha: 2026-07-28  
Ruta estandar usada:

```txt
C:\xampp\panel_db_backups\artianilocal_panel_20260728_antes_catalogo_apertura_empaques.sql
```

Regla aplicada:

- Si el usuario autoriza DDL con placeholder de respaldo, el agente debe generar un respaldo externo nuevo en `C:\xampp\panel_db_backups` antes de aplicar.
- El nombre recomendado para Catálogo es:

```txt
artianilocal_panel_YYYYMMDD_HHmmss_antes_catalogo_<alcance>.sql
```

Ejemplo de alcance: `apertura_empaques`, `imagenes_marcas_categorias`, `paquetes_configurables`.

## Compras - documentos imprimibles y plantillas

Fecha: 2026-07-28  
Ruta estandar usada:

```txt
C:\xampp\panel_db_backups\artianilocal_panel_de_control_20260728_antes_compras_documentos_plantillas.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=33233779
```

Alcance:

- Creacion de `erp_compras_documentos_plantillas`.
- Creacion de `erp_compras_documentos_plantillas_config`.
- Preparacion futura de documentos imprimibles para solicitudes y ordenes de compra.

## TMS Delivery - Logistica pura

Fecha: 2026-07-29  
Ruta estandar usada:

```txt
C:\xampp\panel_db_backups\artianilocal_panel_20260729_antes_tms_logistica_pura.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=33459395
```

Alcance:

- Alineacion de `erp_tms_servicios.motivo_logistico` a `servicio_inicial`.
- Limpieza de valor historico UAT `venta_inicial`.
- No toca POS/Ventas, caja, inventario ni postventa.

## CRM/POS - permisos finos clientes

Fecha: 2026-07-30  
Ruta estandar:

```txt
C:\xampp\panel_db_backups\panel_de_control_artianilocal_2026-07-30_antes_crm_pos_permisos_finos.sql
```

Regla aplicada:

- Si el usuario autoriza con placeholder `[RUTA_RESPALDO]`, el agente debe generar este respaldo externo antes del apply.
- El respaldo se usa para el token `CRM_POS_PERMISOS_FINOS`.
- El alcance del apply es exclusivamente permisos/roles CRM-POS; no toca clientes, ventas, POS, ecommerce, garantias, apartados, devoluciones ni legacy.

## CRM - permisos por submodulo clientes

Fecha: 2026-07-30  
Ruta estandar usada:

```txt
C:\xampp\panel_db_backups\panel_de_control_artianilocal_2026-07-30_antes_crm_submodulos_permisos.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=33654585
```

Alcance:

- Creacion/actualizacion de permisos `crm.clientes.*`, `crm.seguimiento.*`, `crm.comercial.*`, `crm.recompensas.*` y `crm.reportes.ver`.
- Vinculacion a roles base `direccion`, `crm` y `administrador_erp`.
- No retira permisos amplios existentes.
- No toca clientes, ventas, POS, ecommerce, garantias, apartados, devoluciones ni legacy.

## Catalogo ERP - Catalogos comerciales estilos y variantes

Fecha: 2026-08-27  
Ruta estandar usada:

```txt
C:\xampp\panel_db_backups\artianicom_sys_panel_de_control_20260827_antes_catalogos_comerciales_estilos_variantes.sql
```

Ecommerce SEO/migracion URLs:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260903_antes_ecommerce_seo_migracion.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=38934285
aplicacion=DDL SEO/migracion ecommerce
token=ECOMMERCE_SEO_MIGRACION_DDL
fecha=2026-09-03
```

Ecommerce SEO/resync canonicas a dominio productivo:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260905_antes_ecommerce_seo_resync_canonicas.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=38934285
aplicacion=sync canonicas SEO a https://artiani.com.mx
token=ECOMMERCE_SEO_SYNC_URLS_CANONICAS
fecha=2026-09-05
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=39072861
```

Alcance:

- Base efectiva del proyecto para `panel.com.local`: `artianicom_sys`.
- Agrega persistencia de estilo visual por catalogo comercial: fuente, colores y tamanos de texto.
- Agrega bandera `agrupar_variantes` para presentacion visual de variantes.
- No fusiona ni borra SKUs.
- No toca costos, rentabilidad, inventario, compras, ventas ni listas de precios.

## Catalogo ERP - Catalogos comerciales portada e identidad

Fecha: 2026-09-08  
Ruta estandar usada:

```txt
C:\xampp\panel_db_backups\artianicom_sys_panel_de_control_20260909_012318_antes_catalogos_comerciales_portada_contacto.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=40931757
base=artianicom_sys
```

Alcance:

- Agrega persistencia de portada comercial: tipo, imagen principal, logo y contacto.
- La portada es material visual para redes/WhatsApp/PDF/PNG.
- No modifica SKUs, costos, rentabilidad, inventario ni listas de precios.

## Distribucion API - esquema clientes, tokens y cotizaciones

Fecha: 2026-09-09  
Ruta estandar usada:

```txt
C:\xampp\panel_db_backups\artianicom_sys_panel_de_control_20260909_002739_antes_distribucion_api_schema.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=41323600
base=artianicom_sys
```

Alcance:

- Creacion de tablas `erp_distribucion_*` para clientes externos, solicitudes, permisos, listas, tokens, cotizaciones y auditoria.
- Aplicacion acotada de permisos internos ERP `distribucion.*`.
- No modifica catalogo, precios, inventario, compras, ventas ni ecommerce.

## Ecommerce SEO - slugs profesionales

Fecha: 2026-09-10

Respaldo inicial local generado:

```txt
C:\xampp\panel_db_backups\artianilocal_panel_20260910_antes_ecommerce_slugs_seo.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=38934285
base=artianilocal
nota=Se genero antes de detectar que el contexto activo de la app apunta a artianicom_sys.
```

Respaldo real previo a normalizacion masiva:

```txt
C:\xampp\panel_db_backups\artianicom_sys_panel_20260910_antes_slugs_profesionales.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=41733943
base=artianicom_sys
```

Respaldo post DDL/URLs previo a normalizacion:

```txt
C:\xampp\panel_db_backups\artianicom_sys_panel_20260910_despues_ecommerce_slugs_seo.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=41733943
base=artianicom_sys
```

Alcance:

- Aplica DDL complementario de publicaciones ecommerce y migracion SEO.
- Puebla `url_publica` y `canonical_url` desde `erp_ecommerce_publicaciones.slug`.
- Normaliza slugs profesionales quitando `pza`/SKU salvo casos necesarios para diferenciacion.
- No registra 301 por cambio interno de slug; las 301 deben salir de URLs viejas productivas revisadas.
- Resincroniza `erp_ecommerce_seo_urls` y desactiva URLs de producto obsoletas del snapshot.
- No toca inventario, compras, ventas, precios ni frontend externo.

## Ecommerce SEO - limpieza de 301 automaticas por slug

Fecha: 2026-09-10

Respaldo previo:

```txt
C:\xampp\panel_db_backups\artianicom_sys_panel_20260910_antes_limpiar_301_slug_auto.sql
```

Validacion:

```text
archivo_existe=true
tamano_bytes=42405631
base=artianicom_sys
```

Resultado:

- Se eliminaron 1652 redirecciones automaticas `tipo='producto_slug'` con motivos `slug_profesional_pre_lanzamiento` y `slug_publico_actualizado`.
- Quedaron 0 redirecciones automaticas por cambio interno de slug.
- Se conservaron las redirecciones manuales reales de migracion (`motivo='revision_manual_seo'`).
