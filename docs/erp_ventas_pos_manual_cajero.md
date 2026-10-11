# ERP Ventas/POS - Manual operativo de cajero

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: guia operativa para POS; la operacion real requiere turno abierto, permisos vigentes y politicas activas.

## Inicio del dia

1. Entrar al sistema con tu propio usuario.
2. Abrir `Ventas > Caja/Turnos`.
3. Confirmar que el sistema muestre la sucursal/caja asignada.
4. Si la sucursal o caja no corresponde, no vender y avisar a administracion.
5. En `Apertura de turno`, capturar el fondo inicial contado.
6. Presionar `Validar apertura`.
7. Si no hay bloqueos, escribir `ABRIR TURNO`.
8. Presionar `Abrir turno real`.
9. Abrir `Ventas > POS`.
10. Confirmar que arriba diga `Operador: tu nombre`.

Regla de identidad:

- Si el POS muestra otro operador, otra sucursal o una caja distinta, no vender.
- Cada cobro debe quedar ligado al usuario que lo ejecuto.
- Varias personas pueden atender cuentas, pero el cobro debe registrar quien lo realiza y en que caja/turno.

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

Atajos utiles:

- `F2` o `Ctrl+K`: ir al buscador de producto.
- `F3`: abrir camara del POS para escanear codigo.
- `Alt+1`: agregar pago en efectivo.
- `Alt+2`: agregar pago con tarjeta.
- `Alt+3`: agregar pago por transferencia.
- `F6`: ir al monto del primer pago o agregar efectivo si no hay pago.
- `F9`: prevalidar venta.
- `Ctrl+Enter`: cobrar, solo si el POS y backend permiten la operacion.
- `F8`: abrir movimientos de caja.
- `F10`: abrir pedidos/apartados.

Los atajos no saltan permisos, turno, inventario, pagos ni validaciones de caja.

Antes de cobrar:

- El turno debe estar abierto.
- La tienda/caja/terminal deben ser las asignadas.
- El pago debe cubrir el total o quedar en flujo formal de pedido/apartado.
- Si el sistema advierte inventario pendiente, no forzar venta normal: usar solo el flujo autorizado con motivo y trazabilidad.

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

## Venta rapida controlada

Usar `Venta rapida` solo cuando el producto fisico no aparece en Catalogo ERP y el cliente ya esta en mostrador.

Captura minima:

1. Descripcion detallada del producto.
2. Cantidad.
3. Unidad de venta: pieza, kg, gramo, litro, ml, metro, cm, paquete o servicio.
4. Precio unitario provisional.
5. Codigo de barras si existe.
6. Si controla inventario.
7. Motivo.

Reglas:

- Agregar al carrito no crea alerta todavia.
- La alerta nace hasta cobrar la venta real.
- Al cobrar, POS crea pendiente `VRP-*` para Catalogo y conserva la unidad capturada.
- Si el producto ya existia, Catalogo debe resolver la venta rapida vinculandola al SKU correcto.
- Si no existia, Catalogo debe crear el borrador/SKU y despues vincular la incidencia.
- Cuando queda vinculada a un SKU, el pendiente deja de estar abierto, pero conserva historial.
- Si el SKU controla inventario, el sistema genera pendiente de regularizacion para Inventario; Catalogo no mueve kardex.
- No usar venta rapida si el producto existe pero no tiene existencia; para eso se usa inventario pendiente.

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
3. Seleccionar tipo: precio manual, descuento partida, descuento general o precio conjunto.
4. Si aplica a una partida, seleccionar el producto correcto.
5. Capturar motivo y supervisor.
6. Validar.
7. Aplicar folio autorizado.

No entregar producto ni cobrar con descuento si el folio aparece bloqueado, consumido, vencido o no corresponde al carrito.

### De donde sale el codigo de autorizacion

El codigo de autorizacion no debe inventarse en el mostrador.

Flujo correcto:

1. Administracion entra a `Ventas > Configuracion POS`.
2. Abre la pestaña `Autorizadores`.
3. Asigna un usuario como `Supervisor`, `Gerente` o `Administrador`.
4. Define alcance: global, tienda o caja.
5. Define que puede autorizar: precio conjunto, precio manual, descuento, inventario pendiente, gasto de caja, devolucion o diferencia de caja.
6. Captura limites y vigencia.
7. Genera un codigo temporal con motivo.
8. El codigo completo se entrega al cajero solo para esa operacion.

Reglas:

- El codigo es temporal y auditable.
- El historial conserva quien lo genero, para que tipo de autorizacion, vigencia, usos y limites.
- El codigo completo se muestra una sola vez al generarlo.
- Si se olvido el codigo completo, no se puede recuperar por seguridad; se debe generar uno nuevo desde `Ventas > Configuracion POS > Autorizadores`.
- La lista historica solo muestra folio, mascara, estatus, vigencia y usos, no el codigo completo.
- El ticket del cliente no debe mostrar mensajes internos del sistema ni razones de bloqueo.
- Si el codigo vence, se consume o no corresponde al carrito, se debe solicitar uno nuevo.

### Precio conjunto o paquete manual

Usar `Precio conjunto` solo para ajustes puntuales de mostrador, por ejemplo:

- vender 3 piezas por un precio redondo;
- armar una pecera equipada con precio final acordado;
- resolver un paquete temporal mientras todavia no existe una promocion formal.

Pasos:

1. Agregar todas las partidas al carrito con su cantidad correcta.
2. Presionar `Paquete` en el encabezado del carrito.
3. Marcar las partidas que forman el conjunto.
4. Capturar `Precio final paquete`.
5. Capturar motivo claro, por ejemplo `Paquete pecera equipada autorizado`.
6. Capturar codigo o usuario supervisor.
7. Presionar `Validar paquete`.
8. Si no hay bloqueos, presionar `Registrar y aplicar`.
9. Confirmar que el carrito muestre `Paquete aplicado`, el folio y el ahorro.
10. Cobrar normalmente.

Reglas:

- No usar precio conjunto para cambiar la lista de precios del producto.
- No usarlo como promocion permanente; si se repite mucho, debe convertirse en regla formal de listas/promociones.
- Si cambias cantidad, quitas productos o agregas productos despues de autorizar, vuelve a registrar folio.
- El descuento se reparte por el sistema entre las partidas marcadas para que venta, ticket y reportes cuadren.
- El cliente no debe ver mensajes internos de autorizacion; el ticket solo debe reflejar venta, descuento/total y datos comerciales permitidos.
- Si necesitas agregar o quitar un componente del paquete, vuelve a abrir `Paquete`, selecciona las partidas correctas y registra un nuevo folio. No se debe reutilizar un folio con carrito cambiado.

## Cierre del dia

1. Dejar de capturar ventas.
2. Abrir `Ventas > Caja/Turnos`.
3. Presionar `Validar corte`.
4. Revisar ventas, pagos, gastos/retiros y monto esperado.
5. Contar efectivo fisico.
6. Capturar `Monto contado`.
7. Capturar observaciones si hay diferencia.
8. Si el corte es correcto, escribir `CERRAR TURNO`.
9. Presionar `Cerrar turno real`.
10. No borrar ventas, pagos ni tickets para corregir diferencias.

Nota operativa:

- El turno se puede cerrar aunque exista diferencia entre esperado y contado.
- La diferencia no se corrige borrando movimientos; queda registrada para revision de caja.
- Si el sistema bloquea el cierre, revisar el mensaje: puede faltar monto contado, confirmacion escrita o turno abierto.

## Revision del cierre

Despues de cerrar turno, el supervisor puede revisar:

- `Ventas > Reportes POS` para turnos, ventas, diferencias y corte.
- `Ventas > Evidencias caja` para gastos, retiros o reembolsos que requieren comprobante.
- `Ventas > Tablero de ventas` para consultar folios POS y tickets.

Si aparece diferencia o evidencia pendiente, no se reabre ni se borra la venta: se documenta y se sigue el flujo administrativo.

## Reglas importantes

- Cada persona debe usar su propio usuario.
- No vender si el POS muestra otra sucursal.
- No vender si no hay turno abierto.
- No usar ecommerce para corregir ventas POS.
- No entregar producto si el sistema bloqueo inventario.
- No borrar pagos ni partidas; las correcciones se hacen con cancelacion o devolucion.
- Toda salida de inventario debe quedar con folio, kardex y trazabilidad.
