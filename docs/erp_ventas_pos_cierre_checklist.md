# ERP Ventas/POS - Checklist de cierre UAT

Fecha: 2026-07-03

Este documento resume el punto actual del modulo POS y el orden recomendado para terminarlo sin perder trazabilidad ni mezclar flujos legacy.

## Estado actual

- POS mostrador ya opera separado de caja, evidencias, devoluciones, pedidos/apartados y configuracion.
- Venta real UI validada con folio `POS-20260701-000001`.
- Ticket formal read-only validado desde el backend.
- Detalle de venta dedicado disponible en `/ventas/venta_detalle?folio=POS-20260701-000001`.
- Turno UAT abierto: `TUR-20260630-002-002`, caja `2`, almacen `5`.
- Turno UAT `TUR-20260630-002-002` cerrado correctamente con esperado `795`, contado `795`, diferencia `0`.
- Turno nuevo `TUR-20260703-002-001`, `id_turno_caja=11`, caja `2`, almacen `5`, monto inicial `500`, cerrado correctamente con esperado `795`, contado `795`, diferencia `0`.
- Stock UAT cargado para SKU `1760`, cantidad `1`, referencia `INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1`.
- Venta real ejecutada: folio `POS-20260703-000001`, total `295`, pago efectivo `295`, kardex `71`, movimiento caja `24`, existencia `34` de `1` a `0`.
- Turnos abiertos actuales: `0`.
- SKU UAT `1760` en almacen `5` quedo con existencia `0`; una venta nueva debe bloquear por inventario insuficiente hasta recargar stock.
- Pedidos/apartados tienen pantalla dedicada en `/ventas/pedidos`, pero el flujo real de creacion, reserva, abonos y entrega sigue pendiente de autorizacion.
- Caja movimientos y evidencias tienen pantallas separadas de consulta/simulacion, sin acciones reales nuevas.
- Antecedente tecnico: MySQL UAT estuvo inestable; revisar `docs/erp_ventas_pos_bloqueo_mysql_uat.md` y revalidar ping/readiness justo antes de intentar cierre real.

## Pruebas manuales disponibles ahora

### POS mostrador

1. Abrir `/ventas/pos`.
2. Validar que el POS muestre usuario, tienda, almacen, caja y turno.
3. Buscar SKU `1760`.
4. Intentar agregar/cobrar una unidad.
5. Resultado esperado actual: bloqueo por existencia insuficiente si no se ha cargado stock nuevo.
6. Usar boton `Simular pedido` para validar el carrito sin crear folio.
7. Usar boton `Pedidos` o tecla `F10` para abrir el modulo dedicado de pedidos/apartados.

### Detalle de venta

1. Abrir `/ventas/venta_detalle?folio=POS-20260701-000001`.
2. Revisar resumen de venta, cliente, partidas, pagos, caja, kardex, garantia y ticket.
3. Usar imprimir solo como validacion visual.
4. Resultado esperado: la pantalla debe mostrar la venta pagada de `295` y su trazabilidad.

### Caja turnos

1. Abrir `/ventas/caja_turnos`.
2. Revisar turno abierto.
3. Simular cierre con monto contado `795`.
4. Resultado esperado: diferencia `0`.
5. Ejecutar `Revisar readiness` para ver ticket, cierre, reserva, abono y devoluciones pendientes en una sola vista.
6. Si readiness muestra diferencia `0`, copiar la autorizacion sugerida al chat si se desea cerrar real.
7. No ejecutar cierre real sin autorizacion explicita.
8. Usar la navegacion superior para saltar a Movimientos, Evidencias, Configuracion, POS o Ventas sin mezclar flujos.

### Movimientos de caja

1. Abrir `/ventas/caja_movimientos`.
2. Seleccionar un tipo como `gasto_caja`.
3. Capturar monto, motivo y referencia.
4. Ejecutar simulacion.
5. Resultado esperado: prevalidacion/dry-run sin escribir en BD.
6. Validar que la navegacion superior permita volver a Turnos, Evidencias, Configuracion, POS o Ventas.

### Evidencias de caja

1. Abrir `/ventas/caja_evidencias`.
2. Filtrar por pendientes o todas.
3. Abrir detalle de una evidencia.
4. Resultado esperado: consulta de evidencias y correcciones sin aprobar ni rechazar.
5. Validar que la navegacion superior permita volver a Turnos, Movimientos, Configuracion, POS o Ventas.

### Devoluciones

1. Abrir `/ventas/devoluciones`.
2. Buscar folio de venta existente o revisar pendientes fisicos.
3. Resultado esperado: consulta y guia de trazabilidad; acciones reales quedan bajo autorizacion.

### Pedidos y apartados

1. Abrir `/ventas/pedidos`.
2. En `Simular pedido/apartado`, usar tipo `Apartado`, SKU `1760`, cantidad `1`, precio `295`, anticipo `100`, cliente `Cliente UAT POS` e identificador `3312345678`.
3. Ejecutar `Simular reserva`.
4. Resultado esperado actual: politica `POS_APARTADO_UAT`, anticipo minimo `59`, saldo `195`, bloqueo por `Existencia insuficiente` porque SKU `1760` no tiene stock disponible.
5. Intentar simular abono solo si existe folio real.
6. Resultado esperado actual: muestra sin pedidos/apartados UAT reales; abono fake debe bloquear con `Pedido/apartado no encontrado`.

## Pruebas tecnicas sin escritura

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260701-000001
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_turno_dryrun_readonly.php --id_usuario=1 --monto_contado=795
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php --id_usuario=1 --id_almacen=5 --folio=APT-UAT-000001 --monto_abono=100 --id_metodo_pago=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_readiness_readonly.php --compact=1 --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_contado=795 --monto_abono=100 --folio_venta=POS-20260701-000001 --folio_apartado=APT-UAT-000001
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_configuracion_readonly.php --compact=1 --id_usuario=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_cierre_preflight_readonly.php --id_usuario=1 --monto_contado=795 --observaciones="Cierre UAT POS preflight TUR-20260630-002-002"
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_post_cierre_readonly.php --compact=1 --id_turno_caja=10
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_stock_uat_preflight_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --referencia=INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=1 --monto_inicial=500 --observaciones="Apertura UAT POS posterior a cierre"
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=1 --id_sku=1760 --cantidad=1 --precio=295 --pago=295 --cliente="Cliente UAT POS post cierre"
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pedidos_apartados_readonly.php --compact=1 --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_abono=100 --folio=APT-UAT-000001
```

Revalidacion 2026-07-03:

- ticket formal `POS-20260701-000001`: OK, 28 lineas, hallazgos `[]`;
- cierre dry-run turno `10`: esperado `795`, contado `795`, diferencia `0`, bloqueos `[]`;
- pedidos/apartados: `0` pedidos, `0` apartados, reserva dry-run bloqueada por `Existencia insuficiente`, abono fake bloqueado por `Pedido/apartado no encontrado`;
- apartado dry-run: politica `POS_APARTADO_UAT`, anticipo minimo `59`, pagado simulado `100`, saldo estimado `195`, fecha maxima compromiso `2026-08-01`;
- devoluciones fisicas pendientes: `1` partida, folio `DEV-20260630-000001`, decision `cuarentena`, inspeccion pendiente;
- readiness consolidado: cierre diferencia `0`, ticket `28` lineas, reserva bloqueada por stock, abono bloqueado por folio inexistente y `1` devolucion fisica pendiente;
- readiness UI: disponible en `/ventas/caja_turnos` mediante boton `Revisar readiness`;
- readiness CLI: soporta `--compact=1` para guardar evidencia resumida sin exponer todo el detalle interno;
- autorizacion sugerida: Caja turnos muestra boton `Copiar autorizacion` cuando el cierre dry-run/readiness queda sin diferencia;
- configuracion POS read-only: usuario `1` tiene asignacion activa a almacen `5`, caja `2`, terminal `2`, esquema completo, `2` cajas, `2` terminales, `2` asignaciones y `0` hallazgos;
- preflight de cierre: turno `TUR-20260630-002-002`, esperado `795`, contado `795`, diferencia `0`, bloqueos `[]`;
- post-cierre compacto antes de cierre real: turno sigue `abierto`, sin fecha de cierre, hallazgos esperados porque aun no se autorizo el cierre real;
- preflight stock siguiente UAT: SKU `1760`, cantidad `1`, referencia fija `INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1`, bloqueos `[]`;
- preflight apertura siguiente turno: bloqueado por `Ya existe turno abierto para esta caja`, esperado hasta cerrar el turno actual;
- preflight venta siguiente UAT: bloqueado por `Existencia insuficiente`, esperado hasta cargar stock;
- pedidos/apartados compacto: reserva bloqueada por `Existencia insuficiente`, abono bloqueado por `Pedido/apartado no encontrado`;
- no hubo escrituras de BD en esta revalidacion.

## Pendientes para considerar POS cerrado en UAT

1. Abrir turno nuevo solo si se desea probar otra venta, apartado o abono real.
2. Cargar stock si se desea probar apartado real.
3. Continuar pedidos/apartados reales o CRUD de configuracion POS.
4. Validar visualmente POS, detalle de venta, turnos, movimientos de caja, evidencias, devoluciones, configuracion y pedidos.
5. Definir si pedidos/apartados entran en el cierre de esta fase o quedan como fase siguiente.
6. Si entran pedidos/apartados: autorizar flujo real con reserva, abonos, liquidacion, entrega y kardex.
7. Decidir alcance de CRUD real de configuracion POS: tiendas, almacenes, cajas, terminales y asignaciones usuario/caja.
8. Cerrar devoluciones fisicas: inspeccion, decision de inventario, evidencia y trazabilidad.

## Politica de respaldo para UAT POS

- No pedir respaldo en cada peticion pequena si ya hay un respaldo vigente validado para el ciclo UAT.
- Volver a pedir respaldo cuando:
  - se aplique DDL o cambio de esquema;
  - se restaure o recupere MySQL;
  - se haga un bloque grande de escrituras reales;
  - cambie el modulo objetivo o el alcance operativo;
  - haya inestabilidad de BD;
  - pasen suficientes cambios como para que el respaldo vigente ya no represente el estado de trabajo.
- Para acciones chicas dentro del mismo ciclo, usar la referencia `respaldo UAT POS vigente` en docs/evidencia y pedir solo la autorizacion operativa concreta.
- En ejecucion tecnica, `respaldo UAT POS vigente` se resuelve a:
  - `C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql`.
- No repetir la ruta en cada autorizacion humana salvo que se cambie de respaldo o de ciclo.

## Autorizaciones recomendadas en orden

### Cierre historico ya ejecutado

```text
AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 monto_contado=795 observaciones="Cierre UAT POS post venta POS-20260703-000001"
```

Resultado:

- turno `TUR-20260703-002-001`;
- estatus `cerrado`;
- fecha cierre `2026-07-03 21:32:47`;
- esperado `795`;
- contado `795`;
- diferencia `0`;
- hallazgos post-cierre `[]`.

### Apertura historica ya ejecutada

El turno `TUR-20260630-002-002` ya quedo cerrado y esta apertura ya fue ejecutada para crear `TUR-20260703-002-001`. Se conserva solo como evidencia del ciclo.

```text
AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones="Apertura UAT POS posterior a cierre"
```

### Flujo real pedidos/apartados

```text
AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS
```

### Carga de stock UAT historica ya ejecutada

Esta carga ya fue ejecutada para preparar la venta `POS-20260703-000001`. Para un apartado real nuevo se necesita otra carga o un SKU con stock.

```text
AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=1 id_almacen=5 id_sku=1760 cantidad=1 referencia=INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1
```

### Venta real UAT historica ya ejecutada

Esta venta ya fue ejecutada y dejo el SKU `1760` sin existencia disponible en almacen `5`.

```text
AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario=1 id_sku=1760 cantidad=1 precio=295 pago=295 cliente="Cliente UAT POS post cierre"
```

Runbook preparado:

- `docs/erp_ventas_pos_pedidos_apartados_real_runbook.md`
- Acceso desde POS:
  - `/ventas/pos` muestra boton `Pedidos` para abrir `/ventas/pedidos`.

Alcance recomendado:

- crear pedido/apartado real;
- ligar cliente CRM;
- generar folio ERP;
- registrar partidas;
- reservar inventario segun politica;
- registrar abonos contra caja/turno;
- liquidar saldo;
- entregar mercancia;
- descontar inventario con kardex al momento correcto;
- conservar eventos y trazabilidad.

### CRUD real configuracion POS

```text
AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD para UAT POS
```

Runbook preparado:

- `docs/erp_ventas_pos_configuracion_real_runbook.md`

Alcance recomendado:

- tiendas/sucursales;
- almacenes ligados a tienda;
- cajas;
- terminales;
- asignaciones de usuario a POS;
- reglas de caja;
- permisos por accion.

## Criterios de cierre funcional

- Cada venta conoce tienda, almacen, caja, turno, usuario y cliente cuando aplique.
- El POS no permite vender stock inexistente.
- Unidad cerrada y venta a granel respetan reglas de inventario.
- Todo cobro real genera venta, pagos, caja, kardex y ticket.
- Toda excepcion comercial queda autorizada, justificada y ligada a la venta.
- Todo reembolso/gasto/retiro queda en caja con evidencia cuando aplique.
- Las devoluciones no regresan inventario vendible sin inspeccion/decision.
- Pedidos/apartados no prometen stock sin reserva o politica clara.
- Configuracion POS evita que el cajero elija libremente tienda/caja ajena.
- Ecommerce queda fuera de cambios operativos hasta auditoria propia.

## Siguiente paso recomendado

Primero cerrar el turno `TUR-20260703-002-001` o cargar stock adicional si se desea probar apartado real antes del cierre.
