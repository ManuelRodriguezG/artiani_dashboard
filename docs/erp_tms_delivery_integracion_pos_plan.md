# Plan de integracion POS -> TMS Delivery

Fecha: 2026-07-28

## Decision de arquitectura

TMS Delivery se mantiene como modulo completo e independiente. POS/Ventas puede solicitar un servicio logistico, pero no convierte el envio en producto, no vuelve la entrega una condicion de la venta y no transfiere a TMS decisiones comerciales.

La relacion correcta es:

- POS/Ventas: confirma venta, cobra producto, decide si cobra envio en su propio flujo de caja y conserva sus reglas de inventario.
- TMS: recibe una solicitud logistica con snapshot, genera folio propio, programa, opera, evidencia, cierra o reprograma el servicio.
- Garantias/Postventa: decide reclamos de producto. Si necesita movimiento fisico, solicita otro servicio logistico visible y separado.

## Contrato inicial

Cuando POS solicite TMS, debe enviar solo contexto logistico:

- `solicitado_por_modulo`: `ventas`
- `solicitado_por_tipo`: `pos_venta`, `pedido_pos` o `apartado_pos`
- `solicitado_por_id`: id real de venta/pedido si existe
- `referencia_externa`: folio de venta/pedido
- `tipo_servicio`: `entrega_express` o `entrega_programada`
- `prioridad`: `normal`, `express` o `urgente`
- `estatus_cobro`: estado del cobro logistico, no del producto
- `precio_cobrado`: importe del servicio logistico, si POS lo capturo
- `cliente_nombre_snapshot`, `cliente_contacto_snapshot`, `direccion_snapshot`, `zona_snapshot`
- `detalle`: paquetes o referencias fisicas como snapshot

Campos que POS no debe delegar a TMS:

- estatus de venta;
- cancelacion de venta;
- aplicacion de garantia;
- devoluciones;
- salida de inventario;
- cobro de productos;
- descuentos comerciales del producto.

## Punto de enganche recomendado

El punto seguro para crear un servicio TMS real desde POS es despues de `VentasErp::confirmarVentaPosReal`, cuando ya existe `id_venta` y `folio`.

Antes de confirmar la venta, POS puede ofrecer una previsualizacion o dry-run logistico, pero no debe insertar `erp_tms_servicios` porque la venta aun podria no existir.

Para pedidos/apartados, el punto seguro equivalente es despues de `pedidoGuardarReal`, usando el folio del pedido/apartado como referencia externa. Si el pedido se entrega por mostrador, no se crea TMS.

## UX POS propuesta

Agregar una seccion compacta en POS llamada `Entrega TMS`:

- control para activar/desactivar solicitud de entrega;
- tipo de servicio;
- prioridad;
- fecha y ventana prometida;
- contacto;
- direccion;
- zona;
- precio logistico cobrado o por cobrar;
- observaciones logisticas.

El total de producto y el importe logistico deben verse separados. La UI no debe decir ni insinuar que el envio va incluido en el producto.

## Flujo operativo

1. El operador arma carrito en POS.
2. El operador activa `Entrega TMS` si el cliente requiere delivery.
3. POS valida productos, pagos, caja e inventario con sus reglas.
4. Al confirmar venta real, POS crea venta y obtiene folio.
5. Si `Entrega TMS` estaba activa, POS solicita a TMS crear servicio con snapshot logistico.
6. TMS devuelve folio propio.
7. POS muestra folio de venta y folio TMS como referencias separadas.
8. Si TMS falla, TMS registra incidencia o cierre sin entrega; POS no cambia automaticamente.

## Fases

### POS-TMS-T001 - Contrato read-only

Estado: completado.

- Documentar contrato de datos.
- Validar que POS tiene dry-run y confirmacion real separados.
- Validar que TMS ya puede crear servicios manuales sin depender de Ventas.
- Confirmar que no existe integracion POS real activa todavia.

### POS-TMS-T002 - Adapter dry-run

Estado: completado.

Crear en TMS un metodo de prevalidacion de solicitud desde POS que no escriba BD y devuelva bloqueos logisticos.

Implementado:

- Controlador: `Tms::servicio_pos_dryrun_erp`.
- Modelo: `TmsDelivery::servicioDesdePosDryRun`.
- UAT: `storage/uat/uat_tms_delivery_pos_contract_readonly.php`.
- Resultado 2026-07-28: 35/35 checks.

### POS-TMS-T003 - UI opt-in POS

Agregar UI compacta `Entrega TMS` en POS sin crear servicios reales. Debe alimentar solo el payload de prevalidacion.

### POS-TMS-T004 - Creacion real autorizada

Con respaldo y autorizacion separada, crear el servicio TMS despues de venta real exitosa. Si la venta falla, no se crea TMS.

### POS-TMS-T005 - Postcheck

Validar:

- venta creada por POS;
- servicio TMS creado con folio propio;
- referencia cruzada por snapshot;
- importes separados;
- TMS no cambia venta;
- TMS no mueve inventario;
- TMS no decide garantia.

## Regla de continuidad

La siguiente implementacion debe empezar por el adapter dry-run, no por escribir directo desde POS. Asi se conserva la separacion de dominio y se puede probar la forma del payload antes de autorizar escrituras reales.
