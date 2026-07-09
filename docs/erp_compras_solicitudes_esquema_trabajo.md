# ERP Compras - Solicitudes: esquema ERP

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: estructura de datos de solicitudes, detalle, estados, relacion con orden y auditoria

## Proposito

Definir y auditar la estructura ERP necesaria para que Solicitudes funcione sin depender de tablas `ecom_*` ni de campos heredados ambiguos.

## Archivos de trabajo

- `app/modelos/ComprasEsquema.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/controladores/Compra.php`
- `app/modelos/CatalogoErpEsquema.php` si faltan relaciones con producto/SKU/fiscal.

## Capacidades requeridas

La estructura debe soportar:

- Cabecera de solicitud.
- Detalle de solicitud.
- Estado de solicitud.
- Solicitante/usuario.
- Area/departamento si aplica.
- Prioridad.
- Proveedor sugerido.
- Almacen destino sugerido.
- Fecha requerida.
- Motivo/observaciones.
- Producto ERP o producto propuesto.
- Cantidad, unidad y costo estimado.
- Relacion solicitud -> orden.
- Relacion detalle solicitud -> detalle orden.
- Auditoria de cambios de estatus.
- Pendientes generados para Catalogo.

## Reglas de esquema

- No usar tablas `ecom_*` como fuente principal.
- No adaptar el flujo nuevo a campos viejos solo porque existen.
- Si hay campos heredados, documentar si se conservan, migran o dejan de usar.
- No eliminar campos con datos sin plan de migracion y aprobacion del dueno.
- Toda tabla nueva debe tener indices para busqueda por estatus, fecha, solicitante y orden relacionada.

## Permisos involucrados

- Auditar esquema: soporte/desarrollador.
- Ejecutar cambios de esquema: solo con autorizacion explicita.
- Consultar datos de solicitud: `compras.ver`.

## Criterios de terminado

- Existe estructura clara para cabecera y detalle.
- Estados y relaciones con orden estan soportados.
- Productos propuestos pueden guardarse sin crear maestro automaticamente.
- La orden puede conservar `id_solicitud` e `id_solicitud_detalle`.
- El esquema no depende de ecommerce.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Esquema ERP.
Objetivo: auditar y proponer cambios de esquema para solicitudes ERP: cabecera, detalle, estados, relacion con orden, productos propuestos y auditoria.
Revisar ComprasEsquema.php, SolicitudesCompraErp.php y OrdenesCompraErp.php.
No usar tablas ecom_* ni eliminar campos heredados sin plan.
Criterio: entregar plan exacto de tablas/campos/indices a crear, conservar, migrar o dejar de usar.
```
