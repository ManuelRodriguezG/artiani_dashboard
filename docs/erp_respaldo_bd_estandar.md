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
