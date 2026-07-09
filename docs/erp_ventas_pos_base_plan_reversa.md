# ERP Ventas/POS - Plan de reversa base

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: plan previo a autorizacion; no ejecuta cambios.

## Objetivo

Definir como detener o revertir la aplicacion del alcance base de Ventas/POS/Pedidos si ocurre un error durante:

- DDL base;
- semillas de cajas/terminales/asignaciones;
- apertura de turno UAT;
- venta POS UAT.

## Regla principal

La reversa preferida es restaurar el respaldo externo validado:

- `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`

No ejecutar borrados manuales ni `DROP TABLE` sin autorizacion explicita posterior.

## Puntos de alto

### Falla durante DDL base

Detener inmediatamente si:

- cualquier tabla no se crea;
- el aplicador devuelve `ok=false`;
- el aplicador devuelve `modo=rollback` o error inesperado;
- MySQL reporta error de permisos, disco, engine o definicion SQL.

Accion:

1. No ejecutar semillas.
2. Guardar salida completa del comando.
3. Ejecutar auditoria read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=base
```

4. Decidir con el dueno:
   - restaurar respaldo completo; o
   - autorizar correccion puntual si no hubo datos productivos.

### Falla durante semillas

Detener inmediatamente si:

- no se crean las dos cajas esperadas;
- no se crean las dos terminales esperadas;
- no queda asignacion activa para `id_usuario=1`;
- el script devuelve `rollback`.

Accion:

1. No abrir turno.
2. Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_config_readonly.php --id_usuario=1 --alcance=base
```

3. Si no hubo turnos ni ventas, puede evaluarse una correccion puntual con autorizacion.
4. Si hubo cualquier escritura posterior, preferir restaurar respaldo.

### Falla durante apertura de turno

Detener inmediatamente si:

- se crea turno sin movimiento inicial;
- se crea movimiento sin turno;
- queda mas de un turno abierto para la misma caja;
- el monto esperado no coincide con monto inicial.

Accion:

1. No ejecutar venta.
2. Guardar folio de turno si existe.
3. Ejecutar preflight:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500
```

4. Preferir restaurar respaldo si el turno quedo inconsistente.

### Falla durante venta POS UAT

Detener inmediatamente si:

- venta creada sin pago;
- pago creado sin movimiento de caja;
- inventario descontado sin kardex;
- kardex sin trazabilidad `erp_ventas_detalle_inventario`;
- unidad fisica queda en estado incoherente;
- el aplicador devuelve `rollback`.

Accion:

1. No repetir venta hasta auditar folio/SKU.
2. Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_post_venta_readonly.php --folio=FOLIO_POS_GENERADO
```

3. Registrar hallazgo en `docs/erp_ventas_pos_evidencia_uat.md`.
4. Preferir restaurar respaldo completo si hubo descuento parcial de inventario o kardex inconsistente.

## Tablas del alcance base

El alcance `VENTAS_POS_DDL_BASE` crea:

- `erp_pos_cajas`
- `erp_pos_terminales`
- `erp_pos_usuarios_cajas`
- `erp_pos_turnos`
- `erp_pos_movimientos_caja`
- `erp_ventas`
- `erp_ventas_detalle`
- `erp_ventas_detalle_inventario`
- `erp_ventas_pagos`
- `erp_ventas_devoluciones`
- `erp_ventas_devoluciones_detalle`

Nota:

- La tabla `erp_ventas` y su detalle contienen algunos campos preparados para precios/clientes/apartados, pero el alcance base no crea tablas de clientes, listas ni atenciones persistentes.

## Reversa manual solo con autorizacion

Si el dueno decide no restaurar respaldo y autoriza limpieza manual, primero confirmar:

- no hay turnos reales;
- no hay ventas reales;
- no hay movimientos de caja;
- no hay movimientos de inventario con `origen_tipo='venta_pos'`;
- no hay datos que deban conservarse.

Orden conceptual para retirar tablas vacias, si se autoriza expresamente:

1. `erp_ventas_devoluciones_detalle`
2. `erp_ventas_devoluciones`
3. `erp_ventas_detalle_inventario`
4. `erp_ventas_pagos`
5. `erp_ventas_detalle`
6. `erp_ventas`
7. `erp_pos_movimientos_caja`
8. `erp_pos_turnos`
9. `erp_pos_usuarios_cajas`
10. `erp_pos_terminales`
11. `erp_pos_cajas`

Este documento no autoriza ejecutar esa limpieza.

## Criterio de cierre

La aplicacion base se considera recuperable solo si:

- existe respaldo validado;
- cada escritura se ejecuta por script autorizado;
- cada fase tiene evidencia;
- ante falla, se detiene la siguiente fase;
- no se mezclan reparaciones manuales sin autorizacion.
