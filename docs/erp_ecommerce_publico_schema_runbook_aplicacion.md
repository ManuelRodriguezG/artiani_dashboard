# ERP Ecommerce publico - Runbook de aplicacion DDL Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Estado: guia operativa; no ejecutar sin autorizacion explicita.

## Objetivo

Aplicar el esquema minimo `erp_ecommerce_*` para que la Fase 1 pueda guardar publicaciones, configuracion y cotizaciones de catalogo vivo cuando el negocio lo autorice.

## Preflight read-only

Ejecutar:

```bash
php storage/uat/uat_ecommerce_publico_schema_readonly.php --respaldo=RUTA_O_REFERENCIA
php storage/uat/uat_ecommerce_publico_schema_sql_readonly.php --respaldo=RUTA_O_REFERENCIA
```

Validar que:

- `ok=true`
- `modo=read-only`
- `ddl.total=5`
- `ddl_total=5` en la salida SQL;
- `sha256_sql` quede registrado para comparar contra la version revisada;
- `guardrails.no_toca_ecom_legacy=true`
- `guardrails.no_mueve_inventario=true`
- el respaldo tenga `ok=true`

Si el respaldo no es ruta local, debe ser una referencia externa suficientemente clara, por ejemplo folio o ruta de almacenamiento externo.

## Aplicacion autorizada

Ejecutar solo con autorizacion textual:

```bash
php storage/uat/uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=RUTA_O_REFERENCIA
```

El script debe devolver:

- `modo=apply_authorized`
- `ok=true`
- respuesta del modelo `EcommercePublicoEsquema`

Antes de ejecutar, comparar visualmente que las tablas del SQL read-only sean solo:

- `erp_ecommerce_publicaciones`
- `erp_ecommerce_configuracion`
- `erp_ecommerce_cotizaciones`
- `erp_ecommerce_cotizaciones_detalle`
- `erp_ecommerce_cotizaciones_eventos`

## Verificacion posterior

Ejecutar de nuevo:

```bash
php storage/uat/uat_ecommerce_publico_schema_readonly.php --respaldo=RUTA_O_REFERENCIA
```

Resultado esperado:

- `auditoria.tablas_faltantes=0`
- `ddl.pendientes=0`

Tambien validar en ERP:

- abrir `/ecommercePublico/publicaciones`;
- revisar que el panel DDL ya no muestre tablas faltantes;
- confirmar que la lista de SKUs candidatos sigue siendo read-only.

## No hacer durante esta aplicacion

- No insertar publicaciones masivas.
- No activar endpoints POST publicos.
- No registrar cotizaciones reales.
- No migrar `ecom_carrito`, `ecom_pedidos` ni `ecom_productos`.
- No convertir cotizaciones a POS/Pedidos.

## Siguiente paso despues de aplicar

Crear funcionalidad interna controlada para seleccionar SKUs publicables y guardar publicaciones en `erp_ecommerce_publicaciones`, con auditoria y permiso apropiado. Esa accion debe ser una fase separada.
