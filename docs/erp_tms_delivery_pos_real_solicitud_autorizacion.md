# Solicitud de autorizacion - Creacion real POS -> TMS

Fecha: 2026-07-28

## Objetivo

Autorizar, en una fase posterior, que POS pueda crear un servicio TMS real despues de una venta/pedido/apartado confirmado exitosamente.

Esta autorizacion no esta solicitada para ejecutarse ahora. El estado actual permitido es solo preflight y dry-run.

## Regla de dominio

La creacion real POS -> TMS debe ocurrir solo despues de que POS haya creado correctamente la venta o el pedido y tenga folio/id real.

TMS no debe:

- confirmar ventas;
- cancelar ventas;
- cobrar productos;
- modificar pagos POS;
- mover inventario;
- decidir garantias;
- crear servicios si la venta fallo.

## Token propuesto

```text
TMS_POS_REAL_BASE
```

## Respaldo requerido

Antes de ejecutar cualquier UAT real POS -> TMS, crear respaldo externo en:

```text
C:\xampp\panel_db_backups
```

Nombre sugerido:

```text
artianilocal_panel_YYYYMMDD_antes_tms_pos_real.sql
```

## Frase de autorizacion futura

```text
AUTORIZO EJECUTAR UAT REAL POS TMS DELIVERY usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_tms_pos_real.sql con token TMS_POS_REAL_BASE.
```

## Alcance autorizado futuro

Cuando se autorice:

- POS podra pasar un snapshot logistico a TMS solo despues de venta/pedido real exitoso.
- TMS podra insertar en `erp_tms_servicios`, `erp_tms_servicios_detalle`, `erp_tms_servicios_costos` y `erp_tms_eventos`.
- La referencia POS se guardara como `solicitado_por_modulo=ventas`, `solicitado_por_tipo`, `solicitado_por_id` y `referencia_externa`.

## Fuera de alcance

- No crear SKU de envio.
- No sumar delivery al precio del producto como concepto inventariable.
- No cancelar ventas por falla logistica.
- No crear garantias o devoluciones.
- No mover kardex desde TMS.
- No cobrar productos desde TMS.

## Precondiciones

- Permisos TMS aplicados.
- Esquema TMS aplicado.
- Servicio manual UAT existente y cerrado.
- UI/datos TMS validados.
- Adapter dry-run POS -> TMS validado.
- UI POS opt-in validada en navegador o por UAT read-only.

## Siguiente paso antes de autorizar

Ejecutar:

```text
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_pos_real_preflight_readonly.php
```

Debe responder `pos_tms_real_preflight_listo`.
