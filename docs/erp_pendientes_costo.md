# ERP - Calidad / Pendientes de costo

Documentacion IA: Codex GPT-5  
Fecha: 2026-10-22  
Estado: primera fase read-only

## Objetivo

La bandeja detecta productos y SKUs activos que necesitan atención antes de operar costos y precios: sin costo resoluble, sin proveedor relacionado, proveedor sin costo vigente, costo propuesto pendiente de validar, SKU derivado o sin precio vigente en `LP-MENUDEO-01`.

## Fuentes reutilizadas

- `erp_catalogo_productos` y `erp_catalogo_skus` para producto, SKU y estatus activo.
- `erp_catalogo_sku_proveedores` y `erp_proveedores` para relaciones activas y nombres de proveedor.
- `erp_proveedores_sku_costos` para costos vigentes/propuestos y origen.
- `erp_catalogo_sku_precios` y el resolutor de Rentabilidad para el contexto de costo.
- `erp_listas_precios` y `erp_listas_precios_detalle` para el precio vigente de `LP-MENUDEO-01`.
- `CatalogoErpDatos::resolverContextoSkuVendible()` para distinguir normal, variante, paquete, presentación, granel y apertura.

No se agregó DDL ni tabla de pendientes. La primera fase usa detección dinámica read-only; las incidencias persistentes existentes y `erp_notificaciones` siguen siendo los mecanismos de seguimiento cuando un operador autorice esa acción.

## Flujo de estados propuesto

`detectado` -> `en_revision` -> `propuesta lista` / `incidencia solicitada` -> `validado` -> `en listas de precios` -> `resuelto`.

La vista actual solo implementa `detectado` y enlaces de consulta. Relacionar proveedor, crear proveedor, agregar a lista, solicitar costo, marcar revisado y guardar precio requieren endpoints existentes con permisos, auditoría y autorización explícita; no se ejecutan desde esta primera fase.

## Accesos

- Catalogo ERP: `/catalogoerp/pendientes_costo`.
- Proveedores > Costos: enlace a la misma bandeja.
- Comercial > Listas de precios: enlace a la misma bandeja.

El acceso se permite con `catalogo.costos`, `proveedores.costos` o `ventas.listas.ver`. El importe y la fuente detallada del costo solo se devuelven a perfiles con permiso de costos; los demás perfiles ven el pendiente sin exponer importes. Las ligas de Proveedores, Rentabilidad y Listas solo abren pantallas existentes.

## Riesgos y siguiente paso

- Los conteos corresponden a la ventana de SKUs revisada para evitar una consulta ilimitada en productivo; la UI muestra el tamaño de esa ventana.
- Antes de habilitar cualquier botón de aplicación se debe definir la autorización de negocio, generar respaldo externo en `C:\xampp\panel_db_backups`, ejecutar dry-run, informar cantidad/impacto y registrar auditoría.
- No se debe publicar `MAYOREO` ni ecommerce desde esta bandeja.
- Siguiente paso recomendado: validar con usuarios el catálogo de alertas y después conectar acciones existentes en modo dry-run, sin aplicar datos automáticamente.
