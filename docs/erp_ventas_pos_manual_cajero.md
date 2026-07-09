# ERP Ventas/POS - Manual operativo de cajero

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: guia operativa; la venta real sigue pendiente de autorizacion.

## Inicio del dia

1. Entrar al sistema con tu propio usuario.
2. Abrir `Ventas > POS`.
3. Confirmar que arriba diga `Operador: tu nombre`.
4. Confirmar que el POS muestre la sucursal/caja asignada.
5. Si la sucursal o caja no corresponde, no vender y avisar a administracion.
6. Abrir turno cuando el sistema lo permita.
7. Contar el fondo inicial de caja.
8. Registrar el monto inicial autorizado.

## Venta normal

1. Buscar producto por SKU, nombre o escaneo.
2. Revisar la tarjeta del producto:
   - imagen;
   - precio;
   - disponible;
   - etiquetas como `Unidad cerrada`, `Unidad abierta` o `Granel`.
3. Agregar el producto al carrito.
4. Revisar cantidad y modo de salida.
5. Agregar pago:
   - efectivo;
   - tarjeta;
   - transferencia.
6. Si es transferencia, capturar referencia.
7. Prevalidar carrito.
8. Si no hay bloqueos, confirmar venta cuando el boton real este autorizado.
9. Entregar ticket.

## Unidad cerrada

Una unidad cerrada se vende completa.

Validar:

- que aparezca como disponible;
- que este en la sucursal correcta;
- que el sistema la tome como `unidad_cerrada`.

No vender manualmente una unidad cerrada si el sistema la marca como abierta, agotada, consumida, vendida o bloqueada.

## Unidad abierta

Una unidad abierta no se vende como pieza completa.

Solo se vende a granel si:

- el SKU permite venta fraccionaria;
- el POS muestra modo `granel_unidad_abierta`;
- la cantidad respeta el minimo permitido;
- la prevalidacion no muestra bloqueos.

Si el sistema bloquea la unidad abierta, no forzar la venta.

## Pedido o apartado

1. Abrir `Pedidos y apartados`.
2. Elegir `Pedido` o `Apartado`.
3. Capturar cliente o identificador publico.
4. Capturar fecha compromiso.
5. Buscar producto por codigo, SKU o nombre.
6. Capturar cantidad y precio.
7. Presionar `Agregar partida`.
8. Confirmar que la partida aparezca en la tabla.
9. Repetir busqueda/agregado si el cliente pide mas productos.
10. Capturar anticipo y referencia de pago si aplica.
11. Presionar `Simular reserva`.
12. Si no hay bloqueos, confirmar la accion real.

Regla operativa:

- Los campos de SKU/cantidad/precio son solo captura temporal.
- Si el producto no aparece en la tabla de partidas, no forma parte del pedido.
- El total, anticipo y saldo se calculan desde la tabla.
- El pedido puede tener saldo pendiente, segun reglas autorizadas.
- El inventario reservado o consumido se controla con folio ERP, no con nota manual.

## Bloqueos comunes

`Configura cajas POS antes de cobrar`

- La caja no esta creada o asignada.
- Avisar a administracion.

`Abre turno de caja antes de cobrar`

- No hay turno abierto.
- Abrir turno autorizado antes de vender.

`Existencia insuficiente`

- No hay stock disponible en esa sucursal.
- No vender desde otra sucursal sin traspaso o autorizacion.

`La unidad abierta no puede venderse como unidad cerrada`

- Cambiar a granel solo si el SKU lo permite.
- Si no permite granel, no vender esa unidad.

`Transferencia requiere referencia`

- Capturar folio, autorizacion o referencia bancaria.

`Precio enviado por POS no coincide con el precio autorizado`

- No cambiar precios manualmente fuera del flujo de autorizacion.
- Abrir `Autorizacion`.
- Solicitar precio manual, descuento por partida o descuento general con motivo y supervisor.
- Aplicar solo folios autorizados y vigentes.

## Autorizaciones comerciales

Usar este flujo cuando se necesite precio manual o descuento:

1. Agregar productos al carrito.
2. Abrir `Autorizacion`.
3. Seleccionar tipo: precio manual, descuento partida o descuento general.
4. Si aplica a una partida, seleccionar el producto correcto.
5. Capturar motivo y supervisor.
6. Validar.
7. Aplicar folio autorizado.

No entregar producto ni cobrar con descuento si el folio aparece bloqueado, consumido, vencido o no corresponde al carrito.

## Cierre del dia

1. Dejar de capturar ventas.
2. Revisar ventas del turno.
3. Contar efectivo.
4. Comparar monto esperado contra monto contado.
5. Registrar diferencia si existe.
6. Cerrar turno cuando el sistema lo permita.
7. No borrar ventas, pagos ni tickets para corregir diferencias.

## Reglas importantes

- Cada persona debe usar su propio usuario.
- No vender si el POS muestra otra sucursal.
- No vender si no hay turno abierto.
- No usar ecommerce para corregir ventas POS.
- No entregar producto si el sistema bloqueo inventario.
- No borrar pagos ni partidas; las correcciones se hacen con cancelacion o devolucion.
- Toda salida de inventario debe quedar con folio, kardex y trazabilidad.
