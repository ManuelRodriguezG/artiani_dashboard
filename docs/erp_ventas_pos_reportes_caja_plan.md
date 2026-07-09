# ERP Ventas/POS - Plan reportes de caja y supervision

Fecha: 2026-07-03

## Objetivo

Crear un area de reportes para entender la operacion real de tienda: ventas, caja, diferencias de corte, movimientos no venta, devoluciones, garantias, pedidos/apartados y desempeno por usuario/sucursal/caja.

## Regla operativa clave

Un turno puede cerrarse aunque no cuadre en cero.

- Si `monto_contado > monto_esperado`, hay sobrante.
- Si `monto_contado < monto_esperado`, hay faltante.
- La diferencia no debe bloquear el cierre.
- La diferencia debe quedar registrada en el turno y alimentar reportes.
- La diferencia debe poder explicarse despues con evidencia, correccion, investigacion o ajuste autorizado.

## Reportes fase 1 - Caja operativa

### Corte por turno

Filtros:

- fecha;
- sucursal/almacen;
- caja;
- usuario apertura;
- usuario cierre;
- estatus;
- con diferencia / sin diferencia.

Columnas:

- folio turno;
- apertura;
- cierre;
- monto inicial;
- ventas efectivo;
- ventas tarjeta;
- transferencias;
- ingresos extra;
- gastos;
- retiros;
- reembolsos;
- esperado;
- contado;
- diferencia;
- estado revision.

Acciones:

- ver detalle turno;
- ver movimientos;
- ver ventas;
- ver evidencias;
- marcar en revision;
- resolver diferencia con motivo y evidencia.

### Diferencias por empleado

Objetivo:

- detectar patrones de faltantes/sobrantes por usuario, caja, sucursal y periodo.

Indicadores:

- cantidad de turnos cerrados;
- total faltantes;
- total sobrantes;
- diferencia neta;
- promedio por turno;
- turnos con diferencia;
- porcentaje de turnos con diferencia;
- reincidencias.

Estado 2026-07-03:

- Implementado en modo read-only dentro de `/ventas/reportes`.
- Backend devuelve arreglo `por_usuario`.
- UI muestra turnos, diferencias, porcentaje, faltantes, sobrantes y neto.
- Pendiente: detalle drill-down por empleado y flujo de revision/resolucion.

Reglas:

- no asumir mala practica por un evento aislado;
- mostrar tendencia y evidencia;
- separar diferencias por error operativo, gasto sin registrar, cambio mal entregado, pago mal clasificado, devolucion/reembolso pendiente o investigacion.

### Movimientos no venta

Incluye:

- gastos de caja;
- retiros;
- entradas extraordinarias;
- vales;
- reembolsos;
- correcciones.

Columnas:

- fecha;
- turno;
- caja;
- usuario;
- tipo;
- motivo;
- monto;
- responsable;
- referencia;
- evidencia;
- estatus.

## Reportes fase 2 - Ventas y margen

- ventas por sucursal;
- ventas por caja;
- ventas por vendedor/cajero;
- ventas por metodo de pago;
- ticket promedio;
- productos mas vendidos;
- descuentos/excepciones comerciales;
- margen estimado por SKU;
- ventas con garantia;
- ventas con devolucion.

## Reportes fase 3 - Inventario conectado a POS

- productos vendidos sin trazabilidad incompleta;
- salidas con kardex por folio;
- productos con stock bajo por sucursal;
- unidades abiertas usadas en venta a granel;
- intentos bloqueados por stock insuficiente;
- diferencias entre venta y movimiento inventario.

## Reportes fase 4 - Clientes, apartados y recompensas

- clientes nuevos desde POS;
- clientes recurrentes;
- pedidos/apartados abiertos;
- abonos pendientes;
- apartados vencidos;
- saldo por cliente;
- listas de precio aplicadas;
- excepciones comerciales por cliente.

## Modelo de estados para diferencias de caja

Propuesta futura:

- `pendiente_revision`
- `en_revision`
- `explicada`
- `ajustada`
- `aceptada`
- `escalada`

Mientras no exista tabla formal de revision, la diferencia queda en `erp_pos_turnos.diferencia` y la explicacion inicial en `observaciones_cierre`.

## Datos actuales disponibles

Ya existen:

- `erp_pos_turnos.monto_esperado`;
- `erp_pos_turnos.monto_contado`;
- `erp_pos_turnos.diferencia`;
- `erp_pos_turnos.id_usuario_apertura`;
- `erp_pos_turnos.id_usuario_cierre`;
- `erp_pos_movimientos_caja`;
- `erp_ventas`;
- `erp_ventas_pagos`;
- evidencias/correcciones de caja.

## UAT recomendada

1. Abrir turno con `500`.
2. Registrar venta de `295`.
3. Simular cierre con `795`: diferencia `0`.
4. Simular cierre con `785`: faltante `-10`.
5. Simular cierre con `805`: sobrante `10`.
6. Confirmar que los tres escenarios generan autorizacion sugerida.
7. Cerrar real solo uno de los escenarios autorizado.
8. Verificar que `erp_pos_turnos.diferencia` conserve el valor real.
9. Consultar reporte por turno/usuario/caja.

Script read-only preparado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_diferencias_readonly.php --id_usuario=1 --delta=10
```

Estado actual:

- Sin turno abierto, el script bloquea correctamente y no escribe BD.
- Cuando exista turno abierto, simulara:
  - cierre cuadrado;
  - cierre con faltante;
  - cierre con sobrante.

## Siguiente implementacion recomendada

Crear primero un reporte read-only de caja:

- endpoint `/ventas/reportes_caja_erp`;
- vista `/ventas/reportes`;
- script UAT read-only para diferencias;
- sin escribir BD.

Estado 2026-07-03:

- Implementado reporte read-only inicial.
- Agrega KPIs de turnos, faltantes, sobrantes y diferencia neta.
- Agrega detalle por turno.
- Agrega agregado por empleado.
- Agrega agregado por sucursal/caja.
- Agrega filtros UI por sucursal y caja usando configuracion POS read-only.
- Agrega exportacion CSV local de turnos filtrados desde la UI, sin endpoint adicional.
- UAT read-only actual: 11 turnos, 0 con diferencia, caja `CJ-MASCOTAS971-01`, almacen `MASCOTAS971`.

Despues crear flujo de revision de diferencias:

- tabla de revision;
- evidencia;
- responsable;
- resolucion;
- auditoria.

Estado 2026-07-03 posterior al faltante real:

- Implementada bandeja read-only de diferencias:
  - `VentasErp::diferenciasCajaPendientesReadOnly`;
  - endpoint `/ventas/reportes_diferencias_caja_erp`;
  - script `storage/uat/uat_ventas_pos_diferencias_caja_readonly.php`;
  - seccion `Seguimiento de diferencias` en `/ventas/reportes`.
- UAT actual:
  - turno `TUR-20260703-002-002`;
  - faltante `-10`;
  - estado sintetico `pendiente_revision`;
  - `schema_revision_pendiente=true`.
- Siguiente paso:
  - crear tabla formal `erp_pos_turnos_diferencias_revision`;
  - registrar revision/resolucion sin modificar `erp_pos_turnos`;
  - enlazar evidencia/referencia externa;
  - reportar estados reales.
