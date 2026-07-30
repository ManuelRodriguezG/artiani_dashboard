# TMS Delivery - Previsualizacion de comprobante logistico

Fecha: 2026-07-30

## Decision recomendada

Nombre recomendado para el servicio:

```text
ARTIANI Entregas
```

Motivo:

- conserva la marca principal;
- se entiende rapido por el cliente;
- no parece paqueteria externa ni negocio separado;
- permite separar el comprobante logistico del ticket de productos.

Nombres alternativos:

- `ARTIANI Delivery`: claro, pero mas generico.
- `ARTIANI Ruta`: operativo y corto, pero menos claro para cliente.
- `ARTIANI Logistica`: formal, pero puede sentirse frio para ventas express.

Nombre interno del modulo:

```text
TMS Delivery
```

Nombre visible para cliente:

```text
ARTIANI Entregas
```

## Criterio de impresion

El servicio logistico debe imprimir un comprobante propio. Puede salir:

- separado del ticket POS;
- o en la misma impresion despues del ticket POS, pero como bloque independiente.

Aunque se imprima junto, debe tener:

- folio TMS propio;
- importe logistico propio;
- estatus de cobro logistico;
- leyenda que separa el servicio logistico de productos, garantias e inventario.

## Previsualizacion 80mm

```text
              ARTIANI ENTREGAS
          COMPROBANTE LOGISTICO
------------------------------------------------
Folio TMS: TMS-20260729-210207-625
Referencia: POS-SOL-UAT-20260729-210207
Fecha solicitud: 29/07/2026 21:02
Fecha programada: 30/07/2026
Ventana: 10:00 - 12:00
------------------------------------------------
CLIENTE
Cliente UAT POS TMS
Contacto: 3312345678
Zona: Zona UAT POS

DIRECCION
Direccion UAT POS TMS
------------------------------------------------
SERVICIO
Tipo: Entrega express
Prioridad: Express
Estado: Entregada
Resultado: Completa
------------------------------------------------
COBRO LOGISTICO
Importe entrega:              $75.00
Estatus cobro:              Por cobrar
Metodo:                    Efectivo
------------------------------------------------
PAQUETE / REFERENCIA FISICA
1 x Paquete logistico capturado desde POS
Cuidado especial: Si
------------------------------------------------
EVIDENCIA
Nota: servicio logistico entregado.
Eventos registrados: 5
Evidencias registradas: 1
------------------------------------------------
Este comprobante corresponde unicamente al
servicio logistico de entrega.

No modifica garantias, cambios, devoluciones,
pagos ni condiciones de los productos.

La garantia de producto se atiende conforme a
las politicas del local.
------------------------------------------------
Gracias por confiar en ARTIANI.
```

## Version corta para entregar al cliente

```text
           ARTIANI ENTREGAS
        COMPROBANTE LOGISTICO
------------------------------------------
Folio TMS: TMS-20260729-210207-625
Servicio: Entrega express
Estado: Entregada
Resultado: Completa
Fecha programada: 30/07/2026
Ventana: 10:00 - 12:00
------------------------------------------
Cliente: Cliente UAT POS TMS
Contacto: 3312345678
Zona: Zona UAT POS
------------------------------------------
Importe entrega:          $75.00
Estatus cobro:          Por cobrar
------------------------------------------
Comprobante exclusivo del servicio
logistico. No modifica garantias,
pagos ni condiciones de productos.
------------------------------------------
Gracias por confiar en ARTIANI.
```

## Bloque si se imprime junto al ticket POS

```text
==========================================
SERVICIO LOGISTICO SEPARADO
ARTIANI ENTREGAS
Folio TMS: TMS-20260729-210207-625
Tipo: Entrega express
Importe entrega: $75.00
Estatus cobro: Por cobrar

Este importe corresponde al servicio
logistico, no al producto.
==========================================
```

## Campos configurables recomendados

- `nombre_servicio_cliente`: `ARTIANI Entregas`
- `subtitulo_ticket`: `Comprobante logistico`
- `leyenda_separacion`: `Este comprobante corresponde unicamente al servicio logistico de entrega.`
- `leyenda_garantia`: `La garantia de producto se atiende conforme a las politicas del local.`
- `mostrar_direccion`: si
- `mostrar_contacto`: si
- `mostrar_detalle_paquete`: si
- `mostrar_eventos`: solo interno o copia local
- `mostrar_evidencias`: solo resumen, no fotos en ticket termico
- `copias`: cliente y local cuando el cobro logistico queda por cobrar

## Recomendacion operativa

Para el local:

1. Si el cliente paga productos y entrega al mismo tiempo, POS debe registrar el ingreso de productos y el ingreso logistico como conceptos separados.
2. El cliente puede recibir una sola impresion fisica, pero con dos bloques:
   - Ticket POS de productos.
   - Comprobante `ARTIANI Entregas`.
3. Si la entrega queda `por cobrar`, el comprobante TMS debe decirlo claramente.
4. Si la entrega ya fue pagada, el comprobante TMS debe mostrar `Pagado`.

## Pendiente de implementacion

La previsualizacion ya define el formato, pero falta implementar:

- endpoint read-only `Tms::ticket_readonly_erp`;
- metodo `TmsDelivery::ticketServicioReadOnly`;
- boton de imprimir comprobante en servicios/operacion TMS;
- configuracion editable de leyendas y nombre visible;
- movimiento de caja separado para hacer efectivo el cobro logistico cuando se pague en local.
