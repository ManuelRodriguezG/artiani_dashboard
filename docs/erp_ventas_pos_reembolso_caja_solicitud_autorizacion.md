# Solicitud de autorizacion - UAT reembolso de caja POS

Fecha: 2026-06-30

## Proposito

Ejecutar una UAT controlada de devolucion POS con `decision_financiera=reembolso_caja`, validando que el reembolso salga del turno abierto, quede ligado a la devolucion, actualice el esperado de caja y conserve trazabilidad contra la venta original.

## Estado actual read-only

- Usuario UAT: `id_usuario=1`.
- Asignacion POS activa:
  - almacen `5`;
  - caja `2`;
  - terminal `2`;
  - tienda `Mascotas Mina 971`.
- Turno abierto actual:
  - no hay turno abierto;
  - preflight permite abrir turno.
- SKU UAT:
  - `id_sku=1760`;
  - `TP-40352-500GR`;
  - precio `295`;
  - permite venta fraccionaria;
  - controla inventario.
- Stock:
  - preflight permite cargar `cantidad=1`;
  - referencia recomendada `INV-INICIAL-POS-UAT-20260630-A5-S1760`.
- Venta anterior `POS-20260629-000003`:
  - ya quedo `devuelta`;
  - no debe usarse para probar reembolso de caja;
  - readiness bloquea correctamente por `La venta ya esta cancelada/devuelta`.

## Alcance autorizado solicitado

La UAT requiere escrituras reales en BD:

1. Abrir turno POS.
2. Cargar stock UAT por kardex.
3. Ejecutar venta POS real nueva.
4. Ejecutar devolucion POS real sobre esa venta con:
   - `decision_inventario=cuarentena`;
   - `decision_financiera=reembolso_caja`.
5. Validar post-reversa read-only.
6. Generar ticket de devolucion read-only.
7. Cerrar turno con monto contado esperado.

## Reglas de seguridad

- Usar respaldo externo:
  - `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- No usar venta ya devuelta.
- No reintegrar inventario automaticamente.
- No mover inventario en la devolucion porque la decision sera `cuarentena`.
- El reembolso debe crear movimiento de caja y pago tipo `reembolso`.
- El turno debe quedar cerrado al final de la UAT.
- Si cualquier paso falla, detener la secuencia y documentar el bloqueo.

## Autorizacion recomendada

```text
AUTORIZO UAT REEMBOLSO CAJA POS REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 precio=295 pago=295 monto_inicial=500 monto_contado=500 referencia_stock=INV-INICIAL-POS-UAT-20260630-A5-S1760 motivo="UAT devolucion POS con reembolso de caja"
```

## Ejecucion manual equivalente

Abrir turno:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_inicial=500 --observaciones="UAT reembolso caja POS"
```

Cargar stock:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_apply_authorized.php --autorizar=VENTAS_POS_STOCK_UAT --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --referencia=INV-INICIAL-POS-UAT-20260630-A5-S1760
```

Ejecutar venta nueva:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cobro_ui_apply_authorized.php --autorizar=VENTAS_POS_COBRO_UI_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --cliente="Cliente UAT reembolso caja"
```

Ejecutar reversa con reembolso de caja:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_apply_authorized.php --autorizar=VENTAS_POS_REVERSA_REAL --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --folio=[FOLIO_VENTA_NUEVA] --id_venta_detalle=[ID_DETALLE_NUEVO] --cantidad=1 --tipo=devolucion --decision_inventario=cuarentena --decision_financiera=reembolso_caja --motivo="UAT devolucion POS con reembolso de caja"
```

Validar post-reversa:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_reversa_post_readonly.php --folio_devolucion=[FOLIO_DEVOLUCION]
```

Ticket devolucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_devolucion_readonly.php --folio_devolucion=[FOLIO_DEVOLUCION]
```

Cerrar turno:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql --id_usuario=1 --monto_contado=500 --observaciones="Cierre UAT POS reembolso caja"
```

## Resultado esperado

- Venta nueva pagada por `295`.
- Devolucion aplicada por `295`.
- Movimiento caja de ingreso por venta `+295`.
- Movimiento caja de reembolso `-295`.
- Esperado de turno vuelve a `500`.
- Cierre con `monto_contado=500` y diferencia `0`.
- Venta queda `devuelta`.
- Devolucion queda `aplicada`.
- Ticket devolucion muestra `REEMBOLSO_CAJA`.
- Inventario no regresa a disponible porque la decision es `cuarentena`.
