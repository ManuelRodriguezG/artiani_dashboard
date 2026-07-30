# Plan de integracion POS -> TMS Delivery

Fecha: 2026-07-29

## Decision de arquitectura

TMS Delivery se mantiene como modulo completo e independiente. POS puede abrir una solicitud logistica porque es un punto de captura rapido, pero TMS no debe guardar reglas ni estados de venta.

El enfoque de TMS es solamente:

- recoger;
- preparar;
- llevar;
- evidenciar;
- cerrar;
- reprogramar cuando aplique.

TMS no debe responder preguntas comerciales. Si hubo o no venta, si se pago o no se pago un producto, si procede una garantia o si el cliente recibira otro cargo comercial, eso vive fuera de TMS.

## Contrato inicial

Cuando POS abra una solicitud TMS, debe enviar solo contexto logistico:

- `solicitado_por_modulo`: `pos`
- `solicitado_por_tipo`: `solicitud_pos`
- `referencia_externa`: referencia operativa opcional, no folio obligatorio de venta
- `tipo_servicio`: `entrega_express`, `entrega_programada`, `entrega_local`, `recoleccion_cliente` o `entrega_tercero`
- `prioridad`: `normal`, `express` o `urgente`
- `estatus_cobro`: estado del cobro logistico
- `precio_cobrado`: importe del servicio logistico, si se capturo
- `cliente_nombre_snapshot`, `cliente_contacto_snapshot`, `direccion_snapshot`, `zona_snapshot`
- `detalle`: paquetes o referencias fisicas como snapshot

Campos que POS no debe delegar a TMS:

- estatus de operacion comercial;
- cobro de productos;
- descuentos;
- garantias;
- devoluciones;
- salida de inventario;
- decision de cancelar o cerrar una operacion comercial.

## Entrega por tercero

`entrega_tercero` significa que el movimiento fisico lo realiza una plataforma, paqueteria o repartidor externo.

TMS puede registrar:

- responsable externo;
- costo logistico;
- referencia o guia;
- evidencia de entrega al tercero;
- evidencia final recibida, si existe;
- resultado logistico.

TMS no convierte al tercero en responsable del producto ni mezcla politicas de plataforma con reglas internas. Solo documenta que el compromiso logistico se ejecuto usando un tercero.

## Reintento

Si no se concreta la entrega porque el cliente no estaba disponible, TMS debe cerrar o dejar pendiente el servicio logistico con evidencia.

Opciones operativas:

- `reprogramada`: se acuerda nuevo intento.
- `pendiente_cliente`: el cliente debe confirmar nueva ventana.
- `cerrada_sin_entrega`: se cierra el servicio sin entrega.

Si el negocio decide cobrar otro intento, eso se registra como otro servicio logistico o como costo/cobro adicional autorizado dentro de TMS. No se asume automatico.

## UX POS propuesta

La seccion `Entrega TMS` en POS solo previsualiza la solicitud logistica:

- activar/desactivar solicitud;
- tipo de servicio;
- prioridad;
- fecha y ventana;
- contacto;
- direccion;
- zona;
- precio logistico o por cobrar;
- observaciones logisticas.

No crea folio TMS en el dry-run y no modifica la operacion comercial.

## Flujo operativo

1. El operador captura o recibe una necesidad de entrega.
2. Si usa POS como punto rapido de captura, activa `Entrega TMS`.
3. POS envia a TMS un snapshot logistico.
4. TMS previsualiza bloqueos y advertencias.
5. Cuando se autorice la fase real, TMS creara un folio logistico propio.
6. TMS opera ese folio hasta entregarlo, reprogramarlo o cerrarlo sin entrega.

## Fases

### POS-TMS-T001 - Contrato read-only

Estado: completado.

- Documentar contrato de datos logistico.
- Validar que POS solo abre solicitud logistica.
- Validar que TMS ya puede crear servicios manuales sin depender de ventas.

### POS-TMS-T002 - Adapter dry-run

Estado: completado.

- Controlador: `Tms::servicio_pos_dryrun_erp`.
- Modelo: `TmsDelivery::servicioDesdePosDryRun`.
- UAT: `storage/uat/uat_tms_delivery_pos_contract_readonly.php`.

### POS-TMS-T003 - UI opt-in POS

Estado: completado read-only.

Agregar UI compacta `Entrega TMS` en POS sin crear servicios reales. Alimenta solo el payload de prevalidacion.

### POS-TMS-T004 - Creacion real desde POS

Estado: implementado en UI/JS; UAT real pendiente de autorizacion separada.

POS captura el snapshot logistico antes de cerrar su flujo normal. Si el cierre POS responde correctamente, el navegador llama a TMS por separado mediante `/tms/servicio_guardar_erp`.

Reglas:

- POS no envia el payload TMS a `/ventas/pos_confirmar_erp`.
- `Ventas.php` y `VentasErp.php` no instancian ni escriben TMS.
- TMS crea folio logistico propio.
- `referencia_externa` es referencia operativa de captura, no folio obligatorio de venta.
- Si la creacion TMS falla, la operacion POS ya cerrada no se modifica; se muestra el error para reintentar o capturar desde TMS.

La ejecucion real controlada requiere respaldo y autorizacion `TMS_POS_REAL_BASE`.

### POS-TMS-T005 - Postcheck

Validar:

- servicio TMS creado con folio propio;
- origen `pos` como canal de captura;
- importes logisticos separados;
- TMS solo opera compromiso logistico;
- falla de entrega no altera otros modulos.

## Regla de continuidad

La siguiente implementacion real debe mantener a POS como canal de captura, no como origen comercial obligatorio. TMS debe poder existir igual con solicitudes manuales, ecommerce, CRM u operacion interna.

## Fuera de esta fase

El rastreo publico para cliente queda pospuesto. La fase actual se limita a crear y operar servicios logisticos internos con evidencia y estados TMS.
