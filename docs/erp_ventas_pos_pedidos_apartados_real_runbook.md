# ERP Ventas/POS - Runbook flujo real Pedidos/Apartados

Fecha: 2026-07-03

Este runbook prepara la implementacion real de pedidos/apartados POS. No autoriza ni ejecuta escrituras por si mismo.

## Objetivo

Permitir que el POS cree pedidos o apartados con cliente CRM, partidas, anticipo/abonos, reserva de inventario, liquidacion, entrega y trazabilidad.

## Principios

- No usar `ecom_pedidos` ni tablas legacy.
- No reservar ni descontar inventario desde JS.
- Toda escritura debe ser transaccional.
- Toda reserva debe poder consumirse o liberarse.
- Todo pago debe ligarse a caja, turno, metodo y usuario.
- Todo cambio de estatus debe generar evento.
- La salida de inventario y kardex ocurren al entregar o al cobrar/entregar, no por crear un borrador.
- Ecommerce queda fuera de este flujo hasta auditoria propia.

## Tablas objetivo

- `erp_ventas`
- `erp_ventas_detalle`
- `erp_ventas_pagos`
- `erp_ventas_eventos`
- `erp_inventario_reservas`
- `erp_ventas_detalle_inventario`
- `erp_pos_movimientos_caja`
- `erp_ventas_politicas_apartado`
- `erp_clientes` / contrato CRM canonico

## Estados propuestos

Encabezado:

- `borrador`: capturado sin reserva.
- `reservado`: reserva activa.
- `pendiente_pago`: anticipo registrado y saldo pendiente.
- `pagado`: saldo liquidado, pendiente entrega si aplica.
- `entregado`: inventario consumido y mercancia entregada.
- `cancelado`: documento sin efecto vigente.

Reserva:

- `activa`
- `consumida`
- `liberada`
- `vencida`
- `cancelada`

Pago:

- `anticipo`
- `abono`
- `liquidacion`
- `reembolso`

## Endpoints a implementar

### `/ventas/pedido_guardar_erp`

Crea pedido/apartado real con partidas y, si la politica lo indica, reserva inventario.

Validaciones:

- usuario autenticado;
- permiso `ventas.operar`;
- caja/turno activos si hay anticipo;
- cliente CRM o identificador publico;
- tipo `pedido` o `apartado`;
- fecha compromiso valida;
- stock suficiente cuando reserve;
- anticipo minimo si tipo `apartado`;
- precio resuelto por backend o excepcion comercial autorizada.

Escrituras transaccionales:

- encabezado en `erp_ventas`;
- partidas en `erp_ventas_detalle`;
- reservas en `erp_inventario_reservas`;
- ajuste `cantidad_apartada` / `cantidad_disponible`;
- pago anticipo si existe;
- movimiento caja por anticipo si existe;
- evento `pedido_creado` o `apartado_creado`;
- evento `reserva_creada`;
- evento `anticipo_registrado` si aplica.

### `/ventas/apartado_abono_erp`

Registra abono real a pedido/apartado existente.

Validaciones:

- folio/id existente;
- tipo documento `pedido` o `apartado`;
- estatus abonable;
- caja/turno activos;
- metodo de pago valido;
- monto mayor a cero;
- no exceder saldo salvo politica de cambio/sobrepago.

Escrituras transaccionales:

- `erp_ventas_pagos` tipo `abono` o `liquidacion`;
- `erp_pos_movimientos_caja` ingreso;
- actualizar `pagado_total` y `saldo_total`;
- actualizar estatus si liquida;
- evento `abono_registrado` o `liquidacion_registrada`.

### `/ventas/pedido_entregar_erp`

Entrega mercancia y consume reserva/inventario.

Validaciones:

- permiso futuro `ventas.pedidos.entregar`;
- pedido/apartado existente;
- saldo liquidado, salvo politica permita entrega sin liquidar;
- reserva activa o stock disponible revalidado;
- partidas no entregadas previamente.

Escrituras transaccionales:

- consumir reservas;
- actualizar existencias/unidades;
- insertar kardex `salida_venta`;
- insertar trazabilidad en `erp_ventas_detalle_inventario`;
- actualizar partidas a `entregada`;
- actualizar encabezado a `entregado`;
- evento `pedido_entregado`.

### `/ventas/pedido_cancelar_erp`

Cancela pedido/apartado y libera reserva.

Validaciones:

- permiso `ventas.cancelar` o permiso especifico;
- motivo obligatorio;
- no entregado;
- politica de cancelacion/penalizacion.

Escrituras transaccionales:

- liberar reserva;
- revertir `cantidad_apartada`;
- actualizar estatus a `cancelado`;
- registrar evento;
- si hay pagos, crear decision financiera: saldo a favor, reembolso autorizado o penalizacion.

## Secuencia tecnica recomendada

1. Crear helpers privados en `VentasErp` para:
   - obtener politica de apartado;
   - generar folio pedido/apartado;
   - crear encabezado;
   - crear partidas;
   - crear reservas;
   - registrar evento;
   - registrar pago/caja;
   - consumir/liberar reserva.
2. Implementar `pedidoGuardarReal` reutilizando `pedidoReservaDryRun` como preflight.
3. Implementar `apartadoAbonoReal` reutilizando `apartadoAbonoDryRun`.
4. Implementar `pedidoEntregarReal` con consumo de reserva y kardex.
5. Implementar `pedidoCancelarReal` con liberacion de reserva.
6. Agregar endpoints en `Ventas.php`.
7. Conectar UI `/ventas/pedidos` con acciones reales solo cuando autorizadas.
8. Crear scripts UAT apply-authorized separados para cada operacion.

## UAT minima

### UAT-PED-001 Crear apartado con reserva

Datos:

- usuario `1`;
- almacen `5`;
- SKU `1760`;
- cantidad `1`;
- precio `295`;
- anticipo `100`;
- cliente CRM por telefono `3312345678`.

Esperado:

- `erp_ventas.tipo_documento='apartado'`;
- saldo `195`;
- reserva activa;
- existencia disponible baja;
- cantidad apartada sube;
- movimiento caja por anticipo;
- evento creado;
- sin kardex de salida todavia.

### UAT-PED-002 Registrar abono

Esperado:

- pago tipo `abono`;
- movimiento caja ingreso;
- saldo baja;
- evento de abono.

### UAT-PED-003 Liquidar y entregar

Esperado:

- saldo `0`;
- reserva consumida;
- kardex salida;
- detalle inventario trazado;
- estatus `entregado`.

### UAT-PED-004 Cancelar antes de entregar

Esperado:

- reserva liberada;
- disponible regresa;
- apartado queda cancelado;
- pagos quedan para decision financiera.

## Autorizacion requerida

```text
AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS
```
