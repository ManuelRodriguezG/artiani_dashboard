# Solicitud de autorizacion - UAT real POS -> TMS

Fecha: 2026-07-29

## Objetivo

Autorizar una prueba real controlada donde POS capture una solicitud logistica y pida a TMS crear un servicio real con folio propio.

La implementacion de UI/JS ya esta preparada. Esta autorizacion se refiere solo a ejecutar una prueba con escritura real en BD.

## Regla de dominio

POS es solo canal de captura para la solicitud logistica. TMS no debe requerir folio de venta, estatus de venta ni pago de producto para existir.

TMS debe limitarse a:

- recoger;
- preparar;
- llevar;
- evidenciar;
- cerrar;
- reprogramar;
- dejar pendiente por cliente cuando aplique.

TMS no debe:

- confirmar operaciones comerciales;
- cancelar operaciones comerciales;
- cobrar productos;
- modificar pagos POS;
- mover inventario;
- decidir garantias;
- crear obligaciones logisticas automaticas por garantia.

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

Respaldo ya generado para esta fase:

```text
C:\xampp\panel_db_backups\artianilocal_panel_20260729_204819_antes_tms_pos_real.sql
```

Validacion:

```text
archivo_existe=true
archivo_legible=true
tamano_bytes=33532718
```

## Frase de autorizacion futura

```text
AUTORIZO EJECUTAR UAT REAL POS TMS DELIVERY usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_tms_pos_real.sql con token TMS_POS_REAL_BASE.
```

## Alcance autorizado del UAT

Cuando se autorice:

- POS podra enviar un snapshot logistico a TMS.
- TMS podra insertar en `erp_tms_servicios`, `erp_tms_servicios_detalle`, `erp_tms_servicios_costos` y `erp_tms_eventos`.
- La referencia POS se guardara como `solicitado_por_modulo=pos`, `solicitado_por_tipo=solicitud_pos` y `referencia_externa` opcional.
- La prueba no requiere rastreo publico de cliente.

## Fuera de alcance

- No crear SKU de envio.
- No sumar delivery al precio de un producto.
- No cancelar operaciones comerciales por falla logistica.
- No crear garantias o devoluciones.
- No mover kardex desde TMS.
- No cobrar productos desde TMS.
- No exigir folio de venta para crear TMS.

## Precondiciones

- Permisos TMS aplicados.
- Esquema TMS aplicado.
- Servicio manual UAT existente y cerrado.
- UI/datos TMS validados.
- Adapter dry-run POS -> TMS validado como solicitud logistica.
- UI POS opt-in validada por UAT read-only.
- Rastreo publico pospuesto para fase posterior.

## Siguiente paso antes de autorizar

Ejecutar:

```text
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_pos_real_preflight_readonly.php
```

Debe responder `pos_tms_real_implementacion_lista`.

## Ejecucion autorizada

Cuando exista respaldo real y autorizacion explicita:

```text
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_pos_real_apply_authorized.php --autorizar=TMS_POS_REAL_BASE --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_tms_pos_real.sql
```

Con el respaldo generado:

```text
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_pos_real_apply_authorized.php --autorizar=TMS_POS_REAL_BASE --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260729_204819_antes_tms_pos_real.sql
```

El script debe responder `pos_tms_uat_real_completo`.

## Postcheck

Despues del UAT real:

```text
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_pos_real_postcheck_readonly.php
```

Tambien se puede validar una referencia puntual:

```text
C:\xampp\php\php.exe storage\uat\uat_tms_delivery_pos_real_postcheck_readonly.php --referencia=POS-SOL-UAT-YYYYMMDD-HHMMSS
```

Debe responder `pos_tms_postcheck_completo`.
